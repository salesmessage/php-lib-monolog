<?php

namespace Salesmessage\Monolog;

use Illuminate\Support\ServiceProvider;
use Illuminate\Log\Logger;
use Salesmessage\Monolog\SalesmessageLogger;

class LoggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (getenv('LOG_SALESMSG_FORMATTER_ENABLED')) {
            $salesmessageLogger = new SalesmessageLogger();
            $salesmessageLogger($this->app->get(Logger::class));
        }
    }
}
