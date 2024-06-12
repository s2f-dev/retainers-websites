<?php
/**
 * Plugin Name: Skylab REST API
 * Description: Retrieving site information such as installed plugins and site health.
 * Version: 1.0
 * Author: S2F
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the REST API handler.
require_once plugin_dir_path( __FILE__ ) . 'includes/rest-api.php';