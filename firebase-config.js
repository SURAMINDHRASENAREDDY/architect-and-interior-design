import { initializeApp } from "https://www.gstatic.com/firebasejs/13.4.0/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/13.4.0/firebase-auth.js";
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyBaQ6JHqgUEoq-Y-AdPz42vK1tRJfxdX98",
  authDomain: "architect-interior-design.firebaseapp.com",
  projectId: "architect-interior-design",
  storageBucket: "architect-interior-design.firebasestorage.app",
  messagingSenderId: "123475629940",
  appId: "1:123475629940:web:8cf2a73708f3de9617fe21",
  measurementId: "G-K89DFRTJ24"
};
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

export { app, auth };