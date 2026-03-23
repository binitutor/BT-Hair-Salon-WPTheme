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
    $setup_version = '1.0.1';
    $current       = get_option( 'bt_hair_setup_version', '' );

    if ( $current === $setup_version ) {
        return;
    }

    bt_hair_ensure_required_pages();
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
    wp_enqueue_style( 'bt-google-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;800&family=Manrope:wght@400;500;700&display=swap', array(), null );
    wp_enqueue_style( 'bt-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3' );
    wp_enqueue_style( 'bt-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', array(), '6.5.2' );
    wp_enqueue_style( 'bt-hair-main', get_template_directory_uri() . '/assets/css/main.css', array( 'bt-bootstrap' ), '1.0.0' );

    wp_enqueue_script( 'bt-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array(), '5.3.3', true );
    wp_enqueue_script( 'bt-sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11', true );

    if ( bt_hair_is_dashboard_page() ) {
        wp_enqueue_script( 'bt-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js', array(), '4.4.3', true );
        wp_enqueue_script( 'bt-hair-dashboard', get_template_directory_uri() . '/assets/js/dashboard.js', array( 'jquery', 'bt-chartjs', 'bt-sweetalert' ), '1.0.0', true );
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
        wp_enqueue_script( 'bt-hair-public', get_template_directory_uri() . '/assets/js/public.js', array( 'jquery', 'bt-sweetalert' ), '1.0.0', true );
        wp_localize_script(
            'bt-hair-public',
            'btHairPublic',
            array(
                'restUrl' => esc_url_raw( bt_hair_rest_base_url() ),
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
 * Permission callback for admin-only endpoints.
 *
 * @return bool
 */
function bt_hair_can_manage() {
    return current_user_can( 'manage_options' );
}

/**
 * Build a human-readable label from DB datetimes.
 *
 * @param string $start Datetime start.
 * @param string $end Datetime end.
 * @return string
 */
function bt_hair_slot_label( $start, $end ) {
    return wp_date( 'm/d/Y g:iA', strtotime( $start ) ) . ' - ' . wp_date( 'g:iA', strtotime( $end ) );
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
    $now      = current_time( 'mysql' );
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

    if ( strtotime( $slot['slot_start'] ) < strtotime( current_time( 'mysql' ) ) ) {
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

    $timezone = wp_timezone();
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
    return rest_ensure_response(
        array(
            'webhook_url' => (string) get_option( 'bt_hair_n8n_webhook_url', '' ),
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
    $url = trim( (string) $request->get_param( 'webhook_url' ) );

    if ( ! bt_hair_is_valid_webhook_url( $url ) ) {
        return new WP_Error( 'invalid_url', 'Webhook URL is invalid.', array( 'status' => 400 ) );
    }

    update_option( 'bt_hair_n8n_webhook_url', $url );

    return rest_ensure_response( array( 'success' => true ) );
}
