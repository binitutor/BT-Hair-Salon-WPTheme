(function ($) {
    "use strict";

    const state = {
        services: [],
        slots: []
    };

    function initializeRevealAnimations() {
        const elements = document.querySelectorAll(".reveal-on-scroll");

        if (!elements.length) {
            return;
        }

        if (!("IntersectionObserver" in window)) {
            elements.forEach((element) => element.classList.add("is-visible"));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.18,
            rootMargin: "0px 0px -40px 0px"
        });

        elements.forEach((element, index) => {
            element.style.transitionDelay = `${index * 120}ms`;
            observer.observe(element);
        });
    }

    function markSiteLoaded() {
        window.setTimeout(() => {
            document.body.classList.add("site-loaded");
            initializeRevealAnimations();
        }, 900);
    }

    async function api(path, options) {
        const response = await fetch(btHairPublic.restUrl + path, options || {});
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || "Request failed.");
        }

        return data;
    }

    function renderServicesGrid() {
        const grid = $("#services-grid");
        grid.empty();

        if (!state.services.length) {
            grid.append('<div class="col-12"><div class="alert alert-warning mb-0">No services configured yet. Please check back soon.</div></div>');
            return;
        }

        state.services.forEach((service) => {
            const card = `
                <div class="col-sm-6 col-lg-4">
                    <article class="service-card p-4 h-100">
                        <h3 class="h5 mb-2">${service.service_name}</h3>
                        <p class="text-muted mb-3">Salon-quality treatment tailored to your look.</p>
                        <div class="fw-bold">$${Number(service.price).toFixed(2)}</div>
                    </article>
                </div>
            `;
            grid.append(card);
        });
    }

    function fillSelects() {
        const serviceSelect = $("#service_id");
        const slotSelect = $("#slot_id");

        serviceSelect.find("option:not(:first)").remove();
        slotSelect.find("option:not(:first)").remove();

        state.services.forEach((service) => {
            serviceSelect.append(`<option value="${service.id}">${service.service_name} - $${Number(service.price).toFixed(2)}</option>`);
        });

        state.slots.forEach((slot) => {
            slotSelect.append(`<option value="${slot.id}">${slot.label}</option>`);
        });
    }

    async function loadOptions() {
        const data = await api("public/options");
        state.services = data.services || [];
        state.slots = data.slots || [];
        renderServicesGrid();
        fillSelects();
    }

    async function submitAppointment(event) {
        event.preventDefault();

        const payload = {
            full_name: $("#full_name").val().trim(),
            email: $("#email").val().trim(),
            phone: $("#phone").val().trim(),
            service_id: Number($("#service_id").val()),
            slot_id: Number($("#slot_id").val())
        };

        try {
            await api("appointments", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload)
            });

            event.target.reset();
            await loadOptions();
            Swal.fire({
                icon: "success",
                title: "Request Sent",
                text: "Your appointment request was submitted. We will contact you soon."
            });
        } catch (error) {
            Swal.fire({
                icon: "error",
                title: "Unable to Submit",
                text: error.message
            });
        }
    }

    $(document).ready(async function () {
        $("#bt-appointment-form").on("submit", submitAppointment);

        try {
            await loadOptions();
        } catch (error) {
            Swal.fire({
                icon: "error",
                title: "Loading Error",
                text: error.message
            });
        }

        if (document.readyState === "complete") {
            markSiteLoaded();
        } else {
            window.addEventListener("load", markSiteLoaded, { once: true });
        }

        initChatbot();
    });

    function showChatConfigMessage(message) {
        var root = document.getElementById("bt-n8n-chat-root");
        if (!root) { return; }

        root.innerHTML = "";

        var box = document.createElement("div");
        box.className = "bt-chat-config-msg";
        box.textContent = message;
        root.appendChild(box);
    }

    function injectN8nChatStylesheet() {
        if (document.getElementById("bt-n8n-chat-style")) {
            return;
        }

        var link = document.createElement("link");
        link.id = "bt-n8n-chat-style";
        link.rel = "stylesheet";
        link.href = "https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css";
        document.head.appendChild(link);
    }

    async function mountN8nChat() {
        var root = document.getElementById("bt-n8n-chat-root");
        if (!root) { return; }

        var webhookUrl = btHairPublic && btHairPublic.chatWebhookUrl
            ? String(btHairPublic.chatWebhookUrl).trim()
            : "";

        if (!webhookUrl) {
            showChatConfigMessage("AI chat is not configured yet. Please contact the salon directly.");
            return;
        }

        injectN8nChatStylesheet();

        try {
            var n8nChatModule = await import("https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js");

            if (!n8nChatModule || typeof n8nChatModule.createChat !== "function") {
                showChatConfigMessage("Unable to load AI chat widget. Please try again later.");
                return;
            }

            n8nChatModule.createChat({
                webhookUrl: webhookUrl,
                target: "#bt-n8n-chat-root",
                mode: "fullscreen",
                showWelcomeScreen: false,
                defaultLanguage: "en"
            });
        } catch (error) {
            showChatConfigMessage("Unable to load AI chat widget. Please try again later.");
        }
    }

    function initChatbot() {
        var wrap     = document.getElementById("bt-chatbot-wrap");
        var btn      = document.getElementById("bt-chatbot-btn");
        var win      = document.getElementById("bt-chat-window");
        var closeBtn = document.getElementById("bt-chat-close");

        if (!wrap || !btn || !win || !closeBtn) { return; }

        var isOpen = false;
        var mounted = false;

        function openChat() {
            isOpen = true;
            win.classList.add("bt-chat-visible");
            wrap.classList.add("chat-open");

            if (!mounted) {
                mounted = true;
                mountN8nChat();
            }
        }

        function closeChat() {
            isOpen = false;
            win.classList.remove("bt-chat-visible");
            wrap.classList.remove("chat-open");
        }

        btn.addEventListener("click", function () {
            isOpen ? closeChat() : openChat();
        });

        closeBtn.addEventListener("click", closeChat);

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && isOpen) { closeChat(); }
        });
    }
})(jQuery);
