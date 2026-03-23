<?php
/**
 * Shared footer template.
 *
 * @package bt-hair-salon
 */

$footer_classes = 'py-4 text-center small';

if ( ! ( function_exists( 'bt_hair_is_dashboard_page' ) && bt_hair_is_dashboard_page() ) && ! ( function_exists( 'bt_hair_is_sign_in_page' ) && bt_hair_is_sign_in_page() ) ) {
    $footer_classes .= ' reveal-on-scroll';
}
?>
<footer class="<?php echo esc_attr( $footer_classes ); ?>">
    <div class="container">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</div>

    <div class="d-flex justify-content-end gap-3">
        <?php if ( ! current_user_can( 'manage_options' ) ) : ?>
            <a class="btn btn-light btn-sm me-5" href="<?php echo esc_url( home_url( '/sign-in/' ) ); ?>">Sign In</a>
        <?php endif; ?>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
