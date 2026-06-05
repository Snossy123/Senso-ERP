#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html/dashboard

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

composer install --no-interaction --prefer-dist

# #region agent log
DEBUG_LOG="/var/www/html/dashboard/.cursor/debug-06ce38.log"
mkdir -p "$(dirname "${DEBUG_LOG}")"
log_entry() {
  printf '%s\n' "$1" >> "${DEBUG_LOG}"
}
# #endregion

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
  # #region agent log
  log_entry "$(printf '{"sessionId":"06ce38","runId":"pre-fix","hypothesisId":"B","location":"entrypoint-dashboard.sh:key","message":"app_key_generated","data":{"reason":"missing_key"},"timestamp":%s}' "$(date +%s000)")"
  # #endregion
else
  # #region agent log
  log_entry "$(printf '{"sessionId":"06ce38","runId":"pre-fix","hypothesisId":"B","location":"entrypoint-dashboard.sh:key","message":"app_key_preserved","data":{"reason":"existing_key"},"timestamp":%s}' "$(date +%s000)")"
  # #endregion
fi

# Read DB settings from .env (simple parser; supports optional quotes)
while IFS= read -r line || [[ -n "${line}" ]]; do
  [[ -z "${line}" || "${line}" == \#* ]] && continue
  [[ "${line}" != DB_*=* ]] && continue

  key="${line%%=*}"
  value="${line#*=}"
  value="${value%$'\r'}"

  if [[ "${value}" == \"*\" ]]; then
    value="${value:1:${#value}-2}"
  elif [[ "${value}" == \'*\' ]]; then
    value="${value:1:${#value}-2}"
  fi

  case "${key}" in
    DB_HOST) DB_HOST="${value}" ;;
    DB_PORT) DB_PORT="${value}" ;;
    DB_DATABASE) DB_DATABASE="${value}" ;;
    DB_USERNAME) DB_USERNAME="${value}" ;;
    DB_PASSWORD) DB_PASSWORD="${value}" ;;
  esac
done < .env

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-dashboard}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-root}"

echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT} (db=${DB_DATABASE}, user=${DB_USERNAME})..."
for _ in {1..120}; do
  if MYSQL_PWD="${DB_PASSWORD}" mysqladmin ping -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" --silent; then
    echo "MySQL is ready."
    break
  fi
  sleep 0.5
done

if ! MYSQL_PWD="${DB_PASSWORD}" mysqladmin ping -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" --silent; then
  echo "MySQL did not become ready in time." >&2
  # #region agent log
  log_entry "$(printf '{"sessionId":"06ce38","runId":"pre-fix","hypothesisId":"C","location":"entrypoint-dashboard.sh:mysql","message":"mysql_not_ready","data":{"host":"%s","port":"%s","database":"%s"},"timestamp":%s}' "${DB_HOST}" "${DB_PORT}" "${DB_DATABASE}" "$(date +%s000)")"
  # #endregion
  exit 1
fi

# #region agent log
log_entry "$(printf '{"sessionId":"06ce38","runId":"pre-fix","hypothesisId":"C","location":"entrypoint-dashboard.sh:mysql","message":"mysql_ready","data":{"host":"%s","port":"%s","database":"%s"},"timestamp":%s}' "${DB_HOST}" "${DB_PORT}" "${DB_DATABASE}" "$(date +%s000)")"
# #endregion

php artisan migrate --force || true

# #region agent log
log_entry "$(printf '{"sessionId":"06ce38","runId":"pre-fix","hypothesisId":"A","location":"entrypoint-dashboard.sh:serve","message":"starting_artisan_serve","data":{"host":"0.0.0.0","port":"8000"},"timestamp":%s}' "$(date +%s000)")"
# #endregion

exec php artisan serve --host=0.0.0.0 --port=8000
