#!/usr/bin/env bash
# Diff two OpenAPI specs with oasdiff and record the API changes.
#
# Usage:
#   api-changelog.sh <old-spec> <new-spec> <version> [changelog-file] [note]
#       Add one entry (newest first) to the changelog file.
#   api-changelog.sh --markdown <old-spec> <new-spec>
#       Print the changes as a Markdown table (for a PR comment). Prints nothing when there are none.
#
# Needs: oasdiff (https://github.com/oasdiff/oasdiff) and jq.
set -euo pipefail

markdown=false
if [ "${1:-}" = "--markdown" ]; then
  markdown=true
  shift
fi

old="$1"
new="$2"

# oasdiff level: 3 = breaking, 2 = warning, 1 = info. Deprecations are reported by id.
# Spec-level notes (no operation, e.g. "info.version not bumped") are not endpoint changes, so they are skipped.
changes=$(oasdiff changelog "$old" "$new" --format json | jq '[.[] | select(.operation and .path) | {
  method: .operation,
  path: .path,
  level: (if (.id | test("deprecat")) then "deprecation" elif .level == 3 then "breaking" else "non-breaking" end),
  text: .text
}]')

if [ "$markdown" = true ]; then
  [ "$changes" = "[]" ] && exit 0
  echo "$changes" | jq -r '
    "| Endpoint | Level | Change |",
    "|---|---|---|",
    (.[] | "| `\(.method) \(.path)` | \(if .level == "breaking" then "🔴 breaking" elif .level == "deprecation" then "🟡 deprecation" else "🟢 non-breaking" end) | \(.text) |")'
  exit 0
fi

version="$3"
file="${4:-api-changelog.json}"
note="${5:-}"

if [ "$changes" = "[]" ]; then
  echo "No API changes for $version"
  exit 0
fi

[ -f "$file" ] || echo '{"releases": []}' > "$file"

jq --arg v "$version" --arg d "$(date -u +%F)" --arg n "$note" --argjson c "$changes" \
  '.releases = [{version: $v, date: $d} + (if $n == "" then {} else {note: $n} end) + {changes: $c}] + .releases' \
  "$file" > "$file.tmp" && mv "$file.tmp" "$file"

echo "Added $(echo "$changes" | jq length) change(s) for $version to $file"
