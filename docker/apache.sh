#!/bin/sh
# Cấu hình Apache: gốc web = /opt/app/public, mọi đường dẫn không phải tệp tĩnh đi vào index.php
set -e
sed -ri 's!/var/www/html!/opt/app/public!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf
cat > /etc/apache2/conf-enabled/vnas.conf <<'CONF'
<Directory /opt/app/public>
  Require all granted
  FallbackResource /index.php
</Directory>
ServerTokens Prod
ServerSignature Off
CONF
mkdir -p "${DATA_DIR:-/data}"
chown -R www-data:www-data "${DATA_DIR:-/data}"
