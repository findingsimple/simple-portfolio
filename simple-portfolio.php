<?php
/*
Plugin Name: Simple Portfolio Items
Plugin URI: http://plugins.findingsimple.com
Description: Adds the "portfolio" CPT.
Version: 1.0
Author: Finding Simple ( Jason Conroy & Brent Shepherd)
Author URI: http://findingsimple.com
License: GPL2
*/
/*
Copyright 2012  Finding Simple  (email : plugins@findingsimple.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( 'Simple_Portfolio_Items') ) :

/**
 * So that themes and other plugins can customise the text domain, the Simple_Portfolio_Items
 * should not be initialized until after the plugins_loaded and after_setup_theme hooks.
 * However, it also needs to run early on the init hook.
 *
 * @author Jason Conroy <jason@findingsimple.com>
 * @package Simple Team
 * @since 1.0
 */
function initialize_portfolio(){
	Simple_Portfolio_Items::init();
}
add_action( 'init', 'initialize_portfolio', -1 );

/**
 * Plugin Main Class.
 *
 * @package Simple Team
 * @author Jason Conroy <jason@findingsimple.com>
 * @since 1.0
 */
class Simple_Portfolio_Items {

	static $text_domain;

	static $post_type_name;

	static $admin_screen_id;
	
	/**
	 * Initialise
	 */
	public static function init() {
		global $wp_version;

		self::$text_domain = apply_filters( 'simple_portfolio_text_domain', 'Simple_Portfolio_Items' );

		self::$post_type_name = apply_filters( 'simple_portfolio_post_type_name', 'simple_portfolio' );

		self::$admin_screen_id = apply_filters( 'simple_portfolio_admin_screen_id', 'simple_portfolio' );

		add_action( 'init', array( __CLASS__, 'register' ) );
		
		add_filter( 'post_updated_messages', array( __CLASS__, 'updated_messages' ) );
		
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		
		add_action( 'save_post', array( __CLASS__, 'save_meta' ), 10, 1 );
		
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_styles_and_scripts' ) );
				
		add_filter( 'enter_title_here', __CLASS__ . '::change_default_title' );
		
	}

	/**
	 * Register the post type
	 */
	public static function register() {
		
		$labels = array(
			'name'               => __( 'Portfolio items', self::$text_domain ),
			'singular_name'      => __( 'Portfolio item', self::$text_domain ),
			'all_items'          => __( 'All Portfolio Items', self::$text_domain ),
			'add_new_item'       => __( 'Add New Portfolio Item', self::$text_domain ),
			'edit_item'          => __( 'Edit Portfolio Item', self::$text_domain ),
			'new_item'           => __( 'New Portfolio Item', self::$text_domain ),
			'view_item'          => __( 'View Portfolio Item', self::$text_domain ),
			'search_items'       => __( 'Search Portfolio Item', self::$text_domain ),
			'not_found'          => __( 'No portfolio items found', self::$text_domain ),
			'not_found_in_trash' => __( 'No portfolio items found in trash', self::$text_domain )
		);
		$args = array(
			'description' => __( 'Information about portfolio items.', self::$text_domain ),
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'query_var' => true,
			'has_archive' => false,
			'rewrite' => array( 'slug' => 'portfolio', 'with_front' => false ),
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => null,
			'taxonomies' => array(''),
			'show_in_nav_menus' => false,
			'supports' => array('title', 'editor', 'excerpt', 'custom-fields', 'page-attributes', 'thumbnail' )
		); 
		
		$args = apply_filters('simple_portfolio_register_args',$args);
		
		register_post_type( self::$post_type_name , $args );
	}

	/**
	 * Filter the "post updated" messages
	 *
	 * @param array $messages
	 * @return array
	 */
	public static function updated_messages( $messages ) {
		global $post;

		$messages[ self::$post_type_name ] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Portfolio item updated. <a href="%s">View portfolio item</a>', self::$text_domain ), esc_url( get_permalink($post->ID) ) ),
			2 => __('Custom field updated.', self::$text_domain ),
			3 => __('Custom field deleted.', self::$text_domain ),
			4 => __('Portfolio item updated.', self::$text_domain ),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __('Team member restored to revision from %s', self::$text_domain ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Portfolio item published. <a href="%s">View portfolio item</a>', self::$text_domain ), esc_url( get_permalink($post->ID) ) ),
			7 => __('Portfolio item saved.', self::$text_domain ),
			8 => sprintf( __('Portfolio item submitted. <a target="_blank" href="%s">Preview portfolio item</a>', self::$text_domain ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post->ID) ) ) ),
			9 => sprintf( __('Portfolio item scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview portfolio item</a>', self::$text_domain ),
			  // translators: Publish box date format, see http://php.net/date
			  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post->ID) ) ),
			10 => sprintf( __('Portfolio item draft updated. <a target="_blank" href="%s">Preview portfolio item</a>', self::$text_domain ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post->ID) ) ) ),
		);

		return $messages;
	}

	/**
	 * Enqueues the necessary scripts and styles for the plugins
	 *
	 * @since 1.0
	 */
	public static function enqueue_admin_styles_and_scripts() {
				
		if ( is_admin() ) {
	
			wp_register_style( 'simple-portfolio', self::get_url( '/css/simple-portfolio-admin.css', __FILE__ ) , false, '1.0' );
			wp_enqueue_style( 'simple-portfolio' );
		
		}
		
	}
	
	/**
	 * Add the citation meta box
	 *
	 * @wp-action add_meta_boxes
	 */
	public static function add_meta_box() {
		add_meta_box( 'item-details', __( 'Item Details', self::$text_domain  ), array( __CLASS__, 'do_meta_box' ), self::$post_type_name , 'normal', 'high' );
	}

	/**
	 * Output the citation meta box HTML
	 *
	 * @param WP_Post $object Current post object
	 * @param array $box Metabox information
	 */
	public static function do_meta_box( $object, $box ) {
		wp_nonce_field( basename( __FILE__ ), 'item-details' );

		?>

		<p>
			<label for='portfolio-item-client'>
				<?php _e( 'Client:', self::$text_domain ); ?>
				<input type='date' id='portfolio-item-client' name='portfolio-item-client' value='<?php echo esc_attr( get_post_meta( $object->ID, '_portfolio-item-client', true ) ); ?>' />
			</label>
		</p>
		<p>
			<label for='portfolio-item-scope'>
				<?php _e( 'Scope:', self::$text_domain ); ?>
				<input type='date' id='portfolio-item-scope' name='portfolio-item-scope' value='<?php echo esc_attr( get_post_meta( $object->ID, '_portfolio-item-scope', true ) ); ?>' />
			</label>
		</p>
		<p>
			<label for='portfolio-item-thumbnail'>
				<?php _e( 'Thumbnail:', self::$text_domain  ); ?>
				<input type='date' id='portfolio-item-thumbnail' name='portfolio-item-thumbnail' value='<?php echo esc_attr( get_post_meta( $object->ID, '_portfolio-item-thumbnail', true ) ); ?>' />
			</label>
		</p>


<?php
	}

	/**
	 * Save the citation metadata
	 *
	 * @wp-action save_post
	 * @param int $post_id The ID of the current post being saved.
	 */
	public static function save_meta( $post_id ) {

		/* Verify the nonce before proceeding. */
		if ( !isset( $_POST['item-details'] ) || !wp_verify_nonce( $_POST['item-details'], basename( __FILE__ ) ) )
			return $post_id;

		$meta = array(
			'portfolio-item-client',
			'portfolio-item-scope',
			'portfolio-item-thumbnail',
		);

		foreach ( $meta as $meta_key ) {
			$new_meta_value = $_POST[$meta_key];

			/* Get the meta value of the custom field key. */
			$meta_value = get_post_meta( $post_id, '_' . $meta_key , true );

			/* If there is no new meta value but an old value exists, delete it. */
			if ( '' == $new_meta_value && $meta_value )
				delete_post_meta( $post_id, '_' . $meta_key , $meta_value );

			/* If a new meta value was added and there was no previous value, add it. */
			elseif ( $new_meta_value && '' == $meta_value )
				add_post_meta( $post_id, '_' . $meta_key , $new_meta_value, true );

			/* If the new meta value does not match the old value, update it. */
			elseif ( $new_meta_value && $new_meta_value != $meta_value )
				update_post_meta( $post_id, '_' . $meta_key , $new_meta_value );
		}
		
		do_action('simple_portfolio_additional_meta', $post_id);
		
	}

	
	/**
	 * Helper function to get the URL of a given file. 
	 * 
	 * As this plugin may be used as both a stand-alone plugin and as a submodule of 
	 * a theme, the standard WP API functions, like plugins_url() can not be used. 
	 *
	 * @since 1.0
	 * @return array $post_name => $post_content
	 */
	public static function get_url( $file ) {

		// Get the path of this file after the WP content directory
		$post_content_path = substr( dirname( str_replace('\\','/',__FILE__) ), strpos( __FILE__, basename( WP_CONTENT_DIR ) ) + strlen( basename( WP_CONTENT_DIR ) ) );

		// Return a content URL for this path & the specified file
		return content_url( $post_content_path . $file );
	}	
	

	/**
	 * Replaces the "Enter title here" text
	 *
	 * @author Brent Shepherd <brent@findingsimple.com>
	 * @package Simple Team
	 * @since 1.0
	 */
	public static function change_default_title( $title ){
		$screen = get_current_screen();

		if  ( self::$post_type_name == $screen->post_type )
			$title = __( 'Enter name of item', self::$text_domain );

		return $title;
	}
	

	
	/**#@+
	* @internal Template tag for use in templates
	*/
	/**
	* Get the client
	*
	* @param int $post_ID Post ID. Defaults to the current post's ID
	*/
	public static function get_client( $post_ID = 0 ) {

		if ( absint($post_ID) === 0 )
			$post_ID = $GLOBALS['post']->ID;

		return get_post_meta($post_ID, '_portfolio-item-client', true);

	}
	
	/**
	* Get the item's scope
	*
	* @param int $post_ID Post ID. Defaults to the current post's ID
	*/
	public static function get_scope( $post_ID = 0 ) {

		if ( absint($post_ID) === 0 )
			$post_ID = $GLOBALS['post']->ID;

		return get_post_meta($post_ID, '_portfolio-item-scope', true);

	}	
	
	/**
	* Get the item's thumbnail url
	*
	* @param int $post_ID Post ID. Defaults to the current post's ID
	*/
	public static function get_thumb_url( $post_ID = 0 ) {

		if ( absint($post_ID) === 0 )
			$post_ID = $GLOBALS['post']->ID;
			
		$thumbnail_meta = get_post_meta($post_ID, '_portfolio-item-thumbnail', true);
		
		$thumbnail = false;
			
		$thumbnail = ( $thumbnail_meta ) ? $thumbnail_meta : get_the_post_thumbnail( $post_ID );	

		return $thumbnail;

	}	
	
	/**
	* Get the item's gallery xml url
	*
	* @param int $post_ID Post ID. Defaults to the current post's ID
	*/
	public static function get_gallery_xml_url( $post_ID = 0 ) {

		if ( absint($post_ID) === 0 )
			$post_ID = $GLOBALS['post']->ID;

		return get_post_meta($post_ID, '_portfolio-item-gallery-xml-url', true);

	}
	


};

endif;