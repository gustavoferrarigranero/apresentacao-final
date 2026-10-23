#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

command -v tbls >/dev/null || brew install k1low/tap/tbls

DB_FILE="${1:-database.sqlite}"
[ -f "$DB_FILE" ] || { echo "Ficheiro nao encontrado: $DB_FILE"; exit 1; }

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
cp "$DB_FILE" "$TMP/copia.sqlite"
[ -f "$DB_FILE-wal" ] && cp "$DB_FILE-wal" "$TMP/copia.sqlite-wal" || true
[ -f "$DB_FILE-shm" ] && cp "$DB_FILE-shm" "$TMP/copia.sqlite-shm" || true

echo "A documentar copia de: $DB_FILE"
tbls doc "sqlite://$TMP/copia.sqlite" --config .tbls.yml --force

if command -v sqlite3 >/dev/null; then
  sqlite3 "$TMP/copia.sqlite" .schema > docs-bd/schema.sql
fi

echo "Concluido: database/docs-bd/README.md"
