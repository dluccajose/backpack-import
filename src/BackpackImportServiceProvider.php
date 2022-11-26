<?php

namespace Dlucca\BackpackImport;

use Illuminate\Support\ServiceProvider;

class BackpackImportServiceProvider extends ServiceProvider {
    
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'backpack-import');
    }
}
