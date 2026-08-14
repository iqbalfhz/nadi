# syntax=docker/dockerfile:1

# ---- Stage 1: build frontend assets ----
# Debian-based (glibc), not alpine: package.json's optionalDependencies pins
# the "-gnu" native binaries for Rollup/Tailwind oxide/lightningcss (not
# "-musl"), so building on musl-based Alpine leaves those binaries
# unresolved and crashes Rolldown/Vite's build.
FROM node:22-bookworm-slim AS node_builder
WORKDIR /app

# Vite bakes VITE_* values into the built JS at build time, not runtime — so
# these need to arrive as build args (passed via docker-compose.yml's
# build.args, sourced from Coolify's configured environment variables), not
# just as container environment variables like the rest of .env.
ARG VITE_APP_NAME
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME
ENV VITE_APP_NAME=${VITE_APP_NAME} \
    VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY} \
    VITE_REVERB_HOST=${VITE_REVERB_HOST} \
    VITE_REVERB_PORT=${VITE_REVERB_PORT} \
    VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Stage 2: install PHP dependencies ----
FROM composer:2 AS composer_builder
WORKDIR /app
COPY composer.json composer.lock ./
# --ignore-platform-reqs: this build stage uses the minimal official
# composer:2 image, which lacks ext-intl/gd/exif that filament/support
# checks for. It never executes app code, only resolves dependencies — the
# real runtime (stage 3) does have these extensions installed.
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs
COPY . .
# --no-scripts here too: package:discover/filament:upgrade (wired as
# composer's post-autoload-dump hooks) need a real .env to boot the app,
# which doesn't exist at build time — they run instead from entrypoint.sh,
# once the container has real environment variables at hand.
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts

# ---- Stage 3: runtime image ----
FROM dunglas/frankenphp:1-php8.3

RUN install-php-extensions \
    pdo_mysql \
    gd \
    zip \
    intl \
    bcmath \
    opcache \
    pcntl

WORKDIR /app

COPY --from=composer_builder /app /app
COPY --from=node_builder /app/public/build /app/public/build

COPY deploy/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Runs as root, matching FrankenPHP's own documented default (`docker run
# dunglas/frankenphp`) — deliberately not switching to a non-root user here,
# since that requires replicating FrankenPHP's own CAP_NET_BIND_SERVICE +
# Caddy state-dir chown dance to keep port 80 binding working, and getting
# that wrong silently breaks startup. Worth revisiting once the basic
# deployment is confirmed working.
ENV SERVER_NAME=:80
EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
