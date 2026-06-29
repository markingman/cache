ARG PHP_VERSION=8.3
ARG COMPOSER_VERSION=2.9.2
ARG XDEBUG_VERSION=3.5.1

FROM php:${PHP_VERSION}-cli AS base
ARG XDEBUG_VERSION

RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    set -eux; \
    apt-get update; \
	apt-get install -y --no-install-recommends \
		git unzip; \
	rm -rf /var/lib/apt/lists/*; \
	pecl install -o xdebug-${XDEBUG_VERSION}; \
	docker-php-ext-enable xdebug; \
	pecl clear-cache && rm -rf /tmp/pear;

FROM composer:${COMPOSER_VERSION} AS composer

FROM base

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /usr/src/app
COPY . .

RUN /usr/bin/composer install --prefer-dist --no-interaction
