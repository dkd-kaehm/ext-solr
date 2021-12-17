#!/usr/bin/env bash

if ! jq --version > /dev/null 2>&1
then
  1>&2 echo -e "Error:
  jq is not installed in your system. Please install jq in your distribution first.
  See: https://stedolan.github.io/jq/"
  exit 1
fi

if [[ "$#" -lt 2 ]] || [[ $* == *--help* ]] || [[ $* == *-h* ]]; then
  echo "Usage: $0 <composers vendor/package> <version-string>"
  exit 2;
fi

RUN_ID=$(tr -dc A-Za-z0-9 </dev/urandom | head -c 13 ; echo '')

PACKAGE_NAME="$1"
VERSIONS_STRING="$2"

PACKAGE_URL="https://packagist.org/packages/$PACKAGE_NAME.json"
CURL_ERROR_TMP_OUTPUT="/tmp/"$(basename "$0")"_$RUN_ID""_CURL.stderr"
if ! PACKAGIST_DATA=$(curl "$PACKAGE_URL" -sSf 2>"$CURL_ERROR_TMP_OUTPUT"); then
  1>&2 echo -e "Error:
    Something went wrong by fetching the data for \"$PACKAGE_NAME\" package from packagist.org
    $(cat "$CURL_ERROR_TMP_OUTPUT")
    See requested URL: $PACKAGE_URL"
  rm -Rf "$CURL_ERROR_TMP_OUTPUT"
  exit 3;
fi

TEST_PACKAGIST_DATA=$(echo "$PACKAGIST_DATA" | jq --raw-output '.package.name')
if [[ $TEST_PACKAGIST_DATA == "null"  ]] ; then
  PACKAGIST_INVALID_OUTPUT="/tmp/"$(basename "$0")"_$RUN_ID""_invalid_Packagist.json"
  echo "$PACKAGIST_DATA" | jq . > "$PACKAGIST_INVALID_OUTPUT"
  1>&2 echo -e "Error:
    The responded json data for \"$PACKAGE_NAME\" does not have \"name\" property, which indicates the invalid state of response.
    Please see the response data in $PACKAGIST_INVALID_OUTPUT
    "
  exit 4;
fi

SOURCE_REFERENCE=$(echo "$PACKAGIST_DATA" | jq --raw-output '.package.versions."v'"$VERSIONS_STRING"'".source.reference')
if [[ $SOURCE_REFERENCE == "null"  ]] ; then
  SOURCE_REFERENCE=$(echo "$PACKAGIST_DATA" | jq --raw-output '.package.versions."'"$VERSIONS_STRING"'".source.reference')
fi
if [[ $SOURCE_REFERENCE == "null"  ]] ; then
  1>&2 echo -e "Error:
    The version \"$VERSIONS_STRING\" does not exist."
  exit 5;
fi

if [[ $* == *--short* ]]; then
  echo "$SOURCE_REFERENCE" | cut -c 1-6
  exit 0;
fi

echo "$SOURCE_REFERENCE"
