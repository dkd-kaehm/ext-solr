#!/usr/bin/env bash

cd $(dirname "$0")

if ./CAN_PUBLISH.sh --quiet --ter > /dev/null 2>&1 ; then
  TER="TER: ✔ "
else
  TER="TER: ✘ "
fi

if ./CAN_PUBLISH.sh --quiet --packagist > /dev/null 2>&1 ; then
  PACKAGIST="Packagist: ✔ "
else
  PACKAGIST="Packagist: ✘ "
fi

if ./CAN_PUBLISH.sh --quiet --github > /dev/null 2>&1 ; then
  GITHUB="GitHub: ✔ "
else
  GITHUB="GitHub: ✘ "
fi

if [[ $# -eq 0 ]]; then
  echo "($PACKAGIST | $GITHUB | $TER)"
else
  if [[ $* = *--packagist* ]]; then
    echo "$PACKAGIST"
  fi
  if [[ $* = *--ter* ]]; then
    echo "$TER"
  fi
  if [[ $* = *--github* ]]; then
    echo "$GITHUB"
  fi
fi
