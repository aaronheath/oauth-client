<?php

namespace Heath\OauthClient;

use Illuminate\Support\ServiceProvider;

class OauthClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        
    }

    public function boot()
    {
        $this->publishes([__DIR__ . '/config.php' => config_path('oauth-client.php')]);
    }
}