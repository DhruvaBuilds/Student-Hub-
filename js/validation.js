/* ==========================================================================
   StudentHub – client-side form validation (Practicals 5 and 9)
   • Login, Registration and Contact forms
   • Regular expressions identical to the PHP rules in includes/helpers.php
   • Errors appear next to each field, in real time (blur / input), and are
     announced to screen readers (aria-invalid + role="alert").
   • If everything is valid the form is submitted normally to PHP.
   ========================================================================== */

(function () {
    "use strict";

    var patterns = {
        name:     /^[A-Za-z ]{2,100}$/,
        email:    /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        phone:    /^[6-9]\d{9}$/,
        password: /^(?=.*[A-Za-z])(?=.*\d).{8,72}$/
    };

    // ---------- error helpers ----------
    function clearError(input) {
        input.classList.remove("input-error");
        input.removeAttribute("aria-invalid");
        input.removeAttribute("aria-describedby");
        var holder = input.closest(".form-group");
        if (holder) {
            holder.querySelectorAll(".error-message").forEach(function (p) { p.remove(); });
        }
    }

    function showError(input, message) {
        clearError(input);
        input.classList.add("input-error");
        input.setAttribute("aria-invalid", "true");

        var p = document.createElement("p");
        p.className = "error-message";
        p.id = (input.id || input.name) + "-error";
        p.setAttribute("role", "alert");
        p.textContent = "❌ " + message;

        var holder = input.closest(".form-group") || input.parentElement;
        holder.appendChild(p);
        input.setAttribute("aria-describedby", p.id);
    }

    // Each rule returns an error string, or "" when the value is fine.
    var rules = {
        fullname: function (v) {
            if (v === "") return "Please enter your full name.";
            if (!patterns.name.test(v)) return "Name should contain only letters and spaces.";
            return "";
        },
        name: function (v) { return rules.fullname(v).replace("full name", "name"); },
        email: function (v) {
            if (v === "") return "Please enter your email address.";
            if (!patterns.email.test(v)) return "Please enter a valid email address.";
            return "";
        },
        phone: function (v) {
            if (v === "") return "Please enter your mobile number.";
            if (!patterns.phone.test(v)) return "Please enter a valid 10-digit mobile number.";
            return "";
        },
        course: function (v) { return v === "" ? "Please select your course." : ""; },
        year: function (v) { return v === "" ? "Please select your year." : ""; },
        password: function (v) {
            if (v === "") return "Please enter a password.";
            if (!patterns.password.test(v)) return "Password must be 8-72 characters and contain a letter and a number.";
            return "";
        },
        "confirm-password": function (v, form) {
            if (v === "") return "Please confirm your password.";
            if (v !== form.querySelector("#password").value) return "Passwords do not match.";
            return "";
        },
        subject: function (v) {
            if (v === "") return "Please enter a subject.";
            if (v.length < 3 || v.length > 120) return "Subject must be 3 to 120 characters.";
            return "";
        },
        message: function (v) {
            if (v === "") return "Please write your message.";
            if (v.length < 10 || v.length > 1000) return "Message must be 10 to 1000 characters.";
            return "";
        }
    };

    function validateField(form, input) {
        var rule = rules[input.id];
        if (!rule) { return true; }
        var value = (input.type === "password") ? input.value : input.value.trim();
        var msg = rule(value, form);
        if (msg) { showError(input, msg); return false; }
        clearError(input);
        return true;
    }

    function validateExtras(form) {
        var ok = true;

        // gender radios
        var radios = form.querySelectorAll('input[name="gender"]');
        if (radios.length) {
            var group = radios[0].closest(".form-group");
            group.querySelectorAll(".error-message").forEach(function (p) { p.remove(); });
            if (!form.querySelector('input[name="gender"]:checked')) {
                var p = document.createElement("p");
                p.className = "error-message";
                p.setAttribute("role", "alert");
                p.textContent = "❌ Please select your gender.";
                group.appendChild(p);
                ok = false;
            }
        }

        // terms checkbox
        var terms = form.querySelector("#terms");
        if (terms) {
            var holder = terms.closest(".form-group");
            holder.querySelectorAll(".error-message").forEach(function (p) { p.remove(); });
            if (!terms.checked) {
                var t = document.createElement("p");
                t.className = "error-message";
                t.setAttribute("role", "alert");
                t.textContent = "❌ Please accept the Terms and Conditions.";
                holder.appendChild(t);
                ok = false;
            }
        }
        return ok;
    }

    // Attach validation to a form: real-time + on submit
    function wire(form, fieldIds) {
        var inputs = fieldIds
            .map(function (id) { return form.querySelector("#" + id); })
            .filter(Boolean);

        inputs.forEach(function (input) {
            input.addEventListener("blur", function () { validateField(form, input); });
            input.addEventListener("input", function () {
                // Re-check immediately only when a message is already showing (no nagging while typing)
                if (input.classList.contains("input-error")) { validateField(form, input); }
                if (input.id === "password") {
                    var confirm = form.querySelector("#confirm-password");
                    if (confirm && confirm.value !== "") { validateField(form, confirm); }
                }
            });
            if (input.tagName === "SELECT") {
                input.addEventListener("change", function () { validateField(form, input); });
            }
        });

        form.addEventListener("submit", function (event) {
            var allValid = true;
            var firstBad = null;
            inputs.forEach(function (input) {
                if (!validateField(form, input)) {
                    allValid = false;
                    if (!firstBad) { firstBad = input; }
                }
            });
            if (!validateExtras(form)) { allValid = false; }

            if (!allValid) {
                event.preventDefault();                 // block the submit, show the errors
                if (firstBad) { firstBad.focus(); }
            }
            // else: let the browser POST the form to PHP (server validates again)
        });
    }

    // ---------- password strength meter ----------
    function strengthOf(pw) {
        var score = 0;
        if (pw.length >= 8) score++;
        if (pw.length >= 12) score++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
        if (/\d/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        return Math.min(score, 4);                       // 0..4
    }

    function wireStrength(form) {
        var pw = form.querySelector("#password");
        var box = form.querySelector("#password-strength");
        var fill = form.querySelector("#strength-fill");
        var text = form.querySelector("#strength-text");
        if (!pw || !box || !fill || !text) { return; }

        var labels = ["Very weak", "Weak", "Fair", "Good", "Strong"];
        var classes = ["s0", "s1", "s2", "s3", "s4"];

        pw.addEventListener("input", function () {
            if (pw.value === "") { box.hidden = true; return; }
            box.hidden = false;
            var s = strengthOf(pw.value);
            fill.className = classes[s];
            text.textContent = "Password strength: " + labels[s];
        });
    }

    // ---------- login form ----------
    var loginForm = document.querySelector(".login-form");
    if (loginForm) {
        // Login only needs "not empty + looks like an e-mail"; the server decides the rest.
        var loginRules = {
            email: rules.email,
            password: function (v) { return v === "" ? "Please enter your password." : ""; }
        };
        var loginEmail = loginForm.querySelector("#email");
        var loginPass = loginForm.querySelector("#password");
        loginForm.addEventListener("submit", function (event) {
            var ok = true;
            [[loginEmail, loginRules.email], [loginPass, loginRules.password]].forEach(function (pair) {
                var msg = pair[1](pair[0].value.trim());
                if (msg) { showError(pair[0], msg); ok = false; } else { clearError(pair[0]); }
            });
            if (!ok) { event.preventDefault(); }
        });
    }

    // ---------- register form ----------
    var registerForm = document.querySelector(".register-form");
    if (registerForm) {
        wire(registerForm, ["fullname", "email", "phone", "course", "year", "password", "confirm-password"]);
        wireStrength(registerForm);
        registerForm.querySelectorAll('input[name="gender"]').forEach(function (r) {
            r.addEventListener("change", function () { validateExtras(registerForm); });
        });
        var terms = registerForm.querySelector("#terms");
        if (terms) { terms.addEventListener("change", function () { validateExtras(registerForm); }); }
    }

    // ---------- contact form ----------
    var contactForm = document.querySelector(".contact-form");
    if (contactForm) {
        wire(contactForm, ["name", "email", "subject", "message"]);
    }
})();
