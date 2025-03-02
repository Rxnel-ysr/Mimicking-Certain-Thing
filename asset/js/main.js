import _ from "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js";
/**
 * Generates a specified number of password input fields dynamically.
 *
 * This function retrieves the count of password fields to generate from the input
 * element with id "encryptCount", clears any existing password fields, and creates
 * new password fields based on the specified count.
 *
 * @return void This function does not return a value; it directly modifies the DOM.
 */
function generatePasswordFields() {
  const count = document.getElementById("encryptCount").value,
    passwordFieldsContainer = document.getElementById("passwordFields");

  passwordFieldsContainer.innerHTML = "";

  for (let i = 0; i < count; i++) {
    const fieldHTML = `
                <div class="mb-3 password-field">
                    <label for="password${i}" class="form-label">Password ${
      i + 1
    }:</label>
                    <input type="text" name="password[]" id="password${i}" class="form-control" oninput="nextPasswordField(${i})" required>
                </div>`;
    passwordFieldsContainer.insertAdjacentHTML("beforeend", fieldHTML);
  }
}

/**
 * Displays the next password input field when the current field is filled.
 *
 * This function checks the current password input field for a value. If a value is
 * present and it is not the last field, it dynamically generates the next password
 * input field.
 *
 * @param {number} index The index of the current password field.
 * @return void This function does not return a value; it modifies the DOM.
 */
function nextPasswordField(index) {
  const currentInput = document.getElementById(`password${index}`);
  if (currentInput && currentInput.value.trim()) {
    const totalFields = document.getElementById("encryptCount").value;
    if (index + 1 < totalFields) {
      const nextIndex = index + 1;
      if (!document.getElementById(`password${nextIndex}`)) {
        const nextFieldHTML = `
                        <div class="mb-3 password-field">
                            <label for="password${nextIndex}" class="form-label">Password ${
          nextIndex + 1
        }:</label>
                            <input type="password" name="password[]" id="password${nextIndex}" class="form-control" oninput="nextPasswordField(${nextIndex})" required>
                        </div>`;
        document
          .getElementById("passwordFields")
          .insertAdjacentHTML("beforeend", nextFieldHTML);
      }
    }
  }
}

document.addEventListener("keydown", function (event) {
  const activeElement = document.activeElement;
  if (activeElement.matches('input[type="password"]')) {
    const index = parseInt(activeElement.id.replace("password", ""));
    // const totalFields = document.getElementById("encryptCount").value;

    if (event.key === "ArrowDown") {
      event.preventDefault();
      const nextField = document.getElementById(`password${index + 1}`);
      if (nextField) {
        nextField.focus();
      }
    } else if (event.key === "ArrowUp") {
      event.preventDefault();
      const prevField = document.getElementById(`password${index - 1}`);
      if (prevField) {
        prevField.focus();
      }
    }
  }
});
