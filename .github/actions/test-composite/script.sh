#!/bin/bash
set -eo

echo "raw=[$INPUT_DRY_RUN]"

if $INPUT_DRY_RUN; then
  echo "TOOK TRUE BRANCH"
else
  echo "TOOK FALSE BRANCH"
fi
