<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class FinanceController extends Controller
{
    public function summary(): JsonResponse
    {
        $paidOrders = Order::whereIn('status', ['paid', 'processing', 'shipped', 'delivered']);
        $revenue = (float) $paidOrders->sum('grand_total');
        $ordersCount = $paidOrders->count();
        $avgOrderValue = $ordersCount > 0 ? round($revenue / $ordersCount, 2) : 0;

        return response()->json([
            'revenue_total' => $revenue,
            'orders_count' => $ordersCount,
            'average_order_value' => $avgOrderValue,
            'currency' => 'USD',
            'recent_payouts' => [
                ['id' => 'po_102', 'amount' => 1450.00, 'status' => 'paid', 'date' => now()->subDays(3)->toDateString()],
                ['id' => 'po_101', 'amount' => 890.00, 'status' => 'paid', 'date' => now()->subDays(10)->toDateString()],
            ],
        ]);
    }
}
