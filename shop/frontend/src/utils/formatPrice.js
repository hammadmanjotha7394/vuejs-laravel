/**
 * Format a numeric value as currency with symbol and 2 decimal places.
 */
export function formatPrice(amount, currencySymbol = '$') {
  const num = Number(amount || 0)
  if (Number.isNaN(num)) {
    return `${currencySymbol}0.00`
  }
  const isNegative = num < 0
  const absFixed = Math.abs(num).toFixed(2)
  const [intPart, decPart] = absFixed.split('.')
  const formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return `${isNegative ? '-' : ''}${currencySymbol}${formattedInt}.${decPart}`
}

/**
 * Pure cart calculation helper mirroring server-side subtotal, coupon discount, and free shipping progress.
 */
export function calculateCartSummary(items = [], coupon = null, freeShippingThreshold = 75.0, defaultShippingFee = 8.95) {
  const itemCount = items.reduce((sum, item) => sum + Math.max(0, Number(item.quantity || 0)), 0)
  const subtotal = Number(
    items.reduce((sum, item) => sum + Number(item.unit_price || 0) * Math.max(0, Number(item.quantity || 0)), 0).toFixed(2)
  )

  let discount = 0
  let isFreeShippingCoupon = false

  if (coupon && subtotal > 0) {
    const minSpend = Number(coupon.min_spend || 0)
    const minQty = Number(coupon.min_qty || 0)
    if (subtotal >= minSpend && itemCount >= minQty) {
      if (coupon.type === 'percent') {
        discount = Number((subtotal * (Number(coupon.value || 0) / 100)).toFixed(2))
      } else if (coupon.type === 'fixed') {
        discount = Math.min(subtotal, Number(coupon.value || 0))
      } else if (coupon.type === 'free_shipping') {
        isFreeShippingCoupon = true
      }
    }
  }

  const discountedSubtotal = Math.max(0, Number((subtotal - discount).toFixed(2)))
  const isFreeShipping = isFreeShippingCoupon || (freeShippingThreshold > 0 && discountedSubtotal >= freeShippingThreshold)
  const shippingFee = subtotal === 0 || isFreeShipping ? 0 : Number(defaultShippingFee)
  const amountNeeded = isFreeShipping ? 0 : Math.max(0, Number((freeShippingThreshold - discountedSubtotal).toFixed(2)))
  const progressPercentage =
    freeShippingThreshold > 0 ? Math.min(100, Math.round((discountedSubtotal / freeShippingThreshold) * 100)) : 100

  return {
    itemCount,
    subtotal,
    discount,
    shippingFee,
    grandTotal: Number((discountedSubtotal + shippingFee).toFixed(2)),
    shipping: {
      free_threshold: freeShippingThreshold,
      is_free: isFreeShipping,
      amount_needed: amountNeeded,
      progress_percentage: progressPercentage,
    },
  }
}
