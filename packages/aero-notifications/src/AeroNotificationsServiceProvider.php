<?php

declare(strict_types=1);

namespace Aero\Notifications;

use Aero\Notifications\Contracts\MailContextResolver;
use Aero\Notifications\Contracts\SmsContextResolver;
use Aero\Notifications\Services\Mail\MailService;
use Aero\Notifications\Services\Notification\NotificationLoggingService;
use Aero\Notifications\Services\Notification\NotificationPreferenceService;
use Aero\Notifications\Services\Pipeline\NotificationPipeline;
use Aero\Notifications\Services\Push\FcmNotificationService;
use Aero\Notifications\Services\Sms\SmsGatewayService;
use Aero\Notifications\Services\Sms\SmsService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class AeroNotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notifications.php', 'aero.notifications');
        $this->mergeConfigFrom(__DIR__.'/../config/sms-templates.php', 'aero.sms-templates');

        // Register contracts as singletons so downstream packages can bind implementations
        $this->app->singleton(MailContextResolver::class, function () {
            // Default: fallback to env/config. Core/Platform will override.
            return new class implements MailContextResolver {
                public function resolve(): array
                {
                    return [
                        'configured' => true,
                        'driver' => config('mail.default', 'smtp'),
                        'host' => config('mail.mailers.smtp.host', '127.0.0.1'),
                        'port' => (int) config('mail.mailers.smtp.port', 587),
                        'username' => config('mail.mailers.smtp.username', ''),
                        'password' => config('mail.mailers.smtp.password', ''),
                        'encryption' => config('mail.mailers.smtp.encryption', 'tls'),
                        'verify_peer' => false,
                        'from_address' => config('mail.from.address'),
                        'from_name' => config('mail.from.name', config('app.name')),
                    ];
                }
            };
        });

        $this->app->singleton(SmsContextResolver::class, function () {
            return new class implements SmsContextResolver {
                public function resolve(): array
                {
                    return [
                        'configured' => false,
                        'provider' => config('services.sms.default', 'log'),
                    ];
                }
            };
        });

        // Unified pipeline
        $this->app->singleton(NotificationPipeline::class);
        $this->app->singleton(NotificationLoggingService::class);
        $this->app->singleton(NotificationPreferenceService::class);

        // Channel services
        $this->app->singleton(MailService::class);
        $this->app->singleton(SmsService::class);
        $this->app->singleton(SmsGatewayService::class);

        // Push is only registered if Firebase is available
        if (class_exists(\Kreait\Firebase\Factory::class)) {
            $this->app->singleton(FcmNotificationService::class);
        }

        $this->registerHelpers();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerSchedule();
        }
    }

    protected function registerHelpers(): void
    {
        $helpers = __DIR__.'/helpers.php';
        if (file_exists($helpers)) {
            require_once $helpers;
        }
    }

    protected function registerCommands(): void
    {
        $this->commands([
            Console\Commands\RetryFailedNotifications::class,
            Console\Commands\TestMailConfiguration::class,
        ]);
    }

    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Retry failed notifications every 5 minutes
            $schedule->command('aero:notifications:retry-failed')
                ->everyFiveMinutes()
                ->withoutOverlapping();
        });
    }
}
