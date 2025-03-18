import { format, formatDistance, isToday, isYesterday } from "date-fns";

/**
 * Format number with thousands separator
 * @param {number} value - Number to format
 * @returns {string} Formatted number with ₱ symbol
 */
export function formatNumber(value) {
    return `₱${new Intl.NumberFormat("en-US").format(value)}`;
}

/**
 * Converts snake_case or kebab-case to Title Case
 * e.g., "pending_requests" -> "Pending Requests"
 */
export function formatLabel(text) {
    if (!text) return '';
    return text
        .split(/[_-]/)
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
}

/**
 * Format date in MMM d, yyyy format without time
 * Used primarily for rental dates display
 */
export function formatRentalDate(date) {
    if (!date) return '';
    return format(new Date(date), 'MMM d, yyyy');
}

export function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

export function formatPrice(amount) {
    if (!amount && amount !== 0) return '';
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}

export function formatShortDate(date) {
    if (!date) return '';
    return format(new Date(date), 'MMM d, yyyy');
}

export function formatDateTime(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

export function timeAgo(date) {
    if (!date) return '';
    return formatDistance(new Date(date), new Date(), { addSuffix: true });
}