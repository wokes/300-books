FROM ubuntu:24.04 AS base

LABEL maintainer="wokes"

ARG WWWGROUP=1000
ARG POSTGRES_VERSION=18
ARG NODE_VERSION=22

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV SUPERVISOR_PHP_USER="sail"

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

RUN apt-get update && apt-get upgrade -y \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor libcap2-bin libpng-dev dnsutils \
    && curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y libgd3 php8.4-cli \
    php8.4-pgsql php8.4-gd \
    php8.4-curl \
    php8.4-mbstring \
    php8.4-xml php8.4-zip php8.4-bcmath \
    php8.4-intl php8.4-readline \
    php8.4-igbinary php8.4-redis \
    php8.4-swoole \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor | tee /etc/apt/keyrings/pgdg.gpg >/dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt noble-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y postgresql-client-$POSTGRES_VERSION \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN userdel -r ubuntu \
    && groupadd --force -g $WWWGROUP sail \
    && useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail \
    && git config --global --add safe.directory /var/www/html

# Dependencies
FROM base AS deps

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader

COPY package.json package-lock.json* ./
RUN npm install

# Final Image
FROM base

COPY docker/8.4/start-container /usr/local/bin/start-container
COPY docker/8.4/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/8.4/php.ini /etc/php/8.4/cli/conf.d/99-sail.ini
RUN sed -i 's/\r$//' /usr/local/bin/start-container && chmod +x /usr/local/bin/start-container

# Copy pre-built dependencies from builder stage
COPY --from=deps /var/www/html/vendor /tmp/vendor-cache
COPY --from=deps /var/www/html/node_modules /tmp/node-cache

EXPOSE 8000

ENTRYPOINT ["start-container"]
