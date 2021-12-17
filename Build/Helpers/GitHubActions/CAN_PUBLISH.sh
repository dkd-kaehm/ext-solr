#!/usr/bin/env bash

# @todo
### 1. Compare versions in composer.json and Resources/Private/Php/ComposerLibraries/composer.json requirement for solarium/solarium

## BASH COLORS
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

cd $(dirname "$0")
COMPOSER_TAG_CHECKER_DIR="/tmp/apache-solr-for-typo3-version-tag-checker"
EXT_EMCONF_VERSION=$(../GET_VERSION_FROM_ext_emconf.php)
CAN_NOT_PUBLISH=0

if ! composer --version > /dev/null 2>&1; then
  1>&2 echo -e "Error:
    composer is not installed
  "
  exit 9;
fi

if [[ $* == *--quiet* ]]; then
  DO_NOT_PRINT_TO_MUCH=true
fi

check_git_tag_matches_ext_emconf_version()
{
  if [[ "$RELEASE_VERSION" = "$EXT_EMCONF_VERSION" ]]; then
    return 0
  fi
  return 1
}

check_version_string_is_valid_for_packagist()
{
  mkdir -p "$COMPOSER_TAG_CHECKER_DIR"
  cat <<EOT > "$COMPOSER_TAG_CHECKER_DIR/composer.json"
{
  "name": "apache-solr-for-typo3/composer-version-tag-checker",
  "version": "$1",
  "description": "Test semantic version string.",
  "license": "GPL-3.0-or-later",
  "authors": [{"name": "Rafael Kähm","email": "rafael.kaehm@dkd.de"}]
}
EOT
  if ! composer validate --no-check-publish --no-check-lock --no-check-version --working-dir="$COMPOSER_TAG_CHECKER_DIR" > /dev/null 2>&1; then
    rm -Rf "$COMPOSER_TAG_CHECKER_DIR"
    return 1
  fi
  rm -Rf "$COMPOSER_TAG_CHECKER_DIR"
  return 0
}

check_ext_emconf_version_not_published_on_packagist()
{
  if ! ../PACKAGIST_PACKAGE_SOURCE_REFERENCE.sh "apache-solr-for-typo3/solr" "$EXT_EMCONF_VERSION" --short > /dev/null 2>&1;then
    return 0
  fi
  return 1
}

check_version_string_is_valid_for_TER()
{
  if [[ "$1" =~ ^(0|[1-9]{0,2}).(0|[1-9]{0,2}).(0|[1-9]{0,2})$ ]]; then
    return 0
  fi
  return 1
}

can_publish_to_packagist()
{
  echo -ne "  Packagist                              : "
  PACKAGIST_STATUS_MESSAGES=""
  CAN_NOT_PUBLISH_TO_PACKAGIST=0
  if [[ -n "${RELEASE_VERSION}" ]]; then
    if ! check_version_string_is_valid_for_packagist "$RELEASE_VERSION"; then
      CAN_NOT_PUBLISH_TO_PACKAGIST=1
      PACKAGIST_STATUS_MESSAGES="${PACKAGIST_STATUS_MESSAGES}, the git tag is invalid"
    fi

    if ! check_git_tag_matches_ext_emconf_version; then
      CAN_NOT_PUBLISH_TO_PACKAGIST=1
      PACKAGIST_STATUS_MESSAGES="${PACKAGIST_STATUS_MESSAGES}, the git tag does not match the version string from ext_emconf.php"
    fi
  fi

  if ! check_version_string_is_valid_for_packagist "$EXT_EMCONF_VERSION"; then
    CAN_NOT_PUBLISH_TO_PACKAGIST=1
    PACKAGIST_STATUS_MESSAGES="${PACKAGIST_STATUS_MESSAGES}, the version string in ext_emconf.php is invalid"
  fi

  if ! check_ext_emconf_version_not_published_on_packagist; then
    CAN_NOT_PUBLISH_TO_PACKAGIST=1
    PACKAGIST_STATUS_MESSAGES="${PACKAGIST_STATUS_MESSAGES}, the version string in ext_emconf.php already present on packagist."
  fi

  if [[ "$CAN_NOT_PUBLISH_TO_PACKAGIST" == 1 ]]; then
    CAN_NOT_PUBLISH=1
    echo -ne "${RED}"'✘'"${NC}"" ${PACKAGIST_STATUS_MESSAGES}\n"
    return 1
  else
    echo -ne "${GREEN}"'✔\n'"${NC}"
    return 0
  fi
}

# @todo: Check if the version from ext_emconf.php already presents on extensions.typo3.org(Without any API key!)
can_publish_to_TER()
{
  echo -ne "  TER                                    : "
  if check_version_string_is_valid_for_TER "$EXT_EMCONF_VERSION"; then
    if [[ -n "${RELEASE_VERSION}" ]]; then
      if check_git_tag_matches_ext_emconf_version; then
        echo -ne "${GREEN}"'✔\n'"${NC}"
        return 0
      else
        CAN_NOT_PUBLISH=1
        echo -ne "${RED}"'✘'"${NC}" ", the git tag does not match the version string from ext_emconf.php\n"
        return 1
      fi
    else
      echo -ne "${GREEN}"'✔\n'"${NC}"
      return 0
    fi
  else
    CAN_NOT_PUBLISH=1
    echo -ne "${RED}"'✘'"${NC}" ", the version string in ext_emconf.php is invalid for TER\n"
    return 1
  fi
}

can_run_in_non_composer_mode_on_manual_installation()
{
  echo -ne "  NON-Composer mode and manual install   : "

  GITHUB_STATUS_MESSAGES=""
  if [[ "${#EXT_EMCONF_VERSION}" -gt "15" ]]; then
    CAN_NOT_PUBLISH_TO_GITHUB=1
    GITHUB_STATUS_MESSAGES="${GITHUB_STATUS_MESSAGES}, the version string in ext_emconf.php is longer than 15 characters"
  fi
  if [[ -n "${RELEASE_VERSION}" ]] && ! check_git_tag_matches_ext_emconf_version; then
    CAN_NOT_PUBLISH_TO_GITHUB=1
    GITHUB_STATUS_MESSAGES="${GITHUB_STATUS_MESSAGES}, the git tag does not match the version string from ext_emconf.php"
  fi

  if [[ "$CAN_NOT_PUBLISH_TO_GITHUB" == 1 ]]; then
      CAN_NOT_PUBLISH=1
      echo -ne "${RED}"'✘'"${NC}"" ${GITHUB_STATUS_MESSAGES}\n"
      return 1
    else
      echo -ne "${GREEN}"'✔\n'"${NC}"
      return 0
    fi
}

print_info()
{
  if [[ $DO_NOT_PRINT_TO_MUCH == true ]]; then
    return 0
  fi
  echo -ne "Checking the version \"$EXT_EMCONF_VERSION\" from ext_emconf.php"
  if [[ -n "${RELEASE_VERSION}" ]]; then
    echo -ne " and the release tag \"$RELEASE_VERSION\"\n"
  else
    echo -ne "\n"
  fi
}

print_info

if [[ $# -eq 0 ]]; then
  can_publish_to_packagist
  can_publish_to_TER
  can_run_in_non_composer_mode_on_manual_installation
else
  if [[ $* = *--packagist* ]]; then
    can_publish_to_packagist
  fi
  if [[ $* = *--ter* ]]; then
    can_publish_to_TER
  fi
  if [[ $* = *--github* ]]; then
    can_run_in_non_composer_mode_on_manual_installation
  fi
fi

if [[ "$CAN_NOT_PUBLISH" -eq 1 ]]; then
  exit 1
fi
