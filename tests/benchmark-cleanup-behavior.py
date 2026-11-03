"""Offline owned-container cleanup, including a timeout after daemon creation."""
import importlib.util
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('benchmark', ROOT/'scripts/benchmark-media-backup-query.py')
benchmark = importlib.util.module_from_spec(spec)
spec.loader.exec_module(benchmark)


class BenchmarkCleanupTests(unittest.TestCase):
    def interrupted_start(self, owned):
        calls = []

        def run(command, **kwargs):
            calls.append(command)
            if command[1] == 'run':
                raise subprocess.TimeoutExpired(command, 180)
            if command[1] == 'ps':
                return subprocess.CompletedProcess(command, 0, owned, '')
            self.assertEqual(command[1:4], ['rm', '-f', '-v'])
            return subprocess.CompletedProcess(command, 0, '', '')

        with tempfile.TemporaryDirectory() as directory, patch.object(
                benchmark.subprocess, 'run', side_effect=run), patch.object(
                sys, 'argv', ['benchmark', '--output', str(Path(directory)/'output')]):
            with self.assertRaises(subprocess.TimeoutExpired):
                benchmark.main()
        start, listing = calls[:2]
        name = start[start.index('--name') + 1]
        run_label = next(value for value in start if value.startswith('org.npcink.toolkit.benchmark-run='))
        self.assertEqual(listing, ['docker', 'ps', '-aq', '--no-trunc', '--filter',
                                  'name=^/' + name + '$', '--filter', 'label=' + run_label])
        return calls

    def test_timeout_after_creation_removes_exact_owned_id_and_volume(self):
        owned_id = 'a' * 64
        calls = self.interrupted_start(owned_id + '\n')
        self.assertEqual(calls[2:], [['docker', 'rm', '-f', '-v', owned_id]])

    def test_absent_owned_match_does_not_remove_another_container(self):
        self.assertEqual(len(self.interrupted_start('')), 2)

    def test_ambiguous_or_malformed_listing_is_rejected_without_deletion(self):
        for owned in ['bad-id', 'a'*64 + '\n' + 'b'*64]:
            calls = []

            def docker(*args):
                calls.append(args)
                return owned

            with self.subTest(owned=owned), self.assertRaises(RuntimeError):
                benchmark.cleanup_container(docker, 'fixture', 'owner=fixture')
            self.assertEqual(len(calls), 1)


if __name__ == '__main__':
    unittest.main()
