<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Models\UrlRedirect;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemAndExtrasController extends Controller
{
    /**
     * Activity Log Viewer for Super Admin / Staff with activity.view permission.
     */
    public function activityLogs(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user:id,name,email');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->input('action').'%');
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('action', 'like', "%{$term}%")
                    ->orWhere('ip', 'like', "%{$term}%")
                    ->orWhereHas('user', function ($uq) use ($term) {
                        $uq->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            });
        }

        $logs = $query->orderByDesc('id')->paginate((int) $request->input('per_page', 30));

        return response()->json([
            'data' => $logs->items(),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'total' => $logs->total(),
        ]);
    }

    /**
     * Quick Actions Command Palette (Ctrl+K) global search across products, orders, and customers.
     */
    public function quickSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (strlen($q) < 1) {
            return response()->json([
                'products' => Product::latest('id')->take(5)->get(['id', 'name', 'slug', 'sku', 'base_price']),
                'orders' => Order::latest('id')->take(5)->get(['id', 'order_number', 'status', 'grand_total']),
                'customers' => [],
            ]);
        }

        $products = Product::where('name', 'like', "%{$q}%")
            ->orWhere('sku', 'like', "%{$q}%")
            ->take(6)
            ->get(['id', 'name', 'slug', 'sku', 'base_price']);

        $orders = Order::where('order_number', 'like', "%{$q}%")
            ->orWhere('guest_email', 'like', "%{$q}%")
            ->take(5)
            ->get(['id', 'order_number', 'status', 'grand_total']);

        $customers = User::where('name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->take(5)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'products' => $products,
            'orders' => $orders,
            'customers' => $customers,
        ]);
    }

    /**
     * One-Click Database SQL Backup Export (Super Admin).
     * Pure PHP SQL generator so it works on Hostinger shared hosting without exec() or mysqldump.
     */
    public function exportDatabaseBackup(Request $request): StreamedResponse
    {
        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'system.database_backup_exported',
            'subject_type' => null,
            'subject_id' => null,
            'properties_json' => ['timestamp' => now()->toIso8601String()],
            'ip' => $request->ip(),
        ]);

        $filename = 'shop_backup_'.now()->format('Y_m_d_His').'.sql';

        return response()->streamDownload(function () {
            $pdo = DB::connection()->getPdo();
            echo "-- Shop Production Database Backup\n";
            echo '-- Generated at: '.now()->toIso8601String()."\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $tables = DB::select('SHOW TABLES');
            foreach ($tables as $tableObj) {
                $table = array_values((array) $tableObj)[0];
                $createRow = DB::select("SHOW CREATE TABLE `{$table}`");
                $createSql = ((array) $createRow[0])['Create Table'] ?? '';

                echo "-- Table structure for `{$table}`\n";
                echo "DROP TABLE IF EXISTS `{$table}`;\n";
                echo $createSql.";\n\n";

                $rows = DB::table($table)->get();
                if ($rows->isNotEmpty()) {
                    echo "-- Dumping data for `{$table}`\n";
                    foreach ($rows as $row) {
                        $arr = (array) $row;
                        $cols = array_map(fn ($c) => "`{$c}`", array_keys($arr));
                        $vals = array_map(function ($v) use ($pdo) {
                            if ($v === null) {
                                return 'NULL';
                            }

                            return $pdo->quote((string) $v);
                        }, array_values($arr));

                        echo "INSERT INTO `{$table}` (".implode(', ', $cols).') VALUES ('.implode(', ', $vals).");\n";
                    }
                    echo "\n";
                }
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, [
            'Content-Type' => 'application/sql; charset=UTF-8',
        ]);
    }

    /**
     * Newsletter Subscribers Admin List.
     */
    public function newsletterIndex(Request $request): JsonResponse
    {
        $query = NewsletterSubscriber::query();

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where('email', 'like', "%{$term}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $subs = $query->orderByDesc('id')->paginate((int) $request->input('per_page', 30));

        return response()->json([
            'data' => $subs->items(),
            'current_page' => $subs->currentPage(),
            'last_page' => $subs->lastPage(),
            'total' => $subs->total(),
            'active_count' => NewsletterSubscriber::where('is_active', true)->count(),
        ]);
    }

    /**
     * Export Newsletter Subscribers to CSV.
     */
    public function newsletterExportCsv(): StreamedResponse
    {
        $filename = 'newsletter_subscribers_'.now()->format('Y_m_d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Email', 'Status', 'Subscribed At']);
            NewsletterSubscriber::orderByDesc('id')->chunk(200, function ($subs) use ($out) {
                foreach ($subs as $sub) {
                    fputcsv($out, [
                        $sub->id,
                        $sub->email,
                        $sub->is_active ? 'active' : 'unsubscribed',
                        $sub->subscribed_at?->toDateTimeString(),
                    ]);
                }
            });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Delete a newsletter subscriber.
     */
    public function newsletterDestroy(NewsletterSubscriber $subscriber): JsonResponse
    {
        $subscriber->delete();

        return response()->json([
            'message' => 'Subscriber removed.',
        ]);
    }

    /**
     * Contact Form Messages Inbox in Admin.
     */
    public function contactMessagesIndex(Request $request): JsonResponse
    {
        $query = ContactMessage::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%");
            });
        }

        $messages = $query->orderByDesc('id')->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'data' => $messages->items(),
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'total' => $messages->total(),
            'unread_count' => ContactMessage::where('status', 'unread')->count(),
        ]);
    }

    /**
     * Update Contact Message Status (e.g. mark read / replied).
     */
    public function contactMessageUpdate(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:unread,read,replied,archived',
        ]);

        $contactMessage->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Contact message updated.',
            'data' => $contactMessage->fresh(),
        ]);
    }

    /**
     * Delete a Contact Message.
     */
    public function contactMessageDestroy(ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage->delete();

        return response()->json([
            'message' => 'Contact message deleted.',
        ]);
    }

    /**
     * 301 Redirect Manager List & Create & Delete.
     */
    public function redirectsIndex(): JsonResponse
    {
        return response()->json([
            'data' => UrlRedirect::orderByDesc('id')->get(),
        ]);
    }

    public function redirectsStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_path' => 'required|string|max:255',
            'target_path' => 'required|string|max:255',
            'status_code' => 'nullable|in:301,302',
            'is_active' => 'nullable|boolean',
        ]);

        $source = '/'.ltrim(trim($validated['source_path']), '/');
        $target = str_starts_with($validated['target_path'], 'http')
            ? trim($validated['target_path'])
            : '/'.ltrim(trim($validated['target_path']), '/');

        $redirect = UrlRedirect::updateOrCreate(
            ['source_path' => $source],
            [
                'target_path' => $target,
                'status_code' => $validated['status_code'] ?? 301,
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        return response()->json([
            'message' => '301 redirect rule saved.',
            'data' => $redirect,
        ], 201);
    }

    public function redirectsDestroy(UrlRedirect $redirect): JsonResponse
    {
        $redirect->delete();

        return response()->json([
            'message' => 'Redirect rule deleted.',
        ]);
    }
}
