<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Http;

trait ChecksCurrentPod
{
    /**
     * Check the pod metrics to adjust new connection allowance.
     *
     * @param  float  $memoryThresholdPercent
     * @param  int  $echoAppPort
     * @return void
     */
    protected function checkPod(float $memoryThresholdPercent, int $echoAppPort): void
    {
        /** @var \App\Commands\WatchNetworkCommand $this */
        $usedMemoryPercent = $this->getMemoryUsagePercent($echoAppPort);
        $dateTime = now()->toDateTimeString();

        $this->line("[{$dateTime}] Current memory usage is {$usedMemoryPercent}%. Checking...", null, 'v');

        $this->pod->ensureItHasDefaultLabel();

        if ($usedMemoryPercent >= $memoryThresholdPercent) {
            if ($this->pod->acceptsConnections()) {
                $this->info("[{$dateTime}] Pod now rejects connections.");
                $this->info("[{$dateTime}] Echo container uses {$usedMemoryPercent}%, threshold is {$memoryThresholdPercent}%");

                $this->rejectNewConnections($usedMemoryPercent, $memoryThresholdPercent);
            }
        } else {
            if ($this->pod->rejectsConnections()) {
                $this->info("[{$dateTime}] Pod now accepts connections.");
                $this->info("[{$dateTime}] Echo container uses {$usedMemoryPercent}%, threshold is {$memoryThresholdPercent}%");

                $this->acceptNewConnections($usedMemoryPercent, $memoryThresholdPercent);
            }
        }
    }

    /**
     * Mark the Pod as rejecting new connections.
     *
     * @param  float  $usedMemoryPercent
     * @param  float  $memoryThresholdPercent
     * @return void
     */
    protected function rejectNewConnections(float $usedMemoryPercent, float $memoryThresholdPercent): void
    {
        /** @var \App\Commands\WatchNetworkCommand $this */
        $this->pod->rejectNewConnections();

        $now = now()->toIso8601String();

        $this->pod->newEvent()
            ->setMessage("Rejecting new connections. Echo container uses {$usedMemoryPercent}%, threshold is {$memoryThresholdPercent}%")
            ->setReason('OverThreshold')
            ->setType('Warning')
            ->setFirstTimestamp($now)
            ->setLastTimestamp($now)
            ->create();
    }

    /**
     * Mark the Pod as accepting new connections.
     *
     * @param  float  $usedMemoryPercent
     * @param  float  $memoryThresholdPercent
     * @return void
     */
    protected function acceptNewConnections(float $usedMemoryPercent, float $memoryThresholdPercent): void
    {
        /** @var \App\Commands\WatchNetworkCommand $this */
        $this->pod->acceptNewConnections();

        $now = now()->toIso8601String();

        $this->pod->newEvent()
            ->setMessage("Accepting new connections. Echo container uses {$usedMemoryPercent}%, threshold is {$memoryThresholdPercent}%")
            ->setReason('BelowThreshold')
            ->setType('Normal')
            ->setFirstTimestamp($now)
            ->setLastTimestamp($now)
            ->create();
    }

    /**
     * Get the pod metrics from the Usage API.
     *
     * @param  int  $echoAppPort
     * @return array
     */
    protected function getUsage(int $echoAppPort): array
    {
        return Http::get("http://localhost:{$echoAppPort}/usage")->json();
    }

    /**
     * Get the percent of used memory from the Usage API.
     *
     * @param  int  $echoAppPort
     * @return float
     */
    protected function getEchoServerMemoryUsage(int $echoAppPort): float
    {
        $usage = $this->getUsage($echoAppPort);

        return $usage['memory']['percent'] ?? 0.00;
    }
}
