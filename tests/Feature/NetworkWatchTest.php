<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RenokiCo\LaravelK8s\LaravelK8sFacade as LaravelK8s;
use Tests\TestCase;

class NetworkWatchTest extends TestCase
{
    public function test_watch_pod_rejecting_connections()
    {
        $deployment = LaravelK8s::getDeploymentByName('echo-server-test');

        while(! $deployment->allPodsAreRunning()) {
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
            '--probes-token' => 'example-token',
            '--echo-app-port' => 6001,
            '--memory-percent' => 80,
            '--interval' => 1,
            '--test' => true,
        ]);

        Http::assertSent(function (Request $request) {
            return in_array($request->url(), [
                'http://localhost:6001/metrics?json=1',
                'http://localhost:6001/probes/reject-new-connections?token=new-api-token',
            ]);
        });

        $pod->refresh();

        $this->assertEquals('yes', $pod->getLabel('echo.soketi.app/rejects-new-connections'));
    }

    public function test_watch_pod_accepting_connections()
    {
        $deployment = LaravelK8s::getDeploymentByName('echo-server-test');

        while(! $deployment->allPodsAreRunning()) {
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
            '--probes-token' => 'example-token',
            '--echo-app-port' => 6001,
            '--memory-percent' => 90,
            '--interval' => 1,
            '--test' => true,
        ]);

        Http::assertSent(function (Request $request) {
            return in_array($request->url(), [
                'http://localhost:6001/metrics?json=1',
                'http://localhost:6001/probes/accept-new-connections?token=new-api-token',
            ]);
        });

        $pod->refresh();

        $this->assertEquals('no', $pod->getLabel('echo.soketi.app/rejects-new-connections'));
    }
}
