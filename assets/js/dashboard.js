(function ($) {
    "use strict";

    const state = {
        services: [],
        slots: [],
        appointments: [],
        chart: null
    };

    async function api(path, method, payload) {
        const response = await fetch(btHairAdmin.restUrl + path, {
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
        $("#webhook_url").val(settings.webhook_url || "");

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
            webhook_url: $("#webhook_url").val().trim()
        };

        try {
            await api("settings", "POST", payload);
            Swal.fire("Saved", "Webhook URL updated.", "success");
        } catch (error) {
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
