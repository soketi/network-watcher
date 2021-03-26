Echo Network Watcher
=====================

![CI](https://github.com/soketi/network-watcher/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/soketi/network-watcher/branch/master/graph/badge.svg?token=31PP7UF8SX)](https://codecov.io/gh/soketi/network-watcher)
![v1.20.2 K8s Version](https://img.shields.io/badge/K8s%20v1.20.2-Ready-%23326ce5?colorA=306CE8&colorB=green)

Laravel Zero-based app that monitors Echo container and manages the new, incoming connections, within a Kubernetes cluster.

Soketi is the service name for the [soketi/echo-server](https://github.com/soketi/echo-server) project that runs a SaaS and a Dashboard that will allow users to connect via the Soketi Fleet, written for Kubernetes.

## 🙌 Requirements

- PHP 8.0+
- Kubernetes v1.20.2 (optional; for Kubernetes-like testing)

## 🚀 Installation

```bash
$ composer install --ignore-platform-reqs && cp .env.example .env
```

## Running the Network Watcher

```bash
$ php application network:watch
```

## Configuration

Coming soon.

## 🐛 Testing

``` bash
vendor/bin/phpunit
```

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒  Security

If you discover any security related issues, please email alex@renoki.org instead of using the issue tracker.

## 🎉 Credits

- [Alex Renoki](https://github.com/rennokki)
- [All Contributors](../../contributors)
