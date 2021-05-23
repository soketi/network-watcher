Echo Network Watcher
=====================

![CI](https://github.com/soketi/network-watcher/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/soketi/network-watcher/branch/master/graph/badge.svg)](https://codecov.io/gh/soketi/network-watcher)
[![StyleCI](https://github.styleci.io/repos/350800968/shield?branch=master)](https://github.styleci.io/repos/350800968)

![v1.19.10 K8s Version](https://img.shields.io/badge/K8s%20v1.19.10-Ready-%23326ce5?colorA=306CE8&colorB=green)
![v1.20.6 K8s Version](https://img.shields.io/badge/K8s%20v1.20.6-Ready-%23326ce5?colorA=306CE8&colorB=green)
![v1.21.0 K8s Version](https://img.shields.io/badge/K8s%20v1.21.0-Ready-%23326ce5?colorA=306CE8&colorB=green)

Laravel Zero-based app that monitors Echo container and manages the new, incoming connections, within a Kubernetes cluster.

Soketi is the service name for the [soketi/echo-server](https://github.com/soketi/echo-server) project that runs a SaaS and a Dashboard that will allow users to connect via the Soketi Fleet, written for Kubernetes.

## 🙌 Requirements

- PHP 8.0+
- Kubernetes v1.20.2 (optional; for Kubernetes-like testing)
- [Echo Server](https://github.com/soketi/echo-server) 4.2+

## 🚀 Installation

```bash
$ composer install --ignore-platform-reqs && cp .env.example .env
```

## Running the Network Watcher

```bash
$ php application network:watch
```

## Configuration

| Environment variable | Flag | Default | Description |
| - | - | - | - |
| `POD_NAMESPACE` | `--pod-namespace` | `default` | The Pod namespce to watch. |
| `POD_NAME` | `--pod-name` | `some-pod` | The Pod name to watch. |
| `PROBES_TOKEN` | `--probes-token` | `probes-token` | The token used for the [probes API](https://github.com/soketi/echo-server/blob/master/docs/ENV.md#probes-api). |
| `ECHO_APP_PORT` | `--echo-app-port` | `6001` | The port number for the [Echo Server](https://github.com/soketi/echo-server) app. |
| `MEMORY_PERCENT` | `--memory-percent` | `75` | The threshold (in percent) that, once reached, the Pod will be marked as "not ready" to evict any new connections or requests. |
| `CHECKING_INTERVAL` | `--checking-interval` | `1` | The amount of seconds to wait between API checks. |
| `TEST_MODE` | `--test` | - | Run a single check rather than a continous loop of checks. |


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
