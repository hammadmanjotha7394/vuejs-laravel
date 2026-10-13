<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Services\EmailService;
use App\Services\SettingService;
use Illuminate\Console\Command;

class SendAbandonedCartEmailsCommand extends Command
{
    protected $signature = 'shop:abandoned-carts';

    protected $description = 'Send a single 24-hour abandoned cart reminder email with an unsubscribe link.';

    public function handle(): int
    {
        $carts = Cart::with(['user', 'items.product', 'items.variant'])
            ->whereNull('abandoned_reminder_sent_at')
            ->where('updated_at', '<=', now()->subHours(24))
            ->where('updated_at', '>=', now()->subDays(7))
            ->whereHas('items')
            ->get();

        $sentCount = 0;
        $siteUrl = rtrim((string) config('app.url', 'http://localhost'), '/');
        $symbol = (string) SettingService::get('currency_symbol', '$');
        $brandColor = (string) SettingService::get('brand_color', '#2F5D50');

        foreach ($carts as $cart) {
            $email = $cart->guest_email ?: $cart->user?->email;
            if (! $email) {
                continue;
            }

            $name = $cart->user?->name ?: 'there';
            $rows = '';
            foreach ($cart->items as $item) {
                if (! $item->product) {
                    continue;
                }
                $unitPrice = (float) ($item->variant?->price ?? $item->product->base_price);
                $lineTotal = $symbol.number_format($unitPrice * (int) $item->quantity, 2);
                $prodName = e($item->product->name);
                $rows .= "<tr><td style='padding:10px 12px;border-bottom:1px solid #EAE8E3;'>{$prodName} &times; {$item->quantity}</td><td align='right' style='padding:10px 12px;border-bottom:1px solid #EAE8E3;font-weight:600;'>{$lineTotal}</td></tr>";
            }

            $unsubscribeUrl = $siteUrl.'/shop?unsubscribe='.urlencode($email);
            $html = <<<HTML
            <h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Did you forget something, {$name}?</h2>
            <p style="margin:0 0 16px;color:#374151;font-size:15px;">We saved the items in your shopping bag for you. Quantities are limited, so complete your order whenever you are ready:</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #EAE8E3;border-radius:8px;border-collapse:collapse;margin:16px 0;">
              {$rows}
            </table>
            <p style="margin:24px 0;"><a href="{$siteUrl}/cart" style="display:inline-block;background-color:{$brandColor};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;">Return to Your Bag</a></p>
            <p style="margin:16px 0 0;font-size:12px;color:#6B7280;">Prefer not to receive cart reminders? <a href="{$unsubscribeUrl}" style="color:#6B7280;text-decoration:underline;">Unsubscribe here</a>.</p>
            HTML;

            EmailService::sendTemplate(
                templateKey: 'abandoned_cart',
                to: $email,
                variables: ['customer_name' => $name],
                fallbackSubject: 'Your shopping bag is waiting for you — {{store_name}}',
                fallbackHtml: $html
            );

            $cart->update(['abandoned_reminder_sent_at' => now()]);
            $sentCount++;
        }

        $this->info("Sent {$sentCount} abandoned cart reminder email(s).");

        return self::SUCCESS;
    }
}
