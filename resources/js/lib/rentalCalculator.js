import { getValueCategory } from './suggestRate';

function getDiscountRate(valueCategory, rentalDays) {
    if (rentalDays <= 6) return 0;

    const discountRates = {
        'low-value': {
            weekly: 0.05,    // 5% for 7-29 days
            monthly: 0.10    // 10% for 30+ days
        },
        'mid-value': {
            weekly: 0.10,    // 10% for 7-29 days
            monthly: 0.15    // 15% for 30+ days
        },
        'high-value': {
            weekly: 0.15,    // 15% for 7-29 days
            monthly: 0.20    // 20% for 30+ days
        }
    };

    return rentalDays >= 30 
        ? discountRates[valueCategory].monthly 
        : discountRates[valueCategory].weekly;
}

export function calculateDiscountPercentage(rentalDays, itemValue) {
    if (rentalDays <= 6) return 0;

    const category = getValueCategory(itemValue);
    
    const rates = {
        'low-value': { weekly: 5, monthly: 10 },
        'mid-value': { weekly: 10, monthly: 15 },
        'high-value': { weekly: 15, monthly: 20 }
    };

    return rentalDays >= 30 
        ? rates[category].monthly 
        : rates[category].weekly;
}

export function calculateRentalPrice(dailyRate, itemValue, rentalDays, depositFee = 0) {
    if (!dailyRate || !itemValue || !rentalDays || dailyRate < 0 || itemValue < 0 || rentalDays < 1) {
        throw new Error('Invalid input parameters');
    }

    const basePrice = Math.round(dailyRate * rentalDays);
    const category = getValueCategory(itemValue);
    const discountRate = getDiscountRate(category, rentalDays);
    
    const discount = Math.round(basePrice * discountRate);
    const discountedPrice = basePrice - discount;
    const fee = Math.round(discountedPrice * 0.10);
    
    return {
        basePrice,
        fee,
        discount,
        discountPercentage: Math.round(discountRate * 100),
        deposit: depositFee,
        totalPrice: discountedPrice + fee + depositFee  // Rental cost with deposit
    };
}
