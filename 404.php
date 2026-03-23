<?php
/**
 * 404 page not found template.
 *
 * @package bt-hair-salon
 */

get_header();
?>

<header class="bt-nav py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand d-flex align-items-center gap-2">
            <i class="fa-solid fa-scissors"></i>
            <span class="fw-bold fs-5"><?php bloginfo( 'name' ); ?></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a class="btn btn-outline-light btn-sm" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back Home</a>
            <?php if ( current_user_can( 'manage_options' ) ) : ?>
                <a class="btn btn-light btn-sm" href="<?php echo esc_url( home_url( '/salon-dashboard/' ) ); ?>">Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <section class="text-center reveal-on-scroll">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div style="font-size: 120px; font-weight: bold; color: var(--bg-primary); margin-bottom: 20px;">
                        404
                    </div>
                    <h1 class="display-5 fw-bold mb-3">Page Not Found</h1>
                    <p class="lead text-muted mb-4">
                        It seems we couldn't find the page you're looking for. Perhaps the appointment details you seek have gone elsewhere, or the link may have expired.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a class="btn btn-lg btn-accent" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                            <i class="fa-solid fa-house me-2"></i>Back to Home
                        </a>
                        <a class="btn btn-lg btn-outline-secondary" href="<?php echo esc_url( home_url( '/' ) ); ?>#appointment-form">
                            <i class="fa-solid fa-calendar me-2"></i>Book Appointment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
    main.d-flex {
        background: linear-gradient(135deg, rgba(79, 39, 2, 0.05) 0%, rgba(2, 54, 23, 0.03) 100%);
    }
</style>

<?php
get_footer();
