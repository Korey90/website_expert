FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        bash \
        git \
        curl \
        unzip \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        sqlite-dev \
        mariadb-client \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        sockets \
    && apk del $PHPIZE_DEPS \
    && command -v mysql \
    && mysql --version

# Local Docker development only: Laravel imports database/schema/mysql-schema.sql
# through the MariaDB CLI, which rejects MySQL 8's self-signed certificate.
# Keep TLS enabled but skip certificate verification for the CLI in this
# isolated image. Do NOT copy this config to a production image: provision
# a trusted CA and verify the database server certificate there instead.
RUN printf '\n[client-mariadb]\ndisable-ssl-verify-server-cert\n' >> /etc/my.cnf \
    && mysql --print-defaults | grep -- '--disable-ssl-verify-server-cert'

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
