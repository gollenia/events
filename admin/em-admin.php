<?php
//Admin functions
function em_admin_menu(){
	global $menu, $submenu, $pagenow;
	//Count pending bookings	
   	if( get_option('dbem_rsvp_enabled') ){
		$bookings_num = '';
		$bookings_pending_count = apply_filters('em_bookings_pending_count',0);
		if( get_option('dbem_bookings_approval') == 1){ 
			$bookings_pending_count += EM_Bookings::count(array('status'=>'0', 'blog'=>get_current_blog_id()));
		}
		if($bookings_pending_count > 0){
			$bookings_num = '<span class="update-plugins count-'.$bookings_pending_count.'"><span class="plugin-count">'.$bookings_pending_count.'</span></span>';
		}
   	}else{
   		$bookings_num = '';
		$bookings_pending_count = 0;
   	}
	//Count pending events
	$events_num = '';
	$events_pending_count = EM_Events::count(array('status'=>0, 'scope'=>'all', 'blog'=>get_current_blog_id()));
	//TODO Add flexible permissions
	if($events_pending_count > 0){
		$events_num = '<span class="update-plugins count-'.$events_pending_count.'"><span class="plugin-count">'.$events_pending_count.'</span></span>';
	}
	//Count pending recurring events
	$events_recurring_num = '';
	$events_recurring_pending_count = EM_Events::count(array('status'=>0, 'recurring'=>1, 'scope'=>'all', 'blog'=>get_current_blog_id()));
	//TODO Add flexible permissions
	if($events_recurring_pending_count > 0){
		$events_recurring_num = '<span class="update-plugins count-'.$events_recurring_pending_count.'"><span class="plugin-count">'.$events_recurring_pending_count.'</span></span>';
	}
	$both_pending_count = apply_filters('em_items_pending_count', $events_pending_count + $bookings_pending_count + $events_recurring_pending_count);
	$both_num = ($both_pending_count > 0) ? '<span class="update-plugins count-'.$both_pending_count.'"><span class="plugin-count">'.$both_pending_count.'</span></span>':'';
  	// Add a submenu to the custom top-level menu:
   	$plugin_pages = array();
   	if( get_option('dbem_rsvp_enabled') ){
		$plugin_pages['bookings'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Bookings', 'events-manager'), __('Bookings', 'events-manager').$bookings_num, 'manage_bookings', 'events-manager-bookings', "em_bookings_page");
   	}
	$plugin_pages['options'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Events Manager Settings','events-manager'),__('Settings','events-manager'), 'manage_options', "events-manager-options", 'em_admin_options_page');
	$plugin_pages['help'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Getting Help for Events Manager','events-manager'),__('Help','events-manager'), 'manage_options', "events-manager-help", 'em_admin_help_page');

	$plugin_pages['bookingforms'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Edit booking and attendee forms','events-manager'),__('Forms','events-manager'), 'manage_options', "events-manager-forms", ["\\Contexis\\Events\\Forms\\Admin", "option_page"]);
	//If multisite global with locations set to be saved in main blogs we can force locations to be created on the main blog only
	
	$plugin_pages = apply_filters('em_create_events_submenu',$plugin_pages);
	//We have to modify the menus manually
	if( !empty($both_num) ){ //Main Event Menu
		//go through the menu array and modify the events menu if found
		foreach ( (array)$menu as $key => $parent_menu ) {
			if ( $parent_menu[2] == 'edit.php?post_type='.EM_POST_TYPE_EVENT ){
				$menu[$key][0] = $menu[$key][0]. $both_num;
				break;
			}
		}
	}
	if( !empty($events_num) && !empty($submenu['edit.php?post_type='.EM_POST_TYPE_EVENT]) ){ //Submenu Event Item
		//go through the menu array and modify the events menu if found
		foreach ( (array)$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT] as $key => $submenu_item ) {
			if ( $submenu_item[2] == 'edit.php?post_type='.EM_POST_TYPE_EVENT ){
				$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0] = $submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0]. $events_num;
				break;
			}
		}
	}
	if( !empty($events_recurring_num) && !empty($submenu['edit.php?post_type='.EM_POST_TYPE_EVENT]) ){ //Submenu Recurring Event Item
		//go through the menu array and modify the events menu if found
		foreach ( (array)$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT] as $key => $submenu_item ) {
			if ( $submenu_item[2] == 'edit.php?post_type=event-recurring' ){
				$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0] = $submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0]. $events_recurring_num;
				break;
			}
		}
	}
	/* Hack! Add location/recurrence isn't possible atm so this is a workaround */
	global $_wp_submenu_nopriv;
	if( $pagenow == 'post-new.php' && !empty($_REQUEST['post_type']) ){
		if( $_REQUEST['post_type'] == EM_POST_TYPE_LOCATION && !empty($_wp_submenu_nopriv['edit.php']['post-new.php']) && current_user_can('edit_locations') ){
			unset($_wp_submenu_nopriv['edit.php']['post-new.php']);
		}
		if( $_REQUEST['post_type'] == 'event-recurring' && !empty($_wp_submenu_nopriv['edit.php']['post-new.php']) && current_user_can('edit_recurring_events') ){
			unset($_wp_submenu_nopriv['edit.php']['post-new.php']);
		}
	}
}
add_action('admin_menu','em_admin_menu');

/**
 * Generate warnings and notices in the admin area
 */
function em_admin_warnings() {
	global $EM_Notices;
	//If we're editing the events page show hello to new user
	
	$dismiss_link_joiner = ( count($_GET) > 0 ) ? '&amp;':'?';
	
	if( current_user_can('activate_plugins') ){
		//New User Intro
		if (isset ( $_GET ['disable_hello_to_user'] ) && $_GET ['disable_hello_to_user'] == 'true'){
			// Disable Hello to new user if requested
			update_option('dbem_hello_to_user',0);
		}elseif ( get_option ( 'dbem_hello_to_user' ) ) {
			//FIXME update welcome msg with good links
			$advice = sprintf( __("<p>Events Manager is ready to go! It is highly recommended you read the <a href='%s'>Getting Started</a> guide on our site, as well as checking out the <a href='%s'>Settings Page</a>. <a href='%s' title='Don't show this advice again'>Dismiss</a></p>", 'events-manager'), 'http://wp-events-plugin.com/documentation/getting-started-guide/?utm_source=em&utm_medium=plugin&utm_content=installationlink&utm_campaign=plugin_links', EM_ADMIN_URL .'&amp;page=events-manager-options', esc_url($_SERVER['REQUEST_URI'].$dismiss_link_joiner.'disable_hello_to_user=true'));
			?>
			<div id="message" class="updated">
				<?php echo $advice; ?>
			</div>
			<?php
		}

		
		
	}
	//Warn about EM page edit
	
	echo $EM_Notices;		
}
add_action ( 'admin_notices', 'em_admin_warnings', 100 );

/**
 * Settings link in the plugins page menu
 * @param array $links
 * @param string $file
 * @return array
 */
function em_plugin_action_links($actions, $file, $plugin_data) {
	array_unshift($actions, sprintf( '<a href="'.EM_ADMIN_URL.'&amp;page=events-manager-options">%s</a>', __('Settings', 'events-manager') ));
	return $actions;
}

add_filter( 'plugin_action_links_events/events.php', 'em_plugin_action_links', 10, 3 );

function em_user_action_links( $actions, $user ){
	if (!is_admin() && !current_user_can( 'manage_others_bookings')) return $actions;
	
	$bookings_link = EM_ADMIN_URL. "&page=events-manager-bookings&person_id=".$user->ID;
	$actions['bookings'] = "<a href='$bookings_link'>" . __( 'Bookings','events-manager') . "</a>";
	
	return $actions;
}

add_filter('user_row_actions','em_user_action_links',10,2);


?>