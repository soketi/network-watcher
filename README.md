Network Watcher
===============

![CI](https://github.com/soketi/network-watcher/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/soketi/network-watcher/branch/master/graph/badge.svg)](https://codecov.io/gh/soketi/network-watcher)
[![StyleCI](https://github.styleci.io/repos/350800968/shield?branch=master)](https://github.styleci.io/repos/350800968)
![License](https://img.shields.io/github/license/soketi/network-watcher)

![v1.20.10 K8s Version](https://img.shields.io/badge/K8s%20v1.20.10-Ready-%23326ce5?colorA=306CE8&colorB=green)
![v1.21.4 K8s Version](https://img.shields.io/badge/K8s%20v1.21.4-Ready-%23326ce5?colorA=306CE8&colorB=green)
![v1.22.1 K8s Version](https://img.shields.io/badge/K8s%20v1.22.1-Ready-%23326ce5?colorA=306CE8&colorB=green)

Network Watcher is [soketi](https://github.com/soketi/soketi)'s companion that scouts the memory to avoid memory depletion.

This project was meant to be deployed in the same Pod (as a sidecar container) with soketi. Network Watcher will continuously scan the memory usage for soketi containers and in case a specific threshold is reached, it will update the Kubernetes Pod's labels to avoid new connections from kicking in, while keeping the old ones active. You can find examples [in the documentation](https://rennokki.gitbook.io/soketi-docs/network-watcher/installation).

## 🤝 Supporting

soketi is meant to be free, forever. Having a good companion for developing real-time apps locally should not involve any third-party and having a reliable option to deploy behind a firewall makes soketi a good option.

Development is done by investing time, so any help coming is appreciated. You can sponsor the development via [Github Sponsors](https://github.com/sponsors/rennokki). 📦

[<img src="https://github-content.s3.fr-par.scw.cloud/static/39.jpg" height="210" width="418" />](https://github-content.renoki.org/github-repo/39)

## 📜 Documentation

The integration steps of Network Watcher with your soketi-running Kubernetes Pods is available in the [official documentation](https://rennokki.gitbook.io/soketi-docs/network-watcher/getting-started).

## 🚀 Installation

```bash
$ composer install --ignore-platform-reqs && cp .env.example .env
```

## Running the Network Watcher

```bash
$ php artisan network:watch
```

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
