<?php

namespace ctbuh\Salesforce\OAuth;

use ctbuh\Salesforce\OAuth\Http\OAuthController;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class SalesforceServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/salesforce.php', 'salesforce');

        $this->app->singleton(AuthApi::class, function (Application $app) {

            /** @var Repository $config */
            $config = $app['config'];

            return new AuthApi($config->get('salesforce'));
        });

        $this->app->singleton(TokenStorage::class, function (Application $app) {
            return new TokenStorage($app['request'], $app['cookie']);
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/salesforce.php' => config_path('salesforce.php'),
        ]);

        /** @var Router $router */
        $router = $this->app['router'];

        $router->middleware('web')->prefix('oauth')->group(function (Router $router) {

            $router->get('/status', [OAuthController::class, 'status']);
            $router->get('/login', [OAuthController::class, 'login']);
            $router->post('/loginUsingToken', [OAuthController::class, 'loginUsingToken']);
            $router->get('/callback', [OAuthController::class, 'callback']);
            $router->get('/logout', [OAuthController::class, 'logout']);
        });
    }
}