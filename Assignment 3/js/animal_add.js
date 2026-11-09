const form = document.querySelector("#animal-form");
const photo = document.querySelector("#photo");
const photoError = document.querySelector("#photo-error");

function validatePhoto() {
    const file = photo.files[0];
    let message = "";

    if (file) {
        const allowedTypes = ["image/jpeg", "image/png", "image/gif"];

        if (!allowedTypes.includes(file.type)) {
            message = "Choose a JPG, PNG or GIF image.";
        } else if (file.size > 2 * 1024 * 1024) {
            message = "The image must be 2 MB or smaller.";
        }
    }

    photo.setCustomValidity(message);
    photoError.textContent = message;
    photo.setAttribute("aria-invalid", String(message !== ""));

    return message === "";
}

photo.addEventListener("change", validatePhoto);

form.addEventListener("submit", (event) => {
    if (!validatePhoto()) {
        event.preventDefault();
        photo.reportValidity();
    }
});