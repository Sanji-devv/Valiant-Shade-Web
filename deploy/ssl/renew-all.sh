#!/usr/bin/env bash
# Let's Encrypt yenileme + gerekirse SAN genişletme (cron: certbot.timer zaten var; bu manuel / hook için).
set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/Valiant-Shade-Web}"
WEBROOT_LARAVEL="${APP_ROOT}/public"
EMAIL="${CERTBOT_EMAIL:-}"

# Zorunlu alan adları (DNS yoksa certbot zaten başarısız olur).
CORE_DOMAINS=(
  valiantshade.com
  www.valiantshade.com
  gazete.valiantshade.com
  gazette.valiantshade.com
  harita.valiantshade.com
  map.valiantshade.com
  md.valiantshade.com
)

# DNS A/AAAA kaydı yoksa atlanır (NXDOMAIN ile tüm genişletme düşmesin).
OPTIONAL_DOMAINS=(
  npc.valiantshade.com
  npcs.valiantshade.com
  tanri.valiantshade.com
  gods.valiantshade.com
  god.valiantshade.com
)

dns_resolves() {
  local host="$1"
  local ans
  if command -v dig >/dev/null 2>&1; then
    ans="$(dig +short A "$host" @8.8.8.8 2>/dev/null | head -1)"
    [[ -n "$ans" ]] && return 0
    ans="$(dig +short AAAA "$host" @8.8.8.8 2>/dev/null | head -1)"
    [[ -n "$ans" ]]
    return
  fi
  if command -v getent >/dev/null 2>&1; then
    getent ahostsv4 "$host" 2>/dev/null | grep -q .
    return
  fi
  return 0
}

if [[ -z "${EMAIL}" ]]; then
  EMAIL=$(grep -m1 '^email' /etc/letsencrypt/renewal/valiantshade.com.conf 2>/dev/null | cut -d= -f2 | tr -d ' ' || true)
fi
[[ -n "${EMAIL}" ]] || EMAIL="admin@valiantshade.com"

DOMAINS=("${CORE_DOMAINS[@]}")
for d in "${OPTIONAL_DOMAINS[@]}"; do
  if dns_resolves "$d"; then
    DOMAINS+=("$d")
  else
    echo "UYARI: $d DNS'te yok — sertifikaya eklenmedi (A/AAAA kaydı ekleyince tekrar çalıştırın)." >&2
  fi
done

D_ARGS=()
for d in "${DOMAINS[@]}"; do
  D_ARGS+=(-d "$d")
done

echo "==> Certbot SAN: ${DOMAINS[*]}"

if [[ -x "${APP_ROOT}/deploy/ssl/verify-acme-webroot.sh" ]]; then
  echo "==> ACME webroot ön kontrolü (tanri/god dahil)..."
  if ! "${APP_ROOT}/deploy/ssl/verify-acme-webroot.sh"; then
    echo "HATA: Bazı hostlar http-01 dosyasını okuyamıyor. deploy/nginx/README.md — ACME 404." >&2
    exit 1
  fi
fi

certbot certonly --webroot -w "$WEBROOT_LARAVEL" \
  --non-interactive --agree-tos --email "$EMAIL" --expand \
  --cert-name valiantshade.com \
  "${D_ARGS[@]}"

if [[ -f /etc/letsencrypt/renewal/158.51.78.172.conf ]]; then
  ip_renewal="/etc/letsencrypt/renewal/158.51.78.172.conf"
  if grep -qE '^\s*ipaddrs\s*=\s*158\.51\.78\.172' "$ip_renewal" 2>/dev/null \
    || grep -qE '^\s*domains\s*=\s*158\.51\.78\.172' "$ip_renewal" 2>/dev/null; then
    certbot renew --cert-name 158.51.78.172 --quiet 2>/dev/null || \
      echo "UYARI: 158.51.78.172 sertifikası yenilenemedi (süre dolmadı veya profil hatası)." >&2
  else
    echo "UYARI: 158.51.78.172 renewal profili bozuk — atlandı (MonsterDB: https://md.valiantshade.com)." >&2
  fi
else
  echo "IP sertifikası atlandı (yenileme profili yok)." >&2
fi

nginx -t
systemctl reload nginx

echo "OK — $(date -Is)"
certbot certificates 2>/dev/null | sed -n '/Certificate Name/,/^  Certificate/p' | head -24