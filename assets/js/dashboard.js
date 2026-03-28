(function ($) {
    "use strict";

    const state = {
        services: [],
        slots: [],
        appointments: [],
        chart: null
    };

    async function api(path, method, payload) {
        let normalizedPath = path;
        if (btHairAdmin.restUrl.indexOf("?") !== -1 && path.indexOf("?") !== -1) {
            normalizedPath = path.replace("?", "&");
        }

        const response = await fetch(btHairAdmin.restUrl + normalizedPath, {
            method: method || "GET",
            headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": btHairAdmin.nonce
            },
            body: payload ? JSON.stringify(payload) : undefined
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || "Request failed.");
        }

        return data;
    }

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    async function copyText(value) {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(value);
            return;
        }

        const helper = document.createElement("textarea");
        helper.value = value;
        helper.setAttribute("readonly", "readonly");
        helper.style.position = "fixed";
        helper.style.opacity = "0";
        document.body.appendChild(helper);
        helper.select();
        document.execCommand("copy");
        document.body.removeChild(helper);
    }

    function statusBadge(status) {
        return `<span class="status-pill status-${status}">${status}</span>`;
    }

    function renderServices() {
        const body = $("#services-table-body");
        body.empty();

        if (!state.services.length) {
            body.append('<tr><td colspan="3" class="text-muted">No services yet.</td></tr>');
            return;
        }

        state.services.forEach((service) => {
            const row = `
                <tr>
                    <td>${service.service_name}</td>
                    <td>$${Number(service.price).toFixed(2)}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-dark me-1 edit-service" data-id="${service.id}"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-service" data-id="${service.id}"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            `;
            body.append(row);
        });
    }

    function renderSlots() {
        const body = $("#slots-table-body");
        body.empty();

        if (!state.slots.length) {
            body.append('<tr><td colspan="2" class="text-muted">No slots yet.</td></tr>');
            return;
        }

        state.slots.forEach((slot) => {
            const activeBadge = Number(slot.is_active) === 1 ? "" : " <span class='badge text-bg-secondary'>inactive</span>";
            const row = `
                <tr>
                    <td>${slot.label}${activeBadge}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-danger delete-slot" data-id="${slot.id}"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            `;
            body.append(row);
        });
    }

    function renderAppointments() {
        const body = $("#appointments-table-body");
        body.empty();

        if (!state.appointments.length) {
            body.append('<tr><td colspan="6" class="text-muted">No appointment requests yet.</td></tr>');
            return;
        }

        state.appointments.forEach((appt) => {
            const canAct = appt.status === "pending";
            const slotText = appt.slot_label || "Slot removed";
            const actionButtons = canAct
                ? `<button class="btn btn-success btn-sm me-1 appt-action" data-status="accepted" data-id="${appt.id}">Accept</button>
                   <button class="btn btn-danger btn-sm appt-action" data-status="rejected" data-id="${appt.id}">Reject</button>`
                : "<span class='text-muted small'>No actions</span>";

            const row = `
                <tr>
                    <td>${appt.full_name}</td>
                    <td>
                        <div>${appt.email}</div>
                        <small class="text-muted">${appt.phone}</small>
                    </td>
                    <td>${appt.service_name || "N/A"}</td>
                    <td>${slotText}</td>
                    <td>${statusBadge(appt.status)}</td>
                    <td class="text-end">${actionButtons}</td>
                </tr>
            `;
            body.append(row);
        });
    }

    function renderChart() {
        const pending = state.appointments.filter((a) => a.status === "pending").length;
        const accepted = state.appointments.filter((a) => a.status === "accepted").length;
        const rejected = state.appointments.filter((a) => a.status === "rejected").length;

        const context = document.getElementById("appointments-chart");
        if (!context) {
            return;
        }

        if (state.chart) {
            state.chart.destroy();
        }

        state.chart = new Chart(context, {
            type: "doughnut",
            data: {
                labels: ["Pending", "Accepted", "Rejected"],
                datasets: [{
                    data: [pending, accepted, rejected],
                    backgroundColor: ["#f0b429", "#0f9d58", "#db4437"]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "bottom"
                    }
                }
            }
        });
    }

    function renderChatbotFeature(settings) {
        const enabled = settings.chatbot_enabled === true || settings.chatbot_enabled === 1 || settings.chatbot_enabled === "1";
        const protectedMode = settings.chatbot_protected === true || settings.chatbot_protected === 1 || settings.chatbot_protected === "1";
        $("#chatbot-api-key").val(settings.chatbot_api_key || "");
        $("#chatbot-toggle").prop("checked", enabled);
        $("#chatbot-protected-toggle").prop("checked", protectedMode);
        $("#chatbot-status").text(enabled ? "Active" : "Inactive");
    }

    async function loadDashboard() {
        const [services, slots, appointments, settings] = await Promise.all([
            api("services"),
            api("slots"),
            api("appointments"),
            api("settings")
        ]);

        state.services = services;
        state.slots = slots;
        state.appointments = appointments;
        $("#service_webhook_url").val(settings.service_webhook_url || settings.webhook_url || "");
        $("#chat_webhook_url").val(settings.chat_webhook_url || "");
        renderChatbotFeature(settings);

        renderServices();
        renderSlots();
        renderAppointments();
        renderChart();
    }

    async function createService(event) {
        event.preventDefault();
        const payload = {
            service_name: $("#service_name").val().trim(),
            price: Number($("#service_price").val())
        };

        try {
            await api("services", "POST", payload);
            event.target.reset();
            await loadDashboard();
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    }

    async function editService(id) {
        const service = state.services.find((item) => Number(item.id) === Number(id));
        if (!service) {
            return;
        }

        const result = await Swal.fire({
            title: "Edit Service",
            html: `
                <input id="swal-service-name" class="swal2-input" placeholder="Service name" value="${service.service_name}">
                <input id="swal-service-price" class="swal2-input" placeholder="Price" type="number" min="0" step="0.01" value="${service.price}">
            `,
            focusConfirm: false,
            preConfirm: () => {
                const name = document.getElementById("swal-service-name").value.trim();
                const price = Number(document.getElementById("swal-service-price").value);
                if (!name || Number.isNaN(price) || price < 0) {
                    Swal.showValidationMessage("Please provide valid values.");
                    return false;
                }
                return { service_name: name, price };
            },
            showCancelButton: true
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            await api(`services/${id}`, "PUT", result.value);
            await loadDashboard();
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    }

    async function deleteService(id) {
        const confirmed = await Swal.fire({
            title: "Delete service?",
            text: "This action cannot be undone.",
            icon: "warning",
            showCancelButton: true
        });

        if (!confirmed.isConfirmed) {
            return;
        }

        try {
            await api(`services/${id}`, "DELETE");
            await loadDashboard();
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    }

    async function createSlot(event) {
        event.preventDefault();

        const payload = {
            date: $("#slot_date").val(),
            start_time: $("#slot_start").val(),
            end_time: $("#slot_end").val()
        };

        try {
            await api("slots", "POST", payload);
            event.target.reset();
            await loadDashboard();
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    }

    async function deleteSlot(id) {
        const confirmed = await Swal.fire({
            title: "Remove slot?",
            text: "This will remove the slot from public booking.",
            icon: "warning",
            showCancelButton: true
        });

        if (!confirmed.isConfirmed) {
            return;
        }

        try {
            await api(`slots/${id}`, "DELETE");
            await loadDashboard();
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    }

    async function updateAppointmentStatus(id, status) {
        try {
            await api(`appointments/${id}/status`, "POST", { status });
            await loadDashboard();
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    }

    async function saveSettings(event) {
        event.preventDefault();

        const payload = {
            service_webhook_url: $("#service_webhook_url").val().trim(),
            chat_webhook_url: $("#chat_webhook_url").val().trim()
        };

        try {
            await api("settings", "POST", payload);
            Swal.fire("Saved", "Webhook URLs updated.", "success");
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    }

    async function saveChatbotApiKey(event) {
        event.preventDefault();

        const payload = {
            chatbot_api_key: $("#chatbot-api-key").val().trim()
        };

        try {
            await api("settings", "POST", payload);
            Swal.fire("Saved", "Chatbot API key updated.", "success");
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    }

    async function testChatWebhook() {
        const payload = {
            service_webhook_url: $("#service_webhook_url").val().trim(),
            chat_webhook_url: $("#chat_webhook_url").val().trim()
        };

        try {
            Swal.fire({
                title: "Testing Chat Webhook...",
                text: "Please wait while we contact your n8n workflow.",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const result = await api("settings/test-chat", "POST", payload);
            Swal.close();
            const usedUrl = (result.used_url || "").trim();
            const hint = (result.hint || "").trim();
            const testPayload = result.payload || {};
            const payloadDisplay = JSON.stringify(testPayload, null, 2);

            if (result.success) {
                const replyText = (result.reply || "").trim();
                const message = replyText
                    ? `Connected successfully (HTTP ${result.status_code}). Sample response: ${replyText}`
                    : `Connected successfully (HTTP ${result.status_code}).`;
                const detail = usedUrl ? `${message}\n\nURL used: ${usedUrl}` : message;
                Swal.fire("Chat Webhook OK", detail, "success");
                return;
            }

            let failMessage = `Webhook responded with HTTP ${result.status_code}.`;
            if (usedUrl) {
                failMessage += `\n\nURL used: ${usedUrl}`;
            }
            if (hint) {
                failMessage += `\n\n${hint}`;
            }
            failMessage += `\n\nTest Payload Sent:\n${payloadDisplay}`;
            
            Swal.fire({
                title: "Test Failed",
                text: failMessage,
                icon: "error",
                width: 700,
                didOpen: () => {
                    // Make text pre-formatted to show JSON nicely
                    const popup = Swal.getPopup();
                    if (popup) {
                        const textElement = popup.querySelector('.swal2-html-container');
                        if (textElement) {
                            textElement.style.textAlign = "left";
                            textElement.style.whiteSpace = "pre-wrap";
                            textElement.style.fontFamily = "monospace";
                            textElement.style.fontSize = "12px";
                            textElement.style.maxHeight = "400px";
                            textElement.style.overflow = "auto";
                        }
                    }
                }
            });
        } catch (error) {
            Swal.close();
            Swal.fire("Test Failed", error.message, "error");
        }
    }

    async function testServiceWebhook() {
        const payload = {
            service_webhook_url: $("#service_webhook_url").val().trim()
        };

        try {
            Swal.fire({
                title: "Testing Service Webhook...",
                text: "Please wait while we contact your n8n workflow.",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const result = await api("settings/test-service", "POST", payload);
            Swal.close();
            const usedUrl = (result.used_url || "").trim();
            const hint = (result.hint || "").trim();

            if (result.success) {
                const replyText = (result.reply || "").trim();
                const message = replyText
                    ? `Connected successfully (HTTP ${result.status_code}). Sample response: ${replyText}`
                    : `Connected successfully (HTTP ${result.status_code}).`;
                const detail = usedUrl ? `${message}\n\nURL used: ${usedUrl}` : message;
                Swal.fire("Service Webhook OK", detail, "success");
                return;
            }

            let failMessage = `Webhook responded with HTTP ${result.status_code}.`;
            if (usedUrl) {
                failMessage += `\n\nURL used: ${usedUrl}`;
            }
            if (hint) {
                failMessage += `\n\n${hint}`;
            }
            Swal.fire("Test Failed", failMessage, "error");
        } catch (error) {
            Swal.close();
            Swal.fire("Test Failed", error.message, "error");
        }
    }

    async function generateCallbackAuth(forceRegenerate = false) {
        try {
            const result = await api("settings/generate-callback-auth", "POST", { force_regenerate: forceRegenerate });
            const username = (result.wp_username || "").trim();
            const appPassword = (result.application_password || "").trim();
            const callbackKey = (result.callback_key || "").trim();
            const callbackUrl = (result.callback_url || "").trim();
            const generatedNew = result.generated_new === true || result.generated_new === 1 || result.generated_new === "1";
            const notice = (result.notice || "").trim();

            const basicToken = username && appPassword
                ? btoa(`${username}:${appPassword}`)
                : "";

            const authHeader = basicToken ? `Basic ${basicToken}` : "";

            function fieldBlock(label, id, value, minHeight, extraButtonsHtml = "") {
                return `
                    <p class="mt-2"><strong>${escapeHtml(label)}</strong></p>
                    <textarea id="${id}" readonly style="width:100%;min-height:${minHeight};">${escapeHtml(value)}</textarea>
                    <div class="d-grid mt-1 ${extraButtonsHtml ? "d-md-flex gap-2" : ""}">
                        <button type="button" class="btn btn-sm btn-outline-dark bt-copy-field" data-copy-id="${id}">Copy</button>
                        ${extraButtonsHtml}
                    </div>
                `;
            }

            const appPasswordDisplay = appPassword || "(Not shown for existing credentials. Click Generate New to rotate and create a new application password.)";
            const callbackExtraButtons = "<button type=\"button\" class=\"btn btn-sm btn-outline-danger bt-force-regenerate\">Generate New</button>";

            Swal.fire({
                title: generatedNew ? "Callback Credentials Generated" : "Callback Credentials",
                icon: "success",
                width: 760,
                html: `
                    <div style="text-align:left; font-size:14px;">
                        ${notice ? `<p class="mb-2 text-muted">${escapeHtml(notice)}</p>` : ""}
                        ${fieldBlock("Callback URL", "bt-copy-callback-url", callbackUrl, "56px")}
                        ${fieldBlock("Callback API Key", "bt-copy-callback-key", callbackKey, "56px", callbackExtraButtons)}
                        ${fieldBlock("WordPress Username", "bt-copy-callback-user", username, "40px")}
                        ${fieldBlock("Application Password", "bt-copy-callback-pass", appPasswordDisplay, "56px")}
                        ${fieldBlock("Authorization Header (Basic)", "bt-copy-callback-auth", authHeader, "56px")}
                        <p class="mt-2 mb-0 text-muted">Save these now. WordPress only shows generated application passwords once.</p>
                    </div>
                `,
                confirmButtonText: "Close",
                didOpen: () => {
                    document.querySelectorAll(".bt-copy-field").forEach((button) => {
                        button.addEventListener("click", async () => {
                            const fieldId = button.getAttribute("data-copy-id");
                            const field = fieldId ? document.getElementById(fieldId) : null;
                            const value = field ? field.value : "";
                            if (!value) {
                                return;
                            }

                            try {
                                await copyText(value);
                                const original = button.textContent;
                                button.textContent = "Copied";
                                window.setTimeout(() => {
                                    button.textContent = original;
                                }, 900);
                            } catch (copyError) {
                                Swal.fire("Error", "Unable to copy text automatically.", "error");
                            }
                        });
                    });

                    const regenerateButton = document.querySelector(".bt-force-regenerate");
                    if (regenerateButton) {
                        regenerateButton.addEventListener("click", async () => {
                            const confirm = await Swal.fire({
                                title: "Generate new callback credentials?",
                                text: "This will rotate the callback key and create a new WordPress application password.",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonText: "Generate New",
                                cancelButtonText: "Cancel",
                                confirmButtonColor: "#b91c1c"
                            });

                            if (!confirm.isConfirmed) {
                                return;
                            }

                            Swal.close();
                            await generateCallbackAuth(true);
                        });
                    }
                }
            });
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    }

    async function viewCallbackLogs() {
        try {
            const result = await api("chat/callback-logs?limit=30", "GET");
            const logs = Array.isArray(result.logs) ? result.logs : [];

            if (!logs.length) {
                Swal.fire("Callback Logs", "No callback logs yet.", "info");
                return;
            }

            const badgeStyles = {
                ok: "background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:999px;font-weight:700;",
                error: "background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:999px;font-weight:700;",
                other: "background:#e5e7eb;color:#374151;padding:2px 8px;border-radius:999px;font-weight:700;"
            };

            function statusBadge(statusRaw) {
                const status = String(statusRaw || "").toLowerCase();
                const style = status === "ok"
                    ? badgeStyles.ok
                    : (status === "error" ? badgeStyles.error : badgeStyles.other);
                const label = status ? status.toUpperCase() : "UNKNOWN";
                return `<span style="${style}">${escapeHtml(label)}</span>`;
            }

            function buildRows(filterStatus) {
                const filtered = logs.filter((log) => {
                    if (filterStatus === "all") {
                        return true;
                    }

                    return String(log.status || "").toLowerCase() === filterStatus;
                });

                if (!filtered.length) {
                    return `<tr><td colspan="7" class="text-muted">No ${escapeHtml(filterStatus.toUpperCase())} logs in this view.</td></tr>`;
                }

                return filtered.map((log) => {
                    const time = escapeHtml(log.time || "");
                    const status = escapeHtml(log.status || "");
                    const session = escapeHtml(log.session_id || "");
                    const ip = escapeHtml(log.remote_ip || "");
                    const note = escapeHtml(log.note || "");
                    const len = escapeHtml(String(log.reply_length || ""));
                    const result = escapeHtml(log.result || log.reply_preview || "");

                    return `<tr>
                        <td>${time}</td>
                        <td>${statusBadge(status)}</td>
                        <td>${session}</td>
                        <td>${ip}</td>
                        <td>${len}</td>
                        <td>${result}</td>
                        <td>${note}</td>
                    </tr>`;
                }).join("");
            }

            Swal.fire({
                title: "Callback Logs",
                width: 920,
                html: `
                    <div class="d-flex gap-2 mb-2" style="text-align:left;">
                        <button type="button" class="btn btn-sm btn-outline-dark bt-log-filter" data-filter="all">All</button>
                        <button type="button" class="btn btn-sm btn-outline-success bt-log-filter" data-filter="ok">OK</button>
                        <button type="button" class="btn btn-sm btn-outline-danger bt-log-filter" data-filter="error">ERROR</button>
                    </div>
                    <div style="max-height:420px; overflow:auto; text-align:left;">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Session</th>
                                    <th>IP</th>
                                    <th>Reply Len</th>
                                    <th>Result</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody id="bt-callback-logs-body">${buildRows("all")}</tbody>
                        </table>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: "Clear Logs",
                cancelButtonText: "Close",
                confirmButtonColor: "#b91c1c",
                didOpen: () => {
                    const tableBody = document.getElementById("bt-callback-logs-body");
                    const buttons = Array.from(document.querySelectorAll(".bt-log-filter"));

                    function setActiveFilter(filter) {
                        buttons.forEach((btn) => {
                            const btnFilter = String(btn.getAttribute("data-filter") || "all").toLowerCase();
                            const active = btnFilter === filter;
                            btn.classList.toggle("active", active);
                            btn.setAttribute("aria-pressed", active ? "true" : "false");
                        });
                    }

                    setActiveFilter("all");

                    buttons.forEach((button) => {
                        button.addEventListener("click", () => {
                            const filter = String(button.getAttribute("data-filter") || "all").toLowerCase();
                            if (tableBody) {
                                tableBody.innerHTML = buildRows(filter);
                            }
                            setActiveFilter(filter);
                        });
                    });
                }
            }).then(async (decision) => {
                if (!decision.isConfirmed) {
                    return;
                }

                const sure = await Swal.fire({
                    title: "Clear callback logs?",
                    text: "This removes all stored callback log entries.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, clear logs",
                    cancelButtonText: "Cancel",
                    confirmButtonColor: "#b91c1c"
                });

                if (!sure.isConfirmed) {
                    return;
                }

                await api("chat/callback-logs/clear", "POST", {});
                Swal.fire("Cleared", "Callback logs have been cleared.", "success");
            });
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    }

    async function toggleChatbot() {
        const enabled = $("#chatbot-toggle").is(":checked");

        try {
            await api("settings", "POST", { chatbot_enabled: enabled });
            $("#chatbot-status").text(enabled ? "Active" : "Inactive");
            Swal.fire("Saved", enabled ? "Chatbot enabled for public users." : "Chatbot disabled for public users.", "success");
        } catch (error) {
            // Revert UI if save fails.
            $("#chatbot-toggle").prop("checked", !enabled);
            $("#chatbot-status").text(!enabled ? "Active" : "Inactive");
            Swal.fire("Error", error.message, "error");
        }
    }

    async function toggleChatbotProtection() {
        const protectedMode = $("#chatbot-protected-toggle").is(":checked");

        try {
            await api("settings", "POST", { chatbot_protected: protectedMode });
            Swal.fire("Saved", protectedMode ? "Chatbot callback protection enabled." : "Chatbot callback protection disabled.", "success");
        } catch (error) {
            $("#chatbot-protected-toggle").prop("checked", !protectedMode);
            Swal.fire("Error", error.message, "error");
        }
    }

    $(document).ready(async function () {
        if (!$("#appointments-table-body").length) {
            return;
        }

        $("#service-form").on("submit", createService);
        $("#slot-form").on("submit", createSlot);
        $("#settings-form").on("submit", saveSettings);
        $(".bt-test-chat-webhook").on("click", testChatWebhook);
        $(".bt-test-service-webhook").on("click", testServiceWebhook);
        $(".bt-generate-api-key").on("click", generateCallbackAuth);
        $(".bt-view-callback-logs").on("click", viewCallbackLogs);
        $("#chatbot-settings-form").on("submit", saveChatbotApiKey);
        $("#chatbot-toggle").on("change", toggleChatbot);
        $("#chatbot-protected-toggle").on("change", toggleChatbotProtection);
        $("#refresh-dashboard").on("click", loadDashboard);

        $(document).on("click", ".edit-service", function () {
            editService($(this).data("id"));
        });

        $(document).on("click", ".delete-service", function () {
            deleteService($(this).data("id"));
        });

        $(document).on("click", ".delete-slot", function () {
            deleteSlot($(this).data("id"));
        });

        $(document).on("click", ".appt-action", function () {
            updateAppointmentStatus($(this).data("id"), $(this).data("status"));
        });

        try {
            await loadDashboard();
        } catch (error) {
            Swal.fire("Error", error.message, "error");
        }
    });
})(jQuery);
