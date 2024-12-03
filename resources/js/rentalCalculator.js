import calculateDailyRate from "@/suggestRate";

export function calculateRentalPrice(itemValue, rentalDays) {
    let discountRate = 0;
    let valueCategory = "";

    if (itemValue < 25000) {
        valueCategory = "low-value";
    } else if (itemValue <= 100000) {
        valueCategory = "mid-value";
    } else {
        valueCategory = "high-value";
    }

    if (valueCategory === "low-value") {
        if (rentalDays >= 7 && rentalDays <= 29) {
            discountRate = 0.1; // 10%
        } else if (rentalDays >= 30) {
            discountRate = 0.25; // 25%
        }
    } else if (valueCategory === "mid-value") {
        if (rentalDays >= 7 && rentalDays <= 29) {
            discountRate = 0.2; // 20%
        } else if (rentalDays >= 30) {
            discountRate = 0.35; // 35%
        }
    } else if (valueCategory === "high-value") {
        if (rentalDays >= 7 && rentalDays <= 29) {
            discountRate = 0.25; // 25%
        } else if (rentalDays >= 30) {
            discountRate = 0.5; // 50%
        }
    }

    const basePrice = itemValue * rentalDays;
    const discount = basePrice * discountRate;
    const fee = basePrice * 0.25; // 25% of the base price
    const totalPrice = basePrice - discount + fee;

    return {
        basePrice,
        discount,
        fee,
        totalPrice,
    };
}

export function formatCurrency(value) {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "PHP",
    }).format(value);
}
