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
        {--server-port=6001 : The Server port.}
        {--memory-percent=75 : The threshold at which new connections close for a specific server.}
        {--interval=1 : The interval in seconds between each checks.}
        {--kubernetes-label=pws.soketi.app/accepts-new-connections : The label to attach to the Kubernetes services.}
        {--test : Run only one loop for testing.}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Run the Network watcher controller for the pWS server.';

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
        // This way, the pWS Server will close all existing connections internally,
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

        $this->registerPodMacros();

        $podNamespace = env('POD_NAMESPACE') ?: $this->option('pod-namespace');
        $podName = env('POD_NAME') ?: $this->option('pod-name');
        $serverPort = env('SERVER_PORT') ?: $this->option('server-port');
        $memoryThresholdPercent = env('MEMORY_PERCENT') ?: $this->option('memory-percent');
        $interval = env('CHECKING_INTERVAL') ?: $this->option('interval');
        $test = is_bool(env('TEST_MODE')) ? env('TEST_MODE') : $this->option('test');

        $this->line("Namespace: {$podNamespace}");
        $this->line("Pod name: {$podName}");
        $this->line("Server port: {$serverPort}");
        $this->line("Memory threshold: {$memoryThresholdPercent}%");
        $this->line("Monitoring interval: {$interval}s");

        $this->setPod(
            LaravelK8s::getPodByName($podName, $podNamespace)
        );

        if (! $this->pod) {
            throw new Exception("Pod {$podNamespace}/{$podName} not found.");
        }

        while (true) {
            $this->checkPod($memoryThresholdPercent, $serverPort);

            sleep($interval);

            if ($test) {
                break;
            }
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
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
        $kubernetesLabel = env('KUBERNETES_LABEL') ?: $this->option('kubernetes-label');

        K8sPod::macro('getLabel', function (string $name, $default = null) {
            /** @var K8sPod $this */
            return $this->getLabels()[$name] ?? $default;
        });

        K8sPod::macro('acceptsConnections', function () use ($kubernetesLabel) {
            /** @var K8sPod $this */
            return $this->getLabel($kubernetesLabel, 'yes') === 'yes';
        });

        K8sPod::macro('rejectsConnections', function () use ($kubernetesLabel) {
            /** @var K8sPod $this */
            return $this->getLabel($kubernetesLabel, 'yes') === 'no';
        });

        K8sPod::macro('acceptNewConnections', function () use ($kubernetesLabel) {
            /** @var K8sPod $this */
            $labels = array_merge($this->getLabels(), [
                $kubernetesLabel => 'yes',
            ]);

            $this->refresh()->setLabels($labels)->update();

            return true;
        });

        K8sPod::macro('rejectNewConnections', function () use ($kubernetesLabel) {
            /** @var K8sPod $this */
            $labels = array_merge($this->getLabels(), [
                $kubernetesLabel => 'no',
            ]);

            $this->refresh()->setLabels($labels)->update();

            return true;
        });

        K8sPod::macro('ensureItHasDefaultLabel', function () use ($kubernetesLabel) {
            /** @var K8sPod $this */
            if (! $this->getLabel($kubernetesLabel)) {
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
