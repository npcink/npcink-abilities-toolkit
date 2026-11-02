#!/usr/bin/env python3
"""Benchmark cleanup query shapes using synthetic rows in an owned Docker container."""
import argparse, json, subprocess, statistics, math, re, time, shlex, uuid
from pathlib import Path

def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('--ssh', help='Optional Docker host, e.g. user@host')
    p.add_argument('--image', default='mariadb:11.8.9')
    p.add_argument('--output', type=Path, required=True, help='New evidence directory')
    p.add_argument('--fixture', type=Path, default=Path(__file__).resolve().parents[1] / 'tests/fixtures/media-cleanup-query.json')
    args = p.parse_args()
    root = args.output
    root.mkdir(parents=True, exist_ok=False)
    fixture = json.loads(args.fixture.read_text())
    (root / 'schema.json').write_text(json.dumps(fixture['schema']))
    (root / 'captured-sql.json').write_text(json.dumps(fixture['captured_sql']))
    name = 'toolkit-cleanup-bench-' + uuid.uuid4().hex[:12]

    def docker(*argv, input=None, timeout=180):
        command = ['docker', *argv]
        if args.ssh:
            command = ['ssh', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', args.ssh, shlex.join(command)]
        result = subprocess.run(command, input=input, text=True, capture_output=True, timeout=timeout)
        if result.returncode:
            raise subprocess.CalledProcessError(result.returncode, command, output=result.stdout, stderr=result.stderr)
        return result.stdout

    def sql(text):
        return docker('exec', '-i', name, 'mariadb', '-uroot', '--batch', '--raw', '--skip-column-names', input=text)
    created = False
    try:
        docker('run', '--rm', '-d', '--name', name, '--label', 'org.npcink.toolkit.benchmark=media-cleanup', '--network', 'none', '--memory', '1g', '--cpus', '2', '-e', 'MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1', args.image, '--innodb-buffer-pool-size=256M')
        created = True
        for attempt in range(60):
            try:
                docker('exec', name, 'healthcheck.sh', '--connect', '--innodb_initialized')
                version = sql('SELECT VERSION();').strip()
                break
            except subprocess.CalledProcessError:
                time.sleep(1)
        else:
            raise RuntimeError('Isolated MariaDB did not become ready')
        server = dict(version=version, image=json.loads(docker('inspect', name))[0]['Image'], cpus=2, memory_mib=1024, innodb_buffer_mib=256, fixture_sha256=__import__('hashlib').sha256(args.fixture.read_bytes()).hexdigest())
        schema = json.loads((root / 'schema.json').read_text())
        base = json.loads((root / 'captured-sql.json').read_text())['queries'][0]
        base = ' '.join(base.split())
        setup = 'DROP DATABASE IF EXISTS toolkit_bench; CREATE DATABASE toolkit_bench; USE toolkit_bench; SET SESSION sql_mode="";'
        for (table, ddl) in schema.items():
            ddl = re.sub('AUTO_INCREMENT=\\d+', 'AUTO_INCREMENT=1', ddl)
            setup += ddl + ';'
        setup += 'CREATE TABLE digits(d INT PRIMARY KEY); INSERT INTO digits VALUES ' + ','.join((f'({d})' for d in range(10))) + ';'
        sql(setup)
        records = []
        for rows in [100000, 1000000]:
            posts = rows // 10
            seq = ' + '.join((f'd{i}.d*{10 ** i}' for i in range(6)))
            source = ' CROSS JOIN '.join((f'digits d{i}' for i in range(6)))
            seed = f"USE toolkit_bench; SET SESSION sql_mode=''; TRUNCATE wp_postmeta; TRUNCATE wp_posts;\n   INSERT INTO wp_posts(ID,post_content,post_title,post_excerpt,to_ping,pinged,post_content_filtered,post_type,post_status)\n   SELECT n+1,'fixture','fixture','','','','','attachment','inherit' FROM (SELECT {seq} AS n FROM {source}) seqs WHERE n<{posts} ORDER BY n;\n   INSERT INTO wp_postmeta(meta_id,post_id,meta_key,meta_value)\n   SELECT n+1,FLOOR(n/10)+1,CONCAT('_fixture_',MOD(n,10)),REPEAT('x',96) FROM (SELECT {seq} AS n FROM {source}) seqs WHERE n<{rows} ORDER BY n;\n   ANALYZE TABLE wp_posts,wp_postmeta;"
            sql(seed)
            for (pattern, condition) in [('sparse', 'MOD(post_id,100)=0 AND MOD(meta_id-1,10)=0'), ('dense_duplicates', 'MOD(meta_id-1,10)<2'), ('prefix', f'post_id<={posts // 10} AND MOD(meta_id-1,10)=0'), ('suffix', f'post_id>{posts * 9 // 10} AND MOD(meta_id-1,10)=0'), ('none', '0')]:
                sql(f"USE toolkit_bench; UPDATE wp_postmeta SET meta_key=IF({condition},'_npcink_ai_media_file_replacement_history',CONCAT('_fixture_',MOD(meta_id-1,10))); ANALYZE TABLE wp_postmeta;")
                for cursor in [0, posts // 2, max(0, posts - 1000), posts, posts + 1]:
                    query = base if cursor == 0 else base.replace(' GROUP BY', f' AND wp_posts.ID > {cursor} GROUP BY')
                    exists = f"SELECT wp_posts.ID FROM wp_posts WHERE wp_posts.post_type='attachment' AND wp_posts.post_status='inherit' AND wp_posts.ID>{cursor} AND EXISTS (SELECT 1 FROM wp_postmeta WHERE wp_postmeta.post_id=wp_posts.ID AND wp_postmeta.meta_key='_npcink_ai_media_file_replacement_history') ORDER BY wp_posts.ID ASC LIMIT 501"
                    for (variant, query) in [('baseline', query), ('exists', exists)]:
                        batch = 'USE toolkit_bench; SET SESSION query_cache_type=OFF; EXPLAIN FORMAT=JSON ' + query + ';' + ('ANALYZE FORMAT=JSON ' + query + ';') * 8
                        out = sql(batch)
                        decoder = json.JSONDecoder()
                        objects = []
                        start = 0
                        while start < len(out):
                            while start < len(out) and out[start].isspace():
                                start += 1
                            if start == len(out):
                                break
                            (obj, end) = decoder.raw_decode(out, start)
                            objects.append(obj)
                            start = end
                        assert len(objects) == 9, len(objects)
                        times = [x['query_block']['r_total_time_ms'] for x in objects[1:]]
                        scans = []

                        def walk(x):
                            if isinstance(x, dict):
                                if 'table_name' in x:
                                    scans.append({k: x[k] for k in ['table_name', 'access_type', 'key', 'rows', 'r_rows', 'r_loops', 'r_filtered'] if k in x})
                                for v in x.values():
                                    walk(v)
                            elif isinstance(x, list):
                                for v in x:
                                    walk(v)
                        walk(objects[2])
                        expected = [i for i in range(cursor + 1, posts + 1) if pattern == 'sparse' and i % 100 == 0 or pattern == 'dense_duplicates' or (pattern == 'prefix' and i <= posts // 10) or (pattern == 'suffix' and i > posts * 9 // 10)][:501]
                        observed = [int(v) for v in sql('USE toolkit_bench;' + query + ';').splitlines()]
                        if observed != expected:
                            raise RuntimeError('Query result differs from synthetic fixture expectation')
                        item = dict(result_count=len(observed), ids_verified=True, variant=variant, postmeta_rows=rows, posts=posts, pattern=pattern, cursor=cursor, sql=query, first_ms=times[0], warm_median_ms=statistics.median(times[1:]), warm_p95_ms=sorted(times[1:])[math.ceil(0.95 * 7) - 1], times_ms=times, scans=scans, explain=objects[0], analyze=objects[2])
                        records.append(item)
                        (root / 'results.json').write_text(json.dumps(dict(server=server, rows=records), indent=2))
                        print(variant, rows, pattern, cursor, round(item['warm_median_ms'], 3), [(x['table_name'], round((x.get('r_rows') or 0) * (x.get('r_loops') or 0))) for x in scans], flush=True)
        print('Completed', len(records), 'cases')
    finally:
        if created:
            docker('rm', '-f', '-v', name)
            print('Removed owned benchmark container and its anonymous volume.', flush=True)
if __name__ == '__main__':
    main()
