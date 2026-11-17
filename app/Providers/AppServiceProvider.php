<?php

namespace App\Providers;

use App\Application\Services\MessagePublisherInterface;
use App\Application\Services\PaymentGatewayInterface;
use App\Domain\Repositories\InvoiceRepositoryInterface;
use App\Infrastructure\Gateways\MercadoPagoGateway;
use App\Infrastructure\Messaging\Publishers\BillingRabbitMQPublisher;
use App\Infrastructure\Messaging\RabbitMQService;
use App\Infrastructure\Repositories\InvoiceMongoRepository;
use App\Presentation\Console\Commands\StartBillingConsumers;
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
        $this->app->bind(PaymentGatewayInterface::class, MercadoPagoGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                StartBillingConsumers::class,
            ]);
        }
    }
}
