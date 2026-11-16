<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicMapSubdomainTest extends TestCase
{
    public function test_map_root_redirects_to_feldan(): void
    {
        $host = config('map.domain');

        $this->withServerVariables(['HTTP_HOST' => $host])
            ->get('http://'.$host.'/')
            ->assertRedirect(route('web.feldan'));
    }

    public function test_map_alias_redirects_to_harita(): void
    {
        $aliases = config('map.alias_domains', []);
        if ($aliases === []) {
            $this->markTestSkipped('map alias not configured');
        }

        $host = $aliases[0];
        $canonical = config('map.domain');

        $this->withServerVariables(['HTTP_HOST' => $host])
            ->get('http://'.$host.'/feldan')
            ->assertRedirect('http://'.$canonical.'/feldan');
    }

    public function test_feldan_is_served_on_map_subdomain(): void
    {
        $host = config('map.domain');

        $this->withServerVariables(['HTTP_HOST' => $host])
            ->get('http://'.$host.'/feldan')
            ->assertOk();
    }

    public function test_main_domain_feldan_redirects_to_map_subdomain(): void
    {
        $this->get('/feldan')
            ->assertRedirect(route('web.feldan'));
    }
}
