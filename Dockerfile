FROM quay.io/renokico/laravel-base:worker-1.2.0-8.0-cli-alpine

WORKDIR /app

COPY builds/network-watcher /app/network-watcher

CMD ["/app/network-watcher", "network:watch"]
