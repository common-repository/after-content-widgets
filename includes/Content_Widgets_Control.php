<?php
/**
 * pluggable class Content_Widgets_Control
 */
if( !class_exists('Content_Widgets_Control') ) {

	/**
	 * Class Content_Widgets_Control
	 */
	class Content_Widgets_Control extends WP_Customize_Control {

		/**
		 * Contains post type name applicable to the control.
		 *
		 * @var string $post_type
		 */
		public $post_type;

	}

}
