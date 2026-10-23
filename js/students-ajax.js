/* ==========================================================================
   StudentHub – AJAX student CRUD (Practical 13)
   list / search (debounced) / filter / paginate / add / edit / delete / restore
   using fetch() against api/students.php – the page never reloads.
   ========================================================================== */

(function () {
    "use strict";

    var app = document.getElementById("ajax-app");
    if (!app) { return; }

    var API = app.dataset.api;
    var body = document.getElementById("ajax-body");
    var countBox = document.getElementById("ajax-count");
    var msgBox = document.getElementById("ajax-message");
    var pager = document.getElementById("ajax-pagination");
    var dialog = document.getElementById("student-dialog");
    var form = document.getElementById("student-form");

    var qInput = document.getElementById("ajax-q");
    var courseSel = document.getElementById("ajax-course");
    var yearSel = document.getElementById("ajax-year");
    var statusSel = document.getElementById("ajax-status");

    var fields = ["name", "email", "phone", "course", "year", "gender", "status"];
    var page = 1;
    var msgTimer;

    function el(tag, className, text) {
        var n = document.createElement(tag);
        if (className) { n.className = className; }
        if (text !== undefined) { n.textContent = text; }
        return n;
    }

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute("content") : "";
    }

    function debounce(fn, wait) {
        var t;
        return function () { clearTimeout(t); t = setTimeout(fn, wait); };
    }

    function message(text, type) {
        clearTimeout(msgTimer);
        msgBox.hidden = !text;
        msgBox.textContent = text || "";
        msgBox.className = "alert alert-" + (type || "info");
        if (text && type === "success") {
            msgTimer = setTimeout(function () { msgBox.hidden = true; }, 5000);
        }
    }

    // One place that talks to the API and understands its JSON + status codes
    function api(url, options) {
        return fetch(url, Object.assign({
            credentials: "same-origin",
            headers: { "Accept": "application/json" }
        }, options)).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (response.status === 401) {
                    window.location.href = document.body.dataset.baseUrl + "/pages/login.php";
                    throw new Error("Session expired – redirecting to login.");
                }
                if (!response.ok || json.success === false) {
                    var err = new Error(json.message || "Request failed (" + response.status + ")");
                    err.errors = json.errors || {};
                    err.status = response.status;
                    throw err;
                }
                return json;
            });
        });
    }

    function post(payload) {
        return api(API, {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-Token": csrf() },
            body: JSON.stringify(payload)
        });
    }

    // ---------------- list ----------------
    function query() {
        var p = new URLSearchParams({ action: "list", page: String(page), per_page: "10" });
        if (qInput.value.trim()) { p.set("q", qInput.value.trim()); }
        if (courseSel.value) { p.set("course", courseSel.value); }
        if (yearSel.value) { p.set("year", yearSel.value); }
        if (statusSel.value) { p.set("status", statusSel.value); }
        return p.toString();
    }

    function load() {
        body.textContent = "";
        var row = el("tr"); var cell = el("td", "empty-row", "Loading…"); cell.colSpan = 7; row.appendChild(cell); body.appendChild(row);

        api(API + "?" + query())
            .then(function (json) {
                var d = json.data;
                page = d.pagination.page;
                render(d.items);
                countBox.textContent = d.pagination.total + " student" + (d.pagination.total === 1 ? "" : "s") + " found";
                renderPager(d.pagination.total_pages);
            })
            .catch(function (err) {
                body.textContent = "";
                var r = el("tr"); var c = el("td", "empty-row error-message", "Could not load students: " + err.message);
                c.colSpan = 7; r.appendChild(c); body.appendChild(r);
                message(err.message, "error");
            });
    }

    function actionButton(label, cls, handler) {
        var b = el("button", "btn btn-sm " + cls, label);
        b.type = "button";
        b.addEventListener("click", handler);
        return b;
    }

    function render(items) {
        body.textContent = "";
        if (!items.length) {
            var r = el("tr"); var c = el("td", "empty-row", "No students match your search."); c.colSpan = 7;
            r.appendChild(c); body.appendChild(r);
            return;
        }
        items.forEach(function (s) {
            var tr = el("tr", s.status === "deleted" ? "row-deleted" : "");
            [s.student_code, s.name, s.email, s.course, s.year].forEach(function (v) { tr.appendChild(el("td", "", String(v))); });

            var statusCell = el("td");
            statusCell.appendChild(el("span", "badge badge-" + s.status, s.status.charAt(0).toUpperCase() + s.status.slice(1)));
            tr.appendChild(statusCell);

            var actions = el("td", "actions");
            if (s.status === "deleted") {
                actions.appendChild(actionButton("Restore", "btn-outline", function () { restoreStudent(s); }));
            } else {
                actions.appendChild(actionButton("Edit", "btn-outline", function () { openDialog(s); }));
                actions.appendChild(actionButton("Delete", "btn-danger", function () { deleteStudent(s); }));
            }
            tr.appendChild(actions);
            body.appendChild(tr);
        });
    }

    function renderPager(totalPages) {
        pager.textContent = "";
        if (totalPages <= 1) { return; }
        function btn(label, p, current, disabled) {
            var b = el("button", current ? "current" : "", label);
            b.type = "button"; b.disabled = !!disabled;
            if (current) { b.setAttribute("aria-current", "page"); }
            b.addEventListener("click", function () { page = p; load(); });
            return b;
        }
        pager.appendChild(btn("« Prev", page - 1, false, page === 1));
        for (var i = 1; i <= totalPages; i++) { pager.appendChild(btn(String(i), i, i === page, false)); }
        pager.appendChild(btn("Next »", page + 1, false, page === totalPages));
    }

    // ---------------- add / edit dialog ----------------
    function clearErrors() {
        form.querySelectorAll(".error-message").forEach(function (p) { p.remove(); });
        form.querySelectorAll(".input-error").forEach(function (i) { i.classList.remove("input-error"); i.removeAttribute("aria-invalid"); });
    }

    function showFieldErrors(errors) {
        Object.keys(errors).forEach(function (name) {
            var input = document.getElementById("f-" + name);
            if (!input) { return; }
            input.classList.add("input-error");
            input.setAttribute("aria-invalid", "true");
            var p = el("p", "error-message", "❌ " + errors[name]);
            p.setAttribute("role", "alert");
            input.parentElement.appendChild(p);
        });
    }

    function openDialog(student) {
        clearErrors();
        form.reset();
        document.getElementById("f-id").value = student ? student.id : "";
        document.getElementById("dialog-title").textContent = student ? "Edit Student" : "Add Student";
        fields.forEach(function (f) {
            var input = document.getElementById("f-" + f);
            input.value = student ? String(student[f]) : (f === "status" ? "active" : "");
        });
        dialog.showModal();
        document.getElementById("f-name").focus();
    }

    document.getElementById("btn-add").addEventListener("click", function () { openDialog(null); });
    document.getElementById("btn-cancel").addEventListener("click", function () { dialog.close(); });

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        clearErrors();

        var payload = {};
        fields.forEach(function (f) { payload[f] = document.getElementById("f-" + f).value.trim(); });
        var id = document.getElementById("f-id").value;
        payload.action = id ? "update" : "create";
        if (id) { payload.id = Number(id); }

        var save = document.getElementById("btn-save");
        save.disabled = true; save.textContent = "Saving…";

        post(payload)
            .then(function (json) {
                dialog.close();
                message(json.message, "success");
                load();
            })
            .catch(function (err) {
                if (err.errors && Object.keys(err.errors).length) { showFieldErrors(err.errors); }
                else { message(err.message, "error"); }
            })
            .then(function () { save.disabled = false; save.textContent = "Save"; });
    });

    // ---------------- delete / restore ----------------
    function deleteStudent(s) {
        if (!window.confirm("Delete " + s.name + "? You can restore them later.")) { return; }
        post({ action: "delete", id: s.id })
            .then(function (json) { message(json.message, "success"); load(); })
            .catch(function (err) { message(err.message, "error"); });
    }

    function restoreStudent(s) {
        post({ action: "restore", id: s.id })
            .then(function (json) { message(json.message, "success"); load(); })
            .catch(function (err) { message(err.message, "error"); });
    }

    // ---------------- filters ----------------
    var onType = debounce(function () { page = 1; load(); }, 300);   // debounced AJAX search
    qInput.addEventListener("input", onType);
    [courseSel, yearSel, statusSel].forEach(function (s) {
        s.addEventListener("change", function () { page = 1; load(); });
    });

    load();
})();
