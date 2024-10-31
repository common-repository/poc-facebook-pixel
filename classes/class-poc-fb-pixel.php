<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * POC Facebook Pixel Base Class
 *
 * All functionality pertaining to core functionality of the POC Facebook Pixel plugin.
 */
class POC_FB_Pixel {
	/**
	 * The limit of posts to retrieve for conditions.
	 *
	 * @var int
	 */
	public $upper_limit;

	/**
	 * The conditions object.
	 *
	 * @var object
	 */
	public $conditions;

	/**
	 * The plugin token.
	 *
	 * @access private
	 * @var string
	 */
	private $token;

	/**
	 * The plugin prefix.
	 *
	 * @access private
	 * @var string
	 */
	private $prefix;

	/**
	 * This plugin URL.
	 *
	 * @access private
	 * @var string
	 */
	private $plugin_url;

	/**
	 * This plugin assets URL.
	 *
	 * @access private
	 * @var string
	 */
	private $assets_url;

	/**
	 * __construct function.
	 */
	public function __construct () {
		$this->upper_limit = intval( apply_filters( 'poc_fb_pixel_upper_limit', 200 ) );

		$this->token = 'fb_pixel';
		$this->prefix = 'poc_fb_pixel_';

		/* Plugin URL/path settings. */
		$this->plugin_url = str_replace( '/classes', '', plugins_url( plugin_basename( dirname( __FILE__ ) ) ) );
		$this->assets_url = $this->plugin_url . '/assets';

		$this->conditions = new POC_FB_Pixel_Conditions();
		$this->conditions->token = $this->token;

		$this->init();
		$this->load_integrations();
	}

	/**
	 * init function.
	 *
	 * @return void
	 */
	public function init () {
		add_action( 'init', array( $this, 'load_localisation' ) );

		add_action( 'init', array( $this, 'register_post_type' ), 20 );
		add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
		add_filter( 'post_updated_messages', array( $this, 'update_messages' ) );
		add_action( 'wp_head', array( $this, 'add_pixel_code' ) );

		if ( is_admin() ) {
			global $pagenow;

			add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ), 12 );
			if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && esc_attr( $_GET['post_type'] ) == $this->token ) {
				add_filter( 'manage_edit-' . $this->token . '_columns', array( $this, 'register_custom_column_headings' ), 10, 1 );
				add_action( 'manage_posts_custom_column', array( $this, 'register_custom_columns' ), 10, 2 );
			}
		}
	}

	public function load_integrations() {
		// Check if WooCommerce is active
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			require_once( dirname( dirname( __FILE__ ) ) . '/integrations/integration-woocommerce.php' );
		}

		// Check if JigoShop is active
		if ( in_array( 'jigoshop/jigoshop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			require_once( dirname( dirname( __FILE__ ) ) . '/integrations/integration-jigoshop.php' );
		}

		// Check if WP eCommerce is active
		if ( in_array( 'wp-e-commerce/wp-shopping-cart.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			require_once( dirname( dirname( __FILE__ ) ) . '/integrations/integration-wp-ecommerce.php' );
		}

		// Check if bbPress is active
		if ( in_array( 'bbpress/bbpress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			require_once( dirname( dirname( __FILE__ ) ) . '/integrations/integration-bbpress.php' );
		}

		// Check if BuddyPress is active
		if ( in_array( 'buddypress/bp-loader.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			require_once( dirname( dirname( __FILE__ ) ) . '/integrations/integration-buddypress.php' );
		}


	}

	/**
	 * Registers pixel post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		// Allow only users who can adjust the theme to view the FB Pixels admin.
		if ( ! current_user_can( 'edit_theme_options' ) ) return;

		$page = 'themes.php';

		$singular = __( 'Pixel Code', 'poc-fb-pixel' );
		$plural = __( 'Pixel Codes', 'poc-fb-pixel' );
		$rewrite = array( 'slug' => 'fb_pixel' );
		$supports = array( 'title', 'excerpt' );

		if ( $rewrite == '' ) { $rewrite = $this->token; }

		$labels = array(
			'name' => _x( 'Pixel Codes', 'post type general name', 'poc-fb-pixel' ),
			'singular_name' => _x( 'Pixel Code', 'post type singular name', 'poc-fb-pixel' ),
			'add_new' => _x( 'Add New', 'Widget Area' ),
			'add_new_item' => sprintf( __( 'Add New %s', 'poc-fb-pixel' ), $singular ),
			'edit_item' => sprintf( __( 'Edit %s', 'poc-fb-pixel' ), $singular ),
			'new_item' => sprintf( __( 'New %s', 'poc-fb-pixel' ), $singular ),
			'all_items' => $plural,
			'view_item' => sprintf( __( 'View %s', 'poc-fb-pixel' ), $singular ),
			'search_items' => sprintf( __( 'Search %a', 'poc-fb-pixel' ), $plural ),
			'not_found' =>  sprintf( __( 'No %s Found', 'poc-fb-pixel' ), $plural ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash', 'poc-fb-pixel' ), $plural ),
			'parent_item_colon' => '',
			'menu_name' => $plural

		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => false,
			'show_in_menu' => $page,
			'query_var' => true,
			'rewrite' => $rewrite,
			'capability_type' => 'post',
			'has_archive' => 'fb_pixels',
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => $supports
		);

		register_post_type( $this->token, $args );
	}

	/**
	 * Register columns in the admin.
	 *
	 * @param string $column_name
	 * @param int $id
	 * @return void
	 */
	public function register_custom_columns( $column_name, $id ) {
		global $wpdb, $post;

		$meta = get_post_custom( $id );
		$this->conditions->setup_default_conditions_reference();

		switch ( $column_name ) {

			case 'condition':
				$value = '';

				if ( isset( $meta['_condition'] ) && ( $meta['_condition'][0] != '' ) ) {
					foreach ( $meta['_condition'] as $k => $v ) {
						$value .= $this->multidimensional_search( $v, $this->conditions->conditions_reference ) . '<br />' . "\n";
					}
				}

				echo $value;
			break;

			default:
			break;

		}
	}

	/**
	 * Registers column headings
	 *
	 * @param array $defaults
	 * @return array
	 */
	public function register_custom_column_headings( $defaults ) {
		$this->conditions->setup_default_conditions_reference();

		$new_columns = array( 'condition' => __( 'Condition(s)', 'poc-fb-pixel' ) );

		$last_item = '';

		if ( isset( $defaults['date'] ) ) { unset( $defaults['date'] ); }

		if ( count( $defaults ) > 2 ) {
			$last_item = array_slice( $defaults, -1 );

			array_pop( $defaults );
		}
		$defaults = array_merge( $defaults, $new_columns );

		if ( $last_item != '' ) {
			foreach ( $last_item as $k => $v ) {
				$defaults[$k] = $v;
				break;
			}
		}

		return $defaults;
	}

	/**
	 * Sets up the metabox.
	 *
	 * @return void
	 */
	public function meta_box_setup() {
		// Remove "Custom Settings" meta box.
		remove_meta_box( 'woothemes-settings', 'sidebar', 'normal' );

		// Customise the "Excerpt" meta box for the pixels.
		remove_meta_box( 'postexcerpt', $this->token, 'normal' );
		add_meta_box( 'fb-pixel-code', __( 'Pixel Code', 'poc-fb-pixel' ), array( $this, 'code_meta_box' ), $this->token, 'normal', 'core' );
	}

	/**
	 * Changes the metabox description and label.
	 *
	 * @param object $post
	 * @return void
	 */
	public function code_meta_box( $post ) {
		?>
		<label class="screen-reader-text" for="excerpt"><?php _e( 'Pixel Code', 'poc-fb-pixel' ); ?></label><textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"><?php echo $post->post_excerpt; // textarea_escaped ?></textarea>
		<p><?php _e( 'Add here the pixel code from Facebook. Paste it exactly as it is provided by FB.', 'poc-fb-pixel' ) ?></p>
		<?php
	}

	/**
	 * Replace the default title placeholder in the admin.
	 *
	 * @param string $title
	 * @return string
	 */
	public function enter_title_here( $title ) {
		if ( get_post_type() == $this->token ) {
			$title = __( 'Enter FB Pixel code name here', 'poc-fb-pixel' );
		}

		return $title;
	}

	/**
	 * Replaces the default "Updated" message.
	 *
	 * @param array $messages
	 * @return array
	 */
	public function update_messages( $messages ) {
		if ( get_post_type() != $this->token ) {
			return $messages;
		}

		$messages[ $this->token ][1] = __( 'FB Pixel updated.', 'poc-fb-pixel' );

		return $messages;
	}

	/**
	 * Adds pixel codes into posts.
	 *
	 * @return void
	 */
	public function add_pixel_code() {
		// Determine the conditions to construct the query.
		$conditions = $this->conditions->conditions;

		if ( ! isset( $this->conditions->conditions ) || count( $this->conditions->conditions ) <= 0 ) {
			return;
		}

	 	global $poc_fb_pixel_data;

	 	if ( ! isset( $poc_fb_pixel_data ) ) {

		 	$conditions_str = join( ', ', $conditions );

		 	$args = array(
		 		'post_type' => $this->token,
		 		'posts_per_page' => intval( $this->upper_limit ),
		 		'suppress_filters' => 'false'
		 	);

		 	$args['meta_query'] = array(
		 		'relation' => 'AND',
			 	array(
					'key' => '_condition',
					'compare' => 'IN',
					'value' => $conditions
				),
				array(
					'key' => '_condition',
					'compare' => '!=',
					'value' => ''
				)
		 	);

		 	$pixels = get_posts( $args );

		 	if ( count( $pixels ) > 0 ) {
		 		foreach ( $pixels as $id => $post ) {
		 			$conditions = get_post_meta( $post->ID, '_condition', false );
		 			$pixels[ $id ]->conditions = array();

		 			// Remove any irrelevant conditions from the array.
		 			if ( is_array( $conditions ) ) {
		 				foreach ( $conditions as $i => $j ) {
		 					if ( in_array( $j, $this->conditions->conditions ) ) {
		 						$pixels[ $id ]->conditions[] = $j;
		 					}
		 				}
		 			}

		 		}
		 	}

		 	$poc_fb_pixel_data = $pixels;
	 	}

	 	if ( count( $poc_fb_pixel_data ) > 0 ) {
	 		foreach ( $poc_fb_pixel_data as $post ) {
	 			$pixel_name = $post->post_name;
				echo '<!-- ' . $pixel_name . ' -->';
				echo $post->post_excerpt;
	 		}
	 	}
	}

	/**
	 * Enqueues plugin styles.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		global $pagenow;

		if ( in_array( $pagenow, array( 'edit.php', 'post.php', 'post-new.php' ) ) ) {
			if ( get_post_type() != $this->token ) { return; }
			wp_enqueue_style( 'jquery-ui-tabs' );

			wp_register_style( $this->token . '-admin', $this->assets_url . '/css/admin.min.css', array() );
			wp_enqueue_style( $this->token . '-admin' );

			wp_dequeue_style( 'jquery-ui-datepicker' );

			if ( class_exists( 'WPSEO_Metabox' ) ) {
				// Dequeue unused WordPress SEO CSS files.
				wp_dequeue_style( 'edit-page' );
				wp_dequeue_style( 'metabox-tabs' );

				$color = get_user_meta( get_current_user_id(), 'admin_color', true );
				if ( '' == $color ) $color = 'fresh';

				wp_dequeue_style( 'metabox-' . $color );
			}
		}
	}

	/**
	 * Searches for conditions.
	 *
	 * @param string $needle
	 * @param array $haystack
	 * @return string
	 */
	public function multidimensional_search ( $needle, $haystack ) {
		if (empty( $needle ) || empty( $haystack ) ) {
            return false;
        }

        foreach ( $haystack as $key => $value ) {
            $exists = 0;
        	foreach ( (array)$needle as $nkey => $nvalue) {
                if ( ! empty( $value[$nvalue] ) && is_array( $value[$nvalue] ) ) {
                    return $value[$nvalue]['label'];
                }
            }
        }

        return false;
	}

	/**
	 * Loads plugin localisations.
	 *
	 * @return void
	 */
	public function load_localisation () {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'poc-fb-pixel' );
        $dir    = trailingslashit( WP_LANG_DIR );

        load_textdomain( 'poc-fb-pixel', $dir . 'poc-facebook-pixel-' . $locale . '.mo' );
        load_textdomain( 'poc-fb-pixel', $dir . 'plugins/poc-facebook-pixel-' . $locale . '.mo' );
        load_plugin_textdomain( 'poc-fb-pixel', false, dirname( dirname( plugin_basename ( __FILE__ ) ) ) . '/i18n/' );
	}
}