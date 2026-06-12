#!/usr/bin/env sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
EVAL_LAB_PATH="${MAGICK_AI_EVAL_LAB_PATH:-$(dirname "$ROOT")/magick-ai-eval-lab}"

if [ ! -d "$EVAL_LAB_PATH" ]; then
	echo "Magick AI Eval Lab not found: $EVAL_LAB_PATH" >&2
	echo "Set MAGICK_AI_EVAL_LAB_PATH=/path/to/magick-ai-eval-lab." >&2
	exit 1
fi

if [ "$#" -lt 1 ]; then
	echo "Usage: scripts/eval-lab.sh <composer-script> [args...]" >&2
	exit 1
fi

SCRIPT="$1"
shift

cd "$EVAL_LAB_PATH"
composer "$SCRIPT" -- "$@"
