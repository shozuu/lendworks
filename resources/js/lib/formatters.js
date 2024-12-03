/**
 * Format number with thousands separator
 * @param {number} value - Number to format
 * @returns {string} Formatted number with ₱ symbol
 */
export function formatNumber(value) {
    return `₱${new Intl.NumberFormat("en-US").format(value)}`;
}