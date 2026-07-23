#!/usr/bin/env bash
#
# Pre-commit gauntlet for this orange package. Runs each check in order and
# stops at the first failure. Uses the shared tools installed in the webapp's
# vendor/bin (reached via ../../bin) - no per-package composer setup needed.
#
#   cd vendor/orange/<package> && ./sweep.sh
#
set -euo pipefail

# always run from this script's directory (the package root)
cd "$(dirname "$0")"

BIN=../../bin

checks=(
  "$BIN/phpcbf"
  "$BIN/rector process"
  "$BIN/phpstan analyse --memory-limit=1G"
)

# only run the test suite when this package ships one
if [ -f unittest/runUnitTests.sh ]; then
  checks+=("( cd unittest && sh runUnitTests.sh )")
fi

for check in "${checks[@]}"; do
  echo ""
  echo "==> $check"
  if ! eval "$check"; then
    echo "" >&2
    echo "FAILED: $check" >&2
    exit 1
  fi
done

echo ""
echo "All checks passed."
