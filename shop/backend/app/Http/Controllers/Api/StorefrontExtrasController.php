<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackInStockSubscription;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Services\EmailService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class StorefrontExtrasController extends Controller
{
    /**
     * Subscribe an email address to the store newsletter.
     */
    public function subscribeNewsletter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:180',
        ]);

        $email = strtolower(trim($validated['email']));
        $doubleOptIn = (bool) SettingService::get('newsletter_double_optin', false);

        $subscriber = NewsletterSubscriber::updateOrCreate(
            ['email' => $email],
            [
                'is_active' => true,
                'subscribed_at' => now(),
            ]
        );

        return response()->json([
            'message' => $doubleOptIn
                ? 'Please check your inbox to confirm your newsletter subscription.'
                : 'Thank you for subscribing to our newsletter.',
            'double_opt_in' => $doubleOptIn,
            'data' => $subscriber,
        ]);
    }

    /**
     * Unsubscribe an email address from the newsletter.
     */
    public function unsubscribeNewsletter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:180',
        ]);

        $email = strtolower(trim($validated['email']));
        NewsletterSubscriber::where('email', $email)->update(['is_active' => false]);

        return response()->json([
            'message' => 'You have been unsubscribed from future marketing emails.',
        ]);
    }

    /**
     * Submit the public Contact Us form with honeypot spam protection.
     */
    public function submitContact(Request $request): JsonResponse
    {
        // Honeypot field: bots fill hidden 'website' field; silently succeed without storing
        if ($request->filled('website')) {
            return response()->json([
                'message' => 'Thank you for reaching out. Our team will respond shortly.',
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:180',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:4000',
        ]);

        $cleanMessage = trim(strip_tags($validated['message']));

        $contact = ContactMessage::create([
            'name' => trim(strip_tags($validated['name'])),
            'email' => strtolower(trim($validated['email'])),
            'subject' => trim(strip_tags($validated['subject'])),
            'message' => $cleanMessage,
            'status' => 'unread',
        ]);

        $adminEmail = (string) SettingService::get('support_email', 'support@boutique.test');
        EmailService::sendTemplate('admin_new_contact', $adminEmail, [
            'customer_name' => $contact->name,
            'customer_email' => $contact->email,
            'contact_subject' => $contact->subject,
            'message_body' => e($contact->message),
        ]);

        return response()->json([
            'message' => 'Thank you for reaching out. Our team will get back to you within 1 business day.',
            'data' => [
                'id' => $contact->id,
            ],
        ], 201);
    }

    /**
     * Subscribe to "Notify Me When Back in Stock" for a product or variant.
     */
    public function subscribeBackInStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:180',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
        ]);

        $sub = BackInStockSubscription::updateOrCreate(
            [
                'product_id' => $product->id,
                'variant_id' => $validated['variant_id'] ?? null,
                'email' => strtolower(trim($validated['email'])),
            ],
            [
                'notified_at' => null,
            ]
        );

        return response()->json([
            'message' => "We will email you at {$sub->email} as soon as {$product->name} is back in stock.",
            'data' => $sub,
        ]);
    }

    /**
     * Public Guest Order Tracking by Order Number + Customer Email.
     */
    public function trackOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:60',
            'email' => 'required|email|max:180',
        ]);

        $orderNumber = strtoupper(trim($validated['order_number']));
        $email = strtolower(trim($validated['email']));

        $order = Order::with(['items', 'statusHistory' => fn ($q) => $q->latest()])
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'No order found matching that order number and email address.',
            ], 404);
        }

        $orderEmail = strtolower((string) ($order->guest_email ?: $order->user?->email));
        if ($orderEmail !== $email) {
            return response()->json([
                'message' => 'No order found matching that order number and email address.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'placed_at' => ($order->placed_at ?: $order->created_at)?->toIso8601String(),
                'subtotal' => (float) $order->subtotal,
                'discount_total' => (float) $order->discount_total,
                'shipping_total' => (float) $order->shipping_total,
                'tax_total' => (float) $order->tax_total,
                'grand_total' => (float) $order->grand_total,
                'currency' => $order->currency,
                'tracking_number' => $order->tracking_number,
                'tracking_url' => $order->tracking_url,
                'shipping_city' => $order->shipping_address_json['city'] ?? null,
                'shipping_country' => $order->shipping_address_json['country'] ?? null,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name_snapshot,
                    'sku' => $item->sku_snapshot,
                    'image' => $item->image_snapshot,
                    'qty' => (int) $item->qty,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ]),
                'timeline' => $order->statusHistory->map(fn ($h) => [
                    'status' => $h->status,
                    'note' => str_starts_with((string) $h->note, '[Internal Note]') ? 'Order status updated.' : $h->note,
                    'created_at' => $h->created_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Generate cached XML Sitemap for search engines.
     */
    public function sitemap(): Response
    {
        $xml = Cache::remember('public_sitemap_xml', 3600, function () {
            $baseUrl = rtrim((string) config('app.url', url('/')), '/');

            $urls = [
                ['loc' => $baseUrl.'/', 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => $baseUrl.'/shop', 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => $baseUrl.'/track-order', 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => $baseUrl.'/contact', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ];

            foreach (Category::where('is_active', true)->get(['slug', 'updated_at']) as $cat) {
                $urls[] = [
                    'loc' => $baseUrl.'/category/'.$cat->slug,
                    'lastmod' => $cat->updated_at?->toAtomString(),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                ];
            }

            foreach (Product::where('status', 'active')->get(['slug', 'updated_at']) as $prod) {
                $urls[] = [
                    'loc' => $baseUrl.'/products/'.$prod->slug,
                    'lastmod' => $prod->updated_at?->toAtomString(),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                ];
            }

            foreach (Page::where('is_published', true)->get(['slug', 'updated_at']) as $page) {
                $urls[] = [
                    'loc' => $baseUrl.'/page/'.$page->slug,
                    'lastmod' => $page->updated_at?->toAtomString(),
                    'priority' => '0.6',
                    'changefreq' => 'monthly',
                ];
            }

            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach ($urls as $u) {
                $out .= "  <url>\n";
                $out .= '    <loc>'.e($u['loc'])."</loc>\n";
                if (! empty($u['lastmod'])) {
                    $out .= '    <lastmod>'.e($u['lastmod'])."</lastmod>\n";
                }
                $out .= '    <changefreq>'.$u['changefreq']."</changefreq>\n";
                $out .= '    <priority>'.$u['priority']."</priority>\n";
                $out .= "  </url>\n";
            }
            $out .= '</urlset>';

            return $out;
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * Generate robots.txt with sitemap pointer.
     */
    public function robots(): Response
    {
        $baseUrl = rtrim((string) config('app.url', url('/')), '/');
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api/',
            'Disallow: /checkout',
            'Disallow: /account',
            '',
            "Sitemap: {$baseUrl}/sitemap.xml",
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
