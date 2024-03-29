<?php
// TODO make person details more secure and integrate with WP user data 
class EM_Person extends WP_User {

    public $custom_user_fields = array();
	public $phone;

	function __construct( $person_id = false, $username = '' ){
		
		if( is_array($person_id) ){
			if( array_key_exists('person_id',$person_id) ){
				$person_id = $person_id['person_id'];
			}elseif ( array_key_exists('user_id',$person_id) ){
				$person_id = $person_id['user_id'];
			}else{
				$person_id = $person_id['ID'];
			}
		}elseif( is_object($person_id) && get_class($person_id) == 'WP_User'){
			$person_id = $person_id->ID; //create new object if passed a wp_user
		}
		if( is_numeric($person_id) && $person_id == 0 ){
			
			$this->data = new stdClass();
			$this->ID = 0;
			$this->display_name = 'Anonymous User';
			$this->user_email = 'anonymous@'.preg_replace('/https?:\/\//', '', get_site_url());
		}
		if($username){
			parent::__construct($person_id, $username);
		}elseif( is_numeric($person_id) && $person_id == 0 ){
			$this->data = new stdClass();
			$this->ID = 0;
			$this->display_name = 'Anonymous User';
			$this->user_email = 'anonymous@'.preg_replace('/https?:\/\//', '', get_site_url());
		}else{
			parent::__construct($person_id);
		}
		$this->phone = get_metadata('user', $this->ID, 'dbem_phone', true); //extra field for EM
		do_action('em_person',$this, $person_id, $username);
	}
	
	function get_bookings($ids_only = false, bool|array $status = false){
		global $wpdb;
		$status_condition = $blog_condition = '';
		
		if( is_numeric($status) ){
			$status_condition = " AND booking_status=$status";
		}elseif( is_array($status) && !empty($status) && array_is_list($status) ){
			$status_condition = " AND booking_status IN (".implode(',', $status).")";
		}
		$EM_Booking = EM_Booking::find(); //empty booking for fields
		$results = $wpdb->get_results("SELECT b.".implode(', b.', array_keys($EM_Booking->fields))." FROM ".EM_BOOKINGS_TABLE." b, ".EM_EVENTS_TABLE." e WHERE e.event_id=b.event_id AND person_id={$this->ID} {$blog_condition} {$status_condition} ORDER BY event_start_date ASC",ARRAY_A);
		$bookings = array();
		if($ids_only){
			foreach($results as $booking_data){
				$bookings[] = $booking_data['booking_id'];
			}
			return apply_filters('em_person_get_bookings', $bookings, $this);
		}else{
			foreach($results as $booking_data){
				$bookings[] = EM_Booking::find($booking_data);
			}
			return apply_filters('em_person_get_bookings', new EM_Bookings($bookings), $this);
		}
	}

	/**
	 * @return EM_Events
	 */
	function get_events(){
		global $wpdb;
		$events = array();
		foreach( $this->get_bookings()->get_bookings() as $EM_Booking ){
			$events[$EM_Booking->event_id] = $EM_Booking->get_event();
		}
		return apply_filters('em_person_get_events', $events);
	}
	
	function get_bookings_url(){
		return is_admin() ? EM_ADMIN_URL. "&page=events-manager-bookings&person_id=".$this->ID : '';
	}
	
	function display_summary(){
		ob_start();
		?>
		<table class="em-form-fields">
			<tr>
				<td><?php echo get_avatar($this->ID); ?></td>
				<td style="padding-left:10px; vertical-align: top;">
					<table>
						<?php if( $this->ID === 0 ): ?>
						<tr><th><?php _e('Name','events-manager'); ?> : </th><th><?php echo $this->get_name(); ?></th></tr>
						<?php else: ?>
						<tr><th><?php _e('Name','events-manager'); ?> : </th><th><a href="<?php echo $this->get_bookings_url(); ?>"><?php echo $this->get_name(); ?></a></th></tr>
						<?php endif; ?>
						<tr><th><?php _e('Email','events-manager'); ?> : </th><td><?php echo $this->user_email; ?></td></tr>
						<tr><th><?php _e('Phone','events-manager'); ?> : </th><td><?php echo esc_html($this->phone); ?></td></tr>
					</table>
				</td>
			</tr>
		</table>
		<?php
		return apply_filters('em_person_display_summary', ob_get_clean(), $this);
	}

	function get_summary(){
	    $summary = array(
		    'user_name' => array('name' => __('Name','events-manager'), 'value' => $this->get_name()),
		    'user_email' => array('name' => __('Email','events-manager'), 'value' => $this->user_email),
		    'dbem_phone' => array('name' => __('Phone','events-manager'), 'value' => $this->phone),
        );
	    $summary = array_merge( $summary, $this->custom_user_fields );
		
	    return apply_filters('em_person_get_summary', $summary, $this);
    }

	function get_name(){
		$full_name = $this->first_name  . " " . $this->last_name ;
		
		$full_name = wp_kses_data(trim($full_name));
		$name = !empty($full_name) ? $full_name : $this->display_name;
		return apply_filters('em_person_get_name',$name, $this);
	}
}
?>