#!/usr/bin/env bash
set -euo pipefail
SCRIPT="$1"
LOG="$2"

if ! command -v rman >/dev/null 2>&1; then
  echo "rman not found" >&2
  exit 127
fi

rman cmdfile="$SCRIPT" log="$LOG"
