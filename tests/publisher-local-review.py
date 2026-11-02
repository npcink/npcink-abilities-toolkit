#!/usr/bin/env python3
"""Exercise the real shell publisher's review gate without GitHub or model calls."""
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

PUBLISHER = Path(__file__).resolve().parents[1] / 'scripts/publish-pr.sh'


class PublisherReviewTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='toolkit-review-')
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.repo = self.root / 'repo'
        self.repo.mkdir()
        self.env = {k: v for k, v in os.environ.items() if not k.startswith('GIT_')}
        self.env.update(GIT_CONFIG_NOSYSTEM='1', GIT_CONFIG_GLOBAL=os.devnull,
                        HTTPS_PROXY='http://unused.invalid', TOOLKIT_REVIEW_TOOLBOX=str(self.root / 'toolbox'))
        self.git('init', '-q', '-b', 'master')
        self.git('config', 'user.name', 'Fixture')
        self.git('config', 'user.email', 'fixture@example.invalid')
        self.git('commit', '-q', '--allow-empty', '-m', 'base')
        self.base = self.git('rev-parse', 'HEAD')
        self.remote = self.root / 'remote.git'
        subprocess.run(['git', 'clone', '-q', '--bare', str(self.repo), str(self.remote)], check=True, env=self.env)
        self.git('remote', 'add', 'origin', str(self.remote))
        self.git('switch', '-q', '-c', 'codex/fixture')
        self.git('commit', '-q', '--allow-empty', '-m', 'topic')
        self.head = self.git('rev-parse', 'HEAD')
        tools = self.root / 'bin'
        tools.mkdir()
        gh = tools / 'gh'
        gh.write_text('#!/bin/sh\necho github-mutation-attempted >> "$TOOLKIT_FIXTURE_LOG"\nexit 97\n')
        gh.chmod(0o755)
        self.env.update(PATH=str(tools) + os.pathsep + self.env['PATH'],
                        TOOLKIT_FIXTURE_LOG=str(self.root / 'github.log'))
        verifier = self.root / 'toolbox/scripts/local-qwen-review.py'
        verifier.parent.mkdir(parents=True)
        # A verifier double tests delegation/exit propagation; canonical semantics
        # are owned and tested by Toolbox, rather than copied into this repository.
        verifier.write_text('''import json, os, pathlib, sys
pathlib.Path(os.environ['TOOLKIT_FIXTURE_LOG']).write_text(json.dumps(sys.argv[1:]))
sys.exit(int(os.environ.get('TOOLKIT_FIXTURE_REVIEW_STATUS', '0')))
''')
        self.body = self.root / 'body.md'
        self.body.write_text('## Scope\nfixture\n## Boundary\nfixture\n## Verification\nfixture\n## Risk\nfixture\n')
        self.receipt = self.root / 'receipt.json'
        self.receipt.write_text(json.dumps({'base_sha': self.base}))

    def git(self, *args):
        return subprocess.check_output(['git', *args], cwd=self.repo, env=self.env, text=True).strip()

    def publish(self, receipt=True):
        args = ['bash', str(PUBLISHER), '--title', 'fixture', '--body-file', str(self.body), '--dry-run']
        if receipt:
            args += ['--local-review', str(self.receipt)]
        return subprocess.run(args, cwd=self.repo, env=self.env, text=True, capture_output=True)

    def assert_no_remote_mutation(self):
        self.assertEqual(subprocess.check_output(['git', 'ls-remote', '--heads', str(self.remote)], text=True).strip(),
                         self.base + '\trefs/heads/master')

    def test_missing_receipt(self):
        self.assertIn('--local-review PATH is required', self.publish(False).stderr)
        self.assert_no_remote_mutation()

    def test_missing_toolbox(self):
        del self.env['TOOLKIT_REVIEW_TOOLBOX']
        self.assertIn('set TOOLKIT_REVIEW_TOOLBOX', self.publish().stderr)
        self.assert_no_remote_mutation()

    def test_malformed_and_old_base_receipts(self):
        for payload in ['{broken', json.dumps({'base_sha': '0' * 40})]:
            self.receipt.write_text(payload)
            result = self.publish()
            self.assertNotEqual(result.returncode, 0)
            self.assertIn('current target base', result.stderr)
            self.assert_no_remote_mutation()

    def test_central_rejection_blocks_before_push_even_in_dry_run(self):
        self.env['TOOLKIT_FIXTURE_REVIEW_STATUS'] = '1'
        result = self.publish()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('review or finding triage failed', result.stderr)
        self.assertNotIn('would run: git push', result.stdout)
        self.assert_no_remote_mutation()

    def test_valid_review_passes_exact_arguments_to_central_verifier(self):
        result = self.publish()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn('dry-run passed', result.stdout)
        self.assertEqual(json.loads((self.root / 'github.log').read_text()),
                         ['--verify', str(self.receipt), '--head', self.head, '--body-file', str(self.body)])
        self.assert_no_remote_mutation()


if __name__ == '__main__':
    unittest.main(verbosity=2)
