
/**
 * Format number with thousands separator
 * @param {number} value - Number to format
 * @param {boolean} [round=true] - Whether to round the number
 * @returns {string} Formatted number
 */
export function formatNumber(value, round = true) {
    if (round) {
        value = Math.round(value);
    }
    return new Intl.NumberFormat("en-US").format(value);
}

/**
 * Format currency in PHP
 * @param {number} value - Number to format
 * @param {boolean} [round=true] - Whether to round the number
 * @returns {string} Formatted currency with ₱ symbol
 */
export function formatCurrency(value, round = true) {
    return `₱${formatNumber(value, round)}`;
}