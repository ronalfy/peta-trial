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

	/**
	 * General plugin initialization and text domain init.
	 *
	 * General plugin initialization and text domain init.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ), 9 );

		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget_setup' ) );

		//* Localization Code */
		load_plugin_textdomain( 'peta-trial', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	} //end constructor

	/**
	 * Set up and initialize dashboard widget.
	 *
	 * Set up and initialize dashboard widget.
	 *
	 * @since 1.0.0
	 * @access private
	 * @see __construct
	 *
	 * @return void
	 */
	public function dashboard_widget_setup() {
		wp_add_dashboard_widget(
			'peta-widget-title',
			__( 'Website Posts', 'peta-trial' ),
			array( $this, 'dashboard_widget_output' )
		);
	}

	/**
	 * Output Dashboard Widget.
	 *
	 * Output Dashboard Widget.
	 *
	 * @since 1.0.0
	 * @access private
	 * @see dashboard_widget_setup
	 *
	 * @return void
	 */
	public function dashboard_widget_output() {
		$websites = get_option( 'peta-trial' );
		if ( empty( $websites ) || false == $websites ) return;
		$posts = array();
		$all_posts = array();
		// Perform URL validation check and do a remote post to retrieve posts
		foreach( $websites as $url ) {
			if ( false !== wp_http_validate_url( $url ) ) {
				$json_url = $url . '/wp-json/peta/v1/get_posts/10';
				$response = wp_remote_get( $json_url );
				if ( ! is_wp_error( $response ) ) {
					$body = wp_remote_retrieve_body( $response );
					$body = json_decode( $body );
					foreach( $body as $post ) {
						if ( isset( $post->ID ) ) {
							$posts[] = $post;
						}
					}
				}
			}
		}
		if ( empty( $posts ) ) {
			?>
			<p><?php esc_html_e( 'There are no posts to display', 'peta-trial' ); ?></p>
			<?php
		}

		// Randomize posts and trim to 10
		shuffle( $posts );
		$posts = array_slice( $posts, 0, 10 );

		echo '<ul class="peta-dashboard-posts">';
		foreach( $posts as $post ) {
			printf( '<li><a href="%s">%s</a> - <a href="#" class="peta-approve">%s</a>', esc_url( $post->permalink ), esc_html( $post->post_title ), esc_html__( 'Approve', 'peta-trial' ) );
		}
		echo '</ul>';
	}

	/**
	 * General plugin initialization.
	 *
	 * General plugin initialization.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see __construct
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'peta_admin_menu_init') );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		//Plugin settings
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__) , array( $this, 'add_settings_link' ) );
	} //end init

	/**
	 * Add settings link to the plugin screen.
	 *
	 * Add settings link to the plugin screen.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see init
	 *
	 * @param array $links Array of setting links for the plugin
	 * @return array $links New array of settings links
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=peta-urls' ) ), _x( 'Settings', 'Plugin settings link on the plugins page', 'peta-trial' ) );
		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * Register the options page.
	 *
	 * Register the options page.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see init
	 *
	 * @return void
	 */
	public function peta_admin_menu_init() {
		add_options_page( 'PETA Urls', 'PETA Urls', 'manage_options', 'peta-urls', array( $this, 'options_page') );
	}

	/**
	 * Output the optiosn page for the plugin.
	 *
	 * Output the options page for the plugin..
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see peta_admin_menu_init
	 *
	 * @return void
	 */
	public function options_page() {
		?>
		<div class="wrap">
		<h1><?php esc_html_e( 'PETA Urls', 'peta-trial' ); ?></h1>
		<p><?php esc_html_e( 'These URLs will pull posts and display them on your dashboard', 'peta-trial' ); ?>
		<p><?php printf( esc_html__( 'URLs must ping the site\'s REST API and should look like this without a forward slash: %s' ), 'https://sitename.com' ); ?>
		<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="POST">
			<?php settings_fields( 'peta-trial' ); ?>
			<?php do_settings_sections( 'peta-trial' ); ?>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Register the settings for the plugin.
	 *
	 * Register the settings for the plugin using the settings API.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see init
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'peta-trial', 'peta-trial', array( $this, 'sanitization' ) );
		add_settings_section( 'peta-urls', _x( 'URLs to Display', 'plugin settings heading' , 'peta-trial' ), array( $this, 'settings_section' ), 'peta-trial' );
		add_settings_field( 'peta-show-urls', __( 'Enter URLs', 'peta-trial' ), array( $this, 'add_settings_field_urls' ), 'peta-trial', 'peta-urls', array( 'desc' => __( 'Choose WordPress URLs to pull posts from.', 'peta-trial' ) ) );
	}

	/**
	 * Output HTML settings.
	 *
	 * Output HTML setings.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see register_settings
	 *
	 * @return void
	 */
	public function add_settings_field_urls() {
		$settings = get_option( 'peta-trial' );
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
		$output['url_1'] = esc_url( rtrim( '/', $input['url_1'] ) );
		$output['url_2'] = esc_url( rtrim( '/', $input['url_2'] ) );
		$output['url_3'] = esc_url( rtrim( '/', $input['url_3'] ) );
		$output['url_4'] = esc_url( rtrim( '/', $input['url_4'] ) );
		$output['url_5'] = esc_url( rtrim( '/', $input['url_5'] ) );
		return $output;
	}

	/**
	 * Register a route to save approval messsage.
	 *
	 * Register a route to save approval message as post meta.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see init
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( 'peta/v1', '/get_posts/(?P<posts_per_page>\d+)', array(
			'methods' => 'GET',
			'callback' => array( $this, 'rest_api_get_posts' ),
		) );
		register_rest_route( 'peta/v1', '/approve/(?P<id>\d+)/(?P<username>[-_a-zA-Z0-9]+)', array(
			'methods' => 'GET',
			'callback' => array( $this, 'set_approval' ),
		) );
	}

	/**
	 * Custom query for retrieving posts using the REST API.
	 *
	 * Custom query for retrieving posts using the REST API.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see register_routes
	 *
	 * @param REST_Object $request
	 * @return array
	 */
	public function rest_api_get_posts( $request ) {
		$posts_per_page = absint( $object['posts_per_page'] );
		$post_query_args = array(
			'post_type' => 'post',
			'order' => 'DESC',
			'post_status' => 'publish',
			'posts_per_page' => $posts_per_page,
			'orderby' => 'date',
			'meta_query' => array(
				array(
					'key' => 'peta-approved',
					'compare' => 'NOT EXISTS'
				)
			),
		);
		$query = new WP_Query( $post_query_args );
		if ( $query->have_posts() ) {
			$posts = $query->posts;
			foreach( $posts as &$post ) {
				$post->permalink = get_permalink( $post->ID );
			}
			return $posts;
		}
		return array();
	}

	/**
	 * Register a route to save approval messsage.
	 *
	 * Register a route to save approval message as post meta.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see register_routes
	 *
	 * @return void
	 */
	public function set_approval( $request ) {
		$post_id = absint( $request['id'] );
		$username = sanitize_text_field( $request['username']);
		update_post_meta( $post_id, 'peta-approved', true );
		update_post_meta( $post_id, 'peta-username', $username );
		update_post_meta( $post_id, 'peta-date', current_time( 'mysql' ) );
	}

} //end class PETA_TRIAL

add_action( 'plugins_loaded', 'peta_instanciate' );
function peta_instanciate() {
	PETA_TRIAL::get_instance();
} //end sce_instantiate
