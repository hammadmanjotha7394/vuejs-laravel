<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::query();

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where('code', 'like', "%{$term}%");
        }

        return response()->json([
            'data' => $query->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:percent,fixed,free_shipping',
            'value' => 'required|numeric|min:0',
            'min_subtotal' => 'nullable|numeric|min:0',
            'min_qty' => 'nullable|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $validated['is_active'] ?? true;

        $coupon = Coupon::create($validated);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'coupon.created',
            'subject_type' => Coupon::class,
            'subject_id' => $coupon->id,
            'properties_json' => [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'min_qty' => $coupon->min_qty,
            ],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Coupon created successfully.',
            'data' => $coupon,
        ], 201);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($coupon->id)],
            'type' => 'sometimes|required|in:percent,fixed,free_shipping',
            'value' => 'sometimes|required|numeric|min:0',
            'min_subtotal' => 'nullable|numeric|min:0',
            'min_qty' => 'nullable|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }

        $coupon->update($validated);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'coupon.updated',
            'subject_type' => Coupon::class,
            'subject_id' => $coupon->id,
            'properties_json' => [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'is_active' => $coupon->is_active,
            ],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Coupon updated successfully.',
            'data' => $coupon->fresh(),
        ]);
    }

    public function destroy(Request $request, Coupon $coupon): JsonResponse
    {
        $code = $coupon->code;
        $id = $coupon->id;
        $coupon->delete();

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'coupon.deleted',
            'subject_type' => Coupon::class,
            'subject_id' => $id,
            'properties_json' => ['code' => $code],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Coupon deleted successfully.',
        ]);
    }
}
