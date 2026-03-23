<?php
/**
 * Main public page template.
 *
 * @package bt-hair-salon
 */

get_header();
?>

<div id="bt-page-loader" aria-hidden="true">
    <div class="loader-inner text-center">
        <i class="fa-solid fa-scissors loader-icon"></i>
        <p class="mb-0 mt-2">Preparing your salon experience...</p>
    </div>
</div>

<header class="bt-nav py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand d-flex align-items-center gap-2">
            <i class="fa-solid fa-scissors"></i>
            <span class="fw-bold fs-5"><?php bloginfo( 'name' ); ?></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a class="btn btn-outline-light btn-sm" href="#appointment-form">Book Now</a>
            <?php if ( current_user_can( 'manage_options' ) ) : ?>
                <a class="btn btn-light btn-sm" href="<?php echo esc_url( home_url( '/salon-dashboard/' ) ); ?>">Dashboard</a>
            <?php endif; ?>

        </div>
    </div>
</header>

<section class="hero-section position-relative d-flex align-items-center reveal-on-scroll">
    <div class="hero-overlay"></div>
    <div class="container position-relative z-1 py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center text-light">
                <h1 class="display-4 fw-bold">Crafted Hair Experiences, Booked in Minutes</h1>
                <p class="lead mt-3">From precision cuts to elegant styling, BT Hair Salon is where your next look begins.</p>
                <a class="btn btn-lg btn-accent mt-3" href="#appointment-form">Request Appointment</a>
            </div>
        </div>
    </div>
</section>

<main>
    <section class="py-5 services-section reveal-on-scroll">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <h2 class="section-title mb-0">Popular Services</h2>
                <span class="text-muted">Curated salon care for every style</span>
            </div>
            <div id="services-grid" class="row g-4"></div>
        </div>
    </section>

    <section id="appointment-form" class="py-5 booking-section reveal-on-scroll">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="booking-info p-4 h-100">
                        <h3 class="mb-3">Request Your Appointment</h3>
                        <p class="mb-4">Fill out your details and choose an available time slot. We will review and confirm shortly.</p>
                        <ul class="list-unstyled mb-0">
                            <li><i class="fa-solid fa-check"></i> Real-time availability from our admin scheduler</li>
                            <li><i class="fa-solid fa-check"></i> Easy service selection with transparent pricing</li>
                            <li><i class="fa-solid fa-check"></i> Fast confirmation workflow</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form id="bt-appointment-form" class="row g-3">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="service_id" class="form-label">Service Type</label>
                                    <select id="service_id" class="form-select" required>
                                        <option value="">Choose a service</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="slot_id" class="form-label">Appointment Date and Time</label>
                                    <select id="slot_id" class="form-select" required>
                                        <option value="">Choose an available slot</option>
                                    </select>
                                </div>
                                <div class="col-12 d-grid">
                                    <button type="submit" class="btn btn-accent btn-lg">Submit Appointment Request</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
