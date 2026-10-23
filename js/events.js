/* ==========================================================================
   StudentHub – Events page (Practicals 6 and 13)
   • fetch() the event list from the PHP JSON API
   • search, filter by category, sort and paginate with array methods
   • loading / error / empty states
   • register / cancel registration with a POST (no page reload)
   • latest response cached in localStorage: shown if the network/API fails
   • everything is written to the page with textContent (no innerHTML → no XSS)
   ========================================================================== */

(function () {
    "use strict";

    var container = document.getElementById("events-container");
    if (!container) { return; }

    var searchInput = document.getElementById("event-search");
    var filterSelect = document.getElementById("event-filter");
    var sortSelect = document.getElementById("event-sort");
    var statusBox = document.getElementById("events-status");
    var pager = document.getElementById("events-pagination");

    var SOURCE = container.dataset.source;
    var FALLBACK = container.dataset.fallback;
    var UPLOAD_BASE = container.dataset.uploadBase || "";
    var CAN_REGISTER = container.dataset.canRegister === "1";
    var LOGGED_IN = container.dataset.loggedIn === "1";
    var LOGIN_URL = container.dataset.loginUrl;
    var API_URL = SOURCE.split("?")[0];
    var CACHE_KEY = "studenthub-events-cache";
    var PER_PAGE = 6;

    var allEvents = [];
    var currentPage = 1;

    // ---------------------------------------------------------------
    // small helpers
    // ---------------------------------------------------------------
    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute("content") : "";
    }

    function showStatus(message, type) {
        statusBox.textContent = message || "";
        statusBox.className = "events-status" + (type ? " " + type : "");
    }

    function formatDate(iso) {
        var d = new Date(iso + "T00:00:00");
        return isNaN(d) ? iso : d.toLocaleDateString(undefined, { day: "2-digit", month: "short", year: "numeric" });
    }

    function debounce(fn, wait) {
        var t;
        return function () {
            var args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(null, args); }, wait);
        };
    }

    // ---------------------------------------------------------------
    // loading data (API → cache → static JSON file)
    // ---------------------------------------------------------------
    function normalise(list) {
        return list.map(function (e) {
            return {
                id: Number(e.id),
                title: String(e.title || ""),
                description: String(e.description || ""),
                date: String(e.date || e.event_date || ""),
                location: String(e.location || ""),
                category: String(e.category || ""),
                status: e.status || "open",
                poster: e.poster || "",
                registrations: Number(e.registrations || 0),
                is_registered: Boolean(e.is_registered)
            };
        });
    }

    function readCache() {
        try {
            var raw = localStorage.getItem(CACHE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) { return null; }
    }

    function writeCache(events) {
        try {
            localStorage.setItem(CACHE_KEY, JSON.stringify({ savedAt: Date.now(), events: events }));
        } catch (e) { /* storage full / disabled */ }
    }

    function loadEvents() {
        showStatus("Loading events…", "info");

        fetch(SOURCE, { credentials: "same-origin", headers: { "Accept": "application/json" } })
            .then(function (response) {
                if (!response.ok) { throw new Error("HTTP " + response.status); }
                return response.json();
            })
            .then(function (json) {
                if (!json.success) { throw new Error(json.message || "API error"); }
                allEvents = normalise(json.data);
                writeCache(allEvents);
                showStatus("");
                render();
            })
            .catch(function (error) {
                console.error("Events API failed:", error);
                var cached = readCache();
                if (cached && Array.isArray(cached.events) && cached.events.length) {
                    allEvents = normalise(cached.events);
                    showStatus("You appear to be offline – showing events saved on " +
                        new Date(cached.savedAt).toLocaleString() + ".", "warning");
                    render();
                    return;
                }
                // last resort: the static JSON file from Practical 6
                fetch(FALLBACK)
                    .then(function (r) { if (!r.ok) { throw new Error("HTTP " + r.status); } return r.json(); })
                    .then(function (list) {
                        allEvents = normalise(list);
                        showStatus("Live data unavailable – showing the sample events file.", "warning");
                        render();
                    })
                    .catch(function () {
                        container.textContent = "";
                        container.appendChild(el("p", "error-message", "Unable to load events. Please try again later."));
                        showStatus("");
                        pager.textContent = "";
                    });
            });
    }

    // ---------------------------------------------------------------
    // search + filter + sort (array methods)
    // ---------------------------------------------------------------
    function visibleEvents() {
        var text = searchInput.value.trim().toLowerCase();
        var category = filterSelect.value;
        var sort = sortSelect.value;

        var list = allEvents.filter(function (e) {
            var matchesText = text === "" ||
                e.title.toLowerCase().indexOf(text) !== -1 ||
                e.location.toLowerCase().indexOf(text) !== -1 ||
                e.category.toLowerCase().indexOf(text) !== -1;
            var matchesCategory = category === "all" || e.category === category;
            return matchesText && matchesCategory;
        });

        list.sort(function (a, b) {
            switch (sort) {
                case "date-desc":  return new Date(b.date) - new Date(a.date);
                case "title-asc":  return a.title.localeCompare(b.title);
                case "title-desc": return b.title.localeCompare(a.title);
                default:           return new Date(a.date) - new Date(b.date);   // date-asc
            }
        });
        return list;
    }

    // ---------------------------------------------------------------
    // rendering
    // ---------------------------------------------------------------
    function buildCard(ev) {
        var card = el("article", "card event-card");

        if (ev.poster && UPLOAD_BASE) {
            var img = el("img", "event-poster");
            img.src = UPLOAD_BASE + encodeURIComponent(ev.poster);
            img.alt = "Poster for " + ev.title;
            img.loading = "lazy";
            card.appendChild(img);
        }

        card.appendChild(el("h3", "", ev.title));
        card.appendChild(el("span", "badge badge-" + (ev.status === "open" ? "open" : "closed"),
            ev.status === "open" ? "Registration open" : "Registration closed"));

        function line(label, value) {
            var p = el("p");
            p.appendChild(el("strong", "", label + " "));
            p.appendChild(document.createTextNode(value));
            card.appendChild(p);
        }
        line("Date:", formatDate(ev.date));
        line("Category:", ev.category);
        line("Location:", ev.location);

        if (ev.description) { card.appendChild(el("p", "event-desc", ev.description)); }
        if (ev.registrations) { card.appendChild(el("p", "muted", ev.registrations + " registered")); }

        if (ev.status === "open") {
            if (CAN_REGISTER) {
                var btn = el("button", "btn " + (ev.is_registered ? "btn-outline" : "primary-btn"),
                    ev.is_registered ? "✓ Registered (click to cancel)" : "Register");
                btn.type = "button";
                btn.addEventListener("click", function () { toggleRegistration(ev, btn); });
                card.appendChild(btn);
            } else if (!LOGGED_IN) {
                var link = el("a", "btn primary-btn", "Login to register");
                link.href = LOGIN_URL;
                card.appendChild(link);
            }
        }
        return card;
    }

    function render() {
        var list = visibleEvents();
        var totalPages = Math.max(1, Math.ceil(list.length / PER_PAGE));
        currentPage = Math.min(currentPage, totalPages);

        container.textContent = "";

        if (list.length === 0) {
            container.appendChild(el("p", "", "No events found."));
            pager.textContent = "";
            return;
        }

        var start = (currentPage - 1) * PER_PAGE;
        list.slice(start, start + PER_PAGE).forEach(function (ev) {
            container.appendChild(buildCard(ev));
        });
        renderPager(totalPages, list.length);
    }

    function renderPager(totalPages, totalItems) {
        pager.textContent = "";
        if (totalPages <= 1) { return; }

        function pageButton(label, page, opts) {
            opts = opts || {};
            var b = el("button", opts.current ? "current" : "", label);
            b.type = "button";
            if (opts.current) { b.setAttribute("aria-current", "page"); }
            if (opts.disabled) { b.disabled = true; }
            b.addEventListener("click", function () {
                currentPage = page;
                render();
                container.scrollIntoView({ behavior: "smooth", block: "start" });
            });
            return b;
        }

        pager.appendChild(pageButton("« Prev", currentPage - 1, { disabled: currentPage === 1 }));
        for (var p = 1; p <= totalPages; p++) {
            pager.appendChild(pageButton(String(p), p, { current: p === currentPage }));
        }
        pager.appendChild(pageButton("Next »", currentPage + 1, { disabled: currentPage === totalPages }));
        pager.appendChild(el("span", "pager-info", totalItems + " events"));
    }

    // ---------------------------------------------------------------
    // register / cancel (POST to the JSON API)
    // ---------------------------------------------------------------
    function toggleRegistration(ev, button) {
        var action = ev.is_registered ? "unregister" : "register";
        button.disabled = true;
        button.textContent = "Please wait…";

        fetch(API_URL, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-Token": csrfToken()
            },
            body: JSON.stringify({ action: action, event_id: ev.id })
        })
            .then(function (response) {
                return response.json().then(function (json) { return { status: response.status, json: json }; });
            })
            .then(function (res) {
                if (res.status === 401) {
                    window.location.href = LOGIN_URL;
                    return;
                }
                if (!res.json.success) { throw new Error(res.json.message || "Request failed"); }
                ev.is_registered = action === "register";
                ev.registrations = Math.max(0, ev.registrations + (ev.is_registered ? 1 : -1));
                writeCache(allEvents);
                render();
                showStatus(res.json.message, "success");
            })
            .catch(function (error) {
                showStatus(error.message || "Something went wrong.", "error");
                render();
            });
    }

    // ---------------------------------------------------------------
    // wire controls
    // ---------------------------------------------------------------
    var onSearch = debounce(function () { currentPage = 1; render(); }, 250);
    searchInput.addEventListener("input", onSearch);
    filterSelect.addEventListener("change", function () { currentPage = 1; render(); });
    sortSelect.addEventListener("change", function () { currentPage = 1; render(); });

    loadEvents();
})();
