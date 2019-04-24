<?php
/**
 * @package Wine_List
 * @version 0.1
 */
/*
Plugin Name: Wine List
Plugin URI: http://andrewspittle.net/projects/reading-list
Description: Track the wines discussed right from the WordPress Dashboard.
Author: Adapted from Andrew Spittle
Version: 0.1
Author URI: http://andrewspittle.net/
*/

/**
 * Start up our custom post type and hook it in to the init action when that fires.
 *
 * @since Wine List 0.1
 */

add_action( 'init', 'rl_create_post_type' );

function rl_create_post_type() {
	$labels = array(
		'name' 							=> __( 'Wines', 'winelist' ),
		'singular_name' 				=> __( 'Wine', 'winelist' ),
		'search_items'					=> __( 'Search Wines', 'readinglist' ),
		'all_items'						=> __( 'All Wines', 'winelist' ),
		'edit_item'						=> __( 'Edit Wine', 'winelist' ),
		'update_item' 					=> __( 'Update Wine', 'winelist' ),
		'add_new_item' 					=> __( 'Add New Wine', 'winelist' ),
		'new_item_name' 				=> __( 'New Wine', 'winelist' ),
		'menu_name' 					=> __( 'Wines', 'winelist' ),
	);
	
	$args = array (
		'labels' 		=> $labels,
		'public' 		=> true,
		'menu_position' => 20,
		'has_archive' 	=> true,
		'rewrite'		=> array( 'slug' => 'wines' ),
		'supports' 		=> array( 'title', 'thumbnail', 'editor' )
	);
	register_post_type( 'rl_wine', $args );
}

/**
 * Create our custom taxonomies. One hierarchical one for varietals and a flat one for winemakers.
 *
 * @since Wine List 0.1
 */

/* Hook in to the init action and call rl_create_wine_taxonomies when it fires. */
add_action( 'init', 'rl_create_wine_taxonomies', 0 );

function rl_create_wine_taxonomies() {
	// Add new taxonomy, keep it non-hierarchical (like tags)
	$labels = array(
		'name' 							=> __( 'Varietals', 'winelist' ),
		'singular_name' 				=> __( 'Varietal', 'winelist' ),
		'search_items' 					=> __( 'Search Varietals', 'winelist' ),
		'all_items' 					=> __( 'All Varietals', 'winelist' ),
		'edit_item' 					=> __( 'Edit Varietal', 'winelist' ), 
		'update_item' 					=> __( 'Update Varietal', 'winelist' ),
		'add_new_item' 					=> __( 'Add New Varietal', 'winelist' ),
		'new_item_name' 				=> __( 'New Varietal Name', 'winelist' ),
		'separate_items_with_commas' 	=> __( 'Separate varietals with commas', 'winelist' ),
		'choose_from_most_used' 		=> __( 'Choose from the most used varietals', 'winelist' ),
		'menu_name' 					=> __( 'Varietals', 'winelist' ),
	); 	
		
	register_taxonomy( 'wine-varietal', array( 'rl_wine' ), array(
		'hierarchical' 		=> false,
		'labels' 			=> $labels,
		'show_ui' 			=> true,
		'show_admin_column' => true,
		'query_var' 		=> true,
		'rewrite' 			=> array( 'slug' => 'wine-varietal' ),
	));
}

/**
 * Add custom meta box for tracking the vintages of the wine.
 *
 * Props to Justin Tadlock: http://wp.smashingmagazine.com/2011/10/04/create-custom-post-meta-boxes-wordpress/
 *
 * @since Wine List 1.0
 *
*/

/* Fire our meta box setup function on the editor screen. */
add_action( 'load-post.php', 'rl_post_meta_boxes_setup' );
add_action( 'load-post-new.php', 'rl_post_meta_boxes_setup' );

/* Our meta box set up function. */
function rl_post_meta_boxes_setup() {

	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', 'rl_add_post_meta_boxes' );
	
	/* Save post meta on the 'save_post' hook. */
	add_action( 'save_post', 'rl_pages_save_meta', 10, 2 );
}

/* Create one or more meta boxes to be displayed on the post editor screen. */
function rl_add_post_meta_boxes() {

	add_meta_box(
		'rl-vintage',								// Unique ID
		esc_html__( 'Vintage', 'example' ),		// Title
		'rl_vintage_meta_box',					// Callback function
		'rl_wine',								// Add metabox to our custom post type
		'side',									// Context
		'default'								// Priority
	);
}

/* Display the post meta box. */
function rl_vintage_meta_box( $object, $box ) { ?>

	<?php wp_nonce_field( basename( __FILE__ ), 'rl_pages_nonce' ); ?>

	<p class="howto"><label for="rl-vintage"><?php _e( "Add the vintage of the wine.", 'example' ); ?></label></p>
	<p><input class="widefat" type="text" name="rl-vintage" id="rl-vintage" value="<?php echo esc_attr( get_post_meta( $object->ID, 'rl_vintage', true ) ); ?>" size="30" /></p>
<?php }

/* Save the meta box's data. */
function rl_vintage_save_meta( $post_id, $post ) {

	/* Verify the nonce before proceeding. */
	if ( !isset( $_POST['rl_vintage_nonce'] ) || !wp_verify_nonce( $_POST['rl_vintage_nonce'], basename( __FILE__ ) ) )
		return $post_id;

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );

	/* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	/* Get the posted data and sanitize it for use as an HTML class. */
	$new_meta_value = ( isset( $_POST['rl-vintage'] ) ? sanitize_html_class( $_POST['rl-vintage'] ) : '' );

	/* Get the meta key. */
	$meta_key = 'rl_vintage';

	/* Get the meta value of the custom field key. */
	$meta_value = get_post_meta( $post_id, $meta_key, true );

	/* If a new meta value was added and there was no previous value, add it. */
	if ( $new_meta_value && '' == $meta_value )
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );

	/* If the new meta value does not match the old value, update it. */
	elseif ( $new_meta_value && $new_meta_value != $meta_value )
		update_post_meta( $post_id, $meta_key, $new_meta_value );

	/* If there is no new meta value but an old value exists, delete it. */
	elseif ( '' == $new_meta_value && $meta_value )
		delete_post_meta( $post_id, $meta_key, $meta_value );
} 

?>
