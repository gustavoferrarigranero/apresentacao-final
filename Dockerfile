# Instala PHP e as extensões usadas pela aplicação, pelo Redis e pelos workers.
FROM php:8.3-fpm-alpine AS php-runtime
RUN apk add --no-cache icu-libs libzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev linux-headers \
    && docker-php-ext-install bcmath intl opcache pcntl pdo_mysql sockets zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# Resolve as dependências de produção e otimiza o autoloader sem executar scripts prematuramente.
FROM php-runtime AS dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-progress
COPY . .
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

# Executa Wayfinder dentro de um ambiente que tem PHP, Composer e as rotas da aplicação.
FROM php-runtime AS frontend
RUN apk add --no-cache nodejs npm
WORKDIR /var/www/html
COPY --from=dependencies /var/www/html ./
ARG VITE_APP_NAME="Delivery Hub"
ARG VITE_REVERB_APP_KEY=local-key
ARG VITE_REVERB_HOST=localhost
ARG VITE_REVERB_PORT=8080
ARG VITE_REVERB_SCHEME=http
ENV VITE_APP_NAME=${VITE_APP_NAME}
ENV VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
ENV VITE_REVERB_HOST=${VITE_REVERB_HOST}
ENV VITE_REVERB_PORT=${VITE_REVERB_PORT}
ENV VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}
RUN npm ci && npm run build

# Publica apenas a aplicação PHP e os assets já compilados, sem instalar Node na imagem de runtime.
FROM php-runtime AS app
WORKDIR /var/www/html
COPY --from=dependencies /var/www/html ./
COPY --from=frontend /var/www/html/public/build ./public/build
EXPOSE 9000
CMD ["php-fpm", "-F"]

# Publica a mesma aplicação atrás de Nginx; o Compose liga Nginx ao PHP-FPM pela rede privada.
FROM nginx:1.27-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY public ./public
COPY --from=frontend /var/www/html/public/build ./public/build
EXPOSE 80
