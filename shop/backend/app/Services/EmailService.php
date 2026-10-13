<?php

namespace App\Services;

use App\Mail\DynamicTemplateMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EmailService
{
    /**
     * Alias map so both naming conventions resolve to the canonical template key.
     */
    public const KEY_ALIASES = [
        'order_confirmation' => 'order_confirmed',
        'new_order_admin' => 'admin_new_order',
        'refund_issued' => 'order_refunded',
        'admin_low_stock' => 'low_stock_alert',
    ];

    /**
     * Apply SMTP settings saved in the database at runtime.
     * Typical Hostinger values: smtp.hostinger.com, port 465 (ssl) or 587 (tls).
     */
    public static function applyRuntimeSmtpConfig(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $host = SettingService::get('mail_host');
            $port = SettingService::get('mail_port');
            $encryption = SettingService::get('mail_encryption');
            $username = SettingService::get('mail_username');
            $password = SettingService::get('mail_password');
            $fromName = SettingService::get('mail_from_name') ?: SettingService::get('site_name', 'Boutique');
            $fromAddress = SettingService::get('mail_from_address') ?: SettingService::get('support_email', 'orders@boutique.test');

            if (! empty($host)) {
                $scheme = match (strtolower((string) $encryption)) {
                    'ssl' => 'smtps',
                    'tls' => 'smtp',
                    default => null,
                };

                config([
                    'mail.mailers.smtp.host' => $host,
                    'mail.mailers.smtp.port' => (int) ($port ?: 465),
                    'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
                    'mail.mailers.smtp.scheme' => $scheme,
                    'mail.mailers.smtp.username' => $username,
                    'mail.mailers.smtp.password' => $password,
                ]);

                if (! app()->environment('testing')) {
                    config(['mail.default' => 'smtp']);
                }
            }

            if (! empty($fromAddress)) {
                config([
                    'mail.from.address' => $fromAddress,
                    'mail.from.name' => $fromName ?: 'Boutique',
                ]);
            }
        } catch (Throwable) {
            // Ignore during early boot before migrations run
        }
    }

    /**
     * Canonical list of all Customer and Staff email templates with metadata and default HTML.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultTemplates(): array
    {
        return [
            // Customer Emails
            [
                'key' => 'welcome',
                'name' => 'Welcome Email',
                'audience' => 'customer',
                'description' => 'Sent to new customers when they create an account.',
                'placeholders' => ['{{customer_name}}', '{{store_name}}', '{{action_url}}', '{{support_email}}'],
                'subject' => 'Welcome to {{store_name}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Welcome to {{store_name}}, {{customer_name}}</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">Thank you for creating an account with us. You can now track orders, save your favorite pieces to your wishlist, and enjoy faster checkout.</p>
<p style="margin:24px 0;"><a href="{{action_url}}" style="display:inline-block;background-color:{{brand_color}};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;">Explore Collection</a></p>',
            ],
            [
                'key' => 'email_verification',
                'name' => 'Email Verification',
                'audience' => 'customer',
                'description' => 'Sent to verify a customer email address.',
                'placeholders' => ['{{customer_name}}', '{{verification_link}}', '{{store_name}}'],
                'subject' => 'Verify your email address — {{store_name}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Verify your email address</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">Hi {{customer_name}}, please confirm your email address by clicking the button below.</p>
<p style="margin:24px 0;"><a href="{{verification_link}}" style="display:inline-block;background-color:{{brand_color}};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;">Verify Email Address</a></p>',
            ],
            [
                'key' => 'password_reset',
                'name' => 'Password Reset',
                'audience' => 'customer',
                'description' => 'Sent when a user requests a password reset link.',
                'placeholders' => ['{{customer_name}}', '{{reset_link}}', '{{store_name}}'],
                'subject' => 'Reset Your {{store_name}} Password',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Password Reset Request</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">Hi {{customer_name}}, we received a request to reset your password. Click the button below to choose a new password:</p>
<p style="margin:24px 0;"><a href="{{reset_link}}" style="display:inline-block;background-color:{{brand_color}};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;">Reset Password</a></p>
<p style="margin:0;color:#6B7280;font-size:13px;">If you did not request a password reset, you can safely ignore this email.</p>',
            ],
            [
                'key' => 'order_confirmed',
                'name' => 'Order Confirmation',
                'audience' => 'customer',
                'description' => 'Sent immediately after payment is confirmed.',
                'placeholders' => ['{{customer_name}}', '{{order_number}}', '{{order_total}}', '{{items_table}}', '{{shipping_address}}', '{{action_url}}'],
                'subject' => 'Order Confirmed — {{order_number}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Thank you for your order, {{customer_name}}!</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">We have received your order <strong>{{order_number}}</strong> and are preparing your items with care. We will notify you as soon as your package ships.</p>
<div style="margin:20px 0;">{{items_table}}</div>
<p style="margin:16px 0;color:#374151;font-size:14px;"><strong>Shipping To:</strong><br>{{shipping_address}}</p>
<p style="margin:24px 0;"><a href="{{action_url}}" style="display:inline-block;background-color:{{brand_color}};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;">View Order Status</a></p>',
            ],
            [
                'key' => 'payment_failed',
                'name' => 'Payment Failed',
                'audience' => 'customer',
                'description' => 'Sent when a checkout payment attempt fails.',
                'placeholders' => ['{{customer_name}}', '{{order_number}}', '{{order_total}}', '{{action_url}}'],
                'subject' => 'Payment Unsuccessful — {{order_number}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">We could not process your payment</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">Hi {{customer_name}}, the payment of <strong>{{order_total}}</strong> for order <strong>{{order_number}}</strong> could not be completed. Your items are still saved so you can try again with another payment method.</p>
<p style="margin:24px 0;"><a href="{{action_url}}" style="display:inline-block;background-color:{{brand_color}};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;">Complete Your Order</a></p>',
            ],
            [
                'key' => 'order_shipped',
                'name' => 'Order Shipped',
                'audience' => 'customer',
                'description' => 'Sent when an order is marked as shipped with tracking info.',
                'placeholders' => ['{{customer_name}}', '{{order_number}}', '{{tracking_number}}', '{{tracking_link}}', '{{items_table}}'],
                'subject' => 'Your Order Has Shipped — {{order_number}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Your package is on its way!</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">Hi {{customer_name}}, great news — your order <strong>{{order_number}}</strong> has shipped.</p>
<div style="background-color:#F8F7F4;border-radius:10px;padding:16px;margin:16px 0;font-size:14px;color:#111111;">
  <strong>Tracking Number:</strong> {{tracking_number}}
</div>
<p style="margin:24px 0;"><a href="{{tracking_link}}" style="display:inline-block;background-color:{{brand_color}};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;">Track Your Shipment</a></p>
<div style="margin:20px 0;">{{items_table}}</div>',
            ],
            [
                'key' => 'order_delivered',
                'name' => 'Order Delivered & Review Request',
                'audience' => 'customer',
                'description' => 'Sent when an order is delivered, inviting the customer to leave a review.',
                'placeholders' => ['{{customer_name}}', '{{order_number}}', '{{action_url}}', '{{items_table}}'],
                'subject' => 'Your Order Was Delivered — {{order_number}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Your order has arrived</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">Hi {{customer_name}}, your order <strong>{{order_number}}</strong> has been delivered. We hope you love your new pieces! Whenever you have a moment, we would love to hear your thoughts.</p>
<p style="margin:24px 0;"><a href="{{action_url}}" style="display:inline-block;background-color:{{brand_color}};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;">Write a Review</a></p>',
            ],
            [
                'key' => 'order_cancelled',
                'name' => 'Order Cancelled',
                'audience' => 'customer',
                'description' => 'Sent when an order is cancelled.',
                'placeholders' => ['{{customer_name}}', '{{order_number}}', '{{order_total}}', '{{support_email}}'],
                'subject' => 'Order Cancelled — {{order_number}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Your order has been cancelled</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">Hi {{customer_name}}, order <strong>{{order_number}}</strong> ({{order_total}}) has been cancelled. If you were charged, any applicable refund will appear on your statement within 5–7 business days.</p>',
            ],
            [
                'key' => 'order_refunded',
                'name' => 'Refund Issued',
                'audience' => 'customer',
                'description' => 'Sent when a full or partial refund is processed.',
                'placeholders' => ['{{customer_name}}', '{{order_number}}', '{{refund_amount}}', '{{order_total}}'],
                'subject' => 'Refund Processed — {{order_number}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Your refund has been processed</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">Hi {{customer_name}}, we have issued a refund of <strong>{{refund_amount}}</strong> for your order <strong>{{order_number}}</strong>. Please allow 5–7 business days for the credit to appear on your original payment method.</p>',
            ],
            [
                'key' => 'back_in_stock',
                'name' => 'Back in Stock Alert',
                'audience' => 'customer',
                'description' => 'Sent to customers who subscribed to restock notifications for a product.',
                'placeholders' => ['{{customer_name}}', '{{product_name}}', '{{product_price}}', '{{product_url}}'],
                'subject' => 'Back in Stock: {{product_name}} — {{store_name}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Good news — {{product_name}} is back in stock!</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">The item you requested is available again ({{product_price}}). Quantities are limited, so reserve yours before it sells out again.</p>
<p style="margin:24px 0;"><a href="{{product_url}}" style="display:inline-block;background-color:{{brand_color}};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;">Shop {{product_name}}</a></p>',
            ],
            [
                'key' => 'chat_offline_reply',
                'name' => 'Live Chat Offline Reply',
                'audience' => 'customer',
                'description' => 'Sent when a staff member replies to a chat visitor who has gone offline.',
                'placeholders' => ['{{customer_name}}', '{{message_body}}', '{{store_name}}', '{{action_url}}'],
                'subject' => 'Reply from {{store_name}} Support',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">New reply from {{store_name}}</h2>
<p style="margin:0 0 12px;color:#374151;font-size:15px;">Hi {{customer_name}}, our team replied to your chat message:</p>
<div style="background-color:#F8F7F4;border-left:4px solid {{brand_color}};padding:16px;border-radius:8px;color:#111111;margin:16px 0;font-size:15px;">{{message_body}}</div>
<p style="margin:24px 0;"><a href="{{action_url}}" style="display:inline-block;background-color:{{brand_color}};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;">Return to Store</a></p>',
            ],

            // Admin / Staff Alerts
            [
                'key' => 'admin_new_order',
                'name' => 'Admin: New Order Alert',
                'audience' => 'admin',
                'description' => 'Sent to store staff when a new paid order is received.',
                'placeholders' => ['{{order_number}}', '{{customer_name}}', '{{customer_email}}', '{{order_total}}', '{{items_table}}'],
                'subject' => 'New Order Received — {{order_number}} ({{order_total}})',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">New Order {{order_number}}</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;">A new order totaling <strong>{{order_total}}</strong> was placed by <strong>{{customer_name}}</strong> ({{customer_email}}).</p>
<div style="margin:20px 0;">{{items_table}}</div>',
            ],
            [
                'key' => 'low_stock_alert',
                'name' => 'Admin: Low Stock Inventory Alert',
                'audience' => 'admin',
                'description' => 'Sent to the inventory alert email when items fall below their low-stock threshold.',
                'placeholders' => ['{{items_count}}', '{{items_table}}', '{{store_name}}'],
                'subject' => 'Low Stock Inventory Alert ({{items_count}} items below threshold)',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">Low Stock Inventory Alert</h2>
<p style="margin:0 0 16px;color:#374151;font-size:15px;">There are currently <strong>{{items_count}}</strong> product(s) at or below their low-stock threshold.</p>
<div style="margin:20px 0;">{{items_table}}</div>',
            ],
            [
                'key' => 'admin_offline_chat',
                'name' => 'Admin: Offline Chat Message',
                'audience' => 'admin',
                'description' => 'Sent to support staff when a visitor leaves a message outside working hours.',
                'placeholders' => ['{{customer_name}}', '{{customer_email}}', '{{message_body}}'],
                'subject' => 'New Offline Chat Message from {{customer_name}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">New Chat Inquiry</h2>
<p style="margin:0 0 12px;color:#374151;font-size:15px;"><strong>From:</strong> {{customer_name}} ({{customer_email}})</p>
<div style="background-color:#F8F7F4;padding:16px;border-radius:8px;color:#111111;margin:16px 0;">{{message_body}}</div>',
            ],
            [
                'key' => 'admin_new_contact',
                'name' => 'Admin: New Contact Form Message',
                'audience' => 'admin',
                'description' => 'Sent to support staff when a visitor submits the Contact Us form.',
                'placeholders' => ['{{customer_name}}', '{{customer_email}}', '{{contact_subject}}', '{{message_body}}'],
                'subject' => 'New Contact Inquiry: {{contact_subject}}',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">New Contact Form Submission</h2>
<p style="margin:0 0 12px;color:#374151;font-size:15px;"><strong>From:</strong> {{customer_name}} ({{customer_email}})<br><strong>Subject:</strong> {{contact_subject}}</p>
<div style="background-color:#F8F7F4;padding:16px;border-radius:8px;color:#111111;margin:16px 0;">{{message_body}}</div>',
            ],
            [
                'key' => 'admin_new_review',
                'name' => 'Admin: New Review Pending Moderation',
                'audience' => 'admin',
                'description' => 'Sent when a customer submits a new product review awaiting moderation.',
                'placeholders' => ['{{customer_name}}', '{{product_name}}', '{{rating}}', '{{message_body}}'],
                'subject' => 'New Review Pending Moderation — {{product_name}} ({{rating}}★)',
                'body_html' => '<h2 style="margin:0 0 12px;font-size:20px;color:#111111;">New Product Review Submitted</h2>
<p style="margin:0 0 12px;color:#374151;font-size:15px;"><strong>Product:</strong> {{product_name}}<br><strong>Reviewer:</strong> {{customer_name}}<br><strong>Rating:</strong> {{rating}} / 5</p>
<div style="background-color:#F8F7F4;padding:16px;border-radius:8px;color:#111111;margin:16px 0;">{{message_body}}</div>',
            ],
        ];
    }

    /**
     * Ensure all default templates exist in the database.
     */
    public static function ensureDefaultTemplates(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        $existingKeys = EmailTemplate::pluck('key')->all();

        foreach (self::defaultTemplates() as $tpl) {
            if (! in_array($tpl['key'], $existingKeys, true)) {
                EmailTemplate::create([
                    'key' => $tpl['key'],
                    'subject' => $tpl['subject'],
                    'body_html' => $tpl['body_html'],
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Wrap inner email HTML in a Gmail/Outlook-compatible 600px single-column table layout.
     */
    public static function wrapInEmailLayout(string $innerHtml, ?string $title = null): string
    {
        if (str_contains(strtolower($innerHtml), '<html') || str_contains(strtolower($innerHtml), '<!doctype')) {
            return $innerHtml;
        }

        $storeName = e((string) SettingService::get('site_name', 'Boutique'));
        $logoUrl = SettingService::get('logo_url');
        $brandColor = e((string) SettingService::get('brand_color', '#2F5D50'));
        $supportEmail = e((string) SettingService::get('support_email', 'support@boutique.test'));
        $storeAddress = e((string) SettingService::get('store_address', '120 Mercer Street, New York, NY 10012'));
        $siteUrl = e(rtrim((string) config('app.url', 'http://localhost'), '/'));
        $year = date('Y');
        $docTitle = e($title ?: $storeName);

        $headerBrandHtml = ! empty($logoUrl)
            ? '<a href="'.$siteUrl.'" style="text-decoration:none;"><img src="'.e($logoUrl).'" alt="'.$storeName.'" style="max-height:38px;width:auto;display:block;margin:0 auto;border:0;" /></a>'
            : '<a href="'.$siteUrl.'" style="text-decoration:none;font-size:22px;font-weight:700;letter-spacing:-0.02em;color:#111111;">'.$storeName.'</a>';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{$docTitle}</title>
</head>
<body style="margin:0;padding:0;background-color:#F8F7F4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,Roboto,Helvetica,Arial,sans-serif;color:#111111;-webkit-font-smoothing:antialiased;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F8F7F4;width:100%;margin:0;padding:32px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#FFFFFF;border:1px solid #EAE8E3;border-radius:12px;overflow:hidden;">
          <!-- Header with Store Logo -->
          <tr>
            <td align="center" style="padding:28px 32px 22px;border-bottom:1px solid #EAE8E3;">
              {$headerBrandHtml}
            </td>
          </tr>
          <!-- Main Body -->
          <tr>
            <td style="padding:32px;font-size:15px;line-height:1.65;color:#111111;">
              {$innerHtml}
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td align="center" style="padding:24px 32px;background-color:#FAF9F6;border-top:1px solid #EAE8E3;font-size:12px;line-height:1.6;color:#6B7280;text-align:center;">
              <p style="margin:0 0 6px;font-weight:600;color:#111111;">{$storeName}</p>
              <p style="margin:0 0 6px;">{$storeAddress}</p>
              <p style="margin:0;">Questions? Contact us at <a href="mailto:{$supportEmail}" style="color:{$brandColor};text-decoration:underline;">{$supportEmail}</a> · &copy; {$year} {$storeName}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    /**
     * Render a template key with placeholder replacements and return subject + wrapped HTML.
     *
     * @param  array<string, mixed>  $variables
     * @return array{subject: string, html: string, raw_body: string, is_active: bool, template_key: string}
     */
    public static function renderTemplate(
        string $templateKey,
        array $variables = [],
        ?string $fallbackSubject = null,
        ?string $fallbackHtml = null
    ): array {
        self::ensureDefaultTemplates();

        $canonicalKey = self::KEY_ALIASES[$templateKey] ?? $templateKey;

        $template = EmailTemplate::where('key', $canonicalKey)->first()
            ?: EmailTemplate::where('key', $templateKey)->first();

        $defaultMeta = collect(self::defaultTemplates())->firstWhere('key', $canonicalKey);

        $subjectTemplate = $template?->subject
            ?? $fallbackSubject
            ?? ($defaultMeta['subject'] ?? 'Notification from {{store_name}}');

        $bodyTemplate = $template?->body_html
            ?? $fallbackHtml
            ?? ($defaultMeta['body_html'] ?? '<p>{{message_body}}</p>');

        $isActive = $template ? (bool) $template->is_active : true;

        $siteUrl = rtrim((string) config('app.url', 'http://localhost'), '/');
        $defaults = [
            'store_name' => (string) SettingService::get('site_name', 'Boutique'),
            'support_email' => (string) SettingService::get('support_email', 'support@boutique.test'),
            'support_phone' => (string) SettingService::get('support_phone', ''),
            'store_address' => (string) SettingService::get('store_address', '120 Mercer Street, New York, NY 10012'),
            'brand_color' => (string) SettingService::get('brand_color', '#2F5D50'),
            'site_url' => $siteUrl,
            'action_url' => $siteUrl,
            'customer_name' => 'Valued Customer',
            'customer_email' => '',
            'order_number' => '',
            'order_total' => '$0.00',
            'refund_amount' => '$0.00',
            'tracking_number' => 'N/A',
            'tracking_link' => $siteUrl.'/account/orders',
            'items_table' => '',
            'shipping_address' => '',
            'message_body' => '',
            'reset_link' => $siteUrl.'/reset-password',
            'verification_link' => $siteUrl.'/verify-email',
            'product_name' => '',
            'product_price' => '',
            'product_url' => $siteUrl.'/shop',
            'items_count' => '0',
            'contact_subject' => '',
            'rating' => '5',
        ];

        $merged = array_merge($defaults, $variables);

        $replaceTokens = function (string $text) use ($merged): string {
            foreach ($merged as $key => $val) {
                $cleanKey = trim((string) $key, '{} ');
                $text = str_replace('{{'.$cleanKey.'}}', (string) ($val ?? ''), $text);
                $text = str_replace('{{ '.$cleanKey.' }}', (string) ($val ?? ''), $text);
            }

            return $text;
        };

        $renderedSubject = $replaceTokens($subjectTemplate);
        $renderedBody = $replaceTokens($bodyTemplate);
        $wrappedHtml = self::wrapInEmailLayout($renderedBody, $renderedSubject);

        return [
            'subject' => $renderedSubject,
            'html' => $wrappedHtml,
            'raw_body' => $renderedBody,
            'is_active' => $isActive,
            'template_key' => $templateKey,
        ];
    }

    /**
     * Render and dispatch an email template via configured SMTP (or test mailer) and log to email_logs.
     *
     * @param  array<string, mixed>  $variables
     */
    public static function sendTemplate(
        string $templateKey,
        string $to,
        array $variables = [],
        ?string $fallbackSubject = null,
        ?string $fallbackHtml = null,
        bool $forceSendEvenIfDisabled = false
    ): ?EmailLog {
        if (empty($to)) {
            return null;
        }

        $rendered = self::renderTemplate($templateKey, $variables, $fallbackSubject, $fallbackHtml);

        if (! $rendered['is_active'] && ! $forceSendEvenIfDisabled) {
            return null;
        }

        self::applyRuntimeSmtpConfig();

        $replyTo = SettingService::get('mail_reply_to') ?: SettingService::get('support_email');

        try {
            Mail::to($to)->send(new DynamicTemplateMail(
                subjectLine: $rendered['subject'],
                renderedHtml: $rendered['html'],
                templateKey: $templateKey,
                replyToAddress: is_string($replyTo) && ! empty($replyTo) ? $replyTo : null,
            ));

            return EmailLog::create([
                'to' => $to,
                'subject' => $rendered['subject'],
                'body_html' => $rendered['html'],
                'template_key' => $templateKey,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            return EmailLog::create([
                'to' => $to,
                'subject' => $rendered['subject'],
                'body_html' => $rendered['html'],
                'template_key' => $templateKey,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'sent_at' => now(),
            ]);
        }
    }

    /**
     * Build a table-based HTML summary of order items and totals for email bodies.
     */
    public static function buildOrderItemsTableHtml(Order $order): string
    {
        $order->loadMissing('items');
        $symbol = (string) SettingService::get('currency_symbol', '$');

        $rows = '';
        foreach ($order->items as $item) {
            $name = e($item->name_snapshot);
            $qty = (int) $item->qty;
            $lineTotal = $symbol.number_format((float) $item->line_total, 2);

            $rows .= <<<HTML
            <tr>
              <td style="padding:10px 12px;border-bottom:1px solid #EAE8E3;font-size:14px;color:#111111;">{$name} <span style="color:#6B7280;">&times; {$qty}</span></td>
              <td align="right" style="padding:10px 12px;border-bottom:1px solid #EAE8E3;font-size:14px;font-weight:600;color:#111111;">{$lineTotal}</td>
            </tr>
            HTML;
        }

        $subtotal = $symbol.number_format((float) $order->subtotal, 2);
        $shipping = (float) $order->shipping_total > 0
            ? $symbol.number_format((float) $order->shipping_total, 2)
            : 'Free';
        $grandTotal = $symbol.number_format((float) $order->grand_total, 2);

        return <<<HTML
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border:1px solid #EAE8E3;border-radius:8px;overflow:hidden;border-collapse:collapse;">
          <thead>
            <tr style="background-color:#F8F7F4;">
              <th align="left" style="padding:10px 12px;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;border-bottom:1px solid #EAE8E3;">Item</th>
              <th align="right" style="padding:10px 12px;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#6B7280;border-bottom:1px solid #EAE8E3;">Total</th>
            </tr>
          </thead>
          <tbody>
            {$rows}
          </tbody>
          <tfoot>
            <tr>
              <td style="padding:8px 12px;font-size:13px;color:#6B7280;">Subtotal</td>
              <td align="right" style="padding:8px 12px;font-size:13px;color:#111111;">{$subtotal}</td>
            </tr>
            <tr>
              <td style="padding:4px 12px 8px;font-size:13px;color:#6B7280;">Shipping</td>
              <td align="right" style="padding:4px 12px 8px;font-size:13px;color:#111111;">{$shipping}</td>
            </tr>
            <tr style="background-color:#FAF9F6;">
              <td style="padding:10px 12px;font-size:15px;font-weight:700;color:#111111;border-top:1px solid #EAE8E3;">Total</td>
              <td align="right" style="padding:10px 12px;font-size:15px;font-weight:700;color:#111111;border-top:1px solid #EAE8E3;">{$grandTotal}</td>
            </tr>
          </tfoot>
        </table>
        HTML;
    }

    /**
     * Format shipping address snapshot for email display.
     */
    public static function formatOrderAddress(Order $order): string
    {
        $addr = is_array($order->shipping_address_json) ? $order->shipping_address_json : [];
        $name = trim(($addr['first_name'] ?? '').' '.($addr['last_name'] ?? '')) ?: ($addr['name'] ?? '');
        $line1 = $addr['line1'] ?? '';
        $cityState = trim(($addr['city'] ?? '').', '.($addr['state'] ?? '').' '.($addr['postal_code'] ?? ''), ', ');
        $country = $addr['country'] ?? '';

        return collect([$name, $line1, $cityState, $country])
            ->filter()
            ->map(fn ($line) => e((string) $line))
            ->implode('<br>');
    }

    /**
     * Send an order-related notification using the editable template and order context.
     */
    public static function sendOrderEmail(
        Order $order,
        string $templateKey,
        ?string $recipientOverride = null,
        ?string $fallbackSubject = null
    ): ?EmailLog {
        $order->loadMissing(['user', 'items']);
        $symbol = (string) SettingService::get('currency_symbol', '$');
        $siteUrl = rtrim((string) config('app.url', 'http://localhost'), '/');

        $customerEmail = $order->guest_email ?: $order->user?->email;
        $to = $recipientOverride ?: $customerEmail;

        if (! $to) {
            return null;
        }

        $addr = is_array($order->shipping_address_json) ? $order->shipping_address_json : [];
        $customerName = $order->user?->name
            ?: (trim(($addr['first_name'] ?? '').' '.($addr['last_name'] ?? '')) ?: 'Valued Customer');

        $trackingLink = $order->tracking_url ?: ($siteUrl.'/account/orders/'.$order->order_number);

        $variables = [
            'customer_name' => $customerName,
            'customer_email' => (string) $customerEmail,
            'order_number' => $order->order_number,
            'order_total' => $symbol.number_format((float) $order->grand_total, 2),
            'refund_amount' => $symbol.number_format((float) ($order->refunded_amount ?: $order->grand_total), 2),
            'tracking_number' => $order->tracking_number ?: 'Pending',
            'tracking_link' => $trackingLink,
            'items_table' => self::buildOrderItemsTableHtml($order),
            'shipping_address' => self::formatOrderAddress($order),
            'action_url' => $siteUrl.'/account/orders/'.$order->order_number,
        ];

        return self::sendTemplate($templateKey, $to, $variables, $fallbackSubject);
    }

    /**
     * Sample variables for previewing or sending a test of any template in the Admin UI.
     *
     * @return array<string, string>
     */
    public static function sampleVariables(): array
    {
        $symbol = (string) SettingService::get('currency_symbol', '$');
        $siteUrl = rtrim((string) config('app.url', 'http://localhost'), '/');

        $sampleItemsTable = <<<HTML
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border:1px solid #EAE8E3;border-radius:8px;overflow:hidden;border-collapse:collapse;">
          <thead>
            <tr style="background-color:#F8F7F4;">
              <th align="left" style="padding:10px 12px;font-size:12px;text-transform:uppercase;color:#6B7280;border-bottom:1px solid #EAE8E3;">Item</th>
              <th align="right" style="padding:10px 12px;font-size:12px;text-transform:uppercase;color:#6B7280;border-bottom:1px solid #EAE8E3;">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="padding:10px 12px;border-bottom:1px solid #EAE8E3;font-size:14px;color:#111111;">Organic Linen Overshirt (Sage / M) <span style="color:#6B7280;">&times; 1</span></td>
              <td align="right" style="padding:10px 12px;border-bottom:1px solid #EAE8E3;font-size:14px;font-weight:600;color:#111111;">{$symbol}128.00</td>
            </tr>
            <tr>
              <td style="padding:10px 12px;border-bottom:1px solid #EAE8E3;font-size:14px;color:#111111;">Hand-Thrown Ceramic Mug (Oat) <span style="color:#6B7280;">&times; 2</span></td>
              <td align="right" style="padding:10px 12px;border-bottom:1px solid #EAE8E3;font-size:14px;font-weight:600;color:#111111;">{$symbol}68.00</td>
            </tr>
          </tbody>
          <tfoot>
            <tr style="background-color:#FAF9F6;">
              <td style="padding:10px 12px;font-size:15px;font-weight:700;color:#111111;">Total</td>
              <td align="right" style="padding:10px 12px;font-size:15px;font-weight:700;color:#111111;">{$symbol}196.00</td>
            </tr>
          </tfoot>
        </table>
        HTML;

        return [
            'customer_name' => 'Clara Vance',
            'customer_email' => 'clara.vance@example.com',
            'order_number' => 'ORD-20261013-89AB12',
            'order_total' => $symbol.'196.00',
            'refund_amount' => $symbol.'68.00',
            'tracking_number' => '1Z999AA10123456784',
            'tracking_link' => 'https://www.ups.com/track?tracknum=1Z999AA10123456784',
            'items_table' => $sampleItemsTable,
            'shipping_address' => 'Clara Vance<br>450 Sutter Street, Apt 8B<br>San Francisco, CA 94108<br>US',
            'message_body' => 'Your package will be dispatched with our afternoon courier pickup today.',
            'reset_link' => $siteUrl.'/reset-password?token=sample-reset-token',
            'verification_link' => $siteUrl.'/verify-email?token=sample-verify-token',
            'product_name' => 'Organic Linen Overshirt',
            'product_price' => $symbol.'128.00',
            'product_url' => $siteUrl.'/products/organic-linen-overshirt',
            'items_count' => '3',
            'contact_subject' => 'Question about Linen Care Instructions',
            'rating' => '5',
            'action_url' => $siteUrl,
        ];
    }
}
