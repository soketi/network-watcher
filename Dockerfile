FROM quay.io/renokico/laravel-base:worker-1.3.0-8.1-cli-alpine

WORKDIR /app

COPY builds/network-watcher /app/network-watcher

CMD ["/app/network-watcher", "network:watch"]
