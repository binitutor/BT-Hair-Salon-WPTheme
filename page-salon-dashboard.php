<?php
/**
 * Template Name: BT Salon Dashboard
 * Template Post Type: page
 *
 * @package bt-hair-salon
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<div class="dashboard-topbar py-3">
    <div class="container d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"class="dashboard-brand">
            <i class="fa-solid fa-scissors"></i>
            <h1 class="dashboard-brand-title"><?php bloginfo( 'name' ); ?> Dashboard</h1>
        </a>
        <div class="d-flex gap-2 topbar-actions">
            <a class="btn btn-outline-light btn-sm" href="<?php echo esc_url( home_url( '/' ) ); ?>">Public Page</a>
            <a class="btn btn-light btn-sm" href="<?php echo esc_url( wp_logout_url( home_url( '/sign-in/' ) ) ); ?>">Logout</a>
        </div>
    </div>
</div>

<main class="container py-4">
    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3"><i class="fa-solid fa-scissors"></i> Services</h2>
                    <form id="service-form" class="row g-2 mb-3">
                        <div class="col-12">
                            <input type="text" id="service_name" class="form-control" placeholder="Service name" required>
                        </div>
                        <div class="col-8">
                            <input type="number" id="service_price" class="form-control" placeholder="Price" min="0" step="0.01" required>
                        </div>
                        <div class="col-4 d-grid">
                            <button type="submit" class="btn btn-accent">Add</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="services-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3"><i class="fa-regular fa-calendar"></i> Availability Slots</h2>
                    <form id="slot-form" class="row g-2 mb-3">
                        <div class="col-12">
                            <input type="date" id="slot_date" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <input type="time" id="slot_start" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <input type="time" id="slot_end" class="form-control" required>
                        </div>
                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-accent">Add Time Slot</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Slot</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="slots-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3"><i class="fa-solid fa-link"></i> n8n Webhook</h2>
                    <form id="settings-form" class="row g-2">
                        <div class="col-12">
                            form submission webhook URL
                            <input type="url" id="service_webhook_url" class="form-control" placeholder="https://n8n.example.com/webhook/...">
                        </div>
                        <div class="col-12 d-grid border-bottom pb-3 mb-3">
                            <button type="button" id="test-service-webhook" class="btn btn-outline-dark bt-test-service-webhook">Test Service Webhook</button>
                        </div>

                        <div class="col-12">
                            chat widget webhook URL
                            <input type="url" id="chat_webhook_url" class="form-control" placeholder="https://n8n.example.com/webhook/.../chat">
                        </div>
                        <div class="form-check form-switch">
                            Secure <input class="form-check-input" type="checkbox" role="switch" id="chatbot-protected-toggle" checked>
                        </div>
                        <div class="col-12 d-grid">
                            <button type="button" id="test-chat-webhook" class="btn btn-outline-dark bt-test-chat-webhook">Test Chat Webhook</button>
                        </div>
                        <div class="col-12 d-grid border-bottom pb-3 mb-3">
                            <button type="button" id="generate-api-key" class="btn btn-outline-dark bt-generate-api-key">Generate API Key for callback</button>
                        </div>
                        <div class="col-12 d-grid border-bottom pb-3 mb-3">
                            <button type="button" id="view-callback-logs" class="btn btn-outline-secondary bt-view-callback-logs">View Callback Logs</button>
                        </div>

                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-accent">Save Webhook URLs</button>
                        </div>
                    </form>
                    <p class="small text-muted mt-3 mb-0">Top URL handles appointments. Chat URL handles the public AI chat widget.</p>
                    <hr>
                    <h3 class="h6">Appointment Status Chart</h3>
                    <canvas id="appointments-chart" height="180"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0"><i class="fa-regular fa-envelope"></i> Appointment Requests</h2>
                        <button id="refresh-dashboard" class="btn btn-outline-dark btn-sm">Refresh</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Contact</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="appointments-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0"><i class="fa-regular fa-gear"></i> Features</h2>
                        <!-- <button id="refresh-dashboard" class="btn btn-outline-dark btn-sm">Refresh</button> -->
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="features-table-body">
                                <tr>
                                    <td>Chatbot</td>
                                    <td>
                                        Automated chat support for clients.
                                        <!-- <form id="chatbot-settings-form" class="row g-2 mt-2">
                                            <div class="col-sm-8 col-lg-7">
                                                <input type="text" class="form-control form-control-sm" id="chatbot-api-key" placeholder="Enter OpenAI API Key" autocomplete="off">
                                            </div>
                                            <div class="col-sm-4 col-lg-2 d-grid">
                                                <button type="submit" class="btn btn-sm btn-outline-dark">Save API Key</button>
                                            </div>
                                            <div class="col-sm-12 col-lg-3 d-grid">
                                                <button type="button" id="test-chat-webhook-inline" class="btn btn-sm btn-outline-secondary bt-test-chat-webhook">Test Chat Webhook</button>
                                            </div>
                                        </form> -->
                                    </td>
                                    <td id="chatbot-status">Inactive</td>
                                    <td class="text-end">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="chatbot-toggle">
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Email</td>
                                    <td>
                                        <!-- Automated email support for clients.
                                        <input type="text" placeholder="Enter Gmail API Key" class="form-control" id="email-api-key" >
                                        <button class="btn btn-sm btn-outline-dark">Save</button> -->
                                        Automated email support for clients.
                                        <!-- <form class="row g-3">
                                            <div class="col-auto">
                                                <input type="text" class="form-control-plaintext" id="email-api-key" placeholder="Enter Gmail API Key">
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" class="btn btn-sm btn-outline-dark mb-3">Save</button>
                                            </div>
                                        </form> -->
                                    </td>
                                    <td>Inactive</td>
                                    <td class="text-end">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="email-toggle">
                                        </div>
                                    </td>
                                    
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>
<?php
get_footer();
