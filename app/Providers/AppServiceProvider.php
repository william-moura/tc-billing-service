<?php

namespace App\Providers;

use App\Infrastructure\Messaging\RabbitMQService;
use App\Application\Services\MessagePublisherInterface;
use App\Infrastructure\Messaging\Publishers\BillingRabbitMQPublisher;
use App\Domain\Repositories\InvoiceRepositoryInterface;
use App\Infrastructure\Repositories\InvoiceMongoRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RabbitMQService::class, fn () => RabbitMQService::fromEnv());
        $this->app->bind(MessagePublisherInterface::class, BillingRabbitMQPublisher::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceMongoRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
