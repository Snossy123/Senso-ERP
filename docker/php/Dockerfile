FROM php:8.4-cli

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    bash \
    default-mysql-client \
    git \
    unzip \
    libzip-dev \
  && docker-php-ext-install pdo_mysql zip \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/entrypoint-dashboard.sh /usr/local/bin/entrypoint-dashboard.sh
RUN chmod +x /usr/local/bin/entrypoint-dashboard.sh

WORKDIR /var/www/html
