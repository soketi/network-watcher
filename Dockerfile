FROM php:8-cli-alpine

COPY . /app

RUN rm -rf tests/ .git/ .github/ *.md && \
    rm -rf vendor/*/test/ vendor/*/tests/*

WORKDIR /app

CMD ["php", "application", "network:watch"]
