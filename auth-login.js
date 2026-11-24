
import { auth } from "./firebase-config.js";

import {
    signInWithEmailAndPassword
} from "https://www.gstatic.com/firebasejs/13.4.0/firebase-auth.js";

const form = document.getElementById("login-form");
const button = document.getElementById("b1");

const message = document.createElement("p");
message.id = "login-message";
message.setAttribute("role", "status");
message.style.marginTop = "15px";
message.style.fontWeight = "bold";

form.insertAdjacentElement("afterend", message);

function showMessage(text, isError = false) {
    message.textContent = text;
    message.style.color = isError ? "#b00020" : "#087443";
}

form.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (!form.reportValidity()) return;

    const email = document.getElementById("email")
        .value.trim().toLowerCase();

    const password = document.getElementById("password").value;

    button.disabled = true;
    button.textContent = "Logging in...";

    try {
        const result = await signInWithEmailAndPassword(
            auth,
            email,
            password
        );

        // Obtain a Firebase ID token for future API requests.
        await result.user.getIdToken();

        showMessage("Login successful! Redirecting...");

        window.location.assign("./index.html");

    } catch (error) {
        console.error("Login error:", error);

        const errors = {
            "auth/invalid-credential":
                "Incorrect email or password.",
            "auth/user-disabled":
                "This account has been disabled.",
            "auth/too-many-requests":
                "Too many attempts. Please try again later.",
            "auth/network-request-failed":
                "Network error. Check your internet connection."
        };

        showMessage(
            errors[error.code] || "Login failed. Please try again.",
            true
        );

    } finally {
        button.disabled = false;
        button.textContent = "Login";
    }
});

// Navigate to the registration page without submitting credentials.
document.getElementById("a1").addEventListener("click", () => {
    window.location.assign("./registrationpage.html");
});
