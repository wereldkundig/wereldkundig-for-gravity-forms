#!/bin/sh
# Regenerates src/data/hooks/*.json, the hooks reference of the docs, from the plugin's app/ folder.
# Fails when a hook has no docblock directly above it: the generator would silently leave it out of the docs.
# Runs from this folder, because the generator loads ./vendor/autoload.php and reads ./composer.json (ignore-hooks).
set -e

cd "$(dirname "$0")"
composer install --no-interaction --no-progress --quiet

output=$(php vendor/bin/wp-hooks-generator --input=../../app --output=../src/data/hooks)
echo "$output"

if echo "$output" | grep -qE 'missing a docblock|has an empty docblock'; then
    echo 'Add a docblock directly above every hook call, or list the hook under extra.wp-hooks.ignore-hooks in docs/tools/composer.json.' >&2
    exit 1
fi
