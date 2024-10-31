<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'POC_FB_Pixel_Conditions' ) ) :

/**
 * POC FB Pixel Conditions Class
 *
 * Determine the conditions that apply to each screen within WordPress.
 */
class POC_FB_Pixel_Conditions {
	/**
	 * The plugin token
	 *
	 * @var string
	 */
	public $token = '';

	/**
	 * The conditions array
	 *
	 * @var array
	 */
	public $conditions = array();

	/**
	 * The conditions headings array
	 *
	 * @var array
	 */
	public $conditions_headings = array();

	/**
	 * The conditions reference array
	 *
	 * @var array
	 */
	public $conditions_reference = array();

	/**
     * The plugin metabox settings
     *
     * @var array
     */
	public $meta_box_settings = array();

	/**
	 * The limit of posts to retrieve for a post type
	 *
	 * @var int
	 */
	public $upper_limit;

	/**
	 * This plugin assets URL
	 *
	 * @access private
	 * @var string
	 */
	private $assets_url;

	/**
	 * This plugin URL
	 *
	 * @access private
	 * @var string
	 */
	private $plugin_url;

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->meta_box_settings['title'] = __( 'Conditions', 'poc-fb-pixel' );
		$this->upper_limit = intval( apply_filters( 'poc_fb_pixel_upper_limit', 200 ) );

		if ( is_admin() && get_post_type() == $this->token || ! get_post_type() ) {
			add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );
		}

		/* Plugin URL/path settings. */
		$this->plugin_url = str_replace( '/classes', '', plugins_url( plugin_basename( dirname( __FILE__ ) ) ) );
		$this->assets_url = $this->plugin_url . '/assets';

		if ( is_admin() ) {
			add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ), 12 );
		}

		add_action( 'get_header', array( $this, 'get_conditions' ) );
		add_action( 'wp_ajax_poc_fb_pixel_show_advanced', array( $this, 'ajax_toggle_advanced_items' ) );
	}

	/**
	 * Returns the conditions array
	 *
	 * @return void
	 */
	public function get_conditions() {
		$this->determine_conditions();

		$this->conditions = apply_filters( 'poc_fb_pixel_conditions', $this->conditions );

		$this->conditions = array_reverse( $this->conditions );
	}

	/**
	 * Determines the various conditions.
	 *
	 * @return void
	 */
	public function determine_conditions() {
		$this->is_hierarchy();
		$this->is_taxonomy();
		$this->is_post_type_archive();
		$this->is_page_template();
	}

	/**
	 * Setup the default conditions and their information, for display when selecting conditions.
	 *
	 * @return void
	 */
	public function setup_default_conditions_reference() {
		$conditions = array();
		$conditions_headings = array();

		// Get an array of the different post status labels, in case we need it later.
		$post_statuses = get_post_statuses();

		// Pages
		$conditions['pages'] = array();

		$statuses_string = join( ',', array_keys( $post_statuses ) );
		$pages = get_pages( array( 'post_status' => $statuses_string ) );

		if ( count( $pages ) > 0 ) {

			$conditions_headings['pages'] = __( 'Pages', 'poc-fb-pixel' );

			foreach ( $pages as $k => $post ) {
				$token = 'post-' . $post->ID;
				$label = esc_html( $post->post_title );
				if ( 'publish' != $post->post_status ) {
					$label .= ' (' . $post_statuses[ $post->post_status ] . ')';
				}

				$conditions['pages'][ $token ] = array(
					'label'       => $label,
					'description' => sprintf( __( 'The "%s" page', 'poc-fb-pixel' ), $post->post_title )
				);
			}

		}

		$args = array(
			'show_ui'            => true,
			'public'             => true,
			'publicly_queryable' => true,
			'_builtin'           => false
		);

		$post_types = get_post_types( $args, 'object' );

		// Set certain post types that aren't allowed to have custom pixels.
		$disallowed_types = array( 'slide' );

		// Make the array filterable.
		$disallowed_types = apply_filters( 'poc_fb_pixel_disallowed_post_types', $disallowed_types );

		if ( count( $post_types ) ) {
			foreach ( $post_types as $k => $v ) {
				if ( in_array( $k, $disallowed_types ) ) {
					unset( $post_types[ $k ] );
				}
			}
		}

		// Add per-post support for any post type that supports it.
		$args = array(
			'show_ui'            => true,
			'public'             => true,
			'publicly_queryable' => true,
			'_builtin'           => true
		);

		$built_in_post_types = get_post_types( $args, 'object' );

		foreach ( $built_in_post_types as $k => $v ) {
			if ( $k == 'post' ) {
				$post_types[ $k ] = $v;
				break;
			}
		}

		foreach ( $post_types as $k => $v ) {
			if ( ! post_type_supports( $k, 'poc-fb-pixel' ) ) {
				continue;
			}

			$conditions_headings[ $k ] = $v->labels->name;

			$query_args = array(
				'numberposts'      => intval( $this->upper_limit ),
				'post_type'        => $k,
				'meta_key'         => '_enable_sidebar',
				'meta_value'       => 'yes',
				'meta_compare'     => '=',
				'post_status'      => 'any',
				'suppress_filters' => 'false'
			);

			$posts = get_posts( $query_args );

			if ( count( $posts ) > 0 ) {
				foreach ( $posts as $i => $post ) {
					$label = $post->post_title;
					if ( 'publish' != $post->post_status ) {
						$label .= ' <strong>(' . $post_statuses[ $post->post_status ] . ')</strong>';
					}
					$conditions[ $k ]['post' . '-' . $post->ID] = array(
						'label' => $label,
						'description' => sprintf( __( 'A custom pixel code for "%s"', 'poc-fb-pixel' ), esc_attr( $post->post_title ) )
					);
				}
			}
		}

		// Page Templates
		$conditions['templates'] = array();

		$page_templates = get_page_templates();

		if ( count( $page_templates ) > 0 ) {

			$conditions_headings['templates'] = __( 'Page Templates', 'poc-fb-pixel' );

			foreach ( $page_templates as $k => $v ) {
				$token = str_replace( '.php', '', 'page-template-' . $v );
				$conditions['templates'][ $token ] = array(
					'label'       => $k,
					'description' => sprintf( __( 'The "%s" page template', 'poc-fb-pixel' ), $k )
				);
			}
		}

		// Post Type Archives
		$conditions['post_types'] = array();

		if ( count( $post_types ) > 0 ) {

			$conditions_headings['post_types'] = __( 'Post Types', 'poc-fb-pixel' );

			foreach ( $post_types as $k => $v ) {
				$token = 'post-type-archive-' . $k;

				if ( $v->has_archive ) {
					$conditions['post_types'][ $token]  = array(
						'label'       => sprintf( __( '"%s" Post Type Archive', 'poc-fb-pixel' ), $v->labels->name ),
						'description' => sprintf( __( 'The "%s" post type archive', 'poc-fb-pixel' ), $v->labels->name )
					);
				}
			}

			foreach ( $post_types as $k => $v ) {
				$token = 'post-type-' . $k;
				$conditions['post_types'][ $token ] = array(
					'label'       => sprintf( __( 'Each Individual %s', 'poc-fb-pixel' ), $v->labels->singular_name ),
					'description' => sprintf( __( 'Entries in the "%s" post type', 'poc-fb-pixel' ), $v->labels->name )
				);
			}

		}

		// Taxonomies and Taxonomy Terms
		$conditions['taxonomies'] = array();

		$args = array( 'public' => true );

		$taxonomies = get_taxonomies( $args, 'objects' );

		if ( count( $taxonomies ) > 0 ) {

			$conditions_headings['taxonomies'] = __( 'Taxonomy Archives', 'poc-fb-pixel' );

			foreach ( $taxonomies as $k => $v ) {
				$taxonomy = $v;

				if ( $taxonomy->public == true ) {
					$conditions['taxonomies'][ 'archive-' . $k ] = array(
						'label'       => esc_html( $taxonomy->labels->name ) . ' (' . esc_html( $k ) . ')',
						'description' => sprintf( __( 'The default "%s" archives', 'poc-fb-pixel' ), strtolower( $taxonomy->labels->name ) )
					);

					// Setup each individual taxonomy's terms as well.
					$conditions_headings[ 'taxonomy-' . $k ] = $taxonomy->labels->name;
					$terms = get_terms( $k );
					if ( count( $terms ) > 0 ) {
						$conditions[ 'taxonomy-' . $k ] = array();
						foreach ( $terms as $i => $j ) {
							$conditions[ 'taxonomy-' . $k ][ 'term-' . $j->term_id ] = array(
								'label'       => esc_html( $j->name ),
								'description' => sprintf( __( 'The %s %s archive', 'poc-fb-pixel' ), esc_html( $j->name ), strtolower( $taxonomy->labels->name ) )
							);

							if ( $k == 'category' ) {
								$conditions[ 'taxonomy-' . $k ][ 'in-term-' . $j->term_id ] = array(
									'label'       => sprintf( __( 'All posts in "%s"', 'poc-fb-pixel' ), esc_html( $j->name ) ),
									'description' => sprintf( __( 'All posts in the %s %s archive', 'poc-fb-pixel' ), esc_html( $j->name ), strtolower( $taxonomy->labels->name ) )
								);
							}
						}
					}

				}
			}
		}

		$conditions_headings['hierarchy'] = __( 'Template Hierarchy', 'poc-fb-pixel' );

		// Template Hierarchy
		$conditions['hierarchy']['page'] = array(
			'label'       => __( 'Pages', 'poc-fb-pixel' ),
			'description' => __( 'Displayed on all pages that don\'t have a more specific widget area.', 'poc-fb-pixel' )
		);

		$conditions['hierarchy']['search'] = array(
			'label'       => __( 'Search Results', 'poc-fb-pixel' ),
			'description' => __( 'Displayed on search results screens.', 'poc-fb-pixel' )
		);

		$conditions['hierarchy']['home'] = array(
			'label'       => __( 'Default "Your Latest Posts" Screen', 'poc-fb-pixel' ),
			'description' => __( 'Displayed on the default "Your Latest Posts" screen.', 'poc-fb-pixel' )
		);

		$conditions['hierarchy']['front_page'] = array(
			'label'       => __( 'Front Page', 'poc-fb-pixel' ),
			'description' => __( 'Displayed on any front page, regardless of the settings under the "Settings -> Reading" admin screen.', 'poc-fb-pixel' )
		);

		$conditions['hierarchy']['single'] = array(
			'label'       => __( 'Single Entries', 'poc-fb-pixel' ),
			'description' => __( 'Displayed on single entries of any public post type other than "Pages".', 'poc-fb-pixel' )
		);

		$conditions['hierarchy']['archive'] = array(
			'label'       => __( 'All Archives', 'poc-fb-pixel' ),
			'description' => __( 'Displayed on all archives (category, tag, taxonomy, post type, dated, author and search).', 'poc-fb-pixel' )
		);

		$conditions['hierarchy']['author'] = array(
			'label'       => __( 'Author Archives', 'poc-fb-pixel' ),
			'description' => __( 'Displayed on all author archive screens (that don\'t have a more specific code).', 'poc-fb-pixel' )
		);

		$conditions['hierarchy']['date'] = array(
			'label'       => __( 'Date Archives', 'poc-fb-pixel' ),
			'description' => __( 'Displayed on all date archives.', 'poc-fb-pixel' )
		);

		$conditions['hierarchy']['404'] = array(
			'label'       => __( '404 Error Screens', 'poc-fb-pixel' ),
			'description' => __( 'Displayed on all 404 error screens.', 'poc-fb-pixel' )
		);

		$this->conditions_reference = (array) apply_filters( 'poc_fb_pixel_conditions_reference', $conditions );
		$this->conditions_headings  = (array) apply_filters( 'poc_fb_pixel_conditions_headings', $conditions_headings );
	}

	/**
	 * Is the current view a part of the default template heirarchy?
	 *
	 * @return void
	 */
	function is_hierarchy () {
		if ( is_front_page() && ! is_home() ) {
			$this->conditions[] = 'static_front_page';
		}

		if ( ! is_front_page() && is_home() ) {
			$this->conditions[] = 'inner_posts_page';
		}

		if ( is_front_page() ) {
			$this->conditions[] = 'front_page';
		}

		if ( is_home() ) {
			$this->conditions[] = 'home';
		}

		if ( is_singular() ) {
			$this->conditions[] = 'singular';
		}

		if ( is_single() ) {
			$this->conditions[] = 'single';
		}

		if ( is_single() || is_singular() ) {
			$this->conditions[] = 'post-type-' . get_post_type();
			$this->conditions[] = get_post_type();

			$categories = get_the_category( get_the_ID() );

			if ( ! is_wp_error( $categories ) && ( count( $categories ) > 0 ) ) {
				foreach ( $categories as $k => $v ) {
					$this->conditions[] = 'in-term-' . $v->term_id;
				}
			}

			$this->conditions[] = 'post' . '-' . get_the_ID();
		}

		if ( is_search() ) {
			$this->conditions[] = 'search';
		}

		if ( is_home() ) {
			$this->conditions[] = 'home';
		}

		if ( is_front_page() ) {
			$this->conditions[] = 'front_page';
		}

		if ( is_archive() ) {
			$this->conditions[] = 'archive';
		}

		if ( is_author() ) {
			$this->conditions[] = 'author';
		}

		if ( is_date() ) {
			$this->conditions[] = 'date';
		}

		if ( is_404() ) {
			$this->conditions[] = '404';
		}
	}

	/**
	 * Is the current view an archive within a specific taxonomy?
	 *
	 * @return void
	 */
	public function is_taxonomy() {
		if ( ( is_tax() || is_archive() ) && ! is_post_type_archive() ) {
			$obj = get_queried_object();

			if ( ! is_category() && ! is_tag() ) {
				$this->conditions[] = 'taxonomies';
			}

			if ( is_object( $obj ) ) {
				$this->conditions[] = 'archive-' . $obj->taxonomy;
				$this->conditions[] = 'term-' . $obj->term_id;
			}
		}
	}

	/**
	 * Is the current view an archive of a post type?
	 *
	 * @return void
	 */
	public function is_post_type_archive() {
		if ( is_post_type_archive() ) {
			$this->conditions[] = 'post-type-archive-' . get_post_type();
		}
	}

	/**
	 * Does the current view have a specific page template attached (used on single views)?
	 *
	 * @return void
	 */
	public function is_page_template() {
		if ( is_singular() ) {
			global $post;

			$template = get_post_meta( $post->ID, '_wp_page_template', true );
			if ( $template != '' && $template != 'default' ) {
				$this->conditions[] = str_replace( '.php', '', 'page-template-' . $template );
			}
		}
	}

	/**
	 * Sets up metabox
	 *
	 * @return void
	 */
	public function meta_box_setup() {
		add_meta_box( 'poc-fb-pixel-conditions', esc_html( $this->meta_box_settings['title'] ), array( $this, 'meta_box_content' ), $this->token, 'normal', 'low' );
	}

	/**
	 * Print metabox content
	 *
	 * @return void
	 */
	public function meta_box_content() {
		global $post_id;

		if ( count( $this->conditions_reference ) <= 0 ) $this->setup_default_conditions_reference();

		$selected_conditions = get_post_meta( $post_id, '_condition', false );

		if ( $selected_conditions == '' ) {
			$selected_conditions = array();
		}

		$html = '';

		$html .= '<input type="hidden" name="poc_' . $this->token . '_conditions_nonce" id="poc_' . $this->token . '_nonce" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		if ( count( $this->conditions_reference ) > 0 ) {

			// Separate out the taxonomy items for use as sub-tabs of "Taxonomy Terms".
			$taxonomy_terms = array();

			foreach ( $this->conditions_reference as $k => $v ) {
				if ( substr( $k, 0, 9 ) == 'taxonomy-' ) {
					$taxonomy_terms[$k] = $v;
					unset( $this->conditions_reference[$k] );
				}
			}

			$html .= '<div id="taxonomy-category" class="categorydiv tabs poc-fb-pixel-conditions">' . "\n";

				$html .= '<ul id="category-tabs" class="conditions-tabs alignleft">' . "\n";

				$count = 0;

				// Determine whether or not to show advanced items, based on user's preference (default: false).
				$show_advanced = $this->show_advanced_items();

				foreach ( $this->conditions_reference as $k => $v ) {
					$count++;
					$class = '';

					if ( $count == 1 ) {
						$class = 'tabs';
					} else {
						$class = 'hide-if-no-js';
					}
					if ( in_array( $k, array( 'pages' ) ) ) {
						$class .= ' basic';
					} else {
						$class .= ' advanced';

						if ( ! $show_advanced ) {
							$class .= ' hide';
						}
					}

					if ( isset( $this->conditions_headings[ $k ] ) ) {
						$html .= '<li class="' . esc_attr( $class ) . '"><a href="#tab-' . esc_attr( $k ) . '">' . esc_html( $this->conditions_headings[ $k ] ) . '</a></li>' . "\n";
					}

					if ( $k == 'taxonomies' ) {
						$html .= '<li class="' . esc_attr( $class ) . '"><a href="#tab-taxonomy-terms">' . __( 'Taxonomy Terms', 'poc-fb-pixel' ) . '</a></li>' . "\n";
					}
				}

				$class = 'hide-if-no-js advanced';
				if ( ! $show_advanced ) {
					$class .= ' hide';
				}

				$html .= '</ul>' . "\n";

				$html .= '<ul class="conditions-tabs"><li class="advanced-settings alignright hide-if-no-js"><a href="#">' . __( 'Advanced', 'poc-fb-pixel' ) . '</a></li></ul>' . "\n";

			foreach ( $this->conditions_reference as $k => $v ) {
				$count = 0;
				$tab   = '';
				$tab  .= '<div id="tab-' . esc_attr( $k ) . '" class="condition-tab">' . "\n";
				$tab  .= '<h4>' . esc_html( $this->conditions_headings[ $k ] ) . '</h4>' . "\n";
				$tab  .= '<ul class="alignleft conditions-column">' . "\n";

				foreach ( $v as $i => $j ) {
					$count++;

					$checked = '';
					if ( in_array( $i, $selected_conditions ) ) {
						$checked = ' checked="checked"';
					}
					$tab .= '<li><label class="selectit" title="' . esc_attr( $j['description'] ) . '"><input type="checkbox" name="conditions[]" value="' . $i . '" id="checkbox-' . $i . '"' . $checked . ' /> ' . esc_html( $j['label'] ) . '</label></li>' . "\n";

					if ( $count % 10 == 0 && $count < ( count( $v ) ) ) {
						$tab .= '</ul><ul class="alignleft conditions-column">';
					}
				}

				$tab .= '</ul>' . "\n";

				// Filter the contents of the current tab.
				$tab   = apply_filters( 'poc_fb_pixel_conditions_tab_' . esc_attr( $k ), $tab );
				$html .= $tab;
				$html .= '<div class="clear"></div>';
				$html .= '</div>' . "\n";
			}

			// Taxonomy Terms Tab
			$html .= '<div id="tab-taxonomy-terms" class="condition-tab inner-tabs">' . "\n";
			$html .= '<ul class="conditions-tabs-inner hide-if-no-js">' . "\n";

			foreach ( $taxonomy_terms as $k => $v ) {
				if ( ! isset( $this->conditions_headings[ $k ] ) ) { unset( $taxonomy_terms[ $k ] ); }
			}

			$count = 0;
			foreach ( $taxonomy_terms as $k => $v ) {
				$count++;
				$class = '';
				if ( $count == 1 ) {
					$class = 'tabs';
				} else {
					$class = 'hide-if-no-js';
				}

				$html .= '<li><a href="#tab-' . $k . '" title="' . __( 'Taxonomy Token', 'poc-fb-pixel' ) . ': ' . str_replace( 'taxonomy-', '', $k ) . '">' . esc_html( $this->conditions_headings[ $k ] ) . '</a>';
					if ( $count != count( $taxonomy_terms ) ) {
						$html .= ' |';
					}
				$html .= '</li>' . "\n";
			}

			$html .= '</ul>' . "\n";

			foreach ( $taxonomy_terms as $k => $v ) {
				$count = 0;

				$html .= '<div id="tab-' . $k . '" class="condition-tab">' . "\n";
				$html .= '<h4>' . esc_html( $this->conditions_headings[ $k ] ) . '</h4>' . "\n";
				$html .= '<ul class="alignleft conditions-column">' . "\n";
					foreach ( $v as $i => $j ) {
						$count++;

						$checked = '';
						if ( in_array( $i, $selected_conditions ) ) {
							$checked = ' checked="checked"';
						}
						$html .= '<li><label class="selectit" title="' . esc_attr( $j['description'] ) . '"><input type="checkbox" name="conditions[]" value="' . $i . '" id="checkbox-' . esc_attr( $i ) . '"' . $checked . ' /> ' . esc_html( $j['label'] ) . '</label></li>' . "\n";

						if ( $count % 10 == 0 && $count < ( count( $v ) ) ) {
							$html .= '</ul><ul class="alignleft conditions-column">';
						}
					}

				$html .= '</ul>' . "\n";
				$html .= '<div class="clear"></div>';
				$html .= '</div>' . "\n";
			}
			$html .= '</div>' . "\n";
		}

		// Allow themes/plugins to act here (key, args).
		do_action( 'poc_fb_pixel_conditions_meta_box', $k, $v );

		$html .= '<br class="clear" />' . "\n";

		echo $html;
	}

	/**
	 * Save metabox
	 *
	 * @param mixed $post_id
	 * @return void|int
	 */
	public function meta_box_save( $post_id ) {
		global $post, $messages;

		// Verify
		if ( ! isset( $_POST['poc_' . $this->token . '_conditions_nonce'] ) || ( get_post_type() != $this->token ) || ! wp_verify_nonce( $_POST[ 'poc_' . $this->token . '_conditions_nonce' ], plugin_basename(__FILE__) ) ) {
			return $post_id;
		}

		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		if ( isset( $_POST['conditions'] ) && ( 0 < count( $_POST['conditions'] ) ) ) {
			delete_post_meta( $post_id, '_condition' );

			foreach ( $_POST['conditions'] as $k => $v ) {
				add_post_meta( $post_id, '_condition', $v, false );
			}
		}
	}

	/**
	 * Shows the advanced tabs by default or not
	 *
	 * @access private
	 * @return bool
	 */
	private function show_advanced_items() {
		$response = false;

		$setting = get_user_setting( 'pocfbpixelshowadvanced' );

		if ( $setting == '1' ) {
			$response = true;
		}

		return $response;
	}

	/**
	 * Set the user setting pocfbpixelshowadvanced
	 *
	 * @return void
	 */
	public function ajax_toggle_advanced_items() {
		//Add nonce security to the request
		if ( ( ! isset( $_POST['poc_fb_pixel_advanced_nonce'] ) || ! isset( $_POST['new_status'] ) ) || ! wp_verify_nonce( $_POST['poc_fb_pixel_advanced_nonce'], 'poc_fb_pixel_advanced_nonce' ) ) {
			die();
		}

		$response = set_user_setting( 'pocfbpixelshowadvanced', $_POST['new_status'] );

		echo $response;
		die();
	}

	/**
	 * Enqueues scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		global $pagenow;

		if ( get_post_type() != $this->token ) {
			return;
		}

		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( $this->token . '-admin', $this->assets_url . '/js/admin' . $min . '.js', array( 'jquery', 'jquery-ui-tabs' ), '1.2.1', true );
			wp_enqueue_script( $this->token . '-admin' );
			wp_dequeue_script( 'jquery-ui-datepicker' );

			$data = array(
				'poc_fb_pixel_advanced_nonce' => wp_create_nonce( 'poc_fb_pixel_advanced_nonce' )
			);

			wp_localize_script( $this->token . '-admin', 'poc_fb_pixel_localized_data', $data );
		}
	}
}

endif;