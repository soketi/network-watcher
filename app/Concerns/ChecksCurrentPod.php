<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Http;

trait ChecksCurrentPod
{
    /**
     * Check the pod metrics to adjust new connection allowance.
     *
     * @param  float  $memoryThresholdPercent
     * @param  int  $serverPort
     * @return void
     */
    protected function checkPod(float $memoryThresholdPercent, int $serverPort): void
    {
        /** @var \App\Commands\WatchNetworkCommand $this */
        $usedMemoryPercent = $this->getServerMemoryUsagePercent($serverPort);
        $dateTime = now()->toDateTimeString();

        $this->line("[{$dateTime}] Current memory usage is {$usedMemoryPercent}%. Checking...", null, 'v');

        $this->pod->ensureItHasDefaultLabel();

        if ($usedMemoryPercent >= $memoryThresholdPercent) {
            if ($this->pod->acceptsConnections()) {
                $this->info("[{$dateTime}] Pod now rejects connections.");
                $this->info("[{$dateTime}] Server container uses {$usedMemoryPercent}%, threshold is {$memoryThresholdPercent}%");

                $this->rejectNewConnections($usedMemoryPercent, $memoryThresholdPercent);
            }
        } else {
            if ($this->pod->rejectsConnections()) {
                $this->info("[{$dateTime}] Pod now accepts connections.");
                $this->info("[{$dateTime}] Server container uses {$usedMemoryPercent}%, threshold is {$memoryThresholdPercent}%");

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
            ->setMessage("Rejecting new connections. Server container uses {$usedMemoryPercent}%, threshold is {$memoryThresholdPercent}%")
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
            ->setMessage("Accepting new connections. Server container uses {$usedMemoryPercent}%, threshold is {$memoryThresholdPercent}%")
            ->setReason('BelowThreshold')
            ->setType('Normal')
            ->setFirstTimestamp($now)
            ->setLastTimestamp($now)
            ->create();
    }

    /**
     * Get the pod metrics from the Usage API.
     *
     * @param  int  $serverPort
     * @return array
     */
    protected function getUsage(int $serverPort): array
    {
        return Http::get("http://localhost:{$serverPort}/usage")->json();
    }

    /**
     * Get the percent of used memory from the Usage API.
     *
     * @param  int  $serverPort
     * @return float
     */
    protected function getServerMemoryUsagePercent(int $serverPort): float
    {
        $usage = $this->getUsage($serverPort);

        return $usage['memory']['percent'] ?? 0.00;
    }
}
