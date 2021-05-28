Echo Network Watcher
=====================

![CI](https://github.com/soketi/network-watcher/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/soketi/network-watcher/branch/master/graph/badge.svg)](https://codecov.io/gh/soketi/network-watcher)
[![StyleCI](https://github.styleci.io/repos/350800968/shield?branch=master)](https://github.styleci.io/repos/350800968)

![v1.19.10 K8s Version](https://img.shields.io/badge/K8s%20v1.19.10-Ready-%23326ce5?colorA=306CE8&colorB=green)
![v1.20.6 K8s Version](https://img.shields.io/badge/K8s%20v1.20.6-Ready-%23326ce5?colorA=306CE8&colorB=green)
![v1.21.0 K8s Version](https://img.shields.io/badge/K8s%20v1.21.0-Ready-%23326ce5?colorA=306CE8&colorB=green)

Monitor the [Echo Server](https://github.com/soketi/echo-server) container for memory allowance and new connections when running in Kubernetes.

## 🤔 What does this controller solve?

If you run Echo Server standalone in a cluster, at scale, you might run into capacity issues: RAM usage might be near the limit and even if you decide to horizontally scale the pods, new connections might still come to pods that are near-limit and run into OOM at some point.

Running Network Watcher inside the same pod will solve the issues by continuously checking the Echo Server Usage API, labeling the pods that get over a specified threshold with `echo.soketi.app/accepts-new-connections: "no"`, so that the services watching for the pods will ignore them if also checking for this label:

```yaml
spec:
  type: LoadBalancer
  ports:
    - port: 6001
      targetPort: 6001
      protocol: TCP
      name: echo
  selector:
    ...
    echo.soketi.app/accepts-new-connections: "yes"
```

## 🙌 Requirements

- PHP 8.0+
- [Echo Server](https://github.com/soketi/echo-server) 5.3+

## Docker image

[Network Watcher is available via Docker](https://hub.docker.com/r/soketi/network-watcher). Use the images to run them into your cluster and use this project to develop the application.

[Network Watcher also comes with the Echo Server Helm chart](https://github.com/soketi/charts/tree/master/charts/echo-server). It just needs to be turned on if you need the network watcher and the according service annotations will be appended automatically.

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
