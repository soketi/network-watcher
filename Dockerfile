FROM php:8-cli-alpine

WORKDIR /app

COPY builds/network-watcher /app/network-watcher

CMD ["/app/network-watcher", "network:watch"]
