#!/usr/bin/env bash
# Uploads to HostMaria whatever changed in git since the revision recorded in
# .deploy-revision on the server. With no recorded revision, every tracked file
# is uploaded. The revision is written last, so a failed run is redone next time.
#
# Env: FTP_SERVER, FTP_USERNAME, FTP_PASSWORD
#      DEPLOY_FROM=<sha>  diff from this commit instead of the server's revision
#      DRY_RUN=1          print the lftp script instead of running it
set -euo pipefail

: "${FTP_SERVER:?FTP_SERVER secret is missing}"
: "${FTP_USERNAME:?FTP_USERNAME secret is missing}"
: "${FTP_PASSWORD:?FTP_PASSWORD secret is missing}"
export LFTP_PASSWORD="$FTP_PASSWORD"

REVISION_FILE=.deploy-revision

# Repo tooling, server-only secrets and admin uploads never leave GitHub.
EXCLUDES=(
  .git/ .github/ .cursor/ node_modules/ scripts/ tools/ uploads/
  README.md .gitignore .env .env.example db-check.php
  config/config.php config/config.hostmaria.php sql/seed-data.json
)

excluded() {
  local path=$1 pattern
  for pattern in "${EXCLUDES[@]}"; do
    if [[ $pattern == */ ]]; then
      if [[ $path == "$pattern"* ]]; then return 0; fi
    elif [[ $path == "$pattern" ]]; then
      return 0
    fi
  done
  return 1
}

lftp_header() {
  cat <<EOF
set ftp:ssl-force true
set ftp:ssl-protect-data true
set ssl:verify-certificate no
set ftp:passive-mode true
set net:timeout 30
set net:max-retries 8
set net:reconnect-interval-base 5
set net:reconnect-interval-multiplier 1.5
set cmd:fail-exit true
open -u "$FTP_USERNAME" --env-password "$FTP_SERVER"
EOF
}

read_remote_revision() {
  { lftp_header; echo "cat $REVISION_FILE"; echo bye; } | lftp 2>/dev/null | tr -d '[:space:]' || true
}

head_sha=$(git rev-parse HEAD)
base=${DEPLOY_FROM:-$(read_remote_revision)}

uploads=()
deletes=()
if [[ $base =~ ^[0-9a-f]{40}$ ]] && git cat-file -e "${base}^{commit}" 2>/dev/null; then
  echo "Server is at ${base:0:7}; deploying changes up to ${head_sha:0:7}."
  while IFS= read -r -d '' status && IFS= read -r -d '' path; do
    if excluded "$path"; then continue; fi
    if [[ $status == D ]]; then deletes+=("$path"); else uploads+=("$path"); fi
  done < <(git diff --name-status --no-renames -z "$base" HEAD)
else
  echo "No deployed revision found on the server; uploading every tracked file."
  while IFS= read -r -d '' path; do
    if ! excluded "$path"; then uploads+=("$path"); fi
  done < <(git ls-files -z)
fi

echo "Uploading ${#uploads[@]} file(s), deleting ${#deletes[@]}."
for path in "${uploads[@]}"; do echo "  + $path"; done
for path in "${deletes[@]}"; do echo "  - $path"; done

declare -A dirs=()
for path in "${uploads[@]}"; do
  dir=$(dirname "$path")
  if [[ $dir != . ]]; then dirs[$dir]=1; fi
done

script=$(mktemp)
{
  lftp_header
  for dir in "${!dirs[@]}"; do printf 'mkdir -p -f "%s"\n' "$dir"; done
  for path in "${uploads[@]}"; do printf 'put -O "%s" "%s"\n' "$(dirname "$path")" "$path"; done
  echo "set cmd:fail-exit false"
  for path in "${deletes[@]}"; do printf 'rm -f "%s"\n' "$path"; done
  echo "set cmd:fail-exit true"
  printf 'put -O . "%s"\n' "$REVISION_FILE"
  echo bye
} > "$script"

if [[ ${DRY_RUN:-} == 1 ]]; then
  sed 's/^open .*/open <credentials hidden>/' "$script"
  rm -f "$script"
  exit 0
fi

echo "$head_sha" > "$REVISION_FILE"
lftp -f "$script"
rm -f "$script" "$REVISION_FILE"
echo "Deployed ${head_sha:0:7}."
