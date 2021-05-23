<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use RenokiCo\LaravelK8s\LaravelK8sFacade as LaravelK8s;
use Tests\TestCase;

class NetworkWatchTest extends TestCase
{
    public function test_watch_pod_rejecting_connections()
    {
        $deployment = LaravelK8s::getDeploymentByName('echo-server-test');

        while (! $deployment->allPodsAreRunning()) {
            echo "Waiting for {$deployment->getName()} deployment to have pods running...";
            sleep(1);
            $deployment->refresh();
        }

        $pod = $deployment->getPods()->first();

        Http::fakeSequence()
            ->push([
                'data' => [
                    ['name' => 'echo_server_process_virtual_memory_bytes', 'values' => [['value' => 104857600]]], // 100 MB
                    ['name' => 'echo_server_process_resident_memory_bytes', 'values' => [['value' => 83886080]]], // 80 MB @ 80% usage
                    ['name' => 'echo_server_nodejs_external_memory_bytes', 'values' => [['value' => 0]]],
                ],
            ])
            ->push(['acknowledged' => true]);

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
        $deployment = LaravelK8s::getDeploymentByName('echo-server-test');

        while (! $deployment->allPodsAreRunning()) {
            echo "Waiting for {$deployment->getName()} deployment to have pods running...";
            sleep(1);
            $deployment->refresh();
        }

        $pod = $deployment->getPods()->first();

        Http::fakeSequence()
            ->push([
                'data' => [
                    ['name' => 'echo_server_process_virtual_memory_bytes', 'values' => [['value' => 104857600]]], // 100 MB
                    ['name' => 'echo_server_process_resident_memory_bytes', 'values' => [['value' => 83886080]]], // 80 MB @ 80% usage
                    ['name' => 'echo_server_nodejs_external_memory_bytes', 'values' => [['value' => 0]]],
                ],
            ])
            ->push(['acknowledged' => true]);

        $this->artisan('network:watch', [
            '--pod-namespace' => 'default',
            '--pod-name' => $pod->getName(),
            '--echo-app-port' => 6001,
            '--memory-percent' => 90,
            '--interval' => 1,
            '--test' => true,
        ]);

        $pod->refresh();

        $this->assertEquals('yes', $pod->getLabel('accepts-new-connections'));
    }
}
