FROM php:8.5-fpm-alpine

ARG UID=1000
ARG GID=1000

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# pcov: code coverage for the test suite (SonarQube coverage report)
RUN install-php-extensions pdo_mysql bcmath intl zip pcov \
    && apk add --no-cache git unzip \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/app.ini $PHP_INI_DIR/conf.d/zz-app.ini

# Same UID/GID as the host user, so files created in the container are not owned by root
RUN addgroup -g ${GID} app && adduser -D -u ${UID} -G app app \
    && sed -i 's/^user = .*/user = app/; s/^group = .*/group = app/' /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html
USER app
