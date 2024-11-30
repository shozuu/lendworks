export default function calculateDailyRate(itemValue) {
    let minRate;
    let maxRate;

    if (itemValue < 25000) {
        minRate = itemValue * 0.03;
        maxRate = itemValue * 0.05;
    } else if (itemValue <= 100000) {
        minRate = itemValue * 0.02;
        maxRate = itemValue * 0.03;
    } else {
        minRate = itemValue * 0.01;
        maxRate = itemValue * 0.02;
    }

    return {
        minRate,
        maxRate,
    };
}