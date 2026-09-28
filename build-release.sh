#!/usr/bin/env bash
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

version="$(sed -n 's/.*version = \\([0-9][0-9]*\\);/\\1/p' version.php | head -n 1)"
if [[ -z "$version" ]]; then
    printf 'Unable to read plugin version from version.php\n' >&2
    exit 1
fi

output="${1:-mod_videoerrorhunt_${version}.zip}"
git archive --format=zip --prefix=videoerrorhunt/ --output="$output" HEAD
printf 'Created %s\n' "$output"
