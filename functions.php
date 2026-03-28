<?php
/**
 * Theme functions for BT Hair Salon.
 *
 * @package bt-hair-salon
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return table names used by the theme.
 *
 * @return array<string, string>
 */
function bt_hair_tables() {
    global $wpdb;

    return array(
        'services'     => $wpdb->prefix . 'bt_hair_services',
        'slots'        => $wpdb->prefix . 'bt_hair_slots',
        'appointments' => $wpdb->prefix . 'bt_hair_appointments',
    );
}

/**
 * Install custom tables when the theme is activated.
 */
function bt_hair_install_tables() {
    global $wpdb;

    $tables          = bt_hair_tables();
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql_services = "CREATE TABLE {$tables['services']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        service_name VARCHAR(190) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql_slots = "CREATE TABLE {$tables['slots']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        slot_start DATETIME NOT NULL,
        slot_end DATETIME NOT NULL,
        label VARCHAR(191) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY slot_start (slot_start),
        KEY is_active (is_active)
    ) $charset_collate;";

    $sql_appointments = "CREATE TABLE {$tables['appointments']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        full_name VARCHAR(190) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(60) NOT NULL,
        service_id BIGINT UNSIGNED NOT NULL,
        slot_id BIGINT UNSIGNED NOT NULL,
        appointment_start DATETIME NOT NULL,
        appointment_end DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY service_id (service_id),
        KEY slot_id (slot_id),
        KEY status (status)
    ) $charset_collate;";

    dbDelta( $sql_services );
    dbDelta( $sql_slots );
    dbDelta( $sql_appointments );

    if ( false === get_option( 'bt_hair_n8n_webhook_url', false ) ) {
        add_option( 'bt_hair_n8n_webhook_url', '' );
    }

    if ( false === get_option( 'bt_hair_n8n_chat_webhook_url', false ) ) {
        add_option( 'bt_hair_n8n_chat_webhook_url', '' );
    }

    if ( false === get_option( 'bt_hair_chatbot_api_key', false ) ) {
        add_option( 'bt_hair_chatbot_api_key', '' );
    }

    if ( false === get_option( 'bt_hair_chatbot_enabled', false ) ) {
        add_option( 'bt_hair_chatbot_enabled', '0' );
    }

    if ( false === get_option( 'bt_hair_chatbot_protected', false ) ) {
        add_option( 'bt_hair_chatbot_protected', '1' );
    }

    if ( false === get_option( 'bt_hair_chatbot_callback_key', false ) ) {
        add_option( 'bt_hair_chatbot_callback_key', '' );
    }

    if ( false === get_option( 'bt_hair_chatbot_callback_logs', false ) ) {
        add_option( 'bt_hair_chatbot_callback_logs', array() );
    }

    bt_hair_ensure_required_pages();

    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'bt_hair_install_tables' );

/**
 * Register theme supports.
 */
function bt_hair_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
}
add_action( 'after_setup_theme', 'bt_hair_theme_setup' );

/**
 * Return required utility pages for this theme.
 *
 * @return array<string, array<string, string>>
 */
function bt_hair_required_pages() {
    return array(
        'salon-dashboard' => array(
            'title'    => 'Salon Dashboard',
            'template' => 'page-salon-dashboard.php',
            'content'  => 'BT Hair Salon admin dashboard.',
        ),
        'sign-in'         => array(
            'title'    => 'Sign In',
            'template' => 'page-sign-in.php',
            'content'  => 'Sign in to access BT Hair Salon dashboard.',
        ),
    );
}

/**
 * Ensure required pages exist and have expected templates.
 */
function bt_hair_ensure_required_pages() {
    $pages = bt_hair_required_pages();

    foreach ( $pages as $slug => $config ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );

        if ( ! $page instanceof WP_Post ) {
            $page_id = wp_insert_post(
                array(
                    'post_title'   => $config['title'],
                    'post_name'    => $slug,
                    'post_type'    => 'page',
                    'post_status'  => 'publish',
                    'post_content' => $config['content'],
                ),
                true
            );

            if ( ! is_wp_error( $page_id ) ) {
                update_post_meta( (int) $page_id, '_wp_page_template', $config['template'] );
            }

            continue;
        }

        $current_template = get_page_template_slug( $page->ID );
        if ( $current_template !== $config['template'] ) {
            update_post_meta( $page->ID, '_wp_page_template', $config['template'] );
        }
    }
}

/**
 * Register stable rewrites for theme utility pages.
 */
function bt_hair_register_page_rewrites() {
    add_rewrite_rule( '^salon-dashboard/?$', 'index.php?pagename=salon-dashboard', 'top' );
    add_rewrite_rule( '^sign-in/?$', 'index.php?pagename=sign-in', 'top' );
}
add_action( 'init', 'bt_hair_register_page_rewrites', 9 );

/**
 * Run one-time setup after theme updates: ensure pages and flush rewrites.
 */
function bt_hair_maybe_setup_pages_and_rewrites() {
    $setup_version = '1.0.4';
    $current       = get_option( 'bt_hair_setup_version', '' );

    if ( $current === $setup_version ) {
        return;
    }

    bt_hair_ensure_required_pages();

    if ( false === get_option( 'bt_hair_chatbot_api_key', false ) ) {
        add_option( 'bt_hair_chatbot_api_key', '' );
    }

    if ( false === get_option( 'bt_hair_chatbot_enabled', false ) ) {
        add_option( 'bt_hair_chatbot_enabled', '0' );
    }

    if ( false === get_option( 'bt_hair_chatbot_protected', false ) ) {
        add_option( 'bt_hair_chatbot_protected', '1' );
    }

    if ( false === get_option( 'bt_hair_chatbot_callback_key', false ) ) {
        add_option( 'bt_hair_chatbot_callback_key', '' );
    }

    if ( false === get_option( 'bt_hair_chatbot_callback_logs', false ) ) {
        add_option( 'bt_hair_chatbot_callback_logs', array() );
    }

    flush_rewrite_rules( false );
    update_option( 'bt_hair_setup_version', $setup_version );
}
add_action( 'init', 'bt_hair_maybe_setup_pages_and_rewrites', 20 );

/**
 * Check if current request is dashboard page.
 *
 * @return bool
 */
function bt_hair_is_dashboard_page() {
    return is_page( 'salon-dashboard' ) || is_page_template( 'page-salon-dashboard.php' );
}

/**
 * Check if current request is sign-in page.
 *
 * @return bool
 */
function bt_hair_is_sign_in_page() {
    return is_page( 'sign-in' ) || is_page_template( 'page-sign-in.php' );
}

/**
 * Force custom templates by slug even if page template assignment is missing.
 *
 * @param string $template Default resolved template.
 * @return string
 */
function bt_hair_template_fallback_by_slug( $template ) {
    if ( is_page( 'salon-dashboard' ) ) {
        $dashboard_template = locate_template( 'page-salon-dashboard.php' );
        if ( '' !== $dashboard_template ) {
            return $dashboard_template;
        }
    }

    if ( is_page( 'sign-in' ) ) {
        $sign_in_template = locate_template( 'page-sign-in.php' );
        if ( '' !== $sign_in_template ) {
            return $sign_in_template;
        }
    }

    return $template;
}
add_filter( 'template_include', 'bt_hair_template_fallback_by_slug', 99 );

/**
 * Get sign-in page URL.
 *
 * @return string
 */
function bt_hair_sign_in_url() {
    $page = get_page_by_path( 'sign-in' );

    if ( $page instanceof WP_Post ) {
        return get_permalink( $page );
    }

    $template_pages = get_posts(
        array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_key'       => '_wp_page_template',
            'meta_value'     => 'page-sign-in.php',
            'fields'         => 'ids',
        )
    );

    if ( ! empty( $template_pages ) ) {
        return get_permalink( (int) $template_pages[0] );
    }

    return home_url( '/index.php?pagename=sign-in' );
}

/**
 * Get dashboard URL with fallback for non-rewrite environments.
 *
 * @return string
 */
function bt_hair_dashboard_url() {
    $page = get_page_by_path( 'salon-dashboard' );

    if ( $page instanceof WP_Post ) {
        return get_permalink( $page );
    }

    return home_url( '/index.php?pagename=salon-dashboard' );
}

/**
 * Get REST base URL in query format for environments without pretty permalink support.
 *
 * @return string
 */
function bt_hair_rest_base_url() {
    return home_url( '/index.php?rest_route=/bt-hair/v1/' );
}

/**
 * Enforce dashboard authentication and authorization.
 */
function bt_hair_protect_dashboard_page() {
    if ( ! bt_hair_is_dashboard_page() ) {
        return;
    }

    if ( ! is_user_logged_in() ) {
        $redirect_target = is_singular() ? get_permalink() : bt_hair_dashboard_url();
        $redirect_url = add_query_arg(
            array(
                'redirect_to' => rawurlencode( $redirect_target ),
            ),
            bt_hair_sign_in_url()
        );
        wp_safe_redirect( $redirect_url );
        exit;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You are logged in, but do not have permission to access this dashboard.', 'Access Denied', array( 'response' => 403 ) );
    }
}
add_action( 'template_redirect', 'bt_hair_protect_dashboard_page' );

/**
 * Enqueue scripts and styles.
 */
function bt_hair_enqueue_assets() {
    $main_css_path   = get_template_directory() . '/assets/css/main.css';
    $public_js_path  = get_template_directory() . '/assets/js/public.js';
    $dashboard_js_path = get_template_directory() . '/assets/js/dashboard.js';

    $main_css_ver    = file_exists( $main_css_path ) ? (string) filemtime( $main_css_path ) : '1.0.0';
    $public_js_ver   = file_exists( $public_js_path ) ? (string) filemtime( $public_js_path ) : '1.0.0';
    $dashboard_js_ver = file_exists( $dashboard_js_path ) ? (string) filemtime( $dashboard_js_path ) : '1.0.0';

    wp_enqueue_style( 'bt-google-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;800&family=Manrope:wght@400;500;700&display=swap', array(), null );
    wp_enqueue_style( 'bt-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3' );
    wp_enqueue_style( 'bt-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', array(), '6.5.2' );
    wp_enqueue_style( 'bt-hair-main', get_template_directory_uri() . '/assets/css/main.css', array( 'bt-bootstrap' ), $main_css_ver );

    wp_enqueue_script( 'bt-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array(), '5.3.3', true );
    wp_enqueue_script( 'bt-sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11', true );

    if ( bt_hair_is_dashboard_page() ) {
        wp_enqueue_script( 'bt-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js', array(), '4.4.3', true );
        wp_enqueue_script( 'bt-hair-dashboard', get_template_directory_uri() . '/assets/js/dashboard.js', array( 'jquery', 'bt-chartjs', 'bt-sweetalert' ), $dashboard_js_ver, true );
        wp_localize_script(
            'bt-hair-dashboard',
            'btHairAdmin',
            array(
                'restUrl'      => esc_url_raw( bt_hair_rest_base_url() ),
                'nonce'        => wp_create_nonce( 'wp_rest' ),
                'dashboardUrl' => esc_url_raw( bt_hair_dashboard_url() ),
            )
        );
        return;
    }

    if ( ! bt_hair_is_sign_in_page() ) {
        wp_enqueue_script( 'bt-hair-public', get_template_directory_uri() . '/assets/js/public.js', array( 'jquery', 'bt-sweetalert' ), $public_js_ver, true );
        wp_localize_script(
            'bt-hair-public',
            'btHairPublic',
            array(
                'restUrl'        => esc_url_raw( bt_hair_rest_base_url() ),
                'chatWebhookUrl' => trim( (string) get_option( 'bt_hair_n8n_chat_webhook_url', '' ) ),
            )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'bt_hair_enqueue_assets' );

/**
 * Optional: keep default login page redirect behavior consistent.
 */
function bt_hair_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
    if ( isset( $user->ID ) && user_can( $user, 'manage_options' ) ) {
        return bt_hair_dashboard_url();
    }

    return $redirect_to;
}
add_filter( 'login_redirect', 'bt_hair_login_redirect', 10, 3 );

/**
 * Determine whether this site is running in a local/dev environment.
 *
 * @return bool
 */
function bt_hair_is_local_environment() {
    $env_type = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
    if ( in_array( $env_type, array( 'local', 'development' ), true ) ) {
        return true;
    }

    $host = strtolower( trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) );
    return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );
}

/**
 * Allow application passwords in local/dev environments for n8n callback auth.
 *
 * @param bool $available Default availability.
 * @return bool
 */
function bt_hair_allow_application_passwords_local( $available ) {
    if ( $available ) {
        return true;
    }

    return bt_hair_is_local_environment();
}
add_filter( 'wp_is_application_passwords_available', 'bt_hair_allow_application_passwords_local' );

/**
 * Allow application passwords for users in local/dev environments.
 *
 * @param bool    $available Default availability.
 * @param WP_User $user      User being checked.
 * @return bool
 */
function bt_hair_allow_application_passwords_local_for_user( $available, $user ) {
    if ( $available ) {
        return true;
    }

    return bt_hair_is_local_environment();
}
add_filter( 'wp_is_application_passwords_available_for_user', 'bt_hair_allow_application_passwords_local_for_user', 10, 2 );

/**
 * Permission callback for admin-only endpoints.
 *
 * @return bool
 */
function bt_hair_can_manage() {
    return current_user_can( 'manage_options' );
}

/**
 * Canonical timezone for appointment slots.
 *
 * @return DateTimeZone
 */
function bt_hair_slot_timezone() {
    return new DateTimeZone( 'America/New_York' );
}

/**
 * Current datetime in slot timezone (EST/EDT).
 *
 * @return string
 */
function bt_hair_slot_now_mysql() {
    $now = new DateTimeImmutable( 'now', bt_hair_slot_timezone() );
    return $now->format( 'Y-m-d H:i:s' );
}

/**
 * Build a human-readable label from DB datetimes.
 *
 * @param string $start Datetime start.
 * @param string $end Datetime end.
 * @return string
 */
function bt_hair_slot_label( $start, $end ) {
    $timezone = bt_hair_slot_timezone();
    $start_dt = date_create_immutable_from_format( 'Y-m-d H:i:s', (string) $start, $timezone );
    $end_dt   = date_create_immutable_from_format( 'Y-m-d H:i:s', (string) $end, $timezone );

    if ( ! $start_dt || ! $end_dt ) {
        return (string) $start . ' - ' . (string) $end . ' EST';
    }

    return $start_dt->format( 'm/d/Y g:iA' ) . ' - ' . $end_dt->format( 'g:iA' ) . ' EST';
}

/**
 * Register REST routes.
 */
function bt_hair_register_rest_routes() {
    register_rest_route(
        'bt-hair/v1',
        '/public/options',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'bt_hair_rest_public_options',
            'permission_callback' => '__return_true',
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/appointments',
        array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => 'bt_hair_rest_submit_appointment',
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'bt_hair_rest_appointments_list',
                'permission_callback' => 'bt_hair_can_manage',
            ),
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/services',
        array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'bt_hair_rest_services_list',
                'permission_callback' => 'bt_hair_can_manage',
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => 'bt_hair_rest_services_create',
                'permission_callback' => 'bt_hair_can_manage',
            ),
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/services/(?P<id>\\d+)',
        array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => 'bt_hair_rest_services_update',
                'permission_callback' => 'bt_hair_can_manage',
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => 'bt_hair_rest_services_delete',
                'permission_callback' => 'bt_hair_can_manage',
            ),
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/slots',
        array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'bt_hair_rest_slots_list',
                'permission_callback' => 'bt_hair_can_manage',
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => 'bt_hair_rest_slots_create',
                'permission_callback' => 'bt_hair_can_manage',
            ),
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/slots/(?P<id>\\d+)',
        array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => 'bt_hair_rest_slots_delete',
            'permission_callback' => 'bt_hair_can_manage',
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/appointments/(?P<id>\\d+)/status',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'bt_hair_rest_appointment_status',
            'permission_callback' => 'bt_hair_can_manage',
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/settings',
        array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'bt_hair_rest_settings_get',
                'permission_callback' => 'bt_hair_can_manage',
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => 'bt_hair_rest_settings_save',
                'permission_callback' => 'bt_hair_can_manage',
            ),
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/settings/test-chat',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'bt_hair_rest_settings_test_chat',
            'permission_callback' => 'bt_hair_can_manage',
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/settings/test-service',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'bt_hair_rest_settings_test_service',
            'permission_callback' => 'bt_hair_can_manage',
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/settings/generate-callback-auth',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'bt_hair_rest_settings_generate_callback_auth',
            'permission_callback' => 'bt_hair_can_manage',
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/chat',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'bt_hair_rest_chat',
            'permission_callback' => '__return_true',
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/chat/callback',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'bt_hair_rest_chat_callback',
            'permission_callback' => 'bt_hair_rest_chat_callback_can_post',
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/chat/reply',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'bt_hair_rest_chat_reply',
            'permission_callback' => '__return_true',
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/chat/callback-logs',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'bt_hair_rest_chat_callback_logs',
            'permission_callback' => 'bt_hair_can_manage',
        )
    );

    register_rest_route(
        'bt-hair/v1',
        '/chat/callback-logs/clear',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'bt_hair_rest_chat_callback_logs_clear',
            'permission_callback' => 'bt_hair_can_manage',
        )
    );
}
add_action( 'rest_api_init', 'bt_hair_register_rest_routes' );

/**
 * Return services and available slots for public booking.
 *
 * @return WP_REST_Response
 */
function bt_hair_rest_public_options() {
    global $wpdb;

    $tables   = bt_hair_tables();
    $now      = bt_hair_slot_now_mysql();
    $services = $wpdb->get_results( "SELECT id, service_name, price FROM {$tables['services']} ORDER BY service_name ASC", ARRAY_A );
    $slots    = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, slot_start, slot_end, label
            FROM {$tables['slots']}
            WHERE is_active = 1 AND slot_start >= %s
            ORDER BY slot_start ASC",
            $now
        ),
        ARRAY_A
    );

    return rest_ensure_response(
        array(
            'services' => $services,
            'slots'    => $slots,
        )
    );
}

/**
 * Submit a new public appointment request.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_submit_appointment( WP_REST_Request $request ) {
    global $wpdb;

    $tables      = bt_hair_tables();
    $full_name   = sanitize_text_field( (string) $request->get_param( 'full_name' ) );
    $email       = sanitize_email( (string) $request->get_param( 'email' ) );
    $phone       = sanitize_text_field( (string) $request->get_param( 'phone' ) );
    $service_id  = absint( $request->get_param( 'service_id' ) );
    $slot_id     = absint( $request->get_param( 'slot_id' ) );

    if ( '' === $full_name || '' === $email || '' === $phone || $service_id < 1 || $slot_id < 1 ) {
        return new WP_Error( 'invalid_payload', 'Please complete all required fields.', array( 'status' => 400 ) );
    }

    if ( ! is_email( $email ) ) {
        return new WP_Error( 'invalid_email', 'Email format is invalid.', array( 'status' => 400 ) );
    }

    $service = $wpdb->get_row(
        $wpdb->prepare( "SELECT id, service_name, price FROM {$tables['services']} WHERE id = %d", $service_id ),
        ARRAY_A
    );

    if ( ! $service ) {
        return new WP_Error( 'service_not_found', 'Selected service is no longer available.', array( 'status' => 404 ) );
    }

    $slot = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, slot_start, slot_end, label, is_active
            FROM {$tables['slots']}
            WHERE id = %d",
            $slot_id
        ),
        ARRAY_A
    );

    if ( ! $slot || 1 !== (int) $slot['is_active'] ) {
        return new WP_Error( 'slot_not_found', 'Selected appointment slot is not available.', array( 'status' => 404 ) );
    }

    if ( strtotime( $slot['slot_start'] ) < strtotime( bt_hair_slot_now_mysql() ) ) {
        return new WP_Error( 'slot_expired', 'Selected appointment slot has already passed.', array( 'status' => 409 ) );
    }

    $inserted = $wpdb->insert(
        $tables['appointments'],
        array(
            'full_name'         => $full_name,
            'email'             => $email,
            'phone'             => $phone,
            'service_id'        => $service_id,
            'slot_id'           => $slot_id,
            'appointment_start' => $slot['slot_start'],
            'appointment_end'   => $slot['slot_end'],
            'status'            => 'pending',
        ),
        array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
    );

    if ( ! $inserted ) {
        return new WP_Error( 'insert_failed', 'Failed to save appointment request.', array( 'status' => 500 ) );
    }

    $appointment_id = (int) $wpdb->insert_id;
    bt_hair_send_n8n_webhook(
        array(
            'appointment_id' => $appointment_id,
            'full_name'      => $full_name,
            'email'          => $email,
            'phone'          => $phone,
            'service'        => $service,
            'slot'           => $slot,
            'status'         => 'pending',
        )
    );

    return rest_ensure_response(
        array(
            'success'        => true,
            'appointment_id' => $appointment_id,
            'message'        => 'Appointment request submitted successfully.',
        )
    );
}

/**
 * Validate webhook URLs with local development allowance.
 *
 * Uses WordPress strict validation first. If that fails, this allows
 * localhost and common private/loopback ranges that WordPress blocks by default.
 *
 * @param string $url Webhook URL.
 * @return bool
 */
function bt_hair_is_valid_webhook_url( $url ) {
    $url = trim( (string) $url );

    if ( '' === $url ) {
        return true;
    }

    if ( wp_http_validate_url( $url ) ) {
        return true;
    }

    $parsed = wp_parse_url( $url );
    if ( ! is_array( $parsed ) || empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
        return false;
    }

    $scheme = strtolower( (string) $parsed['scheme'] );
    if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
        return false;
    }

    $host = strtolower( trim( (string) $parsed['host'] ) );
    if ( 'localhost' === $host || '127.0.0.1' === $host || '::1' === $host ) {
        return true;
    }

    if ( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
        $octets = array_map( 'intval', explode( '.', $host ) );
        if ( 10 === $octets[0]
            || 127 === $octets[0]
            || ( 172 === $octets[0] && 16 <= $octets[1] && 31 >= $octets[1] )
            || ( 192 === $octets[0] && 168 === $octets[1] )
        ) {
            return true;
        }
    }

    return false;
}

/**
 * Trigger n8n webhook.
 *
 * @param array<string, mixed> $payload Appointment payload.
 */
function bt_hair_send_n8n_webhook( $payload ) {
    $url = trim( (string) get_option( 'bt_hair_n8n_webhook_url', '' ) );

    if ( '' === $url || ! bt_hair_is_valid_webhook_url( $url ) ) {
        return;
    }

    wp_remote_post(
        $url,
        array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 10,
        )
    );
}

/**
 * Build possible chat webhook URL variants.
 *
 * Supports n8n production/test path variants and optional /chat suffix.
 *
 * @param string $url Base chat webhook URL.
 * @return array<int, string>
 */
function bt_hair_chat_webhook_candidates( $url ) {
    $url = trim( (string) $url );
    if ( '' === $url ) {
        return array();
    }

    $variants = array( $url );

    if ( false !== strpos( $url, '/webhook/' ) ) {
        $variants[] = str_replace( '/webhook/', '/webhook-test/', $url );
    } elseif ( false !== strpos( $url, '/webhook-test/' ) ) {
        $variants[] = str_replace( '/webhook-test/', '/webhook/', $url );
    }

    $expanded = array();
    foreach ( $variants as $variant ) {
        $expanded[] = $variant;

        if ( preg_match( '#/chat/?$#i', $variant ) ) {
            $expanded[] = preg_replace( '#/chat/?$#i', '', $variant );
        } else {
            $expanded[] = rtrim( $variant, '/' ) . '/chat';
        }
    }

    $final = array();
    foreach ( $expanded as $candidate ) {
        $candidate = trim( (string) $candidate );
        if ( '' === $candidate ) {
            continue;
        }

        if ( bt_hair_is_valid_webhook_url( $candidate ) && ! in_array( $candidate, $final, true ) ) {
            $final[] = $candidate;
        }
    }

    return $final;
}

/**
 * Merge unique chat webhook candidates from multiple source URLs.
 *
 * @param array<int, string> $urls Source URLs.
 * @return array<int, string>
 */
function bt_hair_merge_chat_webhook_candidates( $urls ) {
    $merged = array();

    foreach ( $urls as $url ) {
        $candidates = bt_hair_chat_webhook_candidates( $url );
        foreach ( $candidates as $candidate ) {
            if ( ! in_array( $candidate, $merged, true ) ) {
                $merged[] = $candidate;
            }
        }
    }

    return $merged;
}

/**
 * Build chat webhook candidates that preserve exact path and only toggle
 * webhook mode between /webhook/ and /webhook-test/.
 *
 * @param string $url Chat webhook URL.
 * @return array<int, string>
 */
function bt_hair_chat_webhook_mode_candidates( $url ) {
    $url = trim( (string) $url );
    if ( '' === $url ) {
        return array();
    }

    $candidates = array( $url );

    if ( false !== strpos( $url, '/webhook/' ) ) {
        $candidates[] = str_replace( '/webhook/', '/webhook-test/', $url );
    } elseif ( false !== strpos( $url, '/webhook-test/' ) ) {
        $candidates[] = str_replace( '/webhook-test/', '/webhook/', $url );
    }

    $final = array();
    foreach ( $candidates as $candidate ) {
        $candidate = trim( (string) $candidate );
        if ( '' === $candidate ) {
            continue;
        }

        if ( bt_hair_is_valid_webhook_url( $candidate ) && ! in_array( $candidate, $final, true ) ) {
            $final[] = $candidate;
        }
    }

    return $final;
}

/**
 * Build transient key for storing callback chat replies.
 *
 * @param string $session_id Chat session identifier.
 * @return string
 */
function bt_hair_chat_reply_transient_key( $session_id ) {
    return 'bt_hair_chat_reply_' . md5( (string) $session_id );
}

/**
 * Append callback event to rolling log storage.
 *
 * @param array<string, mixed> $entry Log entry.
 */
function bt_hair_log_chat_callback_event( $entry ) {
    $logs = get_option( 'bt_hair_chatbot_callback_logs', array() );
    if ( ! is_array( $logs ) ) {
        $logs = array();
    }

    array_unshift( $logs, $entry );
    $logs = array_slice( $logs, 0, 100 );

    update_option( 'bt_hair_chatbot_callback_logs', $logs );
}

/**
 * Permission callback for n8n chat callback route.
 *
 * Requires a valid authenticated admin user (supports application passwords)
 * and the callback API key generated from dashboard settings.
 *
 * @param WP_REST_Request $request Request.
 * @return true|WP_Error
 */
function bt_hair_rest_chat_callback_can_post( WP_REST_Request $request ) {
    $is_protected = '1' === (string) get_option( 'bt_hair_chatbot_protected', '1' );
    $session_id    = sanitize_text_field( (string) $request->get_param( 'session_id' ) );

    $log_base = array(
        'time'       => current_time( 'mysql' ),
        'remote_ip'  => sanitize_text_field( (string) $request->get_header( 'x-forwarded-for' ) ?: (string) $request->get_header( 'x-real-ip' ) ?: (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
        'user_agent' => sanitize_text_field( substr( (string) $request->get_header( 'user-agent' ), 0, 190 ) ),
        'session_id' => $session_id,
    );

    if ( ! $is_protected ) {
        // Explicitly allow open callback mode when Secure toggle is off.
        return true;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        bt_hair_log_chat_callback_event(
            array_merge(
                $log_base,
                array(
                    'status' => 'error',
                    'result' => 'auth_failed',
                    'note'   => 'Callback auth failed (application password or user capability).',
                )
            )
        );
        return new WP_Error( 'forbidden', 'Authentication failed for callback endpoint.', array( 'status' => 401 ) );
    }

    $saved_key = trim( (string) get_option( 'bt_hair_chatbot_callback_key', '' ) );
    $sent_key  = trim( (string) $request->get_header( 'x-bt-chatbot-callback-key' ) );

    if ( '' === $saved_key ) {
        bt_hair_log_chat_callback_event(
            array_merge(
                $log_base,
                array(
                    'status' => 'error',
                    'result' => 'callback_key_missing',
                    'note'   => 'Callback key is not configured in WordPress.',
                )
            )
        );
        return new WP_Error( 'callback_key_missing', 'Callback key is not configured. Generate one from dashboard first.', array( 'status' => 400 ) );
    }

    if ( '' === $sent_key || ! hash_equals( $saved_key, $sent_key ) ) {
        bt_hair_log_chat_callback_event(
            array_merge(
                $log_base,
                array(
                    'status' => 'error',
                    'result' => 'invalid_callback_key',
                    'note'   => 'Invalid or missing X-BT-Chatbot-Callback-Key.',
                )
            )
        );
        return new WP_Error( 'invalid_callback_key', 'Invalid callback key.', array( 'status' => 403 ) );
    }

    return true;
}

/**
 * Handle AI chat message: proxy to configured n8n chat webhook.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_chat( WP_REST_Request $request ) {
    $message    = sanitize_text_field( (string) $request->get_param( 'message' ) );
    $session_id = sanitize_text_field( (string) $request->get_param( 'session_id' ) );

    if ( '1' !== (string) get_option( 'bt_hair_chatbot_enabled', '0' ) ) {
        return new WP_Error( 'chat_disabled', 'AI chat is currently disabled.', array( 'status' => 403 ) );
    }

    if ( '' === $message ) {
        return new WP_Error( 'empty_message', 'Message cannot be empty.', array( 'status' => 400 ) );
    }

    $chat_url = trim( (string) get_option( 'bt_hair_n8n_chat_webhook_url', '' ) );

    $candidate_urls = bt_hair_chat_webhook_candidates( $chat_url );

    if ( empty( $candidate_urls ) ) {
        return new WP_Error( 'chat_unavailable', 'AI chat is not configured. Please contact the salon directly.', array( 'status' => 503 ) );
    }

    $response    = null;
    $status_code = 0;

    foreach ( $candidate_urls as $candidate_url ) {
        $try_response = wp_remote_post(
            $candidate_url,
            array(
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => wp_json_encode(
                    array(
                        'message'    => $message,
                        'session_id' => $session_id,
                        'api_key'    => (string) get_option( 'bt_hair_chatbot_api_key', '' ),
                    )
                ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $try_response ) ) {
            continue;
        }

        $response    = $try_response;
        $status_code = (int) wp_remote_retrieve_response_code( $try_response );

        if ( $status_code >= 200 && $status_code < 300 ) {
            break;
        }
    }

    if ( ! $response ) {
        return new WP_Error( 'chat_error', 'Unable to reach AI agent. Please try again later.', array( 'status' => 502 ) );
    }

    if ( $status_code < 200 || $status_code >= 300 ) {
        return new WP_Error( 'chat_error', 'AI agent returned an unexpected response. Please check the n8n chat webhook URL and workflow activation.', array( 'status' => 502 ) );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    $reply = '';
    if ( is_array( $data ) ) {
        $reply = (string) ( $data['reply'] ?? $data['output'] ?? $data['message'] ?? $data['response'] ?? $data['text'] ?? '' );
    }

    if ( '' === $reply ) {
        $reply = is_string( $body ) ? trim( $body ) : 'No response from AI.';
    }

    return rest_ensure_response( array( 'reply' => $reply ) );
}

/**
 * Get services for dashboard.
 *
 * @return WP_REST_Response
 */
function bt_hair_rest_services_list() {
    global $wpdb;

    $tables   = bt_hair_tables();
    $services = $wpdb->get_results( "SELECT id, service_name, price FROM {$tables['services']} ORDER BY service_name ASC", ARRAY_A );

    return rest_ensure_response( $services );
}

/**
 * Create service.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_services_create( WP_REST_Request $request ) {
    global $wpdb;

    $tables       = bt_hair_tables();
    $service_name = sanitize_text_field( (string) $request->get_param( 'service_name' ) );
    $price_raw    = $request->get_param( 'price' );
    $price        = is_numeric( $price_raw ) ? (float) $price_raw : -1;

    if ( '' === $service_name || $price < 0 ) {
        return new WP_Error( 'invalid_service', 'Service name and valid price are required.', array( 'status' => 400 ) );
    }

    $wpdb->insert(
        $tables['services'],
        array(
            'service_name' => $service_name,
            'price'        => number_format( $price, 2, '.', '' ),
        ),
        array( '%s', '%f' )
    );

    if ( ! $wpdb->insert_id ) {
        return new WP_Error( 'insert_failed', 'Unable to create service.', array( 'status' => 500 ) );
    }

    return rest_ensure_response(
        array(
            'id'           => (int) $wpdb->insert_id,
            'service_name' => $service_name,
            'price'        => number_format( $price, 2, '.', '' ),
        )
    );
}

/**
 * Update service.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_services_update( WP_REST_Request $request ) {
    global $wpdb;

    $tables       = bt_hair_tables();
    $id           = absint( $request['id'] );
    $service_name = sanitize_text_field( (string) $request->get_param( 'service_name' ) );
    $price_raw    = $request->get_param( 'price' );
    $price        = is_numeric( $price_raw ) ? (float) $price_raw : -1;

    if ( $id < 1 || '' === $service_name || $price < 0 ) {
        return new WP_Error( 'invalid_service', 'Service id, name and price are required.', array( 'status' => 400 ) );
    }

    $updated = $wpdb->update(
        $tables['services'],
        array(
            'service_name' => $service_name,
            'price'        => number_format( $price, 2, '.', '' ),
        ),
        array( 'id' => $id ),
        array( '%s', '%f' ),
        array( '%d' )
    );

    if ( false === $updated ) {
        return new WP_Error( 'update_failed', 'Unable to update service.', array( 'status' => 500 ) );
    }

    return rest_ensure_response( array( 'success' => true ) );
}

/**
 * Delete service.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_services_delete( WP_REST_Request $request ) {
    global $wpdb;

    $tables = bt_hair_tables();
    $id     = absint( $request['id'] );

    if ( $id < 1 ) {
        return new WP_Error( 'invalid_id', 'Invalid service id.', array( 'status' => 400 ) );
    }

    $usage_count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$tables['appointments']} WHERE service_id = %d AND status IN ('pending', 'accepted')",
            $id
        )
    );

    if ( $usage_count > 0 ) {
        return new WP_Error( 'service_in_use', 'This service has active appointments and cannot be removed.', array( 'status' => 409 ) );
    }

    $deleted = $wpdb->delete( $tables['services'], array( 'id' => $id ), array( '%d' ) );

    if ( ! $deleted ) {
        return new WP_Error( 'delete_failed', 'Unable to delete service.', array( 'status' => 500 ) );
    }

    return rest_ensure_response( array( 'success' => true ) );
}

/**
 * List slots for dashboard.
 *
 * @return WP_REST_Response
 */
function bt_hair_rest_slots_list() {
    global $wpdb;

    $tables = bt_hair_tables();
    $slots  = $wpdb->get_results(
        "SELECT id, slot_start, slot_end, label, is_active
        FROM {$tables['slots']}
        ORDER BY slot_start DESC",
        ARRAY_A
    );

    return rest_ensure_response( $slots );
}

/**
 * Create slot entry.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_slots_create( WP_REST_Request $request ) {
    global $wpdb;

    $tables     = bt_hair_tables();
    $date       = sanitize_text_field( (string) $request->get_param( 'date' ) );
    $start_time = sanitize_text_field( (string) $request->get_param( 'start_time' ) );
    $end_time   = sanitize_text_field( (string) $request->get_param( 'end_time' ) );

    if ( '' === $date || '' === $start_time || '' === $end_time ) {
        return new WP_Error( 'invalid_slot', 'Date, start time and end time are required.', array( 'status' => 400 ) );
    }

    $timezone = bt_hair_slot_timezone();
    $start_dt = date_create_immutable_from_format( 'Y-m-d H:i', $date . ' ' . $start_time, $timezone );
    $end_dt   = date_create_immutable_from_format( 'Y-m-d H:i', $date . ' ' . $end_time, $timezone );

    if ( ! $start_dt || ! $end_dt || $end_dt <= $start_dt ) {
        return new WP_Error( 'invalid_slot', 'Slot start and end values are invalid.', array( 'status' => 400 ) );
    }

    $start = $start_dt->format( 'Y-m-d H:i:s' );
    $end   = $end_dt->format( 'Y-m-d H:i:s' );

    $overlap_count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*)
            FROM {$tables['slots']}
            WHERE is_active = 1
                AND %s < slot_end
                AND %s > slot_start",
            $start,
            $end
        )
    );

    if ( $overlap_count > 0 ) {
        return new WP_Error( 'overlap', 'This time overlaps an existing active slot.', array( 'status' => 409 ) );
    }

    $label = bt_hair_slot_label( $start, $end );

    $wpdb->insert(
        $tables['slots'],
        array(
            'slot_start' => $start,
            'slot_end'   => $end,
            'label'      => $label,
            'is_active'  => 1,
        ),
        array( '%s', '%s', '%s', '%d' )
    );

    if ( ! $wpdb->insert_id ) {
        return new WP_Error( 'insert_failed', 'Unable to create time slot.', array( 'status' => 500 ) );
    }

    return rest_ensure_response(
        array(
            'id'         => (int) $wpdb->insert_id,
            'slot_start' => $start,
            'slot_end'   => $end,
            'label'      => $label,
            'is_active'  => 1,
        )
    );
}

/**
 * Delete slot.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_slots_delete( WP_REST_Request $request ) {
    global $wpdb;

    $tables = bt_hair_tables();
    $id     = absint( $request['id'] );

    if ( $id < 1 ) {
        return new WP_Error( 'invalid_id', 'Invalid slot id.', array( 'status' => 400 ) );
    }

    $usage_count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$tables['appointments']} WHERE slot_id = %d AND status IN ('pending', 'accepted')",
            $id
        )
    );

    if ( $usage_count > 0 ) {
        return new WP_Error( 'slot_in_use', 'Slot cannot be removed while active appointments exist.', array( 'status' => 409 ) );
    }

    $deleted = $wpdb->delete( $tables['slots'], array( 'id' => $id ), array( '%d' ) );

    if ( ! $deleted ) {
        return new WP_Error( 'delete_failed', 'Unable to delete slot.', array( 'status' => 500 ) );
    }

    return rest_ensure_response( array( 'success' => true ) );
}

/**
 * List appointments for dashboard.
 *
 * @return WP_REST_Response
 */
function bt_hair_rest_appointments_list() {
    global $wpdb;

    $tables = bt_hair_tables();

    $appointments = $wpdb->get_results(
        "SELECT
            a.id,
            a.full_name,
            a.email,
            a.phone,
            a.status,
            a.created_at,
            a.slot_id,
            a.appointment_start,
            a.appointment_end,
            s.service_name,
            s.price,
            sl.label AS slot_label
        FROM {$tables['appointments']} a
        LEFT JOIN {$tables['services']} s ON s.id = a.service_id
        LEFT JOIN {$tables['slots']} sl ON sl.id = a.slot_id
        ORDER BY a.created_at DESC",
        ARRAY_A
    );

    return rest_ensure_response( $appointments );
}

/**
 * Accept or reject appointment.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_appointment_status( WP_REST_Request $request ) {
    global $wpdb;

    $tables = bt_hair_tables();
    $id     = absint( $request['id'] );
    $status = sanitize_text_field( (string) $request->get_param( 'status' ) );

    if ( $id < 1 || ! in_array( $status, array( 'accepted', 'rejected' ), true ) ) {
        return new WP_Error( 'invalid_status', 'Invalid appointment id or status.', array( 'status' => 400 ) );
    }

    $appointment = $wpdb->get_row(
        $wpdb->prepare( "SELECT id, slot_id, status FROM {$tables['appointments']} WHERE id = %d", $id ),
        ARRAY_A
    );

    if ( ! $appointment ) {
        return new WP_Error( 'not_found', 'Appointment not found.', array( 'status' => 404 ) );
    }

    if ( 'accepted' === $status ) {
        $slot_id = (int) $appointment['slot_id'];

        $accepted_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$tables['appointments']} WHERE slot_id = %d AND status = 'accepted' AND id != %d",
                $slot_id,
                $id
            )
        );

        if ( $accepted_count > 0 ) {
            return new WP_Error( 'already_booked', 'This slot has already been accepted for another request.', array( 'status' => 409 ) );
        }

        $wpdb->update(
            $tables['appointments'],
            array( 'status' => 'accepted' ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );

        // Ensure accepted bookings disappear from public availability immediately.
        $wpdb->update(
            $tables['slots'],
            array( 'is_active' => 0 ),
            array( 'id' => $slot_id ),
            array( '%d' ),
            array( '%d' )
        );

        // Reject competing pending requests for the same slot.
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$tables['appointments']}
                SET status = 'rejected'
                WHERE slot_id = %d AND status = 'pending' AND id != %d",
                $slot_id,
                $id
            )
        );

        return rest_ensure_response( array( 'success' => true, 'status' => 'accepted' ) );
    }

    $wpdb->update(
        $tables['appointments'],
        array( 'status' => 'rejected' ),
        array( 'id' => $id ),
        array( '%s' ),
        array( '%d' )
    );

    return rest_ensure_response( array( 'success' => true, 'status' => 'rejected' ) );
}

/**
 * Get dashboard settings.
 *
 * @return WP_REST_Response
 */
function bt_hair_rest_settings_get() {
    $service_webhook_url = (string) get_option( 'bt_hair_n8n_webhook_url', '' );

    return rest_ensure_response(
        array(
            'service_webhook_url' => $service_webhook_url,
            'webhook_url'         => $service_webhook_url,
            'chat_webhook_url' => (string) get_option( 'bt_hair_n8n_chat_webhook_url', '' ),
            'chatbot_api_key'  => (string) get_option( 'bt_hair_chatbot_api_key', '' ),
            'chatbot_enabled'  => '1' === (string) get_option( 'bt_hair_chatbot_enabled', '0' ),
            'chatbot_protected' => '1' === (string) get_option( 'bt_hair_chatbot_protected', '1' ),
        )
    );
}

/**
 * Save dashboard settings.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_settings_save( WP_REST_Request $request ) {
    $url = (string) get_option( 'bt_hair_n8n_webhook_url', '' );
    if ( $request->has_param( 'service_webhook_url' ) ) {
        $url = trim( (string) $request->get_param( 'service_webhook_url' ) );
    } elseif ( $request->has_param( 'webhook_url' ) ) {
        $url = trim( (string) $request->get_param( 'webhook_url' ) );
    }

    $chat_url    = $request->has_param( 'chat_webhook_url' ) ? trim( (string) $request->get_param( 'chat_webhook_url' ) ) : (string) get_option( 'bt_hair_n8n_chat_webhook_url', '' );
    $chatbot_key = $request->has_param( 'chatbot_api_key' ) ? trim( sanitize_text_field( (string) $request->get_param( 'chatbot_api_key' ) ) ) : (string) get_option( 'bt_hair_chatbot_api_key', '' );

    $chatbot_enabled = '1' === (string) get_option( 'bt_hair_chatbot_enabled', '0' ) ? '1' : '0';
    if ( $request->has_param( 'chatbot_enabled' ) ) {
        $chatbot_enabled = rest_sanitize_boolean( $request->get_param( 'chatbot_enabled' ) ) ? '1' : '0';
    }

    $chatbot_protected = '1' === (string) get_option( 'bt_hair_chatbot_protected', '1' ) ? '1' : '0';
    if ( $request->has_param( 'chatbot_protected' ) ) {
        $chatbot_protected = rest_sanitize_boolean( $request->get_param( 'chatbot_protected' ) ) ? '1' : '0';
    }

    if ( ! bt_hair_is_valid_webhook_url( $url ) ) {
        return new WP_Error( 'invalid_url', 'Webhook URL is invalid.', array( 'status' => 400 ) );
    }

    if ( ! bt_hair_is_valid_webhook_url( $chat_url ) ) {
        return new WP_Error( 'invalid_chat_url', 'Chat Webhook URL is invalid.', array( 'status' => 400 ) );
    }

    update_option( 'bt_hair_n8n_webhook_url', $url );
    update_option( 'bt_hair_n8n_chat_webhook_url', $chat_url );
    update_option( 'bt_hair_chatbot_api_key', $chatbot_key );
    update_option( 'bt_hair_chatbot_enabled', $chatbot_enabled );
    update_option( 'bt_hair_chatbot_protected', $chatbot_protected );

    return rest_ensure_response( array( 'success' => true ) );
}

/**
 * Test chat webhook from dashboard settings.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_settings_test_chat( WP_REST_Request $request ) {
    $chat_url = $request->has_param( 'chat_webhook_url' ) ? trim( (string) $request->get_param( 'chat_webhook_url' ) ) : trim( (string) get_option( 'bt_hair_n8n_chat_webhook_url', '' ) );

    $attempt_urls = bt_hair_chat_webhook_candidates( $chat_url );

    if ( empty( $attempt_urls ) ) {
        return new WP_Error( 'invalid_chat_url', 'Chat Webhook URL is invalid or empty.', array( 'status' => 400 ) );
    }

    $status_code   = 0;
    $body          = '';
    $used_url      = $attempt_urls[0];
    $used_payload  = array();
    $used_format   = '';
    $attempt_count = 0;

    $test_payloads = array(
        'wp_message' => array(
            'message'    => 'Dashboard connectivity test',
            'session_id' => 'dashboard-test',
            'api_key'    => (string) get_option( 'bt_hair_chatbot_api_key', '' ),
            'test'       => true,
        ),
        'n8n_chat_widget' => array(
            'action'    => 'sendMessage',
            'chatInput' => 'Dashboard connectivity test',
            'sessionId' => 'dashboard-test',
            'metadata'  => array(
                'source' => 'dashboard-chat-test',
            ),
            'test'      => true,
        ),
        'simple_message' => array(
            'message' => 'Dashboard connectivity test',
            'test'    => true,
        ),
    );

    foreach ( $attempt_urls as $candidate_url ) {
        foreach ( $test_payloads as $format => $test_payload ) {
            $attempt_count++;
            $response = wp_remote_post(
                $candidate_url,
                array(
                    'headers' => array( 'Content-Type' => 'application/json' ),
                    'body'    => wp_json_encode( $test_payload ),
                    'timeout' => 20,
                )
            );

            if ( is_wp_error( $response ) ) {
                continue;
            }

            $status_code  = (int) wp_remote_retrieve_response_code( $response );
            $body         = (string) wp_remote_retrieve_body( $response );
            $used_url     = $candidate_url;
            $used_payload = $test_payload;
            $used_format  = $format;

            if ( $status_code >= 200 && $status_code < 300 ) {
                break 2;
            }
        }
    }

    if ( 0 === $status_code ) {
        return new WP_Error( 'chat_test_failed', 'Unable to reach chat webhook. Verified ' . $attempt_count . ' URL variant(s). Check n8n webhook URL and workflow activation.', array( 'status' => 502 ) );
    }

    $data = json_decode( $body, true );

    $reply = '';
    if ( is_array( $data ) ) {
        $primary = $data;
        if ( isset( $data[0] ) && is_array( $data[0] ) ) {
            $primary = $data[0];
        }

        $reply = (string) ( $primary['reply'] ?? $primary['output'] ?? $primary['message'] ?? $primary['response'] ?? $primary['text'] ?? '' );
    }

    if ( '' === $reply ) {
        $reply = trim( $body );
    }

    $hint = '';
    if ( 404 === $status_code ) {
        $hint = 'n8n returned 404. TROUBLESHOOTING: (1) Verify n8n has an active webhook at this URL; (2) Confirm the URL path exactly matches your published webhook; (3) If your workflow uses /chat, ensure that suffix is present; (4) Check n8n execution logs for incoming test calls.';
    } elseif ( $status_code >= 400 ) {
        $hint = 'n8n returned ' . $status_code . '. Check n8n workflow logs. The test tried multiple payload formats to match different chat workflow schemas.';
    }

    return rest_ensure_response(
        array(
            'success'     => $status_code >= 200 && $status_code < 300,
            'status_code' => $status_code,
            'reply'       => $reply,
            'used_url'    => $used_url,
            'used_format' => $used_format,
            'hint'        => $hint,
            'payload'     => $used_payload,
        )
    );
}

/**
 * Test service webhook from dashboard settings.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_settings_test_service( WP_REST_Request $request ) {
    $url = trim( (string) get_option( 'bt_hair_n8n_webhook_url', '' ) );
    if ( $request->has_param( 'service_webhook_url' ) ) {
        $url = trim( (string) $request->get_param( 'service_webhook_url' ) );
    } elseif ( $request->has_param( 'webhook_url' ) ) {
        $url = trim( (string) $request->get_param( 'webhook_url' ) );
    }

    if ( '' === $url || ! bt_hair_is_valid_webhook_url( $url ) ) {
        return new WP_Error( 'invalid_service_url', 'Service Webhook URL is invalid or empty.', array( 'status' => 400 ) );
    }

    $response = wp_remote_post(
        $url,
        array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode(
                array(
                    'test'           => true,
                    'source'         => 'dashboard-service-test',
                    'appointment_id' => 0,
                    'full_name'      => 'Dashboard Test Client',
                    'email'          => 'test@example.com',
                    'phone'          => '0000000000',
                    'service'        => array(
                        'id'           => 0,
                        'service_name' => 'Connectivity Test',
                        'price'        => '0.00',
                    ),
                    'slot'           => array(
                        'id'         => 0,
                        'slot_start' => current_time( 'mysql' ),
                        'slot_end'   => current_time( 'mysql' ),
                        'label'      => 'Connectivity Test Slot',
                    ),
                    'status'         => 'pending',
                )
            ),
            'timeout' => 20,
        )
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'service_test_failed', 'Unable to reach service webhook.', array( 'status' => 502 ) );
    }

    $status_code = (int) wp_remote_retrieve_response_code( $response );
    $body        = (string) wp_remote_retrieve_body( $response );

    $hint = '';
    if ( 404 === $status_code ) {
        $hint = 'n8n returned 404. Confirm workflow is active and URL path matches this service webhook.';
    }

    return rest_ensure_response(
        array(
            'success'     => $status_code >= 200 && $status_code < 300,
            'status_code' => $status_code,
            'reply'       => trim( $body ),
            'used_url'    => $url,
            'hint'        => $hint,
        )
    );
}

/**
 * Generate callback API key and application password for n8n callback auth.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_settings_generate_callback_auth( WP_REST_Request $request ) {
    $user = wp_get_current_user();
    $force_regenerate = $request->has_param( 'force_regenerate' ) ? rest_sanitize_boolean( $request->get_param( 'force_regenerate' ) ) : false;

    if ( ! $user instanceof WP_User || $user->ID < 1 ) {
        return new WP_Error( 'unauthorized', 'You must be logged in to generate callback credentials.', array( 'status' => 401 ) );
    }

    $existing_callback_key = trim( (string) get_option( 'bt_hair_chatbot_callback_key', '' ) );

    if ( '' !== $existing_callback_key && ! $force_regenerate ) {
        return rest_ensure_response(
            array(
                'success'              => true,
                'generated_new'        => false,
                'callback_url'         => esc_url_raw( rest_url( 'bt-hair/v1/chat/callback' ) ),
                'callback_key'         => $existing_callback_key,
                'wp_username'          => (string) $user->user_login,
                'application_password' => '',
                'app_name'             => '',
                'header_name'          => 'X-BT-Chatbot-Callback-Key',
                'notice'               => 'Existing callback key loaded. Use Generate New only if you want to rotate credentials.',
            )
        );
    }

    if ( ! class_exists( 'WP_Application_Passwords' ) ) {
        return new WP_Error( 'app_password_unavailable', 'Application passwords are not available on this site.', array( 'status' => 500 ) );
    }

    if ( function_exists( 'wp_is_application_passwords_available_for_user' )
        && ! wp_is_application_passwords_available_for_user( $user )
    ) {
        return new WP_Error( 'app_password_unavailable', 'Application passwords are not available for this user.', array( 'status' => 400 ) );
    }

    $callback_key = wp_generate_password( 40, false, false );
    update_option( 'bt_hair_chatbot_callback_key', $callback_key );

    $app_name = sprintf( 'BT Hair Salon n8n Callback %s', wp_date( 'Y-m-d H:i:s' ) );
    $created  = WP_Application_Passwords::create_new_application_password(
        $user->ID,
        array(
            'name'   => $app_name,
            'app_id' => wp_generate_uuid4(),
        )
    );

    if ( is_wp_error( $created ) ) {
        return new WP_Error( 'app_password_error', $created->get_error_message(), array( 'status' => 500 ) );
    }

    $app_password = isset( $created[0] ) ? (string) $created[0] : '';

    return rest_ensure_response(
        array(
            'success'              => true,
            'generated_new'        => true,
            'callback_url'         => esc_url_raw( rest_url( 'bt-hair/v1/chat/callback' ) ),
            'callback_key'         => $callback_key,
            'wp_username'          => (string) $user->user_login,
            'application_password' => $app_password,
            'app_name'             => $app_name,
            'header_name'          => 'X-BT-Chatbot-Callback-Key',
            'notice'               => 'New callback credentials generated.',
        )
    );
}

/**
 * Receive chatbot reply callbacks from n8n and store by session id.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_chat_callback( WP_REST_Request $request ) {
    $session_id = sanitize_text_field( (string) $request->get_param( 'session_id' ) );
    $reply      = trim( (string) $request->get_param( 'reply' ) );
    $result     = sanitize_text_field( (string) $request->get_param( 'result' ) );

    $log_base = array(
        'time'       => current_time( 'mysql' ),
        'remote_ip'  => sanitize_text_field( (string) $request->get_header( 'x-forwarded-for' ) ?: (string) $request->get_header( 'x-real-ip' ) ?: (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
        'user_agent' => sanitize_text_field( substr( (string) $request->get_header( 'user-agent' ), 0, 190 ) ),
        'session_id' => $session_id,
    );

    if ( '' === $session_id ) {
        bt_hair_log_chat_callback_event(
            array_merge(
                $log_base,
                array(
                    'status' => 'error',
                    'result' => 'error',
                    'note'   => 'Missing session_id',
                )
            )
        );
        return new WP_Error( 'invalid_session', 'session_id is required.', array( 'status' => 400 ) );
    }

    if ( '' === $reply ) {
        $reply = trim( (string) $request->get_param( 'message' ) );
    }

    if ( '' === $reply ) {
        bt_hair_log_chat_callback_event(
            array_merge(
                $log_base,
                array(
                    'status' => 'error',
                    'result' => 'error',
                    'note'   => 'Missing reply',
                )
            )
        );
        return new WP_Error( 'invalid_reply', 'reply is required.', array( 'status' => 400 ) );
    }

    if ( '' === $result ) {
        $result = 'success';
    }

    $reply_preview = sanitize_text_field( substr( $reply, 0, 180 ) );

    set_transient( bt_hair_chat_reply_transient_key( $session_id ), $reply, MINUTE_IN_SECONDS * 10 );

    bt_hair_log_chat_callback_event(
        array_merge(
            $log_base,
            array(
                'status'       => 'ok',
                'result'       => $result,
                'reply_length' => strlen( $reply ),
                'reply_preview' => $reply_preview,
            )
        )
    );

    return rest_ensure_response(
        array(
            'success'    => true,
            'session_id' => $session_id,
        )
    );
}

/**
 * Retrieve latest callback reply for chatbot session.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bt_hair_rest_chat_reply( WP_REST_Request $request ) {
    $session_id = sanitize_text_field( (string) $request->get_param( 'session_id' ) );

    if ( '' === $session_id ) {
        return new WP_Error( 'invalid_session', 'session_id is required.', array( 'status' => 400 ) );
    }

    $consume = ! $request->has_param( 'consume' ) || rest_sanitize_boolean( $request->get_param( 'consume' ) );
    $key     = bt_hair_chat_reply_transient_key( $session_id );
    $reply   = get_transient( $key );

    if ( false === $reply || '' === trim( (string) $reply ) ) {
        return rest_ensure_response(
            array(
                'found' => false,
            )
        );
    }

    if ( $consume ) {
        delete_transient( $key );
    }

    return rest_ensure_response(
        array(
            'found'      => true,
            'reply'      => (string) $reply,
            'session_id' => $session_id,
        )
    );
}

/**
 * Return callback logs for dashboard debugging.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bt_hair_rest_chat_callback_logs( WP_REST_Request $request ) {
    $limit = absint( $request->get_param( 'limit' ) );
    if ( $limit < 1 ) {
        $limit = 25;
    }
    if ( $limit > 100 ) {
        $limit = 100;
    }

    $logs = get_option( 'bt_hair_chatbot_callback_logs', array() );
    if ( ! is_array( $logs ) ) {
        $logs = array();
    }

    return rest_ensure_response(
        array(
            'logs'  => array_slice( $logs, 0, $limit ),
            'total' => count( $logs ),
        )
    );
}

/**
 * Clear callback logs from admin dashboard.
 *
 * @return WP_REST_Response
 */
function bt_hair_rest_chat_callback_logs_clear() {
    update_option( 'bt_hair_chatbot_callback_logs', array() );

    return rest_ensure_response(
        array(
            'success' => true,
        )
    );
}
