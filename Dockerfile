FROM php:8-cli-alpine

COPY . /app

RUN docker-php-ext-configure bcmath --enable-bcmath && \
    docker-php-ext-configure pcntl --enable-pcntl && \
    docker-php-ext-configure mbstring --enable-mbstring && \
    docker-php-ext-install \
        bcmath \
        mbstring \
        pcntl

RUN rm -rf tests/ .git/ .github/ *.md && \
    rm -rf vendor/*/test/ vendor/*/tests/*

WORKDIR /app

CMD ["php", "application", "network:watch"]
