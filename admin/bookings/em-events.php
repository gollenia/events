<?php

/**
 * Determines whether to show event page or events page, and saves any updates to the event or events
 * @return null
 */
function em_bookings_events_table() {
	//TODO Simplify panel for events, use form flags to detect certain actions (e.g. submitted, etc)
	global $wpdb;

	$scope_names = array (
		'past' => __ ( 'Past events', 'events'),
		'all' => __ ( 'All events', 'events'),
		'future' => __ ( 'Future events', 'events')
	);
	
	$action_scope = ( !empty($_REQUEST['em_obj']) && $_REQUEST['em_obj'] == 'em_bookings_events_table' );
	$action = ( $action_scope && !empty($_GET ['action']) ) ? $_GET ['action']:'';
	$order = ( $action_scope && !empty($_GET ['order']) ) ? $_GET ['order']:'ASC';
	$limit = ( $action_scope && !empty($_GET['limit']) ) ? $_GET['limit'] : 20;//Default limit
	$page = ( $action_scope && !empty($_GET['pno']) ) ? $_GET['pno']:1;
	$offset = ( $action_scope && $page > 1 ) ? ($page-1)*$limit : 0;
	$scope = ( $action_scope && !empty($_GET ['scope']) && array_key_exists($_GET ['scope'], $scope_names) ) ? $_GET ['scope']:'future';
	
	// No action, only showing the events list
	switch ($scope) {
		case "past" :
			$title = __ ( 'Past Events', 'events');
			break;
		case "all" :
			$title = __ ( 'All Events', 'events');
			break;
		default :
			$title = __ ( 'Future Events', 'events');
			$scope = "future";
	}
	$owner = !current_user_can('manage_others_bookings') ? get_current_user_id() : false;
	$events = EM_Events::get( array('scope'=>$scope, 'limit'=>$limit, 'offset' => $offset, 'order'=>$order, 'orderby'=>'event_start', 'bookings'=>true, 'owner' => $owner, 'pagination' => 1 ) );
	$events_count = EM_Events::$num_rows_found;
	
	$use_events_end = get_option ( 'dbem_use_event_end' );
	?>
	<div class="wrap em_bookings_events_table em_obj">
		
		<form id="posts-filter" action="" method="get">
			<input type="hidden" name="em_obj" value="em_bookings_events_table" />
			<?php if(!empty($_GET['page'])): ?>
			<input type='hidden' name='page' value='events-bookings' />
			<?php endif; ?>		
			<div class="tablenav">			
				<div class="alignleft actions">
					<!--
					<select name="action">
						<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions' ); ?></option>
						<option value="deleteEvents"><?php esc_html_e( 'Delete selected','events'); ?></option>
					</select> 
					<input type="submit" value="<?php esc_html_e( 'Apply' ); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
					 --> 
					<select name="scope">
						<?php
						foreach ( $scope_names as $key => $value ) {
							$selected = "";
							if ($key == $scope)
								$selected = "selected='selected'";
							echo "<option value='$key' $selected>$value</option>  ";
						}
						?>
					</select>
					<button id="post-query-submit" class="button-secondary" type="" value="" ><?php esc_attr_e( 'Filter' )?>
				</div>
				<!--
				<div class="view-switch">
					<a href="/wp-admin/edit.php?mode=list"><img class="current" id="view-switch-list" src="http://wordpress.lan/wp-includes/images/blank.gif" width="20" height="20" title="List View" alt="List View" name="view-switch-list" /></a> <a href="/wp-admin/edit.php?mode=excerpt"><img id="view-switch-excerpt" src="http://wordpress.lan/wp-includes/images/blank.gif" width="20" height="20" title="Excerpt View" alt="Excerpt View" name="view-switch-excerpt" /></a>
				</div>
				-->
				<?php 
				if ( $events_count >= $limit ) {
					$events_nav = Contexis\Events\Admin\Pagination::paginate( $events_count, $limit, $page, array('em_ajax'=>0, 'em_obj'=>'em_bookings_events_table'));
					echo $events_nav;
				}
				?>
			</div>
			<div class="clear"></div>
			<?php
			if (empty ( $events )) {
				// TODO localize
				_e ( 'no events','events');
			} else {
			?>
			<div class='table-wrap'>	
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Event', 'events'); ?></th>
						<th><?php esc_html_e( 'Available', 'events'); ?></th>
						<th><?php esc_html_e( 'Booked', 'events'); ?></th>
						<th><?php esc_html_e( 'Date and time', 'events'); ?></th>
						
					</tr>
				</thead>
				<tbody>
					<?php 
					$rowno = 0;
					foreach ( $events as $EM_Event ) {
						/* @var $event EM_Event */

						$rowno++;
						$class = ($rowno % 2) ? ' class="alternate"' : '';
						$style = "";
						$booked_percent = 0;
						$pending_percent = 0;
						if($EM_Event->get_spaces() > 0) {
							$booked_percent = $EM_Event->get_bookings()->get_booked_spaces() / ($EM_Event->get_spaces() / 100);
							$pending_percent = $EM_Event->get_bookings()->get_pending_spaces() / ($EM_Event->get_spaces() / 100);
						}
						
						
						if ($EM_Event->start()->getTimestamp() < time() && $EM_Event->end()->getTimestamp() < time()){
							$style = "style ='background-color: #FADDB7;'";
						}							
						?>
						<tr <?php echo "$class $style"; ?>>
							<td>
								<strong>
									<?php echo $EM_Event->output('#_BOOKINGSLINK'); ?>
									</strong>
									<div class="row-actions "><span class="trash"><a href="https://kids-team.internal/wp-admin/post.php?post=27&amp;action=trash&amp;_wpnonce=8ebb708296" class="submitdelete" aria-label="„Teenagerfreizeit“ in den Papierkorb verschieben">Buchungen löschen</a> | </span><span class="trash"><a href="https://kids-team.internal/events/teenagerfreizeit/" rel="bookmark" aria-label="„Teenagerfreizeit“ ansehen">Absagen</a></span></div>
								
							</td>
							<td>
								
							<b><?php echo $EM_Event->get_bookings()->get_available_spaces(); echo " "; echo __("Free", "events") ?> </b><br> <?php echo __("Off", "events"); echo " "; echo $EM_Event->get_spaces(); ?>
					
							</td>
							<td >
								<b><?php echo $EM_Event->get_bookings()->get_booked_spaces(); echo " ";  ?> /
								<?php echo $EM_Event->get_bookings()->get_pending_spaces(); echo " "; echo __("Pending", "events") ?></b>
								<div class="em-booking-graph">
									<?php if($booked_percent < 100) { ?>
										<div class="em-booking-graph-booked <?php if($pending_percent) echo "cut" ?>" style="width:<?php echo $booked_percent ?>%;"></div>
										<div class="em-booking-graph-pending <?php if($booked_percent) echo "cut" ?>" style="width:<?php echo $pending_percent ?>%;"></div>
									<?php } ?>
									<?php if($booked_percent >= 100) { ?>
										<div class="em-booking-graph-full" style="width:100%;"></div>
									<?php } ?>
								</div>
							</td>
							<td>
								<?php echo $EM_Event->output_dates() ?>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			</div>
			<?php
			} // end of table
			?>
			<div class='tablenav'>
				<div class="alignleft actions">
				<br class='clear' />
				</div>
				<?php if (!empty($events_nav) &&  $events_count >= $limit ) : ?>
				<div class="tablenav-pages">
					<?php
					echo $events_nav;
					?>
				</div>
				<?php endif; ?>
				<br class='clear' />
			</div>
		</form>
	</div>
	<?php
}

?>