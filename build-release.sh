#!/usr/bin/env bash
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

version="$(php -r '
$content = file_get_contents("version.php");
if (!preg_match("/\\$plugin->version\\s*=\\s*(\\d+)\\s*;/", $content, $matches)) {
    fwrite(STDERR, "Unable to read plugin version from version.php\\n");
    exit(1);
}
echo $matches[1];
')"

output="${1:-mod_videoerrorhunt_${version}.zip}"
git archive --format=zip --prefix=videoerrorhunt/ --output="$output" HEAD
printf 'Created %s\n' "$output"
