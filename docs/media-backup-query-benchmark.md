# Media backup candidate query experiment — 2026-11-02

Keep the existing WordPress query. The measured `EXISTS` alternative regresses
on dense history data, and neither shape guarantees a bounded scan. No schema,
index, cleanup lifecycle, candidate cap, cursor contract or runtime SQL changes
are justified by this experiment.

The fixture captures the actual private candidate-read helper at source
`420dba45d489be099360c7f001505da3e5cea03a` under WordPress 7.1.4. It preserves
the attachment/inherit predicate, history key, `GROUP BY`, ID ordering and
`LIMIT 0, 501`. Only the SQL read runs in the benchmark; cleanup/file deletion
is never invoked. Schema DDL contains no site content. The fixture includes
the captured `post_id` and prefix `meta_key` indexes and posts indexes; no
benchmark-specific indexes were added.

## Method

A disposable MariaDB 11.8.9 container uses 2 CPUs, a 1 GiB memory limit and
256 MiB InnoDB buffer. It has no network and no published ports. The harness
creates its own random container, inserts synthetic data into its own database,
and removes only that container and its anonymous volume in `finally`.
No production or Local.app data is modified.

- 100,000 / 1,000,000 postmeta rows, with 10,000 / 100,000 attachment posts;
  ten 96-byte metadata values per post.
- Sparse: one history key on every hundredth post. Dense: two history rows per
  post, testing duplicate elimination. Prefix/suffix: history on the first/last
  tenth of posts. None: no history keys.
- Cursors: zero, midpoint, 1,000 IDs before the end, end, and beyond end.
- Both baseline and `EXISTS` shapes run against each fixture/cursor, yielding
  100 cases. Every returned ID list matches its synthetic oracle, including
  ascending unique IDs, empty results and the 501-row lookahead cap.
- Each case records `EXPLAIN FORMAT=JSON` and eight `ANALYZE FORMAT=JSON`
  executions. Timing is server `query_block.r_total_time_ms`, excluding SSH
  and client process overhead. The first execution is recorded separately;
  median and nearest-rank p95 cover the following seven executions. With seven
  samples, p95 equals their maximum; this is not a production latency estimate.
- Query cache is disabled; InnoDB caches are not flushed. Fixture population,
  index updates and the earlier baseline can warm later cases. These are
  ordered executions, not certified cold-cache or randomized benchmarks.
  Actual table-row visits use `r_rows * r_loops`, not EXPLAIN estimates or
  distinct rows; sums may include more than one table or materialized lookup.

## Results

The table shows the largest repeated-execution median across the five cursors
for each distribution in the final reproducible run (milliseconds).

| History distribution | Baseline 100k | EXISTS 100k | Baseline 1m | EXISTS 1m |
| --- | ---: | ---: | ---: | ---: |
| Sparse | 0.161 | 0.198 | 1.383 | 1.415 |
| Dense, duplicate history | 10.207 | 8.928 | 2.300 | 92.793 |
| Prefix | 2.049 | 1.087 | 5.135 | 5.169 |
| Suffix | 1.120 | 1.051 | 10.277 | 5.611 |
| No history | 0.058 | 0.005 | 0.006 | 0.005 |

In the million-row dense case at cursor zero, the baseline visits about 5,011
postmeta rows plus 1,003 posts; `EXISTS` materializes a million-row metadata
scan before producing 501 IDs. That regression outweighs the suffix improvement.
The smaller fixture can choose a less efficient plan than the larger one;
size alone does not predict the optimizer's join order.

An initial million-row prefix/midpoint run chose a `post_id` range scan and
visited 500,000 metadata rows for an empty result. Its first two analyzed
executions took 178.866 / 187.521 ms; subsequent runs were 3.95–5.83 ms.
The final fresh-container run chose `meta_key`, visited 10,000 metadata rows,
and repeated at a 5.135 ms median / 5.886 ms maximum. Both plans and the original
outlier are retained in the evidence. Results remain data/statistics/cache
sensitive; the 501-result cap is not a database work or latency cap.

[Machine-readable evidence](evidence/media-cleanup-query-2026-11-02.json)
contains every final case's timings, actual row/loop data, four representative
final EXPLAIN/ANALYZE plans, and the original high-scan plan. Full raw outputs
remain under ignored `build/media-cleanup-benchmark*` with a recorded hash.

## Reproduce

```bash
python3 scripts/benchmark-media-backup-query.py \
  --ssh user@docker-host --image mariadb:11.8.9 \
  --output build/media-cleanup-query-fresh
```

Omit `--ssh` to use a local Docker daemon. Output must be a new directory.
The bundled SQL/schema fixture is a named source snapshot; refresh it from the
real helper before making claims about later query revisions. Record the image
ID, schema, database version and limits with any new results.

This covers two synthetic metadata sizes on one MariaDB version. It does not
establish MySQL performance, wider rows, other attachment ratios, host filter
rewrites, concurrent writers, multiple sites, disk-cold latency, larger datasets
or end-to-end file cleanup time. Reopen optimization for an observed slow
site/query plan or a documented operational budget; measure alternatives on
all distributions before changing SQL or adding an index.
