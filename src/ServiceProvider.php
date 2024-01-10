<?php

namespace dnj\AAA;

use dnj\AAA\Contracts\IType;
use dnj\AAA\Contracts\ITypeManager;
use dnj\AAA\Contracts\IUser;
use dnj\AAA\Contracts\IUserManager;
use dnj\AAA\Policies\TypePolicy;
use dnj\AAA\Policies\UserPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;

class ServiceProvider extends SupportServiceProvider
{
    /**
     * @var array<class-string,class-string>
     */
    protected $policies = [
        IUser::class => UserPolicy::class,
        IType::class => TypePolicy::class,
    ];

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/aaa.php', 'aaa');
        $this->app->singleton(ITypeManager::class, TypeManager::class);
        $this->app->singleton(IUserManager::class, UserManager::class);
        $this->app->singleton(GateHook::class);
        $this->registerUserProvider();
    }

    public function boot()
    {
        $this->integrateToGates();
        $this->registerRoutes();
        $this->registerPublishing();
        $this->registerPolicies();
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            if (config('aaa.migrations.enable')) {
                $this->loadMigrationsFrom([
                    __DIR__.'/../database/migrations' => database_path('migrations'),
                ], ['aaa', 'aaa-migrations']);
            }

            $this->publishes([
                __DIR__.'/../config/aaa.php' => config_path('aaa.php'),
            ], ['aaa', 'aaa-config', 'config']);

            $this->commands([
                Console\PolicyMakeCommand::class,
            ]);
        }
    }

    protected function registerUserProvider(): void
    {
        /*
         * @param \Illuminate\Auth\AuthManager $auth
         */
        Auth::resolved(function ($auth) {
            $auth->provider('aaa', function ($app) {
                return $app->make(UserProvider::class);
            });
        });
    }

    protected function integrateToGates(): void
    {
        $gateHook = $this->app->make(GateHook::class);
        Gate::after(\Closure::fromCallable($gateHook));
    }

    protected function registerRoutes(): void
    {
        if (!config('aaa.routes.enable')) {
            return;
        }
        $prefix = config('aaa.routes.prefix', 'api/users');
        Route::prefix($prefix)->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
