const editForm = document.querySelector("#animal-edit-form");

if (editForm) {
    const requiredFields = editForm.querySelectorAll(
        'input[type="text"][required]'
    );

    const priceInput = document.querySelector("#price");

    function validateRequiredField(input) {
        input.setCustomValidity(
            input.value.trim() === ""
                ? "Enter a value, spaces alone are not accepted."
                : ""
        );
    }

    function validatePrice() {
        const text = priceInput.value.trim();
        let message = "";

        if (text !== "") {
            const value = Number(text);

            if (
                !Number.isFinite(value) ||
                value < 0 ||
                value > 999999999.99
            ) {
                message = "Enter a (reasonable) price between 0 and 999999999.99.";
            }
        }

        priceInput.setCustomValidity(message);
    }

    for (const input of requiredFields) {
        input.addEventListener("input", () => {
            validateRequiredField(input);
        });
    }

    priceInput.addEventListener("input", validatePrice);

    editForm.addEventListener("submit", (event) => {
        for (const input of requiredFields) {
            validateRequiredField(input);
        }

        validatePrice();

        if (!editForm.reportValidity()) {
            event.preventDefault();
        }
    });
}