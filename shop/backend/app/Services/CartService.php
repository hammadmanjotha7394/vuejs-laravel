<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Retrieve or create a cart for an authenticated user or guest token.
     */
    public static function getOrCreateCart(?User $user = null, ?string $token = null): Cart
    {
        if ($user) {
            $cart = Cart::where('user_id', $user->id)->first();

            if (! $cart) {
                $cart = Cart::create([
                    'token' => Str::random(40),
                    'user_id' => $user->id,
                ]);
            }

            // If a guest token was provided, merge guest items into the user's cart
            if ($token && $token !== $cart->token) {
                self::mergeGuestCart($cart, $token);
            }

            return $cart;
        }

        if ($token) {
            $cart = Cart::where('token', $token)->whereNull('user_id')->first();
            if ($cart) {
                return $cart;
            }
        }

        return Cart::create([
            'token' => $token ?: Str::random(40),
            'user_id' => null,
        ]);
    }

    /**
     * Merge items from a guest cart into an authenticated user's cart.
     */
    public static function mergeGuestCart(Cart $userCart, string $guestToken): void
    {
        $guestCart = Cart::where('token', $guestToken)->whereNull('user_id')->first();
        if (! $guestCart || $guestCart->id === $userCart->id) {
            return;
        }

        foreach ($guestCart->items as $guestItem) {
            $existing = $userCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('variant_id', $guestItem->variant_id)
                ->first();

            if ($existing) {
                $existing->quantity += $guestItem->quantity;
                $existing->save();
            } else {
                $guestItem->cart_id = $userCart->id;
                $guestItem->save();
            }
        }

        if ($guestCart->coupon_code && ! $userCart->coupon_code) {
            $userCart->coupon_code = $guestCart->coupon_code;
            $userCart->save();
        }

        $guestCart->delete();
    }

    /**
     * Hydrate and validate the cart using database as the absolute source of truth.
     * Tampering with prices or quantities on the client has ZERO effect.
     *
     * @return array<string, mixed>
     */
    public static function formatCart(Cart $cart, ?User $user = null): array
    {
        $items = $cart->items()
            ->with(['product.images', 'variant.image', 'variant.optionValues'])
            ->get();

        $formattedItems = [];
        $notices = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $product = $item->product;

            // 1. Validate product exists and is active
            if (! $product || ! in_array($product->status, ['active', 'published'])) {
                $productName = $product ? $product->name : 'An item';
                $notices[] = "{$productName} is no longer available and was removed from your bag.";
                $item->delete();

                continue;
            }

            // 2. Validate variant if applicable
            $variant = $item->variant;
            if ($item->variant_id && (! $variant || ! $variant->is_active)) {
                $notices[] = "The selected variant for {$product->name} is no longer available and was removed.";
                $item->delete();

                continue;
            }

            // 3. Database is the source of truth for price
            $unitPrice = $variant ? (float) $variant->price : (float) $product->base_price;

            // 4. Validate stock availability
            $availableStock = $variant
                ? (int) $variant->stock_qty
                : ($product->track_stock ? (int) $product->stock_qty : 9999);

            $inStock = true;
            if ($product->track_stock) {
                if ($availableStock <= 0) {
                    $inStock = false;
                    $notices[] = "{$product->name} is currently out of stock.";
                } elseif ($item->quantity > $availableStock) {
                    $item->quantity = $availableStock;
                    $item->save();
                    $notices[] = "{$product->name} quantity was adjusted to {$availableStock} due to limited stock.";
                }
            }

            $lineTotal = round($unitPrice * $item->quantity, 2);
            $subtotal += $lineTotal;

            // Build image and variant title
            $image = null;
            if ($variant && $variant->image) {
                $image = $variant->image->thumb_url ?: $variant->image->url;
            } elseif ($product->images->isNotEmpty()) {
                $image = $product->images->first()->thumb_url ?: $product->images->first()->url;
            }

            $variantTitle = null;
            if ($variant && $variant->optionValues->isNotEmpty()) {
                $variantTitle = $variant->optionValues->pluck('value')->implode(' / ');
            }

            $formattedItems[] = [
                'id' => $item->id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $variant ? $variant->sku : $product->sku,
                'variant_title' => $variantTitle,
                'unit_price' => $unitPrice,
                'quantity' => (int) $item->quantity,
                'max_quantity' => $product->track_stock ? min($availableStock, 99) : 99,
                'line_total' => $lineTotal,
                'image' => $image,
                'in_stock' => $inStock,
            ];
        }

        $subtotal = round($subtotal, 2);

        // 5. Validate Coupon
        $couponData = null;
        $discountAmount = 0.0;
        if ($cart->coupon_code) {
            $coupon = Coupon::where('code', $cart->coupon_code)->first();

            if (! $coupon) {
                $cart->coupon_code = null;
                $cart->save();
                $notices[] = "Applied coupon '{$cart->coupon_code}' is no longer valid.";
            } else {
                $errorMsg = $coupon->getValidationErrorMessage($subtotal, $user?->id);
                if ($errorMsg) {
                    $cart->coupon_code = null;
                    $cart->save();
                    $notices[] = "Coupon '{$coupon->code}' was removed: {$errorMsg}";
                } else {
                    $discountAmount = $coupon->calculateDiscount($subtotal);
                    $couponData = [
                        'code' => $coupon->code,
                        'type' => $coupon->type,
                        'value' => (float) $coupon->value,
                        'discount_amount' => $discountAmount,
                    ];
                }
            }
        }

        // 6. Free Shipping Progress
        $freeShippingThreshold = (float) SettingService::get('free_shipping_threshold', 75.00);
        $isFreeShipping = $subtotal >= $freeShippingThreshold;
        $amountNeededForFreeShipping = max(0, round($freeShippingThreshold - $subtotal, 2));
        $shippingProgressPercentage = $freeShippingThreshold > 0
            ? min(100, (int) round(($subtotal / $freeShippingThreshold) * 100))
            : 100;

        // 7. Active Shipping Rates
        $rates = ShippingRate::where('is_active', true)->orderBy('rate')->get()->map(function ($r) use ($subtotal, $isFreeShipping) {
            $cost = (float) $r->rate;
            if ($isFreeShipping && $r->free_above_subtotal !== null && $subtotal >= (float) $r->free_above_subtotal) {
                $cost = 0.0;
            } elseif ($r->free_above_subtotal !== null && $subtotal >= (float) $r->free_above_subtotal) {
                $cost = 0.0;
            }

            return [
                'id' => $r->id,
                'name' => $r->name,
                'cost' => $cost,
                'is_free' => $cost === 0.0,
            ];
        });

        $grandTotal = max(0, round($subtotal - $discountAmount, 2));

        return [
            'id' => $cart->id,
            'token' => $cart->token,
            'items' => $formattedItems,
            'item_count' => array_sum(array_column($formattedItems, 'quantity')),
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'coupon' => $couponData,
            'shipping' => [
                'free_threshold' => $freeShippingThreshold,
                'is_free' => $isFreeShipping,
                'amount_needed' => $amountNeededForFreeShipping,
                'progress_percentage' => $shippingProgressPercentage,
                'rates' => $rates,
            ],
            'grand_total' => $grandTotal,
            'notices' => $notices,
        ];
    }

    /**
     * Add an item to the cart, verifying live inventory and product validity.
     *
     * @return array<string, mixed>
     */
    public static function addItem(Cart $cart, int $productId, ?int $variantId = null, int $quantity = 1, ?User $user = null): array
    {
        $quantity = max(1, $quantity);

        $product = Product::where('id', $productId)
            ->whereIn('status', ['active', 'published'])
            ->firstOrFail();

        $variant = null;
        if ($variantId) {
            $variant = ProductVariant::where('id', $variantId)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->firstOrFail();
        }

        // Validate stock
        $availableStock = $variant
            ? (int) $variant->stock_qty
            : ($product->track_stock ? (int) $product->stock_qty : 9999);

        if ($product->track_stock && $availableStock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ["Only {$availableStock} items available in stock."],
            ]);
        }

        $item = $cart->items()
            ->where('product_id', $product->id)
            ->where('variant_id', $variantId)
            ->first();

        if ($existingQuantity = $item?->quantity) {
            $newQuantity = $existingQuantity + $quantity;
            if ($product->track_stock && $availableStock < $newQuantity) {
                $newQuantity = $availableStock;
            }
            $item->quantity = $newQuantity;
            $item->save();
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ]);
        }

        return self::formatCart($cart, $user);
    }

    /**
     * Update an item's quantity in the cart.
     *
     * @return array<string, mixed>
     */
    public static function updateItem(Cart $cart, int $itemId, int $quantity, ?User $user = null): array
    {
        $item = $cart->items()->findOrFail($itemId);

        if ($quantity <= 0) {
            $item->delete();
        } else {
            $product = $item->product;
            $variant = $item->variant;

            $availableStock = $variant
                ? (int) $variant->stock_qty
                : ($product && $product->track_stock ? (int) $product->stock_qty : 9999);

            if ($product && $product->track_stock && $quantity > $availableStock) {
                $quantity = $availableStock;
            }

            $item->quantity = $quantity;
            $item->save();
        }

        return self::formatCart($cart, $user);
    }

    /**
     * Remove an item from the cart.
     *
     * @return array<string, mixed>
     */
    public static function removeItem(Cart $cart, int $itemId, ?User $user = null): array
    {
        $cart->items()->where('id', $itemId)->delete();

        return self::formatCart($cart, $user);
    }

    /**
     * Apply a coupon code to the cart.
     *
     * @return array<string, mixed>
     */
    public static function applyCoupon(Cart $cart, string $code, ?User $user = null): array
    {
        $code = strtoupper(trim($code));
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon' => ["Coupon code '{$code}' does not exist."],
            ]);
        }

        // Calculate current subtotal
        $subtotal = 0.0;
        foreach ($cart->items as $item) {
            $price = $item->variant ? (float) $item->variant->price : (float) $item->product?->base_price;
            $subtotal += $price * $item->quantity;
        }

        $error = $coupon->getValidationErrorMessage($subtotal, $user?->id);
        if ($error) {
            throw ValidationException::withMessages([
                'coupon' => [$error],
            ]);
        }

        $cart->coupon_code = $coupon->code;
        $cart->save();

        return self::formatCart($cart, $user);
    }

    /**
     * Remove the active coupon code from the cart.
     *
     * @return array<string, mixed>
     */
    public static function removeCoupon(Cart $cart, ?User $user = null): array
    {
        $cart->coupon_code = null;
        $cart->save();

        return self::formatCart($cart, $user);
    }

    /**
     * Clear all items from the cart.
     *
     * @return array<string, mixed>
     */
    public static function clearCart(Cart $cart, ?User $user = null): array
    {
        $cart->items()->delete();
        $cart->coupon_code = null;
        $cart->save();

        return self::formatCart($cart, $user);
    }
}
