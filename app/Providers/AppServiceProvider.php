<?php

namespace App\Providers;

use App\Observers\AuditableModelObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.criserve');
        Paginator::defaultSimpleView('vendor.pagination.criserve');

        foreach (File::files(app_path('Models')) as $file) {
            $class = 'App\\Models\\'.$file->getFilenameWithoutExtension();

            if (! class_exists($class) || $class === \App\Models\AuditLog::class) {
                continue;
            }

            if (is_subclass_of($class, Model::class)) {
                $class::observe(AuditableModelObserver::class);
            }
        }
    }
}
