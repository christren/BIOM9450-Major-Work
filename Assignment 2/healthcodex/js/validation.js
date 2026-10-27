// Health Codex - validations and DOM interactions
"use strict";

// Display an error message, or clear it when the field is valid
function showError(fieldId, message) {
  const field = document.getElementById(fieldId);
  const error = document.getElementById(fieldId + "Error");

  error.textContent = message;
  field.setAttribute("aria-invalid", message ? "true" : "false");

  return message === "";
}

// Names: letters, spaces, apostrophes and hyphens
// Require at least one letter so spaces or punctuation alone cannot pass
function validateName(fieldId) {
  const value = document.getElementById(fieldId).value.trim();
  const allowedCharacters = /^[A-Za-z\s'’-]+$/;

  if (value === "") {
    return showError(fieldId, "Please enter your name.");
  }

  if (!allowedCharacters.test(value) || !/[A-Za-z]/.test(value)) {
    return showError(
      fieldId,
      "Use letters, spaces, apostrophes or hyphens only."
    );
  }

  return showError(fieldId, "");
}

// Email: use the simplified rules given in the assignment
function validateEmail() {
  const value = document.getElementById("email").value.trim();
  const emailPattern = /^[A-Za-z0-9._-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/;

  if (!emailPattern.test(value)) {
    return showError("email", "Enter a valid email, such as johndoe@example.com.");
  }

  return showError("email", "");
}

// Password: at least 8 characters, with uppercase, lowercase and a number
function validatePassword() {
  const value = document.getElementById("password").value;

  if (
    value.length < 8 ||
    !/[A-Z]/.test(value) ||
    !/[a-z]/.test(value) ||
    !/[0-9]/.test(value)
  ) {
    return showError(
      "password",
      "ERROR: Please use at least 8 characters, including uppercase, lowercase and a number."
    );
  }

  return showError("password", "");
}

// The repeated password must match the original
function validateConfirmPassword() {
  const password = document.getElementById("password").value;
  const confirmation = document.getElementById("confirmPassword").value;

  if (confirmation === "") {
    return showError("confirmPassword", "Please re-enter your password.");
  }

  if (confirmation !== password) {
    return showError("confirmPassword", "Passwords do not match.");
  }

  return showError("confirmPassword", "");
}

// DOB: correct format, real calendar date, not future, age 0–120
function validateDOB() {
  const value = document.getElementById("dob").value.trim();

  if (!/^\d{2}\/\d{2}\/\d{4}$/.test(value)) {
    return showError("dob", "Use dd/mm/yyyy, for example 04/04/2005.");
  }

  const parts = value.split("/");
  const day = Number(parts[0]);
  const month = Number(parts[1]);
  const year = Number(parts[2]);

  const birthDate = new Date();
  birthDate.setHours(0, 0, 0, 0);
  birthDate.setFullYear(year, month - 1, day);

  // JavaScript can roll impossible dates into another month
  // Therefore compare the resulting date with the original numbers
  if (
    birthDate.getFullYear() !== year ||
    birthDate.getMonth() !== month - 1 ||
    birthDate.getDate() !== day
  ) {
    return showError("dob", "Enter a real calendar date.");
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (birthDate > today) {
    return showError("dob", "Date of birth cannot be in the future.");
  }

  let age = today.getFullYear() - year;

  const birthdayNotReached =
    today.getMonth() < month - 1 ||
    (
      today.getMonth() === month - 1 &&
      today.getDate() < day
    );

  if (birthdayNotReached) {
    age = age - 1;
  }

  if (age < 0 || age > 120) {
    return showError("dob", "You are not that old, age must be between 0 and 120 years.");
  }

  return showError("dob", "");
}

// Check that a required text field or dropdown is not empty
function validateRequired(fieldId, message) {
  const value = document.getElementById(fieldId).value.trim();

  if (value === "") {
    return showError(fieldId, message);
  }

  return showError(fieldId, "");
}

// Connect the validation functions to the registration form
const registrationForm = document.getElementById("registrantInfo");

// This file also loads on the confirmation page
// "blur" event happens when a field loses focus- either a tab out or click away this is when the listener tells browser to run validateEmail or other validations
if (registrationForm) {
  document.getElementById("firstName").addEventListener("blur", function () {
    validateName("firstName");
  });

  document.getElementById("lastName").addEventListener("blur", function () {
    validateName("lastName");
  });

  document.getElementById("email").addEventListener("blur", validateEmail);

  document.getElementById("password").addEventListener("blur", function () {
    validatePassword();

    // Recheck an existing confirmation if the original password changes
    if (document.getElementById("confirmPassword").value !== "") {
      validateConfirmPassword();
    }
  });

  document.getElementById("confirmPassword").addEventListener(
    "blur",
    validateConfirmPassword
  );

  document.getElementById("dob").addEventListener("blur", validateDOB);

  document.getElementById("gender").addEventListener("change", function () {
    validateRequired("gender", "Please select a gender option.");
  });

  document.getElementById("affiliation").addEventListener("blur", function () {
    validateRequired("affiliation", "Enter an affiliation or Independent.");
  });

  document.getElementById("dietary").addEventListener("change", function () {
    validateRequired("dietary", "Please select a dietary preference.");
  });

  // Reset also clears messages added by JavaScript
  registrationForm.addEventListener("reset", function () {
    const errorMessages = registrationForm.querySelectorAll(".error");

    errorMessages.forEach(function (error) {
      error.textContent = "";
    });

    const invalidFields = registrationForm.querySelectorAll("[aria-invalid]");

    invalidFields.forEach(function (field) {
      field.removeAttribute("aria-invalid");
    });
  });
}

// Show the extra dietary field only when "Other" is selected
function updateDietaryField() {
  const dietary = document.getElementById("dietary");
  const group = document.getElementById("otherDietaryGroup");
  const field = document.getElementById("otherDietary");

  const isOther = dietary.value === "Other";

  group.hidden = !isOther;
  field.disabled = !isOther;
  field.required = isOther;

  // Remove an old answer and error when the field is no longer needed
  if (!isOther) {
    field.value = "";
    showError("otherDietary", "");
  }
}

// Only require extra dietary details when "Other" is selected
function validateOtherDietary() {
  const dietary = document.getElementById("dietary").value;

  if (dietary === "Other") {
    return validateRequired(
      "otherDietary",
      "Please describe your dietary requirements."
    );
  }

  return showError("otherDietary", "");
}

// After submit is clicked

// Run every check, including fields the user has not clicked
function validInfo() {
  const results = [
    validateName("firstName"),
    validateName("lastName"),
    validateEmail(),
    validatePassword(),
    validateConfirmPassword(),
    validateDOB(),
    validateRequired("gender", "Please select a gender option."),
    validateRequired("affiliation", "Enter an affiliation or Independent."),
    validateRequired("dietary", "Please select a dietary preference."),
    validateOtherDietary()
  ];

  // Return true only when every validation result is true
  return results.every(function (result) {
    return result === true;
  });
}

// Only run this setup on the registration page
if (registrationForm) {
  const dietary = document.getElementById("dietary");
  const otherDietary = document.getElementById("otherDietary");

  dietary.addEventListener("change", updateDietaryField);
  otherDietary.addEventListener("blur", validateOtherDietary);

  // Set the correct initial state when the page loads
  updateDietaryField();

  registrationForm.addEventListener("submit", function (event) {
    const isValid = validInfo();

    if (!isValid) {
      // Stop the browser from navigating to the confirmation page
      event.preventDefault();

      alert("Please correct the highlighted form errors before registering.");

      const firstInvalidField = registrationForm.querySelector(
        '[aria-invalid="true"]'
      );

      if (firstInvalidField) {
        firstInvalidField.focus();
      }
    }

    // If valid, allow the forms normal GET submission.
  });

  // Reset does not automatically restore hidden/disabled states
  registrationForm.addEventListener("reset", function () {
    document.getElementById("otherDietaryGroup").hidden = true;
    otherDietary.disabled = true;
    otherDietary.required = false;
    otherDietary.value = "";
  });
}

// Display appropriate submitted details only on the confirmation page
const registrationSummary = document.getElementById("registrationSummary");

if (registrationSummary) {
  const params = new URLSearchParams(window.location.search);

  const firstName = (params.get("firstName") || "").trim();
  const lastName = (params.get("lastName") || "").trim();
  const email = (params.get("email") || "").trim();
  const gender = (params.get("gender") || "").trim();

  // Do not show an empty summary if the page was opened directly
  if (firstName && lastName && email && gender) {
    document.getElementById("confirmationMessage").textContent =
      "Thank you, " + firstName +
      ". Your registration details are shown below.";

    document.getElementById("confirmedName").textContent =
      firstName + " " + lastName;

    document.getElementById("confirmedEmail").textContent = email;

    document.getElementById("confirmedGender").textContent = gender;

    document.getElementById("confirmedAffiliation").textContent =
      params.get("affiliation") || "Not provided";

    let dietary = params.get("dietary") || "Not provided";

    if (dietary === "Other") {
      dietary = "Other: " + (params.get("otherDietary") || "Not specified");
    }

    document.getElementById("confirmedDietary").textContent = dietary;

    const wantsMarketing = params.get("marketing") === "yes";

    document.getElementById("confirmedMarketing").textContent =
      wantsMarketing ? "Yes" : "No";

    registrationSummary.hidden = false;
  }
}