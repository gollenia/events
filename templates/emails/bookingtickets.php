<?php foreach($EM_Booking->get_tickets_bookings() as $EM_Ticket_Booking): ?>
<?php
/* @var $EM_Ticket_Booking EM_Ticket_Booking */
echo $EM_Ticket_Booking->get_ticket()->name; 
?>

<?php _e('Quantity','events-manager'); ?>: <?php echo $EM_Ticket_Booking->get_spaces(); ?>

<?php _e('Price','events-manager'); ?>: <?php echo \Contexis\Events\Intl\Price::currency_symbol()." ". number_format($EM_Ticket_Booking->get_price(),2); ?>


<?php endforeach; ?>