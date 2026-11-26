#!/usr/bin/env bash
# Let's Encrypt yenileme + gerekirse SAN genişletme (cron: certbot.timer zaten var; bu manuel / hook için).
set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/Valiant-Shade-Web}"
WEBROOT_LARAVEL="${APP_ROOT}/public"
WEBROOT_ACME="${WEBROOT_ACME:-/var/www/acme}"
EMAIL="${CERTBOT_EMAIL:-}"

DOMAINS=(
  valiantshade.com
  www.valiantshade.com
  gazete.valiantshade.com
  gazette.valiantshade.com
  harita.valiantshade.com
  map.valiantshade.com
  npc.valiantshade.com
  npcs.valiantshade.com
  md.valiantshade.com
)

if [[ -z "${EMAIL}" ]]; then
  EMAIL=$(grep -m1 '^email' /etc/letsencrypt/renewal/valiantshade.com.conf 2>/dev/null | cut -d= -f2 | tr -d ' ' || true)
fi
[[ -n "${EMAIL}" ]] || EMAIL="admin@valiantshade.com"

D_ARGS=()
for d in "${DOMAINS[@]}"; do
  D_ARGS+=(-d "$d")
done

certbot certonly --webroot -w "$WEBROOT_LARAVEL" \
  --non-interactive --agree-tos --email "$EMAIL" --expand \
  --cert-name valiantshade.com \
  "${D_ARGS[@]}"

# IP için yeni Let's Encrypt sertifikası çoğu ortamda artık verilmez; mevcut dosya varsa certbot renew dener.
if [[ -f /etc/letsencrypt/renewal/158.51.78.172.conf ]]; then
  certbot renew --cert-name 158.51.78.172 --quiet || \
    echo "UYARI: 158.51.78.172 sertifikası yenilenemedi — md.valiantshade.com kullanın." >&2
else
  echo "IP sertifikası atlandı (yenileme profili yok)." >&2
fi

nginx -t
systemctl reload nginx

echo "OK — $(date -Is)"
certbot certificates 2>/dev/null | sed -n '/Certificate Name/,/^  Certificate/p' | head -24