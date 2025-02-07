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
            minRate = itemValue * 0.01;  // 1%
            maxRate = itemValue * 0.02;  // 2%
            break;
        case 'mid-value':
            minRate = itemValue * 0.0075;  // 0.75%
            maxRate = itemValue * 0.015;   // 1.5%
            break;
        case 'high-value':
            minRate = itemValue * 0.005;   // 0.5%
            maxRate = itemValue * 0.01;    // 1%
            break;
    }

    return { minRate, maxRate };
}

export function calculateDepositFee(itemValue) {
    const category = getValueCategory(itemValue);
    
    let minRate, maxRate;
    switch (category) {
        case 'low-value':
            minRate = itemValue * 0.10;  // 10%
            maxRate = itemValue * 0.15;  // 15%
            break;
        case 'mid-value':
            minRate = itemValue * 0.15;  // 15%
            maxRate = itemValue * 0.20;  // 20%
            break;
        case 'high-value':
            minRate = itemValue * 0.20;  // 20%
            maxRate = itemValue * 0.25;  // 25%
            break;
    }

    return { minRate, maxRate };
}