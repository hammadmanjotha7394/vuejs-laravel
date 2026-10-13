<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DynamicTemplateMail;
use App\Models\ActivityLog;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Services\EmailService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailTemplateController extends Controller
{
    /**
     * List all editable email templates enriched with metadata, placeholders, and Hostinger cron instructions.
     */
    public function index(): JsonResponse
    {
        EmailService::ensureDefaultTemplates();

        $defaultsByKey = collect(EmailService::defaultTemplates())->keyBy('key');
        $templates = EmailTemplate::orderBy('id')->get()->map(function (EmailTemplate $tpl) use ($defaultsByKey) {
            $canonicalKey = EmailService::KEY_ALIASES[$tpl->key] ?? $tpl->key;
            $meta = $defaultsByKey->get($canonicalKey) ?? $defaultsByKey->get($tpl->key) ?? [];

            return [
                'id' => $tpl->id,
                'key' => $tpl->key,
                'name' => $meta['name'] ?? ucwords(str_replace('_', ' ', $tpl->key)),
                'audience' => $meta['audience'] ?? (str_starts_with($tpl->key, 'admin_') || $tpl->key === 'low_stock_alert' ? 'admin' : 'customer'),
                'description' => $meta['description'] ?? 'Automated transactional email notification.',
                'placeholders' => $meta['placeholders'] ?? ['{{customer_name}}', '{{store_name}}', '{{order_number}}', '{{order_total}}'],
                'subject' => $tpl->subject,
                'body_html' => $tpl->body_html,
                'is_active' => (bool) $tpl->is_active,
                'updated_at' => $tpl->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $templates,
            'sample_variables' => EmailService::sampleVariables(),
            'cron_commands' => [
                'schedule' => '* * * * * cd /home/USER/shop-app && php artisan schedule:run >> /dev/null 2>&1',
                'queue' => '* * * * * cd /home/USER/shop-app && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1',
            ],
            'smtp_summary' => [
                'host' => SettingService::get('mail_host', 'smtp.hostinger.com'),
                'port' => SettingService::get('mail_port', 465),
                'encryption' => SettingService::get('mail_encryption', 'ssl'),
                'from_address' => SettingService::get('mail_from_address', SettingService::get('support_email', 'orders@boutique.test')),
                'from_name' => SettingService::get('mail_from_name', SettingService::get('site_name', 'Boutique')),
            ],
        ]);
    }

    /**
     * Update an email template's subject, HTML body, or active status.
     */
    public function update(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'sometimes|required|string|max:255',
            'body_html' => 'sometimes|required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $emailTemplate->update($validated);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'email_template.updated',
            'subject_type' => EmailTemplate::class,
            'subject_id' => $emailTemplate->id,
            'properties_json' => [
                'key' => $emailTemplate->key,
                'subject' => $emailTemplate->subject,
                'is_active' => $emailTemplate->is_active,
            ],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Email template saved successfully.',
            'data' => $emailTemplate->fresh(),
        ]);
    }

    /**
     * Reset a template back to its default subject and HTML body.
     */
    public function resetToDefault(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $canonicalKey = EmailService::KEY_ALIASES[$emailTemplate->key] ?? $emailTemplate->key;
        $meta = collect(EmailService::defaultTemplates())->firstWhere('key', $canonicalKey);

        if ($meta) {
            $emailTemplate->update([
                'subject' => $meta['subject'],
                'body_html' => $meta['body_html'],
                'is_active' => true,
            ]);
        }

        return response()->json([
            'message' => 'Email template restored to default.',
            'data' => $emailTemplate->fresh(),
        ]);
    }

    /**
     * Render a live preview of an email template (supports unsaved draft subject/body_html).
     */
    public function preview(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $draftSubject = $request->input('subject', $emailTemplate->subject);
        $draftBody = $request->input('body_html', $emailTemplate->body_html);

        $sampleVars = EmailService::sampleVariables();

        $replaceTokens = function (string $text) use ($sampleVars): string {
            $defaults = [
                'store_name' => (string) SettingService::get('site_name', 'Boutique'),
                'support_email' => (string) SettingService::get('support_email', 'support@boutique.test'),
                'store_address' => (string) SettingService::get('store_address', '120 Mercer Street, New York, NY 10012'),
                'brand_color' => (string) SettingService::get('brand_color', '#2F5D50'),
            ];
            $merged = array_merge($defaults, $sampleVars);
            foreach ($merged as $key => $val) {
                $cleanKey = trim((string) $key, '{} ');
                $text = str_replace('{{'.$cleanKey.'}}', (string) $val, $text);
                $text = str_replace('{{ '.$cleanKey.' }}', (string) $val, $text);
            }

            return $text;
        };

        $renderedSubject = $replaceTokens((string) $draftSubject);
        $renderedBody = $replaceTokens((string) $draftBody);
        $wrappedHtml = EmailService::wrapInEmailLayout($renderedBody, $renderedSubject);

        return response()->json([
            'data' => [
                'subject' => $renderedSubject,
                'html' => $wrappedHtml,
            ],
        ]);
    }

    /**
     * Send a test email of a specific template using sample data to the admin's address.
     */
    public function sendTest(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'nullable|email|max:150',
        ]);

        $recipient = $validated['email'] ?? ($request->user()?->email ?: 'admin@boutique.test');

        $log = EmailService::sendTemplate(
            templateKey: $emailTemplate->key,
            to: $recipient,
            variables: EmailService::sampleVariables(),
            forceSendEvenIfDisabled: true
        );

        if ($log && $log->status === 'sent') {
            return response()->json([
                'success' => true,
                'message' => "Test '{$emailTemplate->key}' email sent to {$recipient}.",
                'log' => $log,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send test email: '.($log?->error ?? 'Unknown SMTP error'),
            'log' => $log,
        ], 422);
    }

    /**
     * List email delivery logs with search and status filters.
     */
    public function logs(Request $request): JsonResponse
    {
        $query = EmailLog::query();

        if ($request->filled('status') && in_array($request->input('status'), ['sent', 'failed'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('template_key')) {
            $query->where('template_key', $request->input('template_key'));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('to', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%")
                    ->orWhere('template_key', 'like', "%{$term}%");
            });
        }

        $logs = $query->orderByDesc('id')->paginate((int) $request->input('per_page', 25));

        $summary = [
            'total' => EmailLog::count(),
            'sent' => EmailLog::where('status', 'sent')->count(),
            'failed' => EmailLog::where('status', 'failed')->count(),
        ];

        return response()->json([
            'data' => $logs->items(),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'total' => $logs->total(),
            'summary' => $summary,
        ]);
    }

    /**
     * Resend a previously logged email (useful for retrying failed SMTP deliveries).
     */
    public function resendLog(Request $request, EmailLog $emailLog): JsonResponse
    {
        EmailService::applyRuntimeSmtpConfig();

        $html = $emailLog->body_html;
        if (empty($html)) {
            $rendered = EmailService::renderTemplate(
                $emailLog->template_key ?: 'welcome',
                EmailService::sampleVariables(),
                $emailLog->subject
            );
            $html = $rendered['html'];
        }

        $replyTo = SettingService::get('mail_reply_to') ?: SettingService::get('support_email');

        try {
            Mail::to($emailLog->to)->send(new DynamicTemplateMail(
                subjectLine: $emailLog->subject,
                renderedHtml: $html,
                templateKey: $emailLog->template_key ?: 'resent',
                replyToAddress: is_string($replyTo) && ! empty($replyTo) ? $replyTo : null,
            ));

            $emailLog->update([
                'status' => 'sent',
                'error' => null,
                'body_html' => $html,
                'sent_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Email resent successfully to {$emailLog->to}.",
                'data' => $emailLog->fresh(),
            ]);
        } catch (Throwable $e) {
            $emailLog->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'sent_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Resend failed: '.$e->getMessage(),
                'data' => $emailLog->fresh(),
            ], 422);
        }
    }
}
