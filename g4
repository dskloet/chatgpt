#!/usr/bin/env bash

set -euo pipefail

export MAX_TOKENS=1000
export MODEL="gpt-4-1106-preview"
export INPUT_TOKEN_COST_CENTS="0.001"
export OUTPUT_TOKEN_COST_CENTS="0.003"
SOURCE_DIR="$(dirname $0)"
$SOURCE_DIR/gpt
