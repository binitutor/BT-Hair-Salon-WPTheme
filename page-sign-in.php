<?php
/**
 * Template Name: BT Sign In
 * Template Post Type: page
 *
 * @package bt-hair-salon
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
    wp_safe_redirect( home_url( '/salon-dashboard/' ) );
    exit;
}

$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/salon-dashboard/' );

get_header();
?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 mb-2"><?php bloginfo( 'name' ); ?> Admin Sign In</h1>
                        <p class="text-muted mb-0">Use your admin account to access the salon dashboard.</p>
                    </div>

                    <?php
                    wp_login_form(
                        array(
                            'redirect'       => $redirect_to,
                            'remember'       => true,
                            'label_username' => 'Email or Username',
                            'label_password' => 'Password',
                            'label_remember' => 'Remember me',
                            'label_log_in'   => 'Sign In',
                        )
                    );
                    ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-link d-block mt-4 text-center">
                        <i class="fa-solid fa-scissors"></i>
                        <?php bloginfo( 'name' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
get_footer();
