<?php
/**
 * BP Follow Screens
 *
 * @package BP-Follow
 * @subpackage Screens
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catches any visits to the "Followers (X)" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
function bp_follow_screen_followers() {

	do_action( 'bp_follow_screen_followers' );

	// ignore the template referenced here
	// 'members/single/followers' is for older themes already using this template
	//
	// view bp_follow_load_template_filter() for more info.
	bp_core_load_template( 'members/single/followers' );
}

/**
 * Catches any visits to the "Following (X)" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
function bp_follow_screen_following() {

	do_action( 'bp_follow_screen_following' );

	// ignore the template referenced here
	// 'members/single/following' is for older themes already using this template
	//
	// view bp_follow_load_template_filter() for more info.
	bp_core_load_template( 'members/single/following' );
}

/**
 * Catches any visits to the "Activity > Following" tab on a users profile.
 *
 * @uses bp_core_load_template() Loads a template file.
 */
function bp_follow_screen_activity_following() {
	bp_update_is_item_admin( is_super_admin(), 'activity' );
	do_action( 'bp_activity_screen_following' );
	bp_core_load_template( apply_filters( 'bp_activity_template_following', 'members/single/home' ) );
}

/** TEMPLATE LOADER ************************************************/

/**
 * BP Follow template loader.
 *
 * This function sets up BP Follow to use custom templates.
 *
 * If a template does not exist in the current theme, we will use our own
 * bundled templates.
 *
 * We're doing two things here:
 *  1) Support the older template format for themes that are using them
 *     for backwards-compatibility (the template passed in
 *     {@link bp_core_load_template()}).
 *  2) Route older template names to use our new template locations and
 *     format.
 *
 * View the inline doc for more details.
 *
 * @since 1.0
 */
function bp_follow_load_template_filter( $found_template, $templates ) {
    $bp = $GLOBALS['bp'];

    // Only filter the template location when we're on the follow component pages.
    if ( ! bp_is_current_component( $bp->follow->followers->slug ) && ! bp_is_current_component( $bp->follow->following->slug ) ) {
        return $found_template;
    }
    // Log if a found template exists
    if ( empty( $found_template ) ) {

        // register our theme compat directory
        bp_register_template_stack( 'bp_follow_get_template_directory', 14 );

        // Attempt to locate the plugins.php template in the child and parent theme
        $found_template = locate_template( 'members/single/plugins.php', false, false );

        // Add AJAX support if allowed
        if ( apply_filters( 'bp_follow_allow_ajax_on_follow_pages', true ) ) {

            // Add the "Order by" dropdown filter
            add_action( 'bp_member_plugin_options_nav', 'bp_follow_add_members_dropdown_filter' );

            // Add AJAX support to the members loop
            add_action( 'bp_after_member_plugin_template', 'bp_follow_add_ajax_to_members_loop' );
        } else {
            error_log('AJAX support is disabled for follow pages.');
        }

        // Add the hook to inject content into BP
        add_action( 'bp_template_content', function() {
            bp_get_template_part( 'members/single/follow' );
        });
    } else {
        error_log('Existing template found: ' . $found_template);
    }

    $final_template = apply_filters( 'bp_follow_load_template_filter', $found_template );

    return $final_template;
}
add_filter( 'bp_located_template', 'bp_follow_load_template_filter', 10, 2 );

/** UTILITY ********************************************************/

/**
 * Get the BP Follow template directory.
 *
 * @author r-a-y
 * @since 1.2
 *
 * @uses apply_filters()
 * @return string
 */
function bp_follow_get_template_directory() {
	return apply_filters( 'bp_follow_get_template_directory', constant( 'BP_FOLLOW_DIR' ) . '/_inc/templates' );
}

/**
 * Add ability to use AJAX on the /members/single/plugins.php template.
 *
 * The plugins.php template hardcodes the 'no-ajax' class to prevent AJAX
 * from being used.
 *
 * We want to use AJAX; so we dynamically remove the class with jQuery after
 * the document has finished loading.
 *
 * This will enable AJAX in our members loop.
 *
 * Hooked to the 'bp_after_member_plugin_template' action.
 *
 * @author r-a-y
 * @since 1.2
 *
 * @see bp_follow_load_template_filter()
 */
function bp_follow_add_ajax_to_members_loop() {
?>

	<script type="text/javascript">
	jQuery(document).ready( function() {
		jQuery('#subnav').removeClass('no-ajax');
	});
	</script>

<?php
}

/**
 * Add "Order By" dropdown filter to the /members/single/plugins.php template.
 *
 * Hooked to the 'bp_member_plugin_options_nav' action.
 *
 * @author r-a-y
 * @since 1.2
 *
 * @see bp_follow_load_template_filter()
 */
function bp_follow_add_members_dropdown_filter() {

?>
	<?php do_action( 'bp_members_directory_member_sub_types' ); ?>

	<li id="members-order-select" class="last filter">

		<?php // the ID for this is important as AJAX relies on it! ?>
		<label for="members-<?php echo bp_current_action(); ?>-orderby"><?php _e( 'Order By:', 'buddypress-followers' ); ?></label>
		<select id="members-<?php echo bp_current_action(); ?>-orderby" data-bp-filter="members">
			<?php if ( class_exists( 'BP_User_Query' ) ) : ?>
				<option value="newest-follows"><?php _e( 'Newest Follows', 'buddypress-followers' ); ?></option>
				<option value="oldest-follows"><?php _e( 'Oldest Follows', 'buddypress-followers' ); ?></option>
			<?php endif; ?>
			<option value="active"><?php _e( 'Last Active', 'buddypress-followers' ); ?></option>
			<option value="newest"><?php _e( 'Newest Registered', 'buddypress-followers' ); ?></option>

			<?php if ( bp_is_active( 'xprofile' ) ) : ?>
				<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress-followers' ); ?></option>
			<?php endif; ?>

			<?php do_action( 'bp_members_directory_order_options' ); ?>

		</select>
	</li>

<?php
}
