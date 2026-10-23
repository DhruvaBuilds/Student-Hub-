/* ==========================================================================
   StudentHub – global UI behaviour (Practical 4)
   Theme switcher (saved in localStorage), hamburger menu, FAQ accordion,
   notification banner, modal, greeting/date, confirm-before-submit.
   Page specific code lives in: validation.js, events.js, students-ajax.js, admin-dashboard.js
   ========================================================================== */

(function () {
    "use strict";

    // ---------------------------------------------------------------
    // Light / dark theme – remembered between visits (localStorage)
    // ---------------------------------------------------------------
    var THEME_KEY = "studenthub-theme";
    var themeToggle = document.getElementById("theme-toggle");

    function applyTheme(theme) {
        document.body.classList.toggle("dark-mode", theme === "dark");
        if (themeToggle) {
            themeToggle.textContent = theme === "dark" ? "☀️" : "🌙";
            themeToggle.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
        }
    }

    function savedTheme() {
        try { return localStorage.getItem(THEME_KEY); } catch (e) { return null; }
    }

    applyTheme(savedTheme() === "dark" ? "dark" : "light");

    if (themeToggle) {
        themeToggle.addEventListener("click", function () {
            var next = document.body.classList.contains("dark-mode") ? "light" : "dark";
            applyTheme(next);
            try { localStorage.setItem(THEME_KEY, next); } catch (e) { /* private mode */ }
            document.dispatchEvent(new CustomEvent("themechange", { detail: next }));
        });
    }

    // ---------------------------------------------------------------
    // Hamburger menu (small screens)
    // ---------------------------------------------------------------
    var navToggle = document.getElementById("nav-toggle");
    var navMenu = document.getElementById("nav-menu");

    if (navToggle && navMenu) {
        navToggle.addEventListener("click", function () {
            var open = navMenu.classList.toggle("open");
            navToggle.setAttribute("aria-expanded", open ? "true" : "false");
        });
    }

    // ---------------------------------------------------------------
    // Dashboard greeting + today's date
    // ---------------------------------------------------------------
    var greeting = document.getElementById("greeting");
    if (greeting) {
        var hour = new Date().getHours();
        greeting.textContent = hour < 12 ? "Good Morning!" : hour < 18 ? "Good Afternoon!" : "Good Evening!";
    }

    var today = document.getElementById("today");
    if (today) {
        today.textContent = new Date().toDateString();
    }

    // ---------------------------------------------------------------
    // FAQ accordion
    // ---------------------------------------------------------------
    document.querySelectorAll(".faq-question").forEach(function (question) {
        question.setAttribute("aria-expanded", "false");
        question.addEventListener("click", function () {
            var answer = question.nextElementSibling;
            var icon = question.querySelector("span");
            var isOpen = answer.style.display === "block";
            answer.style.display = isOpen ? "none" : "block";
            if (icon) { icon.textContent = isOpen ? "+" : "−"; }
            question.setAttribute("aria-expanded", isOpen ? "false" : "true");
        });
    });

    // ---------------------------------------------------------------
    // Notification banner
    // ---------------------------------------------------------------
    var banner = document.getElementById("notification-banner");
    var closeBanner = document.getElementById("close-notification");
    if (banner && closeBanner) {
        closeBanner.addEventListener("click", function () { banner.style.display = "none"; });
    }

    // ---------------------------------------------------------------
    // Student ID modal (closes with the buttons, the backdrop or Escape)
    // ---------------------------------------------------------------
    var openModal = document.getElementById("open-modal");
    var studentModal = document.getElementById("student-modal");

    function hideModal() { if (studentModal) { studentModal.style.display = "none"; } }

    if (openModal && studentModal) {
        openModal.addEventListener("click", function () {
            studentModal.style.display = "flex";
            var closeBtn = document.getElementById("modal-close-button");
            if (closeBtn) { closeBtn.focus(); }
        });
        ["close-modal", "modal-close-button"].forEach(function (id) {
            var btn = document.getElementById(id);
            if (btn) { btn.addEventListener("click", hideModal); }
        });
        studentModal.addEventListener("click", function (e) { if (e.target === studentModal) { hideModal(); } });
        document.addEventListener("keydown", function (e) { if (e.key === "Escape") { hideModal(); } });
    }

    // ---------------------------------------------------------------
    // <form data-confirm="Are you sure?"> asks before deleting (Practical 11)
    // ---------------------------------------------------------------
    document.querySelectorAll("form[data-confirm]").forEach(function (form) {
        form.addEventListener("submit", function (e) {
            if (!window.confirm(form.getAttribute("data-confirm"))) {
                e.preventDefault();
            }
        });
    });

    // ---------------------------------------------------------------
    // Auto-hide success messages after a few seconds
    // ---------------------------------------------------------------
    document.querySelectorAll(".alert-success").forEach(function (box) {
        setTimeout(function () { box.classList.add("fade-out"); }, 6000);
    });
})();
