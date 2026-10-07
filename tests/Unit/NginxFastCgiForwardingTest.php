<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NginxFastCgiForwardingTest extends TestCase
{
    public function test_fastcgi_forwards_the_external_host_and_port_to_php(): void
    {
        $configuration = (string) file_get_contents(base_path('docker/nginx/default.conf'));

        $this->assertStringContainsString('fastcgi_param HTTP_HOST $http_host;', $configuration);
    }
}
