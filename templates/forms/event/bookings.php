<?php
global $EM_Event, $post, $allowedposttags, $EM_Ticket, $col_count;
$reschedule_warnings = !empty($EM_Event->event_id) && $EM_Event->is_recurring() && $EM_Event->event_rsvp;
?>
<div id="event-rsvp-box">
	<input id="event-rsvp" name='event_rsvp' value='1' type='checkbox' <?php echo ($EM_Event->event_rsvp) ? 'checked="checked"' : ''; ?> />
	&nbsp;&nbsp;
	<?php _e ( 'Enable registration for this event', 'events-manager')?>
</div>
<div id="event-rsvp-options" style="<?php echo ($EM_Event->event_rsvp) ? '':'display:none;' ?>">
	<?php 
	do_action('em_events_admin_bookings_header', $EM_Event);
	//get tickets here and if there are none, create a blank ticket
	$EM_Tickets = $EM_Event->get_tickets();
	if( count($EM_Tickets->tickets) == 0 ){
		$EM_Tickets->tickets[] = new EM_Ticket();
		$delete_temp_ticket = true;
	}
	?>
	<div class="event-rsvp-options-tickets <?php if( $reschedule_warnings ) echo 'em-recurrence-reschedule'; ?>">
		
			<h3><?php esc_html_e('Tickets','events-manager'); ?></h3>
			
		<?php //If this event is a recurring template, we need to warn the user that editing tickets will delete previous bookings
		if( $reschedule_warnings ){ 
		?>
			<div class="recurrence-reschedule-warning">
			    <p><?php esc_html_e( 'Modifications to event tickets will cause all bookings to individual recurrences of this event to be deleted.', 'events-manager'); ?></p>
	    		<p>			
			    	<a href="<?php echo esc_url( add_query_arg(array('scope'=>'all', 'recurrence_id'=>$EM_Event->event_id), em_get_events_admin_url()) ); ?>">
						<strong><?php esc_html_e('You can edit individual recurrences and disassociate them with this recurring event.', 'events-manager'); ?></strong>
					</a>
                </p>
	    	</div>
		<?php 
		}
		$container_classes = array();
		if( $reschedule_warnings && empty($_REQUEST['recreate_tickets']) ) $container_classes[] = 'reschedule-hidden';
		if( get_option('dbem_bookings_tickets_ordering') ) $container_classes[] = 'em-tickets-sortable';
		?>
		<div id="em-tickets-form" class="em-tickets-form <?php echo implode(' ', $container_classes); ?>">
		
			<p><em><?php esc_html_e('You can have single or multiple tickets, where certain tickets become available under certain conditions, e.g. early bookings, group discounts, maximum bookings per ticket, etc.', 'events-manager'); ?> <?php esc_html_e('Basic HTML is allowed in ticket labels and descriptions.','events-manager'); ?></em></p>					
			<table class="wp-list-table widefat  striped table-view-list">
				<thead>
					<tr valign="top">
						<th colspan="2"><?php esc_html_e('Ticket Name','events-manager'); ?></th>
						<th><?php esc_html_e('Price','events-manager'); ?></th>
						<th><?php esc_html_e('Min/Max','events-manager'); ?></th>
						<th><?php esc_html_e('Start/End','events-manager'); ?></th>
						<th><?php esc_html_e('Avail. Spaces','events-manager'); ?></th>
						<th><?php esc_html_e('Booked Spaces','events-manager'); ?></th>
					</tr>
				</thead>    
				<tfoot>
					<tr valign="top">
						<td colspan="7">
							<a href="#" class="components-button is-primary" id="em-tickets-add"><?php esc_html_e('Add new ticket','events-manager'); ?></a>
						</td>
					</tr>
				</tfoot>
				<?php
					$EM_Ticket = new EM_Ticket();
					$EM_Ticket->event_id = $EM_Event->event_id;
					array_unshift($EM_Tickets->tickets, $EM_Ticket); //prepend template ticket for JS
					$col_count = 0;
					foreach( $EM_Tickets->tickets as $EM_Ticket){
						/* @var $EM_Ticket EM_Ticket */
						$class_name = $col_count == 0 ? 'em-ticket-template':'em-ticket';
						?>
						<tbody id="em-ticket-<?php echo $col_count ?>" class="<?php echo $class_name; ?>">
							<tr class="em-tickets-row">
								<td class="ticket-status">
									<span class="dashicons dashicons-menu"></span>
								</td>
								<td class="ticket-name">
									<span class="ticket_name"><?php if($EM_Ticket->ticket_members) echo '* ';?><?php echo wp_kses_data($EM_Ticket->ticket_name); ?></span>
									<div class="ticket_description"><?php echo wp_kses($EM_Ticket->ticket_description,$allowedposttags); ?></div>
									<div class="ticket-actions row-actions">
										<a href="#" class="ticket-actions-edit"><?php esc_html_e('Edit','events-manager'); ?></a>
										<?php if( $EM_Ticket->get_bookings_count() == 0 ): ?>
										| <a href="<?php bloginfo('wpurl'); ?>/wp-load.php" class="ticket-actions-delete"><?php esc_html_e('Delete','events-manager'); ?></a>
										<?php else: ?>
										| <a href="<?php echo esc_url(add_query_arg('ticket_id', $EM_Ticket->ticket_id, $EM_Event->get_bookings_url())); ?>"><?php esc_html_e('View Bookings','events-manager'); ?></a>
										<?php endif; ?>
									</div>
								</td>
								<td class="ticket-price">
									<span class="ticket_price"><?php echo ($EM_Ticket->ticket_price) ? \Contexis\Events\Intl\Price::format($EM_Ticket->ticket_price) : esc_html__('Free','events-manager'); ?></span>
								</td>
								<td class="ticket-limit">
									<span class="ticket_min">
										<?php  echo ( !empty($EM_Ticket->ticket_min) ) ? esc_html($EM_Ticket->ticket_min):'-'; ?>
									</span> /
									<span class="ticket_max"><?php echo ( !empty($EM_Ticket->ticket_max) ) ? esc_html($EM_Ticket->ticket_max):'-'; ?></span>
								</td>
								<td class="ticket-time">
									<span class="ticket_start ticket-dates-from-normal"><?php echo ( !empty($EM_Ticket->ticket_start) ) ? \Contexis\Events\Intl\Date::get_date($EM_Ticket->start()->getTimestamp()):''; ?></span>
									<span class="ticket_start_recurring_days ticket-dates-from-recurring"><?php if( !empty($EM_Ticket->ticket_meta['recurrences']) ) echo $EM_Ticket->ticket_meta['recurrences']['start_days']; ?></span>
									<span class="ticket_start_recurring_days_text ticket-dates-from-recurring <?php if( !empty($EM_Ticket->ticket_meta['recurrences']) && !is_numeric($EM_Ticket->ticket_meta['recurrences']['start_days']) ) echo 'hidden'; ?>"><?php _e('day(s)','events-manager'); ?></span>
									<span class="ticket_start_time"><?php echo ( !empty($EM_Ticket->ticket_start) ) ? \Contexis\Events\Intl\Date::get_time($EM_Ticket->start()->getTimestamp() ):''; ?></span>
									<br />
									<span class="ticket_end ticket-dates-from-normal"><?php echo ( !empty($EM_Ticket->ticket_end) ) ? \Contexis\Events\Intl\Date::get_date($EM_Ticket->end()->getTimestamp()):''; ?></span>
									<span class="ticket_end_recurring_days ticket-dates-from-recurring"><?php if( !empty($EM_Ticket->ticket_meta['recurrences']) ) echo $EM_Ticket->ticket_meta['recurrences']['end_days']; ?></span>
									<span class="ticket_end_recurring_days_text ticket-dates-from-recurring <?php if( !empty($EM_Ticket->ticket_meta['recurrences']) && !is_numeric($EM_Ticket->ticket_meta['recurrences']['end_days']) ) echo 'hidden'; ?>"><?php _e('day(s)','events-manager'); ?></span>
									<span class="ticket_end_time"><?php echo ( !empty($EM_Ticket->ticket_end) ) ? \Contexis\Events\Intl\Date::get_time($EM_Ticket->end()->getTimestamp() ):''; ?></span>
								</td>
								<td class="ticket-qty">
									<span class="ticket_available_spaces"><?php echo $EM_Ticket->get_available_spaces(); ?></span>/
									<span class="ticket_spaces"><?php echo $EM_Ticket->get_spaces() ? $EM_Ticket->get_spaces() : '-'; ?></span>
								</td>
								<td class="ticket-booked-spaces">
									<span class="ticket_booked_spaces"><?php echo $EM_Ticket->get_booked_spaces(); ?></span>
								</td>
								<?php do_action('em_event_edit_ticket_td', $EM_Ticket); ?>
							</tr>
							<tr class="em-tickets-row-form" style="display:none;">
								<td colspan="<?php echo apply_filters('em_event_edit_ticket_td_colspan', 7); ?>">
									<?php include( em_locate_template('forms/event/bookings-ticket-form.php')); ?>
									<div class="em-ticket-form-actions">
									<button type="button" class="ticket-actions-edited components-button is-secondary"><?php esc_html_e('Close Ticket Editor','events-manager')?></button>
									</div>
								</td>
							</tr>
						</tbody>
						<?php
						$col_count++;
					}
					array_shift($EM_Tickets->tickets);
				?>
			</table>
	
		</div>
		<?php if( $reschedule_warnings ): //If this event is a recurring template, we need to warn the user that editing tickets will delete previous bookings ?>
		<div class="recurrence-reschedule-buttons">
		    <a href="<?php echo esc_url(add_query_arg('recreate_tickets', null)); ?>" class="button-secondary em-button em-reschedule-cancel<?php if( empty($_REQUEST['recreate_tickets']) ) echo ' reschedule-hidden'; ?>" data-target=".em-tickets-form">
		    	<?php esc_html_e('Cancel Ticket Recreation', 'events-manager'); ?>
		    </a>
		    <a href="<?php echo esc_url(add_query_arg('recreate_tickets', '1')); ?>" class="em-reschedule-trigger em-button button-secondary<?php if( !empty($_REQUEST['recreate_tickets']) ) echo ' reschedule-hidden'; ?>" data-target=".em-tickets-form">
		    	<?php esc_html_e('Modify Recurring Event Tickets ', 'events-manager'); ?>
		    </a>
	    	<input type="hidden" name="event_recreate_tickets" class="em-reschedule-value" value="<?php echo empty($_REQUEST['recreate_tickets']) ? 0:1 ?>" />
    	</div>
		<?php endif; ?>
	</div>
	
	<h3 class="title"><?php esc_html_e('Event Options','events-manager'); ?></h3>
	<table id="em-booking-options" class="form-table">
	<tbody>
	<?php if( count($EM_Ticket->get_event()->get_tickets()->tickets) > 0 ): ?>
	
	<tr>
		<th><?php esc_html_e('Total Spaces','events-manager'); ?></th>
		<td>
			<input type="text" name="event_spaces" value="<?php if( $EM_Event->event_spaces > 0 ){ echo $EM_Event->event_spaces; } ?>" /><br />
			<p class="description"><?php esc_html_e('Individual tickets with remaining spaces will not be available if total booking spaces reach this limit. Leave blank for no limit.','events-manager'); ?></p>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e('Maximum Spaces Per Booking','events-manager'); ?></th>
		<td>
			<input type="text" name="event_rsvp_spaces" value="<?php if( $EM_Event->event_rsvp_spaces > 0 ){ echo $EM_Event->event_rsvp_spaces; } ?>" /><br />
			<p class="description"><?php esc_html_e('If set, the total number of spaces for a single booking to this event cannot exceed this amount.','events-manager'); ?><?php esc_html_e('Leave blank for no limit.','events-manager'); ?></p>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e('Booking Cut-Off Date','events-manager'); ?></th>
		<td><span class="em-booking-date-normal">
			<span class="em-date-single">
				<input id="em-bookings-date" class="em-date-input" type="date" name="event_rsvp_date" value="<?php echo $EM_Event->event_rsvp_date; ?>" />
			</span>
		</span>
		um
		<span class="em-booking-date-recurring">
			<input type="number" name="recurrence_rsvp_days" size="3" value="<?php echo absint($EM_Event->recurrence_rsvp_days); ?>" />
			<?php _e('day(s)','events-manager'); ?>
			<select name="recurrence_rsvp_days_when">
				<option value="before" <?php if( !empty($EM_Event->recurrence_rsvp_days) && $EM_Event->recurrence_rsvp_days <= 0) echo 'selected="selected"'; ?>><?php echo sprintf(_x('%s the event starts','before or after','events-manager'),__('Before','events-manager')); ?></option>
				<option value="after" <?php if( !empty($EM_Event->recurrence_rsvp_days) && $EM_Event->recurrence_rsvp_days > 0) echo 'selected="selected"'; ?>><?php echo sprintf(_x('%s the event starts','before or after','events-manager'),__('After','events-manager')); ?></option>
			</select>
			<?php _e('at','events-manager'); ?>
		</span>
		<input type="time" name="event_rsvp_time" class="em-time-input" maxlength="8" size="8" value="<?php if (!empty($EM_Event->event_rsvp_time)) echo $EM_Event->rsvp_end()->format('H:i'); ?>" />
		<br />
		<p class="description"><?php esc_html_e('This is the definite date after which bookings will be closed for this event, regardless of individual ticket settings above. Default value will be the event start date.','events-manager'); ?></p>
		</td>
	</tr>
	<?php endif; ?>
	</tbody>
	</table>
	<hr>
	<?php
		if( !empty($delete_temp_ticket) ){
			array_pop($EM_Tickets->tickets);
		}
		do_action('em_events_admin_bookings_footer', $EM_Event); 
	?>
</div>