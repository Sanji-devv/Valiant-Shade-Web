<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicMapSubdomainTest extends TestCase
{
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
