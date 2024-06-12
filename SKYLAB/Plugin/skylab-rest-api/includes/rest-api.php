<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the REST API routes.
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'wp-site-info/v1', '/plugins', array(
        'methods'  => 'GET',
        'callback' => 'get_installed_plugins',
    ) );

    register_rest_route( 'wp-site-info/v1', '/health', array(
        'methods'  => 'GET',
        'callback' => 'get_site_health',
    ) );
} );

/**
 * Get the list of installed plugins.
 *
 * @return WP_REST_Response
 */
function get_installed_plugins() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
    }

    $all_plugins = get_plugins();
    $plugins = array();

    foreach ( $all_plugins as $plugin_file => $plugin_data ) {
        $plugins[] = array(
            'name'        => $plugin_data['Name'],
            'version'     => $plugin_data['Version'],
            'description' => $plugin_data['Description'],
            'author'      => $plugin_data['Author'],
            'active'      => is_plugin_active( $plugin_file ),
        );
    }

    return new WP_REST_Response( $plugins, 200 );
}

/**
 * Get the site health information.
 *
 * @return WP_REST_Response
 */
function get_site_health() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
    }

    if ( ! class_exists( 'WP_Site_Health' ) ) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
    }

    $site_health = WP_Site_Health::get_instance();
    $health_data = $site_health->get_tests();

    return new WP_REST_Response( $health_data, 200 );
}