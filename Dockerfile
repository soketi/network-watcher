FROM php:8-cli-alpine

WORKDIR /app

COPY builds/network-watcher /app/network-watcher

RUN docker-php-ext-configure bcmath --enable-bcmath && \
    docker-php-ext-configure pcntl --enable-pcntl && \
    docker-php-ext-configure mbstring --enable-mbstring && \
    docker-php-ext-install bcmath intl mbstring pcntl sockets zip

RUN docker-php-ext-configure intl --with-icu-dir=/usr/local && \
    docker-php-ext-install intl

CMD ["/app/network-watcher", "network:watch"]
