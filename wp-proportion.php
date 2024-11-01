<?php
/**
 * Plugin Name: WP Proportion Image Maker
 * Plugin URI:  http://lugano.com/wp-proportion/
 * Description: Plugin for making images, what allow to show real size of products.
 * Author:      Lugano
 * Author URI:  http://lugano.com
 * Version:     1.0.0.0
 * Text Domain: lugano
 * Domain Path: languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 *
 * @since 1.0.0
 *
 * @package WP Proportion Image Maker
 * @author  Lugano
 */
class WP_Proportion {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $version = '1.0.0.0';

    /**
     * The name of the plugin.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_name = 'WP Proportion Image Maker';

    /**
     * Unique plugin slug identifier.
     *
     * @since 1.0.0.0
     *
     * @var string
     */
    public $plugin_slug = 'wp-proportion';

    /**
     * Plugin textdomain.
     *
     * @since 1.0.0.0
     *
     * @var string
     */
    public $domain = 'wp-proportion';

    /**
     * Plugin file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Fire a hook before the class is setup.
        do_action( 'wp_proportion_pre_init' );

        // Load the plugin.
        add_action( 'init', array( $this, 'init' ), 0 );

    }

    /**
     * Loads the plugin into WordPress.
     *
     * @since 1.0.0
     */
    public function init() {

        // Run hook once Soliloquy has been initialized.
        do_action( 'wp_proportion_init' );

        // Load admin only components.
        if ( is_admin() ) {
            $this->require_admin();
        }
    }

    /**
     * Loads all admin related files into scope.
     *
     * @since 1.0.0
     */
    public function require_admin() {

        require_once plugin_dir_path( __FILE__ ) . 'includes/admin/settings.php';
    }

    /**
     * Loads all global files into scope.
     *
     * @since 1.0.0
     */
    public function require_global() {
        
        require_once plugin_dir_path( __FILE__ ) . 'includes/admin/options.php';
    }
    
       /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The WP_Proportion object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Proportion ) ) {
            self::$instance = new WP_Proportion();
        }

        return self::$instance;

    }


}

// Load the main plugin class.
$wp_proportion = WP_Proportion::get_instance();


register_activation_hook( __FILE__, 'wp_proportion_activation_hook' );
/**
 * Fired when the plugin is activated.
 *
 * @since 1.0.0
 *
 * @global int $wp_version      The version of WordPress for this install.
 * @global object $wpdb         The WordPress database object.
 */
function wp_proportion_activation_hook() {

    global $wp_version;
    if ( version_compare( $wp_version, '3.5.1', '<' )) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( sprintf( __( 'Sorry, but your version of WordPress does not meet WP Proportion Image Maker required version of <strong>3.5.1</strong> to run properly. The plugin has been deactivated. <a href="%s">Click here to return to the Dashboard</a>.', 'soliloquy' ), get_admin_url() ) );
    }
}

register_deactivation_hook(__FILE__, 'wp_proportion_deactivation_hook');

/**
 * Fired when the plugin is deactivated.
 *
 * @since 1.0.0
 *
 */

function wp_proportion_deactivation_hook() {

}


