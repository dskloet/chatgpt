set -euo pipefail

export MAX_TOKENS=200
export MODEL="gpt-4"
SOURCE_DIR="$(dirname $0)"
$SOURCE_DIR/gpt
