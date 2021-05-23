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

        $this->registerPodMacros();

        $podNamespace = env('POD_NAMESPACE') ?: $this->option('pod-namespace');
        $podName = env('POD_NAME') ?: $this->option('pod-name');
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

            $this->checkPod($pod, $memoryThreshold, $echoAppPort);

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
     * Register macros for the K8sPod instance.
     *
     * @return void
     */
    protected function registerPodMacros(): void
    {
        K8sPod::macro('acceptsConnections', function () {
            /** @var K8sPod $this */
            return $this->getLabel('echo.soketi.app/accepts-new-connections', 'yes') === 'yes';
        });

        K8sPod::macro('rejectsConnections', function () {
            /** @var K8sPod $this */
            return $this->getLabel('echo.soketi.app/accepts-new-connections', 'yes') === 'no';
        });

        K8sPod::macro('acceptNewConnections', function () {
            /** @var K8sPod $this */

            $labels = array_merge($this->getLabels(), [
                'echo.soketi.app/accepts-new-connections' => 'yes',
            ]);

            $this->refresh()->setLabels($labels)->update();

            return true;
        });

        K8sPod::macro('rejectNewConnections', function () {
            /** @var K8sPod $this */

            $labels = array_merge($this->getLabels(), [
                'echo.soketi.app/accepts-new-connections' => 'no',
            ]);

            $this->refresh()->setLabels($labels)->update();

            return true;
        });
    }

    /**
     * Check the pod metrics to adjust new connection allowance.
     *
     * @param  \RenokiCo\PhpK8s\K8sResources\K8sPod  $pod
     * @param  int  $memoryThreshold
     * @param  int  $echoAppPort
     * @return void
     */
    protected function checkPod(K8sPod $pod, int $memoryThreshold, int $echoAppPort): void
    {
        $memoryUsagePercentage = $this->getMemoryUsagePercentage($this->getEchoServerMetrics($echoAppPort));
        $dateTime = now()->toDateTimeString();

        $this->line("[{$dateTime}] Current memory usage is {$memoryUsagePercentage}%. Checking...", null, 'v');

        if ($memoryUsagePercentage >= $memoryThreshold) {
            if ($pod->acceptsConnections()) {
                $this->info("[{$dateTime}] Pod now rejects connections.");
                $this->info("[{$dateTime}] Echo container uses {$memoryUsagePercentage}%, threshold is {$memoryThreshold}%");

                $pod->rejectNewConnections();
            }
        } else {
            if ($pod->rejectsConnections()) {
                $this->info("[{$dateTime}] Pod now accepts connections.");
                $this->info("[{$dateTime}] Echo container uses {$memoryUsagePercentage}%, threshold is {$memoryThreshold}%");

                $pod->acceptNewConnections();
            }
        }
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
