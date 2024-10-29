<?php
/**
 * Content Widgets
 *
 * Theme / plugin module to enable support for content widgets.
 *
 * @author Serge Liatko <contact@sergeliatko.com> https://sergeliatko.com
 * @GitHub https://github.com/sergeliatko/content-widgets
 *
 */

/** define content-widgets text domain */
if( !defined('CONTENT_WIDGETS_TXD') ) {
	define( 'CONTENT_WIDGETS_TXD', 'content-widgets' );
}

/**
 * Load class only once
 */
if( !class_exists('Content_Widgets') ) {

	/**
	 * Class Content_Widgets
	 */
	class Content_Widgets {

		/**
		 * @var Content_Widgets $instance
		 */
		public static $instance;

		/**
		 * Content_Widgets constructor.
		 */
		protected function __construct() {

			/** register content widget areas */
			add_action( 'widgets_init', array( $this, 'register_content_widget_areas' ), 10, 0 );

			/** add meta box */
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 1 );

			/** save post meta */
			add_action( 'save_post', array( $this, 'save_post_meta' ), 10, 3 );

			/** add user interface to customizer */
			add_action( 'customize_register', array( $this, 'customize_register' ), 10, 1 );

			/** add display controller */
			add_action( 'wp', array( $this, 'display_controller' ), 10, 0 );

		}

		/**
		 * Saves post meta.
		 *
		 * @param int $post_ID
		 * @param WP_Post $post
		 * @param $update
		 */
		public function save_post_meta( $post_ID, WP_Post $post, $update ) {

			/** process only if not autosave and the nonce is verified */
			if(
				( true === $update )
				&& !( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				&& ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'update-post_' . $post->ID ) )
			) {

				/** @var array $fields to save */
				$fields = array(
					'_hide_content_widgets' => array(
						array( $this, 'sanitize_checkbox_field' )
					)
				);

				/**
				 * @var string $field to save
				 * @var array $sanitizers array of functions to use to sanitize the submitted value
				 */
				foreach ( $fields as $field => $sanitizers ) {

					/** if not submitted - delete post meta and do next field */
					if( !isset( $_POST[ $field ] ) ) {
						delete_post_meta( $post->ID, $field );
						continue;
					}

					/** @var mixed $value submitted */
					$value = $_POST[ $field ];

					/** @var callable $sanitizer */
					foreach ( $sanitizers as $sanitizer ) {
						$value = call_user_func( $sanitizer, $value );
					}

					/** save/delete post meta */
					$this->is_empty_not_zero( $value ) ?  delete_post_meta( $post->ID, $field ) : update_post_meta( $post->ID, $field, $value );

				}

			}

		}
		/**
		 * Adds meta box to edit post screen.
		 *
		 * @param string $type
		 */
		public function add_meta_boxes( $type = 'post' ) {

			if( in_array( $type, array_keys( $this->get_post_types() ) ) ) {

				add_meta_box(
					'content_widgets',
					__( 'Content widgets', CONTENT_WIDGETS_TXD ),
					array( $this, 'do_meta_box' ),
					$type,
					'side',
					'low'
				);

			}

		}

		/**
		 * Displays meta box.
		 *
		 * @param WP_Post $post
		 */
		public function do_meta_box( WP_Post $post ) {

			/** @var array $post_types */
			$post_types = $this->get_post_types();

			/** @var WP_Post_Type $post_type */
			$post_type = $post_types[ get_post_type( $post ) ];

			printf(
				'<p><input type="checkbox" id="content-widgets-checkbox" name="_hide_content_widgets" value="1" %1$s/> <label for="content-widgets-checkbox">%2$s</label></p>',
				checked( 1, absint( get_post_meta( $post->ID, '_hide_content_widgets', true ) ), false ),
				sprintf(
					__( 'Hide content widgets on this %s?', CONTENT_WIDGETS_TXD ),
					strtolower( $post_type->labels->singular_name )
				)
			);

		}

		/**
		 * Registers content widget areas.
		 */
		public function register_content_widget_areas() {

			/**
			 * @var string $type post type name
			 * @var  WP_Post_Type $object post type
			 */
			foreach( $this->get_post_types() as $type => $object ) {

				register_sidebar(
					apply_filters(
						'content_widget_area_attributes',
						array(
							'name' => sprintf(
								__( 'After %s widgets', CONTENT_WIDGETS_TXD ),
								strtolower( $object->labels->name )
							),
							'id' => "content-widgets-{$type}",
							'description' => sprintf(
								__( 'Widgets placed here will be displayed after the content on %s.', CONTENT_WIDGETS_TXD ),
								strtolower( $object->labels->name )
							),
							'before_widget' => '<div id="%1$s" class="widget content-widget %2$s">',
							'after_widget' => "</div>\n",
							'before_title' => '<h3 class="widgettitle content-widgettitle">',
							'after_title' => "</h3>\n"
						),
						$type,
						$object
					)
				);

			}

		}

		/**
		 * Adds content widgets to the end of the content.
		 *
		 * @param string $content
		 *
		 * @return string
		 */
		public function add_content_widgets( $content ) {
			global $post, $wp_query;
			if( $wp_query->is_main_query() ) {
				$content .= $this->get_content_widgets_area( $post->post_type );
			}
			return $content;
		}

		/**
		 * Returns content widget area by post type name.
		 *
		 * @param string $type post type
		 *
		 * @return string
		 */
		public function get_content_widgets_area( $type = 'post' ) {
			$out = '';
			if( is_active_sidebar("content-widgets-{$type}") ) {
				ob_start();
				dynamic_sidebar("content-widgets-{$type}");
				$widgets = ob_get_clean();
				if( !empty( $widgets ) ) {
					$format = apply_filters(
						'content_widget_area_format',
						array(
							'before' => '<div id="content-widgets-%1$s" class="content-widget-area content-widget-area-%1$s">',
							'after' => "</div>\n"
						)
					);
					$before = sprintf( $format['before'], $type );
					$after = sprintf( $format['after'], $type );
					$out .= $before . $widgets . $after;
				}
			}
			return $out;
		}

		/**
		 * Adds content widgets display filter to the_content if necessary.
		 */
		public function display_controller() {

			global $post;

			if(
				is_singular()
				&& !is_front_page()
				&& !$this->is_empty( get_theme_mod("content_widgets_display_{$post->post_type}") )
				&& $this->is_empty( get_post_meta( $post->ID, '_hide_content_widgets', true ) )
				&& ( true === apply_filters( 'display_content_widgets', true, $post ) )
			) {

				/** hook into the_content filter */
				add_filter(
					'the_content',
					array( $this, 'add_content_widgets' ),
					absint( get_theme_mod("content_widgets_filter_order_{$post->post_type}", 9 ) ),
					1
				);

			}

		}

		/**
		 * Registers interface in customizer.
		 *
		 * @param WP_Customize_Manager $wp_customize_manager
		 */
		public function customize_register( WP_Customize_Manager $wp_customize_manager ) {

			/** register settings */
			$this->register_customizer_settings( $wp_customize_manager );

			/** add section */
			$wp_customize_manager->add_section(
				'content_widgets',
				array(
					'title' => __( 'Content Widgets', CONTENT_WIDGETS_TXD ),
					'priority' => 115
				)
			);

			/** add controls */
			$this->register_customizer_controls( $wp_customize_manager );

		}

		/**
		 * Registers customizer controls.
		 *
		 * @param WP_Customize_Manager $wp_customize_manager
		 */
		protected function register_customizer_controls( WP_Customize_Manager $wp_customize_manager ) {

			/** require Content_Widgets_Control */
			require_once( dirname( __FILE__ ) . '/Content_Widgets_Control.php' );

			/**
			 * @var string $type
			 * @var  WP_Post_Type $object
			 */
			foreach( $this->get_post_types() as $type => $object ) {

				/** add display control */
				$wp_customize_manager->add_control(
					new Content_Widgets_Control(
						$wp_customize_manager,
						"content_widgets_display_control_{$type}",
						array(
							'settings' => "content_widgets_display_{$type}",
							'section' => 'content_widgets',
							'type' => 'checkbox',
							'post_type' => $type,
							'active_callback' => array( $this, 'show_control' ),
							'label' => sprintf(
								__( 'Display content widgets on %s', CONTENT_WIDGETS_TXD ),
								strtolower( $object->labels->name )
							)
						)
					)
				);

				/** add display filter order control */
				$wp_customize_manager->add_control(
					new Content_Widgets_Control(
						$wp_customize_manager,
						"content_widgets_filter_order_control_{$type}",
						array(
							'settings' => "content_widgets_filter_order_{$type}",
							'section' => 'content_widgets',
							'type' => 'number',
							'post_type' => $type,
							'active_callback' => array( $this, 'show_control' ),
							'label' => __( 'Display order', CONTENT_WIDGETS_TXD ),
							'input_attrs' => array(
								'min' => 0,
								'max' => 100,
								'step' => 1
							)
						)
					)
				);

			}

		}

		/**
		 * Registers customizer settings.
		 *
		 * @param WP_Customize_Manager $wp_customize_manager
		 */
		protected function register_customizer_settings( WP_Customize_Manager $wp_customize_manager ) {

			/** @var string $type post type name */
			foreach( array_keys( $this->get_post_types() ) as $type ) {

				/** add display setting */
				$wp_customize_manager->add_setting(
					"content_widgets_display_{$type}",
					array(
						'type' => 'theme_mod',
						'transport' => 'refresh',
						'sanitize_callback' => 'sanitize_text_field',
						'default' => ''
					)
				);

				/** add display filter order */
				$wp_customize_manager->add_setting(
					"content_widgets_filter_order_{$type}",
					array(
						'type' => 'theme_mod',
						'transport' => 'refresh',
						'sanitize_callback' => 'absint',
						'default' => 9
					)
				);

			}

		}

		/**
		 * Checks if the control should be displayed in customizer.
		 *
		 * @param Content_Widgets_Control $control
		 *
		 * @return bool
		 */
		public function show_control( Content_Widgets_Control $control ) {
			return ( is_singular( $control->post_type ) && !is_front_page() );
		}

		/*
		 * Returns array of public post types objects.
		 */
		protected function get_post_types() {
			return get_post_types( array( 'public' => true ), 'objects', 'and' );
		}

		/**
		 * Checks if data is empty but accepts integer zero value.
		 *
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function is_empty_not_zero( $data ) {
			return ( empty( $data ) && ( 0 !== $data ) );
		}


		/**
		 * Sanitizes submitted checkbox.
		 *
		 * @param $data
		 *
		 * @return int|string
		 */
		public function sanitize_checkbox_field( $data ) {
			return $this->is_empty( absint( $data ) ) ? '' : 1;
		}

		/**
		 * Checks if data is empty.
		 *
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function is_empty( $data ) {
			return empty( $data );
		}

		/**
		 * @return Content_Widgets|static
		 */
		public static function getInstance() {
			return ( null === static::$instance ) ? ( static::$instance = new static() ) : static::$instance;
		}

		/**
		 * prevent cloning
		 */
		private function __clone() {}

	}

	/** load the addon after the theme is loaded */
	add_action( 'after_setup_theme', array( 'Content_Widgets', 'getInstance' ), 10, 0 );

}
