<?php
namespace Tests;

use Bkwld\Decoy\Models\Admin;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class TestCase extends LaravelTestCase
{
    use DatabaseMigrations,
        MockeryPHPUnitIntegration; // Increments assertion count

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../example/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Authenticate an admin
     *
     * @return void
     */
    protected function auth()
    {
        $this->actingAs(Admin::create([
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'test@domain.com',
            'password' => 'pass',
        ]), 'decoy');
    }

    /**
     * Helper for creating the header that Request::ajax() looks for
     *
     * @return array
     */
    protected function ajaxHeader()
    {
        return [ 'X-Requested-With' => 'XMLHttpRequest' ];
    }
}