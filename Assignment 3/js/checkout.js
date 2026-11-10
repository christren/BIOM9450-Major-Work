const checkoutForm = document.querySelector("#checkout-form");

if (checkoutForm) {
    const quantities = checkoutForm.querySelectorAll(".checkout-quantity");
    const totalOutput = document.querySelector("#checkout-total");

    function updateEstimate() {
        let totalCents = 0;
        let selectedItems = 0;

        for (const input of quantities) {
            const quantity = Number(input.value);
            const unitCents = Number(input.dataset.cents);

            if (
                Number.isInteger(quantity) &&
                quantity > 0 &&
                quantity <= 9999
            ) {
                selectedItems += 1;
                totalCents += quantity * unitCents;
            }
        }

        totalOutput.textContent =
            "$" + (totalCents / 100).toLocaleString("en-AU", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

        return selectedItems;
    }

    checkoutForm.addEventListener("input", updateEstimate);

    checkoutForm.addEventListener("submit", (event) => {
        if (updateEstimate() === 0) {
            event.preventDefault();
            alert("Choose at least one merchandise item.");
        }
    });

    updateEstimate();
}