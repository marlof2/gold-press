<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_renderizar_login_nos_testes_nao_usa_cache_de_views_da_aplicacao(): void
    {
        $this->get('/login')->assertOk();

        $compiledPath = app('blade.compiler')->getCompiledPath(resource_path('views/layout.blade.php'));
        $this->assertFileExists($compiledPath);
        $this->assertNotSame(realpath(storage_path('framework/views')), realpath(dirname($compiledPath)));
        $this->assertStringStartsWith(sys_get_temp_dir().'/gold-press-tests-', $compiledPath);
    }
}
