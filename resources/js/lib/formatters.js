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
 * Format date in MMM d, yyyy format without time
 * Used primarily for rental dates display
 */
export function formatRentalDate(date) {
    if (!date) return '';
    return format(new Date(date), 'MMM d, yyyy');
}

export function formatDate(date) {
    if (!date) return '';
    const dateObj = new Date(date);
    
    if (isToday(dateObj)) {
        return `Today at ${format(dateObj, 'h:mm a')}`;
    }
    
    if (isYesterday(dateObj)) {
        return `Yesterday at ${format(dateObj, 'h:mm a')}`;
    }

    return format(dateObj, 'MMM d, yyyy');
}

export function formatShortDate(date) {
    if (!date) return '';
    return format(new Date(date), 'MMM d, yyyy');
}

export function formatDateTime(date) {
    if (!date) return '';
    return format(new Date(date), 'MMM d, yyyy h:mm a');
}

export function timeAgo(date) {
    if (!date) return '';
    return formatDistance(new Date(date), new Date(), { addSuffix: true });
}