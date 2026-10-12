<?php

namespace App\Providers;

use App\Services\SettingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
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
        try {
            // Guard: ensure database connection is ready and settings table exists
            if (! Schema::hasTable('settings')) {
                return;
            }

            // 1. Dynamic SMTP / Mail Configuration
            $mailHost = SettingService::get('mail_host');
            if (! empty($mailHost)) {
                Config::set('mail.mailers.smtp.host', $mailHost);
            }

            $mailPort = SettingService::get('mail_port');
            if (! empty($mailPort)) {
                Config::set('mail.mailers.smtp.port', (int) $mailPort);
            }

            $mailEncryption = SettingService::get('mail_encryption');
            if (! empty($mailEncryption)) {
                Config::set('mail.mailers.smtp.encryption', $mailEncryption === 'none' ? null : $mailEncryption);
            }

            $mailUsername = SettingService::get('mail_username');
            if (! empty($mailUsername)) {
                Config::set('mail.mailers.smtp.username', $mailUsername);
            }

            $mailPassword = SettingService::get('mail_password');
            if (! empty($mailPassword)) {
                Config::set('mail.mailers.smtp.password', $mailPassword);
            }

            $fromAddress = SettingService::get('mail_from_address');
            if (! empty($fromAddress)) {
                Config::set('mail.from.address', $fromAddress);
            }

            $fromName = SettingService::get('mail_from_name');
            if (! empty($fromName)) {
                Config::set('mail.from.name', $fromName);
            }

            // 2. Application Timezone
            $timezone = SettingService::get('timezone');
            if (! empty($timezone)) {
                Config::set('app.timezone', $timezone);
                date_default_timezone_set($timezone);
            }
        } catch (\Throwable) {
            // Graceful fallback to default configuration (.env)
        }
    }
}
