Soketi Network Watcher
=======================

![CI](https://github.com/soketi/network-watcher/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/soketi/network-watcher/branch/master/graph/badge.svg)](https://codecov.io/gh/soketi/network-watcher)
[![StyleCI](https://github.styleci.io/repos/350800968/shield?branch=master)](https://github.styleci.io/repos/350800968)
![License](https://img.shields.io/github/license/soketi/network-watcher)

![v1.19.10 K8s Version](https://img.shields.io/badge/K8s%20v1.19.10-Ready-%23326ce5?colorA=306CE8&colorB=green)
![v1.20.6 K8s Version](https://img.shields.io/badge/K8s%20v1.20.6-Ready-%23326ce5?colorA=306CE8&colorB=green)
![v1.21.0 K8s Version](https://img.shields.io/badge/K8s%20v1.21.0-Ready-%23326ce5?colorA=306CE8&colorB=green)

Monitor the [pWS server](https://github.com/soketi/pws) container for memory allowance and new connections when running in Kubernetes.

## 🤝 Supporting

If you are using one or more Renoki Co. open-source packages in your production apps, in presentation demos, hobby projects, school projects or so, spread some kind words about our work or sponsor our work via Patreon. 📦

You will sometimes get exclusive content on tips about Laravel, AWS or Kubernetes on Patreon and some early-access to projects or packages.

[<img src="https://c5.patreon.com/external/logo/become_a_patron_button.png" height="41" width="175" />](https://www.patreon.com/bePatron?u=10965171)

## 📜 Documentation

Documentation about how to integrate Network Watcher with your pWS-running pods is available [here](https://rennokki.gitbook.io/soketi-pws/network-watcher/getting-started).

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
