FROM php:8.1-apache

# ─── PHP Extensions ───────────────────────────────────────────────────────────
RUN docker-php-ext-install pdo pdo_mysql

# ─── Apache modules ───────────────────────────────────────────────────────────
RUN a2enmod rewrite headers

# ─── Apache: AllowOverride All (para .htaccess del backoffice) ────────────────
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# ─── Suprimir ServerName warning ──────────────────────────────────────────────
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# ─── PHP config de producción ligera ──────────────────────────────────────────
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

EXPOSE 80
