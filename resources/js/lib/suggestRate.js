export function getValueCategory(itemValue) {
    if (itemValue < 25000) return 'low-value';
    if (itemValue <= 100000) return 'mid-value';
    return 'high-value';
}

export function calculateDailyRate(itemValue) {
    const category = getValueCategory(itemValue);
    
    let minRate, maxRate;
    switch (category) {
        case 'low-value':
            minRate = itemValue * 0.03;
            maxRate = itemValue * 0.05;
            break;
        case 'mid-value':
            minRate = itemValue * 0.02;
            maxRate = itemValue * 0.03;
            break;
        case 'high-value':
            minRate = itemValue * 0.01;
            maxRate = itemValue * 0.02;
            break;
    }

    return { minRate, maxRate };
}

export function calculateDepositFee(itemValue) {
    const category = getValueCategory(itemValue);
    
    let minRate, maxRate;
    switch (category) {
        case 'low-value':
            minRate = itemValue * 0.20;  // 20% of item value
            maxRate = itemValue * 0.30;  // 30% of item value
            break;
        case 'mid-value':
            minRate = itemValue * 0.25;  // 25% of item value
            maxRate = itemValue * 0.35;  // 35% of item value
            break;
        case 'high-value':
            minRate = itemValue * 0.30;  // 30% of item value
            maxRate = itemValue * 0.40;  // 40% of item value
            break;
    }

    return { minRate, maxRate };
}