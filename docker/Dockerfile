FROM php:8.1-apache

# ─── PHP Extensions ───────────────────────────────────────────────────────────
RUN docker-php-ext-install pdo pdo_mysql

# unzip: composer descarga paquetes en .zip (sin esto cae a git y falla)
RUN apt-get update && apt-get install -y --no-install-recommends unzip && rm -rf /var/lib/apt/lists/*

# ─── Apache modules ───────────────────────────────────────────────────────────
RUN a2enmod rewrite headers

# ─── Apache: AllowOverride All (para .htaccess del backoffice) ────────────────
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# ─── Suprimir ServerName warning ──────────────────────────────────────────────
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# ─── PHP config de producción ligera ──────────────────────────────────────────
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# ─── www-data con el UID/GID del host ────────────────────────────────────────
# landing/ es un bind mount: así Apache puede leer/escribir storage/ y uploads/
# sin chmod 777 y los archivos que crea quedan del usuario del host
ARG HOST_UID=1000
ARG HOST_GID=1000
RUN groupmod -o -g ${HOST_GID} www-data && usermod -o -u ${HOST_UID} -g ${HOST_GID} www-data

EXPOSE 80
