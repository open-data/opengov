#!/bin/sh
# Setup a solr instance with configset and collections necessary for running
# tests. This script should be run from the solr base directory (inside the
# solr service container) with the path to a drupal configset as its only
# parameter.

set -eu

command -v bin/solr

if [ -x bin/post ]; then
    SOLR_POST_COMMAND="bin/post"
else
    SOLR_POST_COMMAND="bin/solr post"
fi

test -d "${1}"

echo "Setting up techproducts collection" >&2
bin/solr create -c techproducts -d server/solr/configsets/sample_techproducts_configs/conf -n sample_techproducts_configs
${SOLR_POST_COMMAND} -c techproducts example/exampledocs/*.xml

echo "Setting up drupal collection with config set from ${1}" >&2
bin/solr create -c drupal -d "${1}" -n drupal

echo "Setting up checkpoints collection with _default config set" >&2
bin/solr create -c checkpoints
