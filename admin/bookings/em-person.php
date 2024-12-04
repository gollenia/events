<?php

/**
 * Generates a "widget" table of confirmed bookings for a specific event.
 * 
 * @param int $event_id
 */
function em_bookings_person_table(){
	global $wpdb, $current_user,$EM_Person;
	if(!is_object($EM_Person)){
		return false;
	}
	$action_scope = ( !empty($_REQUEST['em_obj']) && $_REQUEST['em_obj'] == 'em_bookings_confirmed_table' );
	$action = ( $action_scope && !empty($_GET ['action']) ) ? $_GET ['action']:'';
	$order = ( $action_scope && !empty($_GET ['order']) ) ? $_GET ['order']:'ASC';
	$limit = ( $action_scope && !empty($_GET['limit']) ) ? $_GET['limit'] : 20;//Default limit
	$page = ( $action_scope && !empty($_GET['pno']) ) ? $_GET['pno']:1;
	$offset = ( $action_scope && $page > 1 ) ? ($page-1)*$limit : 0;
	
	$bookings = $EM_Person->get_bookings();
	$bookings_count = count($bookings);
	if($bookings_count > 0){
		//Get events here in one query to speed things up
		foreach($bookings as $EM_Booking){
			$event_ids[] = $EM_Booking->event_id;
		}
		$events = EM_Events::get($event_ids);
	}
	?>
		<div class='wrap em_bookings_pending_table em_obj'>
			<form id='bookings-filter' method='get' action='<?php bloginfo('wpurl') ?>/wp-admin/edit.php'>
				<input type="hidden" name="em_obj" value="em_bookings_pending_table" />
				<!--
				<ul class="subsubsub">
					<li>
						<a href='edit.php?post_type=post' class="current">All <span class="count">(1)</span></a> |
					</li>
				</ul>
				<p class="search-box">
					<label class="screen-reader-text" for="post-search-input"><?php _e('Search', 'events'); ?>:</label>
					<input type="text" id="post-search-input" name="em_search" value="<?php echo (!empty($_GET['em_search'])) ? esc_attr($_GET['em_search']):''; ?>" />
					<input type="submit" value="<?php _e('Search', 'events'); ?>" class="button" />
				</p>
				-->
				<?php if ( $bookings_count >= $limit ) : ?>
				<div class='tablenav'>
				
					<?php 
					if ( $bookings_count >= $limit ) {
						$bookings_nav = Contexis\Events\Admin\Pagination::paginate( $bookings_count, $limit, $page, array('em_ajax'=>0, 'em_obj'=>'em_bookings_confirmed_table'));
						echo $bookings_nav;
					}
					?>
					<div class="clear"></div>
				</div>
				<?php endif; ?>
				<div class="clear"></div>
				<?php if( $bookings_count > 0 ): ?>
				<div class='table-wrap'>
				<table id='dbem-bookings-table' class='widefat post person'>
					<thead>
						<tr>
							<th class='manage-column column-cb check-column' scope='col'>
								<input class='select-all' type="checkbox" value='1' />
							</th>
							<th class='manage-column' scope='col'><?php _e('Event', 'events'); ?></th>
							<th class='manage-column' scope='col'><?php _e('Spaces', 'events'); ?></th>
							<th class='manage-column' scope='col'><?php _e('Status', 'events'); ?></th>
							<th class='manage-column' scope='col'>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$rowno = 0;
						$event_count = 0;
						foreach ($bookings as $EM_Booking) {
							$EM_Event = $events[$EM_Booking->event_id];							
							if( $EM_Event->can_manage('edit_events','edit_others_events') && ($rowno < $limit || empty($limit)) && ($event_count >= $offset || $offset === 0) ) {
								$rowno++;
								?>
								<tr>
									<th scope="row" class="check-column" style="padding:7px 0px 7px;"><input type='checkbox' value='<?php echo $EM_Booking->booking_id ?>' name='bookings[]'/></th>
									<td><a class="row-title" href="<?php echo EM_ADMIN_URL; ?>&amp;page=events-bookings&amp;event_id=<?php echo $EM_Event->event_id ?>"><?php echo ($EM_Event->event_name); ?></a></td>
									<td><?php echo $EM_Booking->get_spaces() ?></td>
									<td><?php echo $EM_Booking->status_array[$EM_Booking->booking_status]; ?>
									</td>
									<td>
										<?php
										$unapprove_url = add_query_arg(['action'=>'bookings_unapprove', 'booking_id'=>$EM_Booking->booking_id], $_SERVER['REQUEST_URI']);
										$approve_url = add_query_arg(['action'=>'bookings_approve', 'booking_id'=>$EM_Booking->booking_id], $_SERVER['REQUEST_URI']);
										$reject_url = add_query_arg(['action'=>'bookings_reject', 'booking_id'=>$EM_Booking->booking_id], $_SERVER['REQUEST_URI']);
										$delete_url = add_query_arg(['action'=>'bookings_delete', 'booking_id'=>$EM_Booking->booking_id], $_SERVER['REQUEST_URI']);
										?>
										<?php if( get_option('dbem_bookings_approval') && ($EM_Booking->booking_status == EM_Booking::PENDING ) ): ?>
										<a class="em-bookings-approve" href="<?php echo $approve_url ?>"><?php _e('Approve','events'); ?></a> |
										<?php endif; ?>
										<?php if( get_option('dbem_bookings_approval') && $EM_Booking->booking_status == EM_Booking::APPROVED ): ?>
										<a class="em-bookings-unapprove" href="<?php echo $unapprove_url ?>"><?php _e('Unapprove','events'); ?></a> |
										<?php endif; ?>
										<?php if( $EM_Booking->booking_status == EM_Booking::REJECTED ): ?>
										<a class="em-bookings-approve" href="<?php echo $approve_url ?>"><?php _e('Restore','events'); ?></a> |
										<?php endif; ?>
										<?php if( $EM_Booking->booking_status == EM_Booking::PENDING || $EM_Booking->booking_status == EM_Booking::APPROVED ): ?>
										<a class="em-bookings-reject" href="<?php echo $reject_url ?>"><?php _e('Reject','events'); ?></a> |
										<?php endif; ?>
										<a class="em-bookings-edit" href="<?php echo EM_ADMIN_URL; ?>&amp;page=events-bookings&amp;booking_id=<?php echo $EM_Booking->booking_id; ?>"><?php _e('Edit/View','events'); ?></a> |
										<span class="trash"><a class="em-bookings-delete" href="<?php echo $delete_url ?>"><?php _e('Delete','events'); ?></a></span>
									</td>
								</tr>
								<?php
							}
							$event_count++;
						}
						?>
					</tbody>
				</table>
				</div>
				<?php else: ?>
					<?php _e('No confirmed bookings.', 'events'); ?>
				<?php endif; ?>
			</form>
			<?php if( !empty($bookings_nav) && $bookings >= $limit ) : ?>
			<div class='tablenav'>
				<?php echo $bookings_nav; ?>
				<div class="clear"></div>
			</div>
			<?php endif; ?>
		</div>	
	<?php
	
}
?>