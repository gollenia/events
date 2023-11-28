<?php 

$id = get_the_ID();
$event = EM_Event::find($id);
$date = \Contexis\Events\Intl\Date::get_date($event->rsvp_end()->getTimestamp())

?>

<div class="event-details-item">
		<div class="event-details-image">
			<i class="event-details-icon material-icons">event_busy</i>
		</div>
		<div class="event-details-text">
			<h4><?php echo $attributes['description'] ?: __("Booking end", "em-pro") ?></h4>
			<time class="event-details-data">
				<?php echo $date ?>

			</time>
		</div>                        
	</div>