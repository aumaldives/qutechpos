<?php

namespace Modules\Woocommerce\Providers;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Business;
use App\Utils\ModuleUtil;
use Illuminate\Console\Scheduling\Schedule;

class WoocommerceServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerScheduleCommands();

        //TODO: Need to be removed.
        view::composer('woocommerce::layouts.partials.sidebar', function ($view) {
            $module_util = new ModuleUtil();

            if (auth()->user()->can('superadmin')) {
                $__is_woo_enabled = $module_util->isModuleInstalled('Woocommerce');
            } else {
                $business_id = session()->get('user.business_id');
                $__is_woo_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'woocommerce_module', 'superadmin_package');
            }

            $view->with(compact('__is_woo_enabled'));
        });

        $this->registerScheduleCommands();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerCommands();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('woocommerce.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'woocommerce'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/woocommerce');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/woocommerce';
        }, config('view.paths')), [$sourcePath]), 'woocommerce');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/woocommerce');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'woocommerce');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'woocommerce');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (!app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            \Modules\Woocommerce\Console\RecoverSyncErrorsCommand::class,
            \Modules\Woocommerce\Console\SendDailySyncSummaryCommand::class,
            \Modules\Woocommerce\Console\ProcessSyncSchedulesCommand::class,
        ]);
    }

    public function registerScheduleCommands()
    {
        // Legacy scheduling system removed
        // All WooCommerce sync operations are now handled by the modern 
        // job-based system defined in app/Console/Kernel.php
        // This provides better performance, error handling, and monitoring
    }
}
