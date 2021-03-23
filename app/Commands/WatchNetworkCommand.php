<?php

namespace App\Commands;

use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use RenokiCo\LaravelK8s\LaravelK8sFacade as LaravelK8s;
use RenokiCo\PhpK8s\Kinds\K8sPod;

class WatchNetworkCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'network:watch
        {--pod-namespace=default : The Pod namespace. Defaults to the current Pod namespace.}
        {--pod-name=some-pod : The Pod name to watch. Defaults to the current Pod name.}
        {--probes-token=probes-token : The Probes API token used to update the network probing status.}
        {--echo-app-port=6001 : The Echo App socket port.}
        {--memory-percent=75 : The threshold at which new connections close for a specific server.}
        {--interval=1 : The interval in seconds between each checks.}
        {--test : Run only one loop for testing.}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Run the Network watcher controller for the Echo app.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('Starting the watcher...');

        $podNamespace = env('POD_NAMESPACE') ?: $this->option('pod-namespace');
        $podName = env('POD_NAME') ?: $this->option('pod-name');
        $probesToken = env('PROBES_TOKEN') ?: $this->option('probes-token');
        $echoAppPort = env('ECHO_APP_PORT') ?: $this->option('echo-app-port');
        $memoryThreshold = env('MEMORY_PERCENT') ?: $this->option('memory-percent');
        $interval = env('CHECKING_INTERVAL') ?: $this->option('interval');
        $test = is_bool(env('TEST_MODE')) ? env('TEST_MODE') : $this->option('test');

        $this->line("Namespace: {$podNamespace}");
        $this->line("Pod name: {$podName}");
        $this->line("Echo port: {$echoAppPort}");
        $this->line("Memory threshold: {$memoryThreshold}%");
        $this->line("Monitoring interval: {$interval}s");

        while (true) {
            $pod = LaravelK8s::getPodByName($podName, $podNamespace);

            if (! $pod) {
                throw new Exception("Pod {$podNamespace}/{$podName} not found.");
            }

            $this->checkPod($pod, $memoryThreshold, $probesToken, $echoAppPort);

            sleep($interval);

            if ($test) {
                break;
            }
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    /**
     * Check the pod metrics to adjust new connection allowance.
     *
     * @param  \RenokiCo\PhpK8s\K8sResources\K8sPod  $pod
     * @param  int  $memoryThreshold
     * @param  string  $probesToken
     * @param  int  $echoAppPort
     * @return void
     */
    protected function checkPod(K8sPod $pod, int $memoryThreshold, string $probesToken, int $echoAppPort): void
    {
        if (! $pod->isRunning()) {
            return;
        }

        $memoryUsagePercentage = $this->getMemoryUsagePercentage($this->getPodMetrics($pod, $echoAppPort));
        $rejectsNewConnections = $pod->getLabel('echo.soketi.app/rejects-new-connections', 'no');
        $dateTime = now()->toDateTimeString();

        if ($memoryUsagePercentage >= $memoryThreshold) {
            if (! $rejectsNewConnections) {
                $this->info("[{$dateTime}] Pod now rejects connections.");
                $this->info("[{$dateTime}] Pod uses {$memoryUsagePercentage}, threshold is {$memoryThreshold}.");
            }

            $this->rejectNewConnections($pod, $probesToken, $echoAppPort);
        } else {
            if ($rejectsNewConnections) {
                $this->info("[{$dateTime}] Pod now accepts connections (memory usage: {$memoryUsagePercentage}% RAM used.");
                $this->info("[{$dateTime}] Pod uses {$memoryUsagePercentage}, threshold is {$memoryThreshold}.");
            }

            $this->acceptNewConnections($pod, $probesToken, $echoAppPort);
        }
    }

    protected function getPodMetrics(K8sPod $pod, int $echoAppPort): array
    {
        return Http::get("http://localhost:{$echoAppPort}/metrics?json=1")->json()['data'] ?? [];
    }

    protected function rejectNewConnections(K8sPod $pod, string $probesToken, int $echoAppPort): void
    {
        Http::post("http://localhost:{$echoAppPort}/probes/reject-new-connections?token={$probesToken}");

        $this->updatePodLabels($pod, ['echo.soketi.app/rejects-new-connections' => 'yes']);
    }

    protected function acceptNewConnections(K8sPod $pod, string $probesToken, int $echoAppPort): void
    {
        Http::post("http://localhost:{$echoAppPort}/probes/accept-new-connections?token={$probesToken}");

        $this->updatePodLabels($pod, ['echo.soketi.app/rejects-new-connections' => 'no']);
    }

    protected function updatePodLabels(K8sPod $pod, array $newLabels = []): K8sPod
    {
        $labels = array_merge($pod->getLabels(), $newLabels);

        $pod->setLabels($labels)->update();

        return $pod;
    }

    protected function getMemoryUsagePercentage(array $metrics): float
    {
        return $this->getUsedMemoryBytes($metrics) * 100 / $this->getTotalMemoryBytes($metrics);
    }

    protected function getTotalMemoryBytes(array $metrics): int
    {
        return $this->getMetricValue($metrics, 'echo_server_process_virtual_memory_bytes');
    }

    protected function getUsedMemoryBytes(array $metrics): int
    {
        return $this->getMetricValue($metrics, 'echo_server_nodejs_external_memory_bytes') +
            $this->getMetricValue($metrics, 'echo_server_process_resident_memory_bytes');
    }

    protected function getMetricValue(array $metrics, string $name): int
    {
        return collect($metrics)->where('name', $name)->first()['values'][0]['value'] ?? 0;
    }
}
