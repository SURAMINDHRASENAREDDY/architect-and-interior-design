
import { auth } from "./firebase-config.js";

import {
    createUserWithEmailAndPassword,
    signInWithEmailAndPassword,
    updateProfile,
    getIdToken
} from "https://www.gstatic.com/firebasejs/13.4.0/firebase-auth.js";

// Local PHP backend address.
// We will change this when deploying the backend.
const API_BASE_URL = "https://architect-interior-design-api.onrender.com";

const form = document.getElementById("registration-form");
const submitButton = document.getElementById("submit");

// Create a message area without changing your existing HTML design.
const messageBox = document.createElement("p");
messageBox.id = "auth-message";
messageBox.setAttribute("role", "status");
messageBox.style.marginTop = "15px";
messageBox.style.fontSize = "14px";
messageBox.style.fontWeight = "bold";
form.insertAdjacentElement("afterend", messageBox);

function showMessage(message, isError = false) {
    messageBox.textContent = message;
    messageBox.style.color = isError ? "#b00020" : "#087443";
}

function getFirebaseErrorMessage(error) {
    const messages = {
        "auth/invalid-credential":
            "Incorrect email or password for the existing account.",
        "auth/user-disabled":
            "This Firebase account has been disabled.",
        "auth/email-already-in-use":
            "This email is already registered. Please log in.",
        "auth/invalid-email":
            "Please enter a valid email address.",
        "auth/weak-password":
            "Your password is too weak. Use at least 8 characters.",
        "auth/network-request-failed":
            "Network error. Check your internet connection.",
        "auth/too-many-requests":
            "Too many attempts. Please try again later."
    };

    return messages[error.code] || error.message ||
        "Registration failed. Please try again.";
}

form.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (!form.reportValidity()) {
        return;
    }

    const firstName =
        document.getElementById("firstname").value.trim();

    const lastName =
        document.getElementById("lastname").value.trim();

    const email =
        document.getElementById("email").value.trim().toLowerCase();

    const username =
        document.getElementById("username").value.trim();

    const password =
        document.getElementById("password").value;

    const fullName = `${firstName} ${lastName}`.trim();

    if (fullName.length < 2 || fullName.length > 120) {
        showMessage("Please enter a valid full name.", true);
        return;
    }

    if (!/^[A-Za-z0-9_]{3,50}$/.test(username)) {
        showMessage(
            "Username must be 3–50 characters using letters, numbers or underscores.",
            true
        );
        return;
    }

    if (password.length < 8) {
        showMessage("Password must contain at least 8 characters.", true);
        return;
    }

    submitButton.disabled = true;
    submitButton.textContent = "Registering...";

    let firebaseAccountAuthenticated = false;

    try {
        // 1. Create a new Firebase account or recover the existing one.
        let credential;

        try {
            credential = await createUserWithEmailAndPassword(
                auth,
                email,
                password
            );
        } catch (creationError) {
            if (creationError.code !== "auth/email-already-in-use") {
                throw creationError;
            }

            // Authenticate the existing account instead of creating a duplicate.
            credential = await signInWithEmailAndPassword(
                auth,
                email,
                password
            );
        }

        const user = credential.user;
        firebaseAccountAuthenticated = true;

        // 2. Save the user's display name in Firebase.
        await updateProfile(user, {
            displayName: fullName
        });

        // 3. Obtain the Firebase ID token.
        const idToken = await getIdToken(user, true);

        // 4. Send the verified identity to our PHP backend.
        const response = await fetch(
            `${API_BASE_URL}/api/register`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${idToken}`
                },
                body: JSON.stringify({
                    full_name: fullName,
                    username: username
                })
            }
        );

        const result = await response.json();

        if (!response.ok) {
            throw new Error(
                result.message || "Unable to save your user profile."
            );
        }

        showMessage(
            "Success! Your Firebase account and database profile are synchronized."
        );

        form.reset();

    } catch (error) {
        console.error("Registration error:", error);

        if (firebaseAccountAuthenticated) {
            showMessage(
                `Firebase authentication succeeded, but database profile synchronization failed: ${error.message}. Do not register again; we'll resolve the profile synchronization.`,
                true
            );
        } else {
            showMessage(getFirebaseErrorMessage(error), true);
        }

    } finally {
        submitButton.disabled = false;
        submitButton.textContent = "Submit";
    }
});
