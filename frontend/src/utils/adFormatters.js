/**
 * Dedicated monetary formatters for the Member Business Page Ads/Campaign screens.
 * Ensures all monetary amounts are displayed with exactly 4 decimal places.
 *
 * Examples:
 *   0        -> "0.0000"
 *   50       -> "50.0000"
 *   49.98    -> "49.9800"
 *   499.98   -> "499.9800"
 *   0.03     -> "0.0300"
 *   0.005    -> "0.0050"
 *   0.025    -> "0.0250"
 *   0.035    -> "0.0350"
 *   12.3456  -> "12.3456"
 *
 * @param {number|string|null|undefined} val
 * @param {boolean} [includeCommas=true]
 * @returns {string}
 */
export function formatAdAmount(val, includeCommas = true) {
  if (val === null || val === undefined || val === '') {
    return '0.0000';
  }
  const num = typeof val === 'number' ? val : parseFloat(String(val).replace(/,/g, ''));
  if (isNaN(num)) {
    return '0.0000';
  }

  if (includeCommas) {
    return num.toLocaleString('en-US', {
      minimumFractionDigits: 4,
      maximumFractionDigits: 4,
    });
  }

  return num.toFixed(4);
}

/**
 * Formats a monetary amount with currency symbol ($) and exactly 4 decimal places.
 * Example: 499.98 -> "$499.9800"
 *
 * @param {number|string|null|undefined} val
 * @param {boolean} [includeCommas=true]
 * @returns {string}
 */
export function formatAdCurrency(val, includeCommas = true) {
  return `$${formatAdAmount(val, includeCommas)}`;
}

export default {
  formatAdAmount,
  formatAdCurrency,
};
