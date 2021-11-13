Network Watcher
===============

![CI](https://github.com/soketi/network-watcher/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/soketi/network-watcher/branch/master/graph/badge.svg)](https://codecov.io/gh/soketi/network-watcher)
[![StyleCI](https://github.styleci.io/repos/350800968/shield?branch=master)](https://github.styleci.io/repos/350800968)
![License](https://img.shields.io/github/license/soketi/network-watcher)

![v1.20.10 K8s Version](https://img.shields.io/badge/K8s%20v1.20.10-Ready-%23326ce5?colorA=306CE8&colorB=green)
![v1.21.4 K8s Version](https://img.shields.io/badge/K8s%20v1.21.4-Ready-%23326ce5?colorA=306CE8&colorB=green)
![v1.22.1 K8s Version](https://img.shields.io/badge/K8s%20v1.22.1-Ready-%23326ce5?colorA=306CE8&colorB=green)

Monitor Kubernetes containers for memory allowance and redirect new HTTP/WebSocket connections to pods that have enough memory to sustain them.

This can be generally used for any kind of server, but its main purpose was to redirect new WebSocket connections to pods that have enough memory to withstand them in [soketi server](https://github.com/soketi/soketi).

Under the hood, it works by setting a pod label to either `yes`/`no` and you should make the Kubernetes Service to seek for pods by that label, with the value `yes`. You can find examples [in the documentation](https://rennokki.gitbook.io/soketi/network-watcher/getting-started).

## 🤝 Supporting

[<img src="https://github-content.s3.fr-par.scw.cloud/static/39.jpg" height="210" width="418" />](https://github-content.renoki.org/github-repo/39)

If you are using one or more Renoki Co. open-source packages in your production apps, in presentation demos, hobby projects, school projects or so, spread some kind words about our work or sponsor our work via Patreon. 📦

[<img src="https://c5.patreon.com/external/logo/become_a_patron_button.png" height="41" width="175" />](https://www.patreon.com/bePatron?u=10965171)

## 📜 Documentation

Documentation about how to integrate Network Watcher with your soketi-running pods is available [here](https://rennokki.gitbook.io/soketi-docs/network-watcher/getting-started).

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
