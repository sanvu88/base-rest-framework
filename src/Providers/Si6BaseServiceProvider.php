<?php

namespace Si6\Base\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Si6\Base\Http\Middleware\Authenticate;
use Si6\Base\Http\Middleware\Authorize;
use Si6\Base\Http\Middleware\BeforeResponse;
use Si6\Base\Http\Middleware\CheckForMaintenanceMode;
use Si6\Base\Http\Middleware\ClientPlatform;
use Si6\Base\Http\Middleware\LanguageCode;
use Si6\Base\Http\Middleware\TrimStrings;
use Si6\Base\Http\Middleware\Unacceptable;
use Si6\Base\Http\Middleware\Unsupported;
use Si6\Base\Http\Middleware\Versioning;
use Si6\Base\Infrastructure\MicroserviceDispatcher;
use Si6\Base\Infrastructure\ScheduleMicroserviceDispatcher;

class Si6BaseServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function register()
    {
        $this->registerGlobalMiddleware();
        $this->registerMicroservicesDependence();
        $this->registerAuthProvider();
        $this->registerLogProvider();
        $this->registerStorageProvider();
        $this->registerExternalService();
        $this->registerBettingService();
        $this->mergeConfigFrom(__DIR__ . '/../../config/time.php', 'time');
        $this->app->bind(MicroserviceDispatcher::class, ScheduleMicroserviceDispatcher::class);
    }

    /**
     * @throws BindingResolutionException
     */
    protected function registerGlobalMiddleware()
    {
        $kernel = $this->app->make(Kernel::class);

        $kernel->prependMiddleware(TrimStrings::class);
        $kernel->prependMiddleware(CheckForMaintenanceMode::class);
        $kernel->prependMiddleware(ConvertEmptyStringsToNull::class);

        $kernel->prependMiddleware(LanguageCode::class);
        $kernel->prependMiddleware(ClientPlatform::class);
        $kernel->prependMiddleware(Unsupported::class);
        $kernel->prependMiddleware(Unacceptable::class);
        $kernel->prependMiddleware(BeforeResponse::class);
    }

    protected function registerMicroservicesDependence()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/microservices.php', 'microservices');
        $this->app->bind(ClientInterface::class, function ($app, $options) {
            return new Client($options);
        });
    }

    protected function registerAuthProvider()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/auth.php', 'auth');
        $this->app->register(AuthServiceProvider::class);
    }

    protected function registerLogProvider()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/logging.php', 'logging');
    }

    protected function registerStorageProvider()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/filesystems.php', 'filesystems');
    }

    protected function registerExternalService()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/external.php', 'external');
    }

    public function registerBettingService()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/vote.php', 'vote');
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot()
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('versioning', Versioning::class);
        $router->aliasMiddleware('auth', Authenticate::class);
        $router->aliasMiddleware('authorize', Authorize::class);
    }
}
