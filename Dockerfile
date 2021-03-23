FROM php:8-cli-alpine

COPY . /app

WORKDIR /app

CMD ["php", "application", "network:watch"]
