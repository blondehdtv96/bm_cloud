#!/usr/bin/env bash
# API smoke test for bm_cloud: login -> me -> create folder -> upload -> list ->
# download -> delete. Requires the server to be running (default 127.0.0.1:8000).
# Usage: bash .claude/skills/run-bm-cloud/smoke.sh [base_url]
set -euo pipefail
BASE="${1:-http://127.0.0.1:8000}"
USER_="${BM_USER:-admin}"
PASS="${BM_PASS:-admin123}"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
H=(-H "Accept: application/json")

step() { printf '\n== %s\n' "$*"; }
json() { php -r '$d=json_decode(stream_get_contents(STDIN),true); $p=explode(".",$argv[1]); foreach($p as $k){ $d=$d[$k]??null; } echo is_scalar($d)?$d:json_encode($d);' "$1"; }

step "GET / (SPA shell)"
curl -sf "$BASE/" | grep -o '<title>[^<]*'

step "POST /api/login as $USER_"
LOGIN=$(curl -sf "${H[@]}" -H "Content-Type: application/json" \
  -d "{\"username\":\"$USER_\",\"password\":\"$PASS\"}" "$BASE/api/login")
TOKEN=$(echo "$LOGIN" | json access_token)
[ -n "$TOKEN" ] || { echo "login failed: $LOGIN"; exit 1; }
A=(-H "Authorization: Bearer $TOKEN")
echo "token ok"

step "GET /api/me"
curl -sf "${H[@]}" "${A[@]}" "$BASE/api/me" | json name

step "POST /api/folders"
FOLDER=$(curl -sf "${H[@]}" "${A[@]}" -H "Content-Type: application/json" \
  -d "{\"name\":\"smoke-$(date +%s)\"}" "$BASE/api/folders")
FID=$(echo "$FOLDER" | json id); [ -n "$FID" ] || FID=$(echo "$FOLDER" | json data.id)
echo "folder id=$FID"

step "POST /api/files/upload"
echo "hello from smoke.sh $(date)" > "$TMP/smoke.txt"
UP=$(curl -sf "${H[@]}" "${A[@]}" -F "file=@$TMP/smoke.txt" --form-string "folder_id=$FID" "$BASE/api/files/upload")
FILE_ID=$(echo "$UP" | json id); [ -n "$FILE_ID" ] || FILE_ID=$(echo "$UP" | json data.id)
echo "file id=$FILE_ID"

step "GET /api/folders?folder_id=$FID (what DriveView calls)"
curl -sf "${H[@]}" "${A[@]}" "$BASE/api/folders?folder_id=$FID" | grep -o "smoke.txt" | head -1

step "GET /api/files/$FILE_ID/download"
curl -sf "${A[@]}" "$BASE/api/files/$FILE_ID/download" -o "$TMP/dl.txt"
cmp "$TMP/smoke.txt" "$TMP/dl.txt" && echo "download matches upload"

step "DELETE file + folder"
curl -sf -o /dev/null -w "file %{http_code}\n" -X DELETE "${H[@]}" "${A[@]}" "$BASE/api/files/$FILE_ID"
curl -sf -o /dev/null -w "folder %{http_code}\n" -X DELETE "${H[@]}" "${A[@]}" "$BASE/api/folders/$FID"

echo; echo "SMOKE OK"
