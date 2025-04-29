ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli

RUN apt-get update && apt-get install -y \
	zip

RUN pecl install xdebug-3.4.2 \
	&& docker-php-ext-enable xdebug

COPY --from=composer:2.8.1 /usr/bin/composer /usr/bin/composer

COPY . /usr/src/app
WORKDIR /usr/src/app
RUN /usr/bin/composer install

ENV XDEBUG_MODE=coverage

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
