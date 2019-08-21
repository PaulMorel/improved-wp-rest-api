
<?php
/**
 * Improved WordPress REST API setup
 *
 * @package Improved WordPress REST API
 * @since   1.0
 */

defined( 'WPINC' ) || exit;

final class Improved_WP_REST_API {
   
    /**
     * Version number
     *
     * @var string
	 * @since 1.0
     */
	public static $version = '1.0';
        
    /**
     * The single instance of the class
     *
     * @var Improved_WP_REST_API
     * @since 1.0
     */
    protected static $_instance = null;

    /**
	 * Main Improved WP REST API Instance.
	 *
	 * Ensures only one instance of mproved WP REST API is loaded or can be loaded.
	 *
	 * @since 1.0
	 * @static
	 * @return Improved_WP_REST_API - Main instance.
	 */
    static function instance() {
        if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }

    
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		// $this->includes();
		$this->init_hooks();
    }
    
    /**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
    }
    
    /**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
		// register_activation_hook( WC_PLUGIN_FILE, array( 'WC_Install', 'install' ) );
		// register_shutdown_function( array( $this, 'log_errors' ) );
		// add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -1 );
		// add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		// add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		// add_action( 'init', array( $this, 'init' ), 0 );
		// add_action( 'init', array( 'WC_Shortcodes', 'init' ) );
		// add_action( 'init', array( 'WC_Emails', 'init_transactional_emails' ) );
		// add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		// add_action( 'init', array( $this, 'add_image_sizes' ) );
		// add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
		// add_action( 'activated_plugin', array( $this, 'activated_plugin' ) );
        // add_action( 'deactivated_plugin', array( $this, 'deactivated_plugin' ) );
        
        add_action( 'rest_api_init', array( $this, 'rest_api_includes' ), 50 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 50 );
    }
    
	/**
	 * Define WC Constants.
     * 
     * @since 1.0
     * @return void
	 */
	private function define_constants() {
        $this->define( 'IWP_REST_NAMESPACE', apply_filters('iwra_rest_namespace', 'iwp/v1') );
		// $upload_dir = wp_upload_dir( null, false );
		// $this->define( 'WC_ABSPATH', dirname( WC_PLUGIN_FILE ) . '/' );
		// $this->define( 'WC_PLUGIN_BASENAME', plugin_basename( WC_PLUGIN_FILE ) );
		// $this->define( 'WC_VERSION', $this->version );
		// $this->define( 'WOOCOMMERCE_VERSION', $this->version );
		// $this->define( 'WC_ROUNDING_PRECISION', 6 );
		// $this->define( 'WC_DISCOUNT_ROUNDING_MODE', 2 );
		// $this->define( 'WC_TAX_ROUNDING_MODE', 'yes' === get_option( 'woocommerce_prices_include_tax', 'no' ) ? 2 : 1 );
		// $this->define( 'WC_DELIMITER', '|' );
		// $this->define( 'WC_LOG_DIR', $upload_dir['basedir'] . '/wc-logs/' );
		// $this->define( 'WC_SESSION_CACHE_GROUP', 'wc_session_id' );
		// $this->define( 'WC_TEMPLATE_DEBUG_MODE', false );
	}

    public function rest_api_includes() {
		include_once 'api/class-iwp-rest-posts-controller.php';
        include_once 'api/class-iwp-rest-menu-controller.php';		
    }

    public function register_rest_routes() {
		
		// Get all post types
		$post_types = get_post_types([
			'show_in_rest' => true,
			'_builtin' => false
		]);

		array_unshift( $post_types, 'post', 'page');

		foreach( $post_types as $post_type) {
			$controller = new IWP_REST_Posts_Controller( $post_type );
			$controller->register_routes();
		}

		$controller = new IWP_REST_Menu_Controller();
		$controller->register_routes();

    }
}