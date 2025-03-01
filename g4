#!/usr/bin/env bash

set -euo pipefail

export MAX_TOKENS=1000
export MODEL="gpt-4o"
export INPUT_TOKEN_COST_CENTS="0.00025"
export OUTPUT_TOKEN_COST_CENTS="0.001"
SOURCE_DIR="$(dirname $0)"
$SOURCE_DIR/gpt
