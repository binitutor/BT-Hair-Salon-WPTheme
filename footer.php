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

<?php if ( ! ( function_exists( 'bt_hair_is_dashboard_page' ) && bt_hair_is_dashboard_page() ) && ! ( function_exists( 'bt_hair_is_sign_in_page' ) && bt_hair_is_sign_in_page() ) && '1' === (string) get_option( 'bt_hair_chatbot_enabled', '0' ) ) : ?>

<div id="bt-chatbot-wrap">
    <div id="bt-chat-tooltip">Got questions? Ask AI agent!</div>
    <button id="bt-chatbot-btn" type="button" aria-label="Chat with AI assistant">
        <i class="fa-solid fa-robot"></i>
    </button>
</div>

<div id="bt-chat-window" aria-label="AI Chat Assistant">
    <div id="bt-chat-header">
        <span><i class="fa-solid fa-robot me-2"></i>AI Assistant</span>
        <button id="bt-chat-close" type="button" aria-label="Close chat">&times;</button>
    </div>
    <div id="bt-chat-content" aria-live="polite">
        <div id="bt-n8n-chat-root" aria-label="AI chat messages"></div>
    </div>
</div>

<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
