<?php

namespace App\Commands;

use App\Concerns\ChecksCurrentPod;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use RenokiCo\LaravelK8s\LaravelK8sFacade as LaravelK8s;
use RenokiCo\PhpK8s\Kinds\K8sPod;
use Symfony\Component\Console\Command\SignalableCommandInterface;

class WatchNetworkCommand extends Command implements SignalableCommandInterface
{
    use ChecksCurrentPod;

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
     * The current pod the instance is running into.
     *
     * @var K8sPod
     */
    protected K8sPod $pod;

    /**
     * Initialize the command.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->registerPodMacros();
    }

    /**
     * Returns the list of signals to subscribe.
     *
     * @return array
     */
    public function getSubscribedSignals(): array
    {
        return [
            SIGINT,
            SIGTERM,
        ];
    }

    /**
     * The method will be called when the application is signaled.
     *
     * @param  int  $signal
     * @return void
     */
    public function handleSignal(int $signal): void
    {
        // Simply just mark the pod as rejecting the new connections while it's terminating.
        // This way, the Echo Server will close all existing connections internally,
        // but the Network Watcher will also mark the pod as not being able to receive new connections for
        // the sole purpose of redirecting the traffic to other pods.
        $this->pod->rejectNewConnections();
    }

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
        $echoAppPort = env('ECHO_APP_PORT') ?: $this->option('echo-app-port');
        $memoryThreshold = env('MEMORY_PERCENT') ?: $this->option('memory-percent');
        $interval = env('CHECKING_INTERVAL') ?: $this->option('interval');
        $test = is_bool(env('TEST_MODE')) ? env('TEST_MODE') : $this->option('test');

        $this->line("Namespace: {$podNamespace}");
        $this->line("Pod name: {$podName}");
        $this->line("Echo port: {$echoAppPort}");
        $this->line("Memory threshold: {$memoryThreshold}%");
        $this->line("Monitoring interval: {$interval}s");

        $this->setPod(
            LaravelK8s::getPodByName($podName, $podNamespace)
        );

        if (! $this->pod) {
            throw new Exception("Pod {$podNamespace}/{$podName} not found.");
        }

        while (true) {
            $this->checkPod($memoryThreshold, $echoAppPort);

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
        K8sPod::macro('getLabel', function (string $name, $default = null) {
            /** @var K8sPod $this */
            return $this->getLabels()[$name] ?? $default;
        });

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

        K8sPod::macro('ensureItHasDefaultLabel', function () {
            /** @var K8sPod $this */
            if (! $this->getLabel('echo.soketi.app/accepts-new-connections')) {
                $this->acceptNewConnections();
            }
        });
    }

    /**
     * Set the pod.
     *
     * @param  \RenokiCo\PhpK8s\Kinds\K8sPod  $pod
     * @return self
     */
    public function setPod(K8sPod $pod)
    {
        $this->pod = $pod;

        return $this;
    }
}
