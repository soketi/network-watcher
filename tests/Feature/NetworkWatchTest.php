<?php

namespace Tests\Feature;

use App\Commands\WatchNetworkCommand;
use Illuminate\Support\Facades\Http;
use RenokiCo\LaravelK8s\LaravelK8sFacade as LaravelK8s;
use Symfony\Component\Console\Input\InputOption;
use Tests\TestCase;

class NetworkWatchTest extends TestCase
{
    public function test_watch_pod_rejecting_connections()
    {
        /** @var \RenokiCo\PhpK8s\Kinds\K8sDeployment $deployment */
        $deployment = LaravelK8s::getDeploymentByName('echo-server-test');

        while (! $deployment->allPodsAreRunning()) {
            echo "Waiting for {$deployment->getName()} deployment to have pods running...";
            sleep(1);
            $deployment->refresh();
        }

        /** @var \RenokiCo\PhpK8s\Kinds\K8sPod $pod */
        $pod = $deployment->getPods()->first();

        Http::fakeSequence()->push([
            'data' => [
                ['name' => 'echo_server_process_virtual_memory_bytes', 'values' => [['value' => 104857600]]], // 100 MB
                ['name' => 'echo_server_process_resident_memory_bytes', 'values' => [['value' => 83886080]]], // 80 MB @ 80% usage
                ['name' => 'echo_server_nodejs_external_memory_bytes', 'values' => [['value' => 0]]],
            ],
        ]);

        $this->artisan('network:watch', [
            '--pod-namespace' => 'default',
            '--pod-name' => $pod->getName(),
            '--echo-app-port' => 6001,
            '--memory-percent' => 80,
            '--interval' => 1,
            '--test' => true,
        ]);

        $pod->refresh();

        $this->assertEquals('no', $pod->getLabel('echo.soketi.app/accepts-new-connections'));
    }

    public function test_watch_pod_accepting_connections()
    {
        /** @var \RenokiCo\PhpK8s\Kinds\K8sDeployment $deployment */
        $deployment = LaravelK8s::getDeploymentByName('echo-server-test');

        while (! $deployment->allPodsAreRunning()) {
            echo "Waiting for {$deployment->getName()} deployment to have pods running...";
            sleep(1);
            $deployment->refresh();
        }

        /** @var \RenokiCo\PhpK8s\Kinds\K8sPod $pod */
        $pod = $deployment->getPods()->first();

        Http::fakeSequence()->push([
            'data' => [
                ['name' => 'echo_server_process_virtual_memory_bytes', 'values' => [['value' => 104857600]]], // 100 MB
                ['name' => 'echo_server_process_resident_memory_bytes', 'values' => [['value' => 83886080]]], // 80 MB @ 80% usage
                ['name' => 'echo_server_nodejs_external_memory_bytes', 'values' => [['value' => 0]]],
            ],
        ]);

        $this->artisan('network:watch', [
            '--pod-namespace' => 'default',
            '--pod-name' => $pod->getName(),
            '--echo-app-port' => 6001,
            '--memory-percent' => 90,
            '--interval' => 1,
            '--test' => true,
        ]);

        $pod->refresh();

        $this->assertEquals('yes', $pod->getLabel('echo.soketi.app/accepts-new-connections'));
    }

    public function test_signaling_should_incapacitate_the_pod()
    {
        /** @var \RenokiCo\PhpK8s\Kinds\K8sDeployment $deployment */
        $deployment = LaravelK8s::getDeploymentByName('echo-server-test');

        while (! $deployment->allPodsAreRunning()) {
            echo "Waiting for {$deployment->getName()} deployment to have pods running...";
            sleep(1);
            $deployment->refresh();
        }

        /** @var \RenokiCo\PhpK8s\Kinds\K8sPod $pod */
        $pod = $deployment->getPods()->first();

        $labels = array_merge($pod->getLabels(), [
            'echo.soketi.app/accepts-new-connections' => 'yes',
        ]);

        $pod->refresh()->setLabels($labels)->update();

        $this->assertEquals('yes', $pod->getLabel('echo.soketi.app/accepts-new-connections'));

        /** @var WatchNetworkCommand $command */
        $command = app(WatchNetworkCommand::class);

        $command->getDefinition()->setOptions([
            new InputOption('pod-name', null, 4, $pod->getName()),
        ]);

        $command->setPod($pod)->handleSignal(0);

        $pod->refresh();

        $this->assertEquals('no', $pod->getLabel('echo.soketi.app/accepts-new-connections'));
    }
}
