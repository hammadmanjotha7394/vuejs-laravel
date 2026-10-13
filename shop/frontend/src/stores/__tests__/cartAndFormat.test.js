import test from 'node:test'
import assert from 'node:assert/strict'
import { formatPrice, calculateCartSummary } from '../../utils/formatPrice.js'

test('formatPrice formats currency values with 2 decimals and thousands separators', () => {
  assert.equal(formatPrice(0), '$0.00')
  assert.equal(formatPrice(49.5), '$49.50')
  assert.equal(formatPrice(1250.99, '$'), '$1,250.99')
  assert.equal(formatPrice(85, '€'), '€85.00')
  assert.equal(formatPrice(null), '$0.00')
  assert.equal(formatPrice('invalid'), '$0.00')
})

test('calculateCartSummary computes subtotal, item count, and free shipping threshold accurately', () => {
  const items = [
    { product_id: 1, unit_price: 25.0, quantity: 2 },
  ]
  const summary = calculateCartSummary(items, null, 75.0, 8.95)
  assert.equal(summary.itemCount, 2)
  assert.equal(summary.subtotal, 50.0)
  assert.equal(summary.shipping.is_free, false)
  assert.equal(summary.shipping.amount_needed, 25.0)
  assert.equal(summary.shippingFee, 8.95)
  assert.equal(summary.grandTotal, 58.95)
})

test('calculateCartSummary applies percentage, fixed, and Buy X Get Y% coupons properly', () => {
  const items = [
    { product_id: 1, unit_price: 40.0, quantity: 2 },
  ]

  // Buy 2 Get 15% Off
  const summaryWithBuyXGetY = calculateCartSummary(
    items,
    { code: 'BUY2GET15', type: 'percent', value: 15, min_qty: 2, min_spend: 0 },
    75.0,
    8.95
  )
  assert.equal(summaryWithBuyXGetY.subtotal, 80.0)
  assert.equal(summaryWithBuyXGetY.discount, 12.0)
  // After $12 discount, discountedSubtotal is $68 (< $75 threshold)
  assert.equal(summaryWithBuyXGetY.shipping.is_free, false)
  assert.equal(summaryWithBuyXGetY.grandTotal, 76.95)

  // Free shipping coupon
  const summaryFreeShip = calculateCartSummary(
    [{ product_id: 1, unit_price: 30.0, quantity: 1 }],
    { code: 'FREESHIP', type: 'free_shipping', value: 0 },
    75.0,
    8.95
  )
  assert.equal(summaryFreeShip.shipping.is_free, true)
  assert.equal(summaryFreeShip.shippingFee, 0)
  assert.equal(summaryFreeShip.grandTotal, 30.0)
})
