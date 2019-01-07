<?php
/*
Plugin Name: Peta Trial
Plugin URI: https://github.com/ronalfy/peta-trial
Description: Use the REST API to grab posts and display in a dashboard widget.
Author: Ronald Huereca
Version: 1.0.0
Requires at least: 4.9
Author URI: https://mediaron.com
Contributors: ronalfy
Text Domain: peta-trial
Domain Path: /languages
*/
define('PETA_VERSION', '2.3.3');
class PETA_TRIAL {
	private static $instance = null;

	//Singleton
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	} //end get_instance

	private function __construct() {
		add_action( 'init', array( $this, 'init' ), 9 );

		//* Localization Code */
		load_plugin_textdomain( 'peta-trial', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	} //end constructor

	public function init() {
		add_action( 'admin_menu', array( $this, 'peta_admin_menu_init') );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		//Plugin settings
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__) , array( $this, 'add_settings_link' ) );
	} //end init

	public function add_settings_link( $links ) {
		$settings_link = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=peta-urls' ) ), _x( 'Settings', 'Plugin settings link on the plugins page', 'peta-trial' ) );
		array_unshift($links, $settings_link);
		return $links;
	}

	public function peta_admin_menu_init() {
		add_options_page( 'PETA Urls', 'PETA Urls', 'manage_options', 'peta-urls', array( $this, 'options_page') );
	}

	public function options_page() {
		?>
		<div class="wrap">
		<h1><?php esc_html_e( 'PETA Urls', 'peta-trial' ); ?></h1>
		<p><?php esc_html_e( 'These URLs will pull posts and display them on your dashboard', 'peta-trial' ); ?>
		<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="POST">
			<?php settings_fields( 'peta-trial' ); ?>
			<?php do_settings_sections( 'peta-trial' ); ?>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	public function register_settings() {
		register_setting( 'peta-trial', 'peta-trial', array( $this, 'sanitization' ) );
		add_settings_section( 'peta-urls', _x( 'URLs to Display', 'plugin settings heading' , 'peta-trial' ), array( $this, 'settings_section' ), 'peta-trial' );
		add_settings_field( 'peta-show-urls', __( 'Enter URLs', 'peta-trial' ), array( $this, 'add_settings_field_urls' ), 'peta-trial', 'peta-urls', array( 'desc' => __( 'Choose WordPress URLs to pull posts from.', 'peta-trial' ) ) );
	}

	public function add_settings_field_urls() {
		$settings = get_option( 'peta-trial' );
		echo '<pre>' . print_r( $settings, true ) . '</pre>';
		$url_1 = isset( $settings[ 'url_1' ] ) ? $settings[ 'url_1' ] : '';
		$url_2 = isset( $settings[ 'url_2' ] ) ? $settings[ 'url_2' ] : '';
		$url_3 = isset( $settings[ 'url_3' ] ) ? $settings[ 'url_3' ] : '';
		$url_4 = isset( $settings[ 'url_4' ] ) ? $settings[ 'url_4' ] : '';
		$url_5 = isset( $settings[ 'url_5' ] ) ? $settings[ 'url_5' ] : '';
		?>
		<p><label><?php esc_html_e( 'URL 1:' ); ?> <input class="regular-text code" type="url" value="<?php echo esc_attr( $url_1 ); ?>" name="peta-trial[url_1]" /></label></p>
		<p><label><?php esc_html_e( 'URL 2:' ); ?> <input class="regular-text code" type="url" value="<?php echo esc_attr( $url_2 ); ?>" name="peta-trial[url_2]" /></label></p>
		<p><label><?php esc_html_e( 'URL 3:' ); ?> <input class="regular-text code" type="url" value="<?php echo esc_attr( $url_3 ); ?>" name="peta-trial[url_3]" /></label></p>
		<p><label><?php esc_html_e( 'URL 4:' ); ?> <input class="regular-text code" type="url" value="<?php echo esc_attr( $url_4 ); ?>" name="peta-trial[url_4]" /></label></p>
		<p><label><?php esc_html_e( 'URL 5:' ); ?> <input class="regular-text code" type="url" value="<?php echo esc_attr( $url_5 ); ?>" name="peta-trial[url_5]" /></label></p>
		<?php
	}

	/**
	 * Output settings HTML
	 *
	 * Output any HTML required to go into a settings section
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see register_settings
	 *
	 */
	public function settings_section() {
	}

	/**
	 * Sanitize options before they are saved.
	 *
	 * Sanitize and prepare error messages when saving options.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see register_settings
	 *
	 * @param array $input {
	 *		@type string url to sanitize
	 *      }
	 *
	 * @return array Sanitized array of options
	 */
	public function sanitization( $input = array() ) {
		$output = get_option( 'peta-trial' );
		$output['url_1'] = esc_url( $input['url_1'] );
		$output['url_2'] = esc_url( $input['url_2'] );
		$output['url_3'] = esc_url( $input['url_3'] );
		$output['url_4'] = esc_url( $input['url_4'] );
		$output['url_5'] = esc_url( $input['url_5'] );
		return $output;
	}

} //end class Simple_Comment_Editing

add_action( 'plugins_loaded', 'peta_instanciate' );
function peta_instanciate() {
	PETA_TRIAL::get_instance();
} //end sce_instantiate
