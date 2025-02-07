import { getValueCategory } from './suggestRate';

function getDiscountRate(valueCategory, rentalDays) {
    if (rentalDays <= 6) return 0;

    const discountRates = {
        'low-value': {
            weekly: 0.10,    // 10% for 7-29 days
            monthly: 0.25    // 25% for 30+ days
        },
        'mid-value': {
            weekly: 0.20,    // 20% for 7-29 days
            monthly: 0.35    // 35% for 30+ days
        },
        'high-value': {
            weekly: 0.25,    // 25% for 7-29 days
            monthly: 0.50    // 50% for 30+ days
        }
    };

    return rentalDays >= 30 
        ? discountRates[valueCategory].monthly 
        : discountRates[valueCategory].weekly;
}

export function calculateRentalPrice(dailyRate, itemValue, rentalDays) {
    if (!dailyRate || !itemValue || !rentalDays || dailyRate < 0 || itemValue < 0 || rentalDays < 1) {
        throw new Error('Invalid input parameters');
    }

    const basePrice = Math.round(dailyRate * rentalDays);
    const category = getValueCategory(itemValue);
    const discountRate = getDiscountRate(category, rentalDays);
    
    const discount = Math.round(basePrice * discountRate);
    const discountedPrice = Math.round(basePrice - discount);
    const fee = Math.round(discountedPrice * 0.10);
    const totalPrice = Math.round(discountedPrice + fee);

    return {
        basePrice,
        discount,
        fee,
        totalPrice,
        discountPercentage: Math.round(discountRate * 100)
    };
}
