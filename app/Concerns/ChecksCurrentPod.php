<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Http;

trait ChecksCurrentPod
{
    /**
     * Check the pod metrics to adjust new connection allowance.
     *
     * @param  float  $memoryThreshold
     * @param  int  $echoAppPort
     * @return void
     */
    protected function checkPod(float $memoryThreshold, int $echoAppPort): void
    {
        /** @var \App\Commands\WatchNetworkCommand $this */
        $memoryUsagePercentage = $this->getMemoryUsagePercentage($this->getEchoServerMetrics($echoAppPort));
        $dateTime = now()->toDateTimeString();

        $this->line("[{$dateTime}] Current memory usage is {$memoryUsagePercentage}%. Checking...", null, 'v');

        $this->pod->ensureItHasDefaultLabel();

        if ($memoryUsagePercentage >= $memoryThreshold) {
            if ($this->pod->acceptsConnections()) {
                $this->info("[{$dateTime}] Pod now rejects connections.");
                $this->info("[{$dateTime}] Echo container uses {$memoryUsagePercentage}%, threshold is {$memoryThreshold}%");

                $this->rejectNewConnections($memoryUsagePercentage, $memoryThreshold);
            }
        } else {
            if ($this->pod->rejectsConnections()) {
                $this->info("[{$dateTime}] Pod now accepts connections.");
                $this->info("[{$dateTime}] Echo container uses {$memoryUsagePercentage}%, threshold is {$memoryThreshold}%");

                $this->acceptNewConnections($memoryUsagePercentage, $memoryThreshold);
            }
        }
    }

    /**
     * Mark the Pod as rejecting new connections.
     *
     * @param  float  $memoryUsagePercentage
     * @param  float  $memoryThreshold
     * @return void
     */
    protected function rejectNewConnections(float $memoryUsagePercentage, float $memoryThreshold): void
    {
        /** @var \App\Commands\WatchNetworkCommand $this */
        $this->pod->rejectNewConnections();

        $now = now()->toIso8601String();

        $this->pod->newEvent()
            ->setMessage("Rejecting new connections. Echo container uses {$memoryUsagePercentage}%, threshold is {$memoryThreshold}%")
            ->setReason('OverThreshold')
            ->setType('Warning')
            ->setFirstTimestamp($now)
            ->setLastTimestamp($now)
            ->create();
    }

    /**
     * Mark the Pod as accepting new connections.
     *
     * @param  float  $memoryUsagePercentage
     * @param  float  $memoryThreshold
     * @return void
     */
    protected function acceptNewConnections(float $memoryUsagePercentage, float $memoryThreshold): void
    {
        /** @var \App\Commands\WatchNetworkCommand $this */
        $this->pod->acceptNewConnections();

        $now = now()->toIso8601String();

        $this->pod->newEvent()
            ->setMessage("Accepting new connections. Echo container uses {$memoryUsagePercentage}%, threshold is {$memoryThreshold}%")
            ->setReason('BelowThreshold')
            ->setType('Normal')
            ->setFirstTimestamp($now)
            ->setLastTimestamp($now)
            ->create();
    }

    /**
     * Get the pod metrics from Prometheus.
     *
     * @param  int  $echoAppPort
     * @return array
     */
    protected function getEchoServerMetrics(int $echoAppPort): array
    {
        return Http::get("http://localhost:{$echoAppPort}/metrics?json=1")->json()['data'] ?? [];
    }

    /**
     * Get the memory usage as percentage,
     * based on the given metrics from Prometheus.
     *
     * @param  array  $metrics
     * @return float
     */
    protected function getMemoryUsagePercentage(array $metrics): float
    {
        $totalMemoryBytes = $this->getTotalMemoryBytes($metrics);

        if ($totalMemoryBytes === 0) {
            return 0.00;
        }

        return $this->getUsedMemoryBytes($metrics) * 100 / $totalMemoryBytes;
    }

    /**
     * Get the total amount of memory allocated to the Echo Server container,
     * based on the given metrics from Prometheus.
     *
     * @param  array  $metrics
     * @return int
     */
    protected function getTotalMemoryBytes(array $metrics): int
    {
        return $this->getMetricValue($metrics, 'echo_server_process_virtual_memory_bytes');
    }

    /**
     * Get the total amount of memory that's being used by the Echo Server container,
     * based on the given metrics from Prometheus.
     *
     * @param array $metrics
     * @return int
     */
    protected function getUsedMemoryBytes(array $metrics): int
    {
        return $this->getMetricValue($metrics, 'echo_server_nodejs_external_memory_bytes') +
            $this->getMetricValue($metrics, 'echo_server_process_resident_memory_bytes');
    }

    /**
     * Get the Prometheus metric value from the list of metrics.
     *
     * @param  array  $metrics
     * @param  string  $name
     * @return int
     */
    protected function getMetricValue(array $metrics, string $name): int
    {
        return collect($metrics)->first(function ($metric) use ($name) {
            return $metric['name'] === $name;
        })['values'][0]['value'] ?? 0;
    }
}
