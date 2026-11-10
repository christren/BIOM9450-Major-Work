const registrationForm = document.querySelector("#customer-register-form");
const requiredInputs = registrationForm.querySelectorAll("input[required]");
const phoneInput = document.querySelector("#phone");

function validateRegistration() {
    for (const input of requiredInputs) {
        input.setCustomValidity(
            input.value.trim() === ""
                ? "Enter a value, spaces alone are not accepted."
                : ""
        );
    }

    const phone = phoneInput.value.trim();
    const digits = phone.replace(/\D/g, "");

    let message = "";

    if (
        phone !== "" &&
        (
            !/^[+0-9(). -]+$/.test(phone) ||
            digits.length < 7 ||
            digits.length > 15
        )
    ) {
        message =
            "Enter 7–15 digits. Spaces, +, parentheses, dots and hyphens are allowed.";
    }

    phoneInput.setCustomValidity(message);
}

registrationForm.addEventListener("input", validateRegistration);

registrationForm.addEventListener("submit", (event) => {
    validateRegistration();

    if (!registrationForm.reportValidity()) {
        event.preventDefault();
    }
});