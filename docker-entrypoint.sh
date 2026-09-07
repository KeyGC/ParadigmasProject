#!/usr/bin/env bash
set -e

# Render.com inyecta $PORT; Apache debe escuchar en ese puerto. Fuera de Render
# se usa 80 por defecto (docker run -p 8080:80 ...).
PORT="${PORT:-80}"

# Ajustar el puerto de escucha global de Apache
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf

# VirtualHost que sirve la app desde Publico/
cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
	ServerName localhost
	DocumentRoot /var/www/html/Publico
	<Directory /var/www/html/Publico>
		Options Indexes FollowSymLinks MultiViews
		AllowOverride All
		Require all granted
	</Directory>

	ErrorLog \${APACHE_LOG_DIR}/error.log
	CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

a2ensite 000-default.conf >/dev/null 2>&1 || true

exec apache2-foreground