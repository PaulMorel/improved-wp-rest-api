<?php
/**
 * Improved WordPress REST API Plugin
 *
 * @link              https://github.com/PaulMorel/improved-wp-rest-api
 * @since             1.0.0
 * @package           ImprovedWpRestApi
 *
 * @wordpress-plugin
 * Plugin Name:       Improved WordPress REST API
 * Plugin URI:        https://github.com/PaulMorel/improved-wp-rest-api
 * Description:       A WordPress plugin that overhauls the default WP REST API endpoints. Inspired by better-rest-endpoints
 * Version:           1.0.0
 * Author:            Paul Morel
 * Author URI:        paulmorel.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       improved-wp-rest-api
 * Domain Path:       /languages
 */

defined( 'WPINC' ) || exit;

if ( ! defined( 'IWRA_PLUGIN_FILE' ) ) {
	define( 'IWRA_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists('Improved_WP_REST_API') ) {
    include_once dirname( __FILE__ ) . '/includes/class-improved-wp-rest-api.php';
}

function Improved_WP_REST_API() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return Improved_WP_REST_API::instance();
}
// Global for backwards compatibility.
$GLOBALS['Improved_WP_REST_API'] = Improved_WP_REST_API();