<?php
use EM_Event_Locations\Event_Location, EM_Event_Locations\Event_Locations;
use \Contexis\Events\EventPost;

/**
 * Event Object. This holds all the info pertaining to an event, including location and recurrence info.
 * An event object can be one of three "types" a recurring event, recurrence of a recurring event, or a single event.
 * The single event might be part of a set of recurring events, but if loaded by specific event id then any operations and saves are 
 * specifically done on this event. However, if you edit the recurring group, any changes made to single events are overwritten.
 *
 * @property string $language           Language of the event, shorthand for event_language
 * @property string $translation        Whether or not a event is a translation (i.e. it was translated from an original event), shorthand for event_translation
 * @property int $parent                Event ID of parent event, shorthand for event_parent
 * @property int $id                    The Event ID, case sensitive, shorthand for event_id
 * @property string $slug               Event slug, shorthand for event_slug
 * @property string name                Event name, shorthand for event_name
 * @property int owner                  ID of author/owner, shorthand for event_owner
 * @property int status                 ID of post status, shorthand for event_status
 * @property string $event_start_time   Start time of event
 * @property string $event_end_time     End time of event
 * @property string $event_start_date   Start date of event
 * @property string $event_end_date     End date of event
 * @property string $event_start        The event start date in local time. represented by a mysql DATE format
 * @property string $event_end          The event end date in local time. represented by a mysql DATE format
 * @property string $event_timezone     Timezone representation in PHP string or WP-style UTC offset
 * @property string $event_rsvp_date    Start rsvo date of event
 * @property string $event_rsvp_time    End rsvp time of event
 *
 */
//TODO Can add more recurring functionality such as "also update all future recurring events" or "edit all events" like google calendar does.
//TODO Integrate recurrences into events table
//FIXME If you create a super long recurrence timespan, there could be thousands of events... need an upper limit here.
class EM_Event extends EM_Object{ 

	const POST_TYPE = "event";
	/* Field Names */
	var $event_id;
	var $post_id;
	var $event_parent;
	var $event_slug;
	var $event_owner;
	var $event_name;
	var $event_image;
	/**
	 * The event start time in local time, represented by a mysql TIME format or 00:00:00 default.
	 * Protected so when set in PHP it will reset the EM_Event->start property (EM_DateTime object) so it will have the correct UTC time according to timezone.
	 * @var string
	 */
	protected $event_start_time = '00:00:00';
	/**
	 * The event end time in local time, represented by a mysql TIME format or 00:00:00 default.
	 * Protected so when set in PHP it will reset the EM_Event->end property (EM_DateTime object) so it will have the correct UTC time according to timezone.
	 * @var string
	 */
	protected $event_end_time = '00:00:00';
	/**
	 * The event start date in local time. represented by a mysql DATE format.
	 * Protected so when set in PHP it will reset the EM_Event->start property (EM_DateTime object) so it will have the correct UTC time according to timezone.
	 * @var string
	 */
	protected $event_start_date;
	/**
	 * The event end date in local time. represented by a mysql DATE format.
	 * Protected so when set in PHP it will reset the EM_Event->start property (EM_DateTime object) so it will have the correct UTC time according to timezone.
	 * @var string
	 */
	protected $event_end_date;
	/**
	 * The event start date/time in UTC timezone, represented as a mysql DATETIME value. Protected non-accessible property. 
	 * Use $EM_Event->start() to obtain the date/time via the returned EM_DateTime object.
	 * @var string
	 */
	protected $event_start;
	/**
	 * The event end date/time in UTC timezone, represented as a mysql DATETIME value. Protected non-accessible property.
	 * Use $EM_Event->end() to obtain the date/time via the returned EM_DateTime object.
	 * @var string
	 */
	protected $event_end;
	/**
	 * Whether an event is all day or at specific start/end times. When set to true, event start/end times are assumed to be 00:00:00 and 11:59:59 respectively.
	 * @var boolean
	 */
	var $event_all_day;
	/**
	 * Timezone representation in PHP string or WP-style UTC offset.
	 * @var string
	 */
	protected $event_timezone;
	var $post_content;
	var $event_rsvp = 0;
	protected $event_rsvp_date;
	protected $event_rsvp_time;
	var $event_rsvp_spaces;
	var $event_spaces;
	var $event_private;
	var $location_id;
	/**
	 * Key name of event location type associated to this event.
	 *
	 * Events can have an event-specific location type, such as a url, webinar or another custom type instead of a regular geographical location. If this value is set, then a registered event location type is loaded and relevant saved event meta is used.
	 *
	 * @var string
	 */
	public $event_location_type;
	var $recurrence_id;
	var $event_status;
	var $blog_id = 0;
	var $group_id;
	var $event_language;
	var $event_translation = 0;
	/**
	 * Populated with the non-hidden event post custom fields (i.e. not starting with _) 
	 * @var array
	 */
	var $event_attributes = array();
	/* Recurring Specific Values */
	var $recurrence = 0;
	var $recurrence_interval;
	var $recurrence_freq;
	var $recurrence_byday;
	var $recurrence_days = 0;
	var $recurrence_byweekno;
	var $recurrence_rsvp_days;

	/* new attributes */
	var $event_audience = "";
	var int $speaker_id = 0;
	/**
	 * Previously used to give this object shorter property names for db values (each key has a name) but this is now deprecated, use the db field names as properties. This propertey provides extra info about the db fields.
	 * @var array
	 */
	var $fields = array(
		'event_id' => array( 'name'=>'id', 'type'=>'%d' ),
		'post_id' => array( 'name'=>'post_id', 'type'=>'%d' ),
		'event_parent' => array( 'type'=>'%d', 'null'=>true ),
		'event_slug' => array( 'name'=>'slug', 'type'=>'%s', 'null'=>true ),
		'event_owner' => array( 'name'=>'owner', 'type'=>'%d', 'null'=>true ),
		'event_name' => array( 'name'=>'name', 'type'=>'%s', 'null'=>true ),
		'event_timezone' => array('type'=>'%s', 'null'=>true ),
		'event_start_time' => array( 'name'=>'start_time', 'type'=>'%s', 'null'=>true ),
		'event_end_time' => array( 'name'=>'end_time', 'type'=>'%s', 'null'=>true ),
		'event_start' => array('type'=>'%s', 'null'=>true ),
		'event_end' => array('type'=>'%s', 'null'=>true ),
		'event_all_day' => array( 'name'=>'all_day', 'type'=>'%d', 'null'=>true ),
		'event_start_date' => array( 'name'=>'start_date', 'type'=>'%s', 'null'=>true ),
		'event_end_date' => array( 'name'=>'end_date', 'type'=>'%s', 'null'=>true ),
		'post_content' => array( 'name'=>'notes', 'type'=>'%s', 'null'=>true ),
		'event_rsvp' => array( 'name'=>'rsvp', 'type'=>'%d' ),
		'event_rsvp_date' => array( 'name'=>'rsvp_date', 'type'=>'%s', 'null'=>true ),
		'event_rsvp_time' => array( 'name'=>'rsvp_time', 'type'=>'%s', 'null'=>true ),
		'event_rsvp_spaces' => array( 'name'=>'rsvp_spaces', 'type'=>'%d', 'null'=>true ),
		'event_spaces' => array( 'name'=>'spaces', 'type'=>'%d', 'null'=>true),
		'location_id' => array( 'name'=>'location_id', 'type'=>'%d', 'null'=>true ),
		'event_location_type' => array( 'type'=>'%s', 'null'=>true ),
		'recurrence_id' => array( 'name'=>'recurrence_id', 'type'=>'%d', 'null'=>true ),
		'event_status' => array( 'name'=>'status', 'type'=>'%d', 'null'=>true ),
		'event_private' => array( 'name'=>'status', 'type'=>'%d', 'null'=>true ),
		'blog_id' => array( 'name'=>'blog_id', 'type'=>'%d', 'null'=>true ),
		'group_id' => array( 'name'=>'group_id', 'type'=>'%d', 'null'=>true ),
		'event_language' => array( 'type'=>'%s', 'null'=>true ),
		'event_translation' => array( 'type'=>'%d'),
		'recurrence' => array( 'name'=>'recurrence', 'type'=>'%d', 'null'=>false ), //is this a recurring event template
		'recurrence_interval' => array( 'name'=>'interval', 'type'=>'%d', 'null'=>true ), //every x day(s)/week(s)/month(s)
		'recurrence_freq' => array( 'name'=>'freq', 'type'=>'%s', 'null'=>true ), //daily,weekly,monthly?
		'recurrence_days' => array( 'name'=>'days', 'type'=>'%d', 'null'=>true ), //each event spans x days
		'recurrence_byday' => array( 'name'=>'byday', 'type'=>'%s', 'null'=>true ), //if weekly or monthly, what days of the week?
		'recurrence_byweekno' => array( 'name'=>'byweekno', 'type'=>'%d', 'null'=>true ), //if monthly which week (-1 is last)
		'recurrence_rsvp_days' => array( 'name'=>'recurrence_rsvp_days', 'type'=>'%d', 'null'=>true ), //days before or after start date to generat bookings cut-off date
	);
	var $post_fields = array('event_slug','event_owner','event_name','event_private','event_status','event_attributes','post_id','post_content'); //fields that won't be taken from the em_events table anymore
	var $recurrence_fields = array('recurrence', 'recurrence_interval', 'recurrence_freq', 'recurrence_days', 'recurrence_byday', 'recurrence_byweekno', 'recurrence_rsvp_days');
	
	protected $shortnames = array(
		'language' => 'event_language',
		'translation' => 'event_translation',
		'parent' => 'event_parent',
		'id' => 'event_id',
		'slug' => 'event_slug',
		'name' => 'event_name',
		'status' => 'event_status',
		'owner' => 'event_owner',
	);
	
	var $image_url = '';
	
	/**
	 * Timestamp for booking cut-off date/time
	 * @var EM_DateTime
	 */
	protected $rsvp_end;
	
	/**
	 * @var EM_Location
	 */
	var $location;
	/**
	 * @var Event_Location
	 */
	var $event_location;
	/**
	 * If we're switching event location types, previous event location is kept here and deleted upon save()
	 * @var Event_Location
	 */
	var $event_location_deleted = null;
	/**
	 * @var EM_Bookings
	 */
	var $bookings;
	/**
	 * The contact person for this event
	 * @var WP_User
	 */
	var $contact;
	/**
	 * The categories object containing the event categories
	 * @var EM_Categories
	 */
	var $categories;
	/**
	 * The tags object containing the event tags
	 * @var EM_Tags
	 */
	var $tags;
	/**
	 * If there are any errors, they will be added here.
	 * @var array
	 */
	var array $errors = array();

	/**
	 * If something was successful, a feedback message might be supplied here.
	 * @var string
	 */
	var $feedback_message;
	/**
	 * Any warnings about an event (e.g. bad data, recurrence, etc.)
	 * @var string
	 */
	var $warnings;
	/**
	 * Array of dbem_event field names required to create an event 
	 * @var array
	 */
	var $required_fields = array('event_name', 'event_start_date');
	var $mime_types = array(1 => 'gif', 2 => 'jpg', 3 => 'png'); 
	/**
	 * previous status of event when instantiated
	 * @access protected
	 * @var mixed
	 */
	var $previous_status = false;
	/**
	 * If set to true, recurring events will delete and recreate recurrences when saved.
	 * @var boolean
	 */
	var $recurring_reschedule = false;
	/**
	 * If set to true, recurring events will delete bookings and tickets of recurrences and recreate tickets. If set explicitly to false bookings will be ignored when creating recurrences.
	 * @var boolean
	 */
	var $recurring_recreate_bookings;
	/**
	 * Flag used for when saving a recurring event that previously had bookings enabled and then subsequently disabled.
	 * If set to true, and $this->recurring_recreate_bookings is false, bookings and tickets of recurrences will be deleted.
	 * @var boolean
	 */
	var $recurring_delete_bookings = false;
	/**
	 * If the event was just added/created during this execution, value will be true. Useful when running validation or making decisions on taking actions when events are saved/created for the first time.
	 * @var boolean
	 */
	var $just_added_event = false;
	
	/* Post Variables - copied out of post object for easy IDE reference */
	var $ID;
	var $post_author;
	var $post_date;
	var $post_date_gmt;
	var $post_title;
	var $post_excerpt = '';
	var $post_status;
	var $comment_status;
	var $ping_status;
	var $post_password;
	var $post_name;
	var $to_ping;
	var $pinged;
	var $post_modified;
	var $post_modified_gmt;
	var $post_content_filtered;
	var $post_parent;
	var $guid;
	var $menu_order;
	var $post_type;
	var $post_mime_type;
	var $comment_count;
	var $ancestors;
	var $filter;

	var $status_array;
	
	/**
	 * Initialize an event. You can provide event data in an associative array (using database table field names), an id number, or false (default) to create empty event.
	 * @param mixed $id
	 * @param mixed $search_by default is post_id, otherwise it can be by event_id as well. In multisite global mode, a blog id can be supplied to load events from another blog.
	 */
	function __construct($id = false, $search_by = 'event_id') {
		global $wpdb;
		if( is_array($id) ) new \WP_Error('events', "Events can't be arrays anymore");

		$is_post = !empty($id->ID) && ($id->post_type == EventPost::TYPE || $id->post_type == 'event-recurring');

		if( $is_post ){
			$id->ID = absint($id->ID);
		} else {
			$id = absint($id);
			if( $id == 0 ) $id = false;
		}

		if( is_numeric($id) || $is_post ){ //only load info if $id is a number
			$event_post = null;
			if($search_by == 'event_id' && !$is_post ){
				//search by event_id, get post_id and blog_id (if in ms mode) and load the post
				$results = $wpdb->get_row($wpdb->prepare("SELECT post_id, blog_id FROM ".EM_EVENTS_TABLE." WHERE event_id=%d",$id), ARRAY_A);
				if( !empty($results['post_id']) ) { $this->post_id = $results['post_id']; $this->event_id = $id; }
				if( !array_key_exists('blog_id', $results) || $results['blog_id']=='' ){
				    $results['blog_id'] = '';
					$event_post = get_post($results['blog_id'], $results['post_id']);
					$search_by = $this->blog_id = $results['blog_id'];
				}elseif( !empty($results['post_id']) ){
					$event_post = get_post($results['post_id']);	
				}
			}else{
				//get post data based on ID and search context
				if(!$is_post){
					if( $search_by == '' ){
					    if( $search_by == '' ) $search_by = 'post_id';
						//we've been given a blog_id, so we're searching for a post id
						$event_post = get_post($search_by, $id);
						$this->blog_id = $search_by;
					}else{
						//search for the post id only
						$event_post = get_post($id);
					}
				}else{
					$event_post = $id;
				}
				$this->post_id = !empty($id->ID) ? $id->ID : $id;
			}
			$this->load_postdata($event_post, $search_by);
		}
		//set default timezone
		if( empty($this->event_timezone) ){
			$this->event_timezone = EM_DateTimeZone::create()->getName(); //set a default timezone if none exists
		}
		//set recurrence value already
		$this->recurrence = $this->is_recurring() ? 1:0;
		//Do it here so things appear in the po file.
		$this->status_array = array(
			0 => __('Pending','events-manager'),
			1 => __('Approved','events-manager')
		);
		// fire hook to add any extra info to an event
		do_action('em_event', $this, $id, $search_by);
		//add this event to the cache
		if( $this->event_id && $this->post_id ){
			wp_cache_set($this->event_id, $this, 'em_events');
			wp_cache_set($this->post_id, $this->event_id, 'em_events_ids');
		}
	}
	
	function __get( $var ){
	    //get the modified or created date from the DB only if requested, and save to object
	    if( $var == 'event_date_modified' || $var == 'event_date_created'){
	        global $wpdb;
	        $row = $wpdb->get_row($wpdb->prepare("SELECT event_date_created, event_date_modified FROM ".EM_EVENTS_TABLE.' WHERE event_id=%s', $this->event_id));
	        if( $row ){
	            $this->event_date_modified = $row->event_date_modified;
	            $this->event_date_created = $row->event_date_created;
	            return $this->$var;
	        }
	    }elseif( in_array($var, array('event_start_date', 'event_start_time', 'event_end_date', 'event_end_time', 'event_rsvp_date', 'event_rsvp_time')) ){
	    	return $this->$var;
	    }elseif( $var == 'event_timezone' ){
	    	return $this->get_timezone()->getName();
	    }
	    
	    return parent::__get( $var );
	}
	
	public function __set( $prop, $val ){
		if( $prop == 'event_start_date' || $prop == 'event_end_date' || $prop == 'event_rsvp_date' ){
			//if date is valid, set it, if not set it to null
			$this->$prop = preg_match('/^\d{4}-\d{2}-\d{2}$/', $val) ? $val : null;
			if( $prop == 'event_start_date') $this->event_start = $this->start()->getTimestamp();
			elseif( $prop == 'event_end_date') $this->event_end = $this->end()->getTimestamp();
			elseif( $prop == 'event_rsvp_date') $this->rsvp_end = null;
		}elseif( $prop == 'event_start_time' || $prop == 'event_end_time' || $prop == 'event_rsvp_time' ){
			//if time is valid, set it, otherwise set it to midnight
			$this->$prop = preg_match('/^\d{2}:\d{2}:\d{2}$/', $val) ? $val : '00:00:00';
			if( $prop == 'event_rsvp_date') $this->rsvp_end = null;
		}
		//deprecated properties, use start()->setTimestamp() instead
		elseif( $prop == 'start' || $prop == 'end' || $prop == 'rsvp_end' ){
			if( is_numeric($val) ){
				$this->$prop()->setTimestamp( (int) $val);
			}elseif( is_string($val) ){
				$this->$val = new EM_DateTime($val, $this->event_timezone);
			}
		}
		//anything else
		else{
			$this->$prop = $val;
		}
		parent::__set( $prop, $val );
	}
	
	public function __isset( $prop ){
		if( in_array($prop, array('event_start_date', 'event_end_date', 'event_start_time', 'event_end_time', 'event_rsvp_date', 'event_rsvp_time', 'event_start', 'event_end')) ){
			return !empty($this->$prop);
		}elseif( $prop == 'event_timezone' ){
			return true;
		}elseif( $prop == 'start' || $prop == 'end' || $prop == 'rsvp_end' ){
			return $this->$prop()->valid;
		}
		return parent::__isset( $prop );
	}
	
	/**
	 * When cloning this event, we get rid of the bookings and location objects, since they can be retrieved again from the cache instead. 
	 */
	public function __clone(){
		$this->bookings = null;
		$this->location = null;
		if( is_object($this->event_location) ){
			$this->event_location = clone $this->event_location;
			$this->event_location->event = $this;
		}
	}

		/**
	 * Get an event in a db friendly way, by checking globals, cache and passed variables to avoid extra class instantiations.
	 * @param mixed $id can be either a post object, event object, event id or post id
	 * @param mixed $search_by default is post_id, otherwise it can be by event_id as well. In multisite global mode, a blog id can be supplied to load events from another blog.
	 * @return EM_Event
	 */
	public static function find($id = false, $search_by = 'event_id') {
		global $EM_Event;
		//check if it's not already global so we don't instantiate again
		if( is_object($EM_Event) && get_class($EM_Event) == 'EM_Event' ){
			if( is_object($id) && $EM_Event->post_id == $id->ID ){
				return $EM_Event;
			}elseif( !is_object($id) ){
				if( $search_by == 'event_id' && $EM_Event->event_id == $id ){
					return $EM_Event;
				}elseif( $search_by == 'post_id' && $EM_Event->post_id == $id ){
					return $EM_Event;
				}
			}
		}
		if( is_object($id) && get_class($id) == 'EM_Event' ){
			return $id;
		}else{
			//check the cache first
			$event_id = false;
			if( is_numeric($id) ){
				if( $search_by == 'event_id' ){
					$event_id = absint($id);
				}elseif( $search_by == 'post_id' ){
					$event_id = wp_cache_get($id, 'em_events_ids');
				}
			}elseif( !empty($id->ID) && !empty($id->post_type) && ($id->post_type == EM_POST_TYPE_EVENT || $id->post_type == 'event-recurring') ){
				$event_id = wp_cache_get($id->ID, 'em_events_ids');
			}
			if( $event_id ){
				$event = wp_cache_get($event_id, 'em_events');
				if( is_object($event) && !empty($event->event_id) && $event->event_id){
					return $event;
				}
			}
		}
		//if we get this far, just create a new event
		return new EM_Event($id,$search_by);
	}

	/**
	 * return an array of tickets. We also include the attendee fields, since maybe we later want to 
	 * have separate fields for each ticket.
	 *
	 * @return void
	 */
	public function get_tickets_rest() {
		$tickets = (array)$this->get_bookings()->get_available_tickets()->tickets;
		$ticket_collection = [];
		
        foreach($tickets as $id => $ticket) {
			$ticket_collection[$id]= [
                "id" => $id,
                "event_id" => $ticket->event_id,
                "is_available" => $ticket->is_available,
                "max" => intval($ticket->ticket_max ? min( $ticket->ticket_spaces, $ticket->ticket_max ) : $ticket->ticket_spaces),
                "price" => floatval($ticket->ticket_price),
                "min" => $ticket->ticket_min ?: 0,
                "name" => $ticket->ticket_name,
                "description" => $ticket->ticket_description,
				"fields" => []
            ];
        }
        return $ticket_collection;
	}

	private function get_image() {

		$thumbnail = get_post_thumbnail_id($this->post_id);

		if(!$thumbnail) return false;

		$attachment = [
			'attachment_id' => $thumbnail,
			'sizes' => []
		];
		
		foreach(get_intermediate_image_sizes($thumbnail) as $size) {
			$attachment['sizes'][$size] = array_combine(['url', 'width',  'height', 'resized'], wp_get_attachment_image_src( $thumbnail, $size) );
		}
		
		return $attachment;
		
	}
	
	function load_postdata($event_post, $search_by = false){
		//load event post object if it's an actual object and also a post type of our event CPT names
		
		if( is_object($event_post) && ($event_post->post_type == 'event-recurring' || $event_post->post_type == EM_POST_TYPE_EVENT) ){
			//load post data - regardless
			$this->post_id = absint($event_post->ID);
			$this->event_name = $event_post->post_title;
			$this->event_owner = $event_post->post_author;
			$this->post_content = $event_post->post_content;
			$this->post_excerpt = $event_post->post_excerpt;
			$this->event_slug = $event_post->post_name;
			$this->event_image = $this->get_image();

			foreach( $event_post as $key => $value ){ //merge post object into this object
				$this->$key = $value;
			}

			$this->recurrence = $this->is_recurring() ? 1:0;
			//load meta data and other related information
			if( $event_post->post_status != 'auto-draft' ){
			    $event_meta = $this->get_event_meta($search_by);
			    
				//Get custom fields and post meta
				
				foreach($event_meta as $event_meta_key => $event_meta_val){
					
					$field_name = substr($event_meta_key, 1);
					if($event_meta_key[0] != '_'){
						$this->event_attributes[$event_meta_key] = ( is_array($event_meta_val) ) ? $event_meta_val[0]:$event_meta_val;
					}elseif( is_string($field_name) && !in_array($field_name, $this->post_fields) ){
						if( array_key_exists($field_name, $this->fields) ){
							$this->$field_name = $event_meta_val[0];
						}
					}
				}

				$this->speaker_id = array_key_exists('_speaker_id', $event_meta) ? intval($event_meta['_speaker_id'][0]) : 0;
				
				if( $this->has_event_location() ) $this->get_event_location()->load_postdata($event_meta);
				//quick compatability fix in case _event_id isn't loaded or somehow got erased in post meta
				if( empty($this->event_id) && !$this->is_recurring() ){
					global $wpdb;
					
					$event_array = $wpdb->get_row('SELECT * FROM '.EM_EVENTS_TABLE. ' WHERE post_id='.$this->post_id, ARRAY_A);	
					
					if( !empty($event_array['event_id']) ){
						foreach($event_array as $key => $value){
							if( !empty($value) && empty($this->$key) ){
								update_post_meta($event_post->ID, '_'.$key, $value);
								$this->$key = $value;
							}
						}
					}
				}
			}
			$this->get_status();
			$this->compat_keys();
		}elseif( !empty($this->post_id) ){
			//we have an orphan... show it, so that we can at least remove it on the front-end
			global $wpdb;
			
			$event_array = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".EM_EVENTS_TABLE." WHERE post_id=%d",$this->post_id), ARRAY_A);
			
		    if( is_array($event_array) ){
				$this->orphaned_event = true;
				$this->post_id = $this->ID = $event_array['post_id'] = null; //reset post_id because it doesn't really exist
				$this->to_object($event_array);
		    }
		}
		if( empty($this->location_id) && !empty($this->event_id) ) $this->location_id = 0; //just set location_id to 0 and avoid any doubt
	}
	
	function get_event_meta($blog_id = false){
		
		
		if( empty($this->post_id) ) return array();
		
		$event_meta = get_post_meta($this->post_id);
		
		if( !is_array($event_meta) ) $event_meta = array();
		
		return apply_filters('em_event_get_event_meta', $event_meta);
	}
	
	/**
	 * Retrieve event information via POST (only used in situations where posts aren't submitted via WP)
	 * @return boolean
	 */
	function get_post($validate = true){	
		global $allowedposttags;
		do_action('em_event_get_post_pre', $this);
		//we need to get the post/event name and content.... that's it.
		$this->post_content = isset($_POST['content']) ? wp_kses( wp_unslash($_POST['content']), $allowedposttags):'';
		$this->post_excerpt = !empty($this->post_excerpt) ? $this->post_excerpt:'';
		$this->post_type = ($this->is_recurring() || !empty($_POST['recurring'])) ? 'event-recurring':EM_POST_TYPE_EVENT;
		//don't forget categories!
		$this->get_categories()->get_post();
		//get the rest and validate (optional)
		$this->get_post_meta();
		
		//validate and return results
		$result = $validate ? $this->validate():true; //validate both post and meta, otherwise return true
		return apply_filters('em_event_get_post', $result, $this);
	}
	
	/**
	 * Retrieve event post meta information via POST, which should be always be called when saving the event custom post via WP.
	 * @return boolean
	 */
	function get_post_meta(){
		
		do_action('em_event_get_post_meta_pre', $this);
		
		//Check if this is recurring or not early on so we can take appropriate action further down
		$this->recurrence = get_post_type($this->post_id) == 'event-recurring' ? 1:0;
		$this->post_type = $this->recurrence ? 'event-recurring':EM_POST_TYPE_EVENT;
		
		$this->event_timezone = EM_DateTimeZone::create()->getName();
		
		$this->event_start = $this->event_end = null;

		
		$this->event_start_date = get_post_meta($this->post_id, '_event_start_date', true) ?? date('Y-m-d')	;
		$this->event_end_date = get_post_meta($this->post_id, '_event_end_date', true) ?? date('Y-m-d')	;
		
		$this->event_rsvp_time = get_post_meta($this->post_id, '_event_rsvp_time', true);
		$this->event_all_day = get_post_meta($this->post_id, '_event_all_day', true);
		
		$this->event_start_time = $this->event_all_day ? "00:00:00" : get_post_meta($this->post_id, '_event_start_time', true);
		$this->event_end_time = $this->event_all_day ? "23:59:59" : get_post_meta($this->post_id, '_event_end_time', true);
		$this->event_start = $this->start()->getTimestamp();
		$this->event_end = $this->end()->getTimestamp();
		
		//Get Location Info
		$this->location_id = get_option('dbem_locations_enabled') ? get_post_meta($this->post_id, '_location_id', true) : 0;
		
		$can_manage_bookings = $this->can_manage('manage_bookings','manage_others_bookings');
		$preview_autosave = is_admin() && !empty($_REQUEST['wp-preview']) && $_REQUEST['wp-preview'] == 'dopreview'; 
		//we shouldn't save new data during a preview auto-save
		if( !$preview_autosave && $can_manage_bookings && !empty($_POST['event_rsvp']) && $_POST['event_rsvp'] ){
			//get tickets only if event is new, non-recurring, or recurring but specifically allowed to reschedule by user
			if( !$this->is_recurring() || (empty($this->event_id) || !empty($_REQUEST['event_recreate_tickets'])) || !$this->event_rsvp ){
				$this->get_bookings()->get_tickets()->get_post();
			}
			$this->event_rsvp = 1;
			$this->rsvp_end = null;
			//RSVP cuttoff TIME is set up above where start/end times are as well
				
			//if no rsvp cut-off date supplied, make it the event start date
			$this->event_rsvp_date = ( !empty($_POST['event_rsvp_date']) ) ? wp_kses_data($_POST['event_rsvp_date']) : $this->event_start_date;
			//if no specificed time, default to event start time
			if ( empty($_POST['event_rsvp_time']) ) $this->event_rsvp_time = $this->event_start_time;
			    
			    //reset EM_DateTime object
			$this->rsvp_end = null;
			$this->event_spaces = ( isset($_POST['event_spaces']) ) ? absint($_POST['event_spaces']):0;
			$this->event_rsvp_spaces = ( isset($_POST['event_rsvp_spaces']) ) ? absint($_POST['event_rsvp_spaces']):0;
		}elseif( !$preview_autosave && ($can_manage_bookings || !$this->event_rsvp) ){
			if( empty($_POST['event_rsvp']) && $this->event_rsvp ) $deleting_bookings = true;
			$this->event_rsvp = 0;
			$this->event_rsvp_date = $this->event_rsvp_time = $this->rsvp_end = null;
		}
		
		
		
		//group id
		$this->group_id = (!empty($_POST['group_id']) && is_numeric($_POST['group_id'])) ? absint($_POST['group_id']):0;
		
		//Recurrence data
		if( $this->is_recurring() ){
			$this->recurrence = 1; //just in case
			
			$this->recurrence_freq = get_post_meta($this->post_id, '_recurrence_freq', true) ?? 'daily';
			$this->recurrence_interval = get_post_meta($this->post_id, '_recurrence_interval', true) ?? 1;
			if($this->recurrence_interval == 0) $this->recurrence_interval = 1; // prevent loop
			$this->recurrence_byweekno = get_post_meta($this->post_id, '_recurrence_byweekno', true) ?? '';
			$this->recurrence_days = get_post_meta($this->post_id, '_recurrence_days', true) ?? 0;
			$this->recurrence_byday = get_post_meta($this->post_id, '_recurrence_byday', true) ?? null;
		
			
			//here we do a comparison between new and old event data to see if we are to reschedule events or recreate bookings
			if( $this->event_id ){ //only needed if this is an existing event needing rescheduling/recreation
				//Get original recurring event so we can tell whether event recurrences or bookings will be recreated or just modified
				
				$this->recurring_reschedule = $this->check_reschedule();
				
				
				//now check tickets if we don't already have to reschedule
				if( !$this->recurring_reschedule && $this->event_rsvp ){
					//@TODO - ideally tickets could be independent of events, it'd make life easier here for comparison and editing without rescheduling
					$EM_Tickets = $this->get_tickets();
					//we compare tickets
					foreach( $this->get_tickets()->tickets as $EM_Ticket ){
						if( !empty($EM_Ticket->ticket_id) && !empty($EM_Tickets->tickets[$EM_Ticket->ticket_id]) ){
							$new_ticket = $EM_Ticket->to_array(true);
							foreach( $EM_Tickets->tickets[$EM_Ticket->ticket_id]->to_array() as $k => $v ){
								if( !(empty($new_ticket[$k]) && empty($v)) && ((empty($new_ticket[$k]) && $v) || $new_ticket[$k] != $v) ){
									if( $k == 'ticket_meta' && is_array($v) && is_array($new_ticket['ticket_meta']) ){
										foreach( $v as $k_meta => $v_meta ){
											if( (empty($new_ticket['ticket_meta'][$k_meta]) && !empty($v_meta)) || $new_ticket['ticket_meta'][$k_meta] != $v_meta ){
												$this->recurring_recreate_bookings = true; //something changed, so we reschedule
											}
										}
									}else{
										$this->recurring_recreate_bookings = true; //something changed, so we reschedule
									}
								}
							}
						}else{
							$this->recurring_recreate_bookings = true; //we have a new ticket
						}
					}
				}elseif( !empty($deleting_bookings) ){
					$this->recurring_delete_bookings = true;
				}
				unset($EM_Event);
			}else{
				//new event so we create everything from scratch
				$this->recurring_reschedule = $this->recurring_recreate_bookings = true;
			}
			//recurring events may have a cut-off date x days before or after the recurrence start dates
			$this->recurrence_rsvp_days = null;
			
			if( array_key_exists('recurrence_rsvp_days', $_POST) ){
				if( !empty($_POST['recurrence_rsvp_days_when']) && $_POST['recurrence_rsvp_days_when'] == 'after' ){
					$this->recurrence_rsvp_days = absint($_POST['recurrence_rsvp_days']);
				}else{ //by default the start date is the point of reference
					$this->recurrence_rsvp_days = absint($_POST['recurrence_rsvp_days']) * -1;
				}
			}
			
			//create timestamps and set rsvp date/time for a normal event
			if( !is_numeric($this->recurrence_rsvp_days) ){
				//falback in case nothing gets set for rsvp cut-off
				$this->event_rsvp_date = $this->event_rsvp_time = $this->rsvp_end = null;
			}else{
				$this->event_rsvp_date = $this->start()->copy()->modify($this->recurrence_rsvp_days.' days')->getDate();
			}
		}else{
			foreach( $this->recurrence_fields as $recurrence_field ){
				$this->$recurrence_field = null;
			}
			$this->recurrence = 0; // to avoid any doubt
		}
		//event language
		if( EM_ML::$is_ml && !empty($_POST['event_language']) && array_key_exists($_POST['event_language'], EM_ML::$langs) ){
			$this->event_language = $_POST['event_language'];
		}

		$this->compat_keys(); //compatability

		$this->event_audience = get_post_meta($this->post_id, '_event_audience', true);
		echo "Bimml";
		
		
		$this->speaker_id = intval(get_post_meta($this->post_id, '_speaker_id', true)) ?? 0	;

		return apply_filters('em_event_get_post_meta', count($this->errors) == 0, $this);

	}

	function check_reschedule() {
		global $wpdb;
		$result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ". EM_EVENTS_TABLE ." WHERE event_id=%d", $this->event_id) );
		if(!$result) return true;

	//first check event times
		$recurring_event_dates = array(
				'event_start_date' => $result->event_start_date,
				'event_end_date' => $result->event_end_date,
				'event_start_time' => $result->event_start_time,
				'event_end_time' => $result->event_end_time,
				'recurrence_byday' => $result->recurrence_byday,
				'recurrence_byweekno' => $result->recurrence_byweekno,
				'recurrence_days' => $result->recurrence_days,
				'recurrence_freq' => $result->recurrence_freq,
				'recurrence_interval' => $result->recurrence_interval
		);
		
		$reschedule = false;
		//check previously saved event info compared to current recurrence info to see if we need to reschedule
		foreach($recurring_event_dates as $k => $v){
			if( $this->$k != $v ){
				$reschedule = true; //something changed, so we reschedule
			}
		}

		return $reschedule;
	}
	
	function validate(){
		$validate_post = true;
		if( empty($this->event_name) ){
			$validate_post = false; 
			$this->add_error( sprintf(__("%s is required.", 'events-manager'), __('Event name','events-manager')) );
		}
		//anonymous submissions and guest basic info
		
		$validate_tickets = true; //must pass if we can't validate bookings
		if( $this->can_manage('manage_bookings','manage_others_bookings') ){
		    $validate_tickets = $this->get_bookings()->get_tickets()->validate();
		}
		
		$validate_meta = $this->validate_meta();
		return apply_filters('em_event_validate', $validate_post && $validate_meta && $validate_tickets, $this );		
	}
	
	function validate_meta(){
		if ( !session_id() ) {
			session_start();
		}
		$missing_fields = Array ();
		foreach ( array('event_start_date') as $field ) {
			if ( $this->$field == "") {
				$missing_fields[$field] = $field;
			}
		}
		if( preg_match('/\d{4}-\d{2}-\d{2}/', $this->event_start_date) && preg_match('/\d{4}-\d{2}-\d{2}/', $this->event_end_date) ){
			if( $this->start()->getTimestamp() > $this->end()->getTimestamp() ){
				$this->add_error(__('Events cannot start after they end.','events-manager'));
			}elseif( $this->is_recurring() && $this->recurrence_days == 0 && $this->start()->getTimestamp() > $this->end()->getTimestamp() ){
				$this->add_error(__('Events cannot start after they end.','events-manager').' '.__('For recurring events that end the following day, ensure you make your event last 1 or more days.'));
			}
		}else{
			if( !empty($missing_fields['event_start_date']) ) { unset($missing_fields['event_start_date']); }
			if( !empty($missing_fields['event_end_date']) ) { unset($missing_fields['event_end_date']); }
			$this->add_error(__('Dates must have correct formatting. Please use the date picker provided.','events-manager'));
		}
		if( $this->event_rsvp ){
		    if( !$this->get_bookings()->get_tickets()->validate() ){
		        $this->add_error($this->get_bookings()->get_tickets()->get_errors());
		    }
		    if( !empty($this->event_rsvp_date) && !preg_match('/\d{4}-\d{2}-\d{2}/', $this->event_rsvp_date) ){
				$this->add_error(__('Dates must have correct formatting. Please use the date picker provided.','events-manager'));
		    }
		}
		if( get_option('dbem_locations_enabled') ){
			if( $this->has_location() ){
				// physical location
				if( empty($this->location_id) && !$this->get_location()->validate() ){
					// new location doesn't validate
					$this->add_error($this->get_location()->get_errors());
				}elseif( !empty($this->location_id) && !$this->get_location()->location_id ){
					// non-existent location selected
					$this->add_error( __('Please select a valid location.', 'events-manager') );
				}
			}elseif( $this->has_event_location() ){
				// event location, validation applies errors directly to $this
				$this->get_event_location()->validate();
			}
		}
		if ( count($missing_fields) > 0){
			// TODO Create friendly equivelant names for missing fields notice in validation
			$this->add_error( __( 'Missing fields: ', 'events-manager') . implode ( ", ", $missing_fields ) . ". " );
		}
		if ( $this->is_recurring() ){
		    if( $this->event_end_date == "" || $this->event_end_date == $this->event_start_date){
		        $this->add_error( __( 'Since the event is repeated, you must specify an event end date greater than the start date.', 'events-manager'));
		    }
		    if( $this->recurrence_freq == 'weekly' && !preg_match('/^[0-9](,[0-9])*$/',$this->recurrence_byday) ){
		        $this->add_error( __( 'Please specify what days of the week this event should occur on.', 'events-manager'));
		    }
		}
		return apply_filters('em_event_validate_meta', count($this->errors) == 0, $this );
	}
	
	/**
	 * Will save the current instance into the database, along with location information if a new one was created and return true if successful, false if not.
	 * Will automatically detect whether it's a new or existing event. 
	 * @return boolean
	 */
	function save(){
		global $blog_id, $EM_SAVING_EVENT;
		$EM_SAVING_EVENT = true; //this flag prevents our dashboard save_post hooks from going further
		if( !$this->can_manage('edit_events', 'edit_others_events') && empty($this->event_id) ){
			//unless events can be submitted by an anonymous user (and this is a new event), user must have permissions.
			return apply_filters('em_event_save', false, $this);
		}
		//start saving process
		do_action('em_event_save_pre', $this);
		$post_array = array();
		//Deal with updates to an event
		if( !empty($this->post_id) ) $post_array = (array) get_post($this->post_id);

		//Overwrite new post info
		$post_array['post_type'] = ($this->recurrence && get_option('dbem_recurrence_enabled')) ? 'event-recurring':EM_POST_TYPE_EVENT;
		$post_array['post_title'] = $this->event_name;
		$post_array['post_content'] = !empty($this->post_content) ? $this->post_content : '';
		$post_array['post_excerpt'] = $this->post_excerpt;
		
		//decide on post status
		if( empty($this->force_status) ){
			if( count($this->errors) == 0 ){
				$post_array['post_status'] = ( $this->can_manage('publish_events','publish_events') ) ? 'publish':'pending';
			}else{
				$post_array['post_status'] = 'draft';
				
			}
		}else{
		    $post_array['post_status'] = $this->force_status;
		}
		
		//Save post and continue with meta
		$post_id = wp_insert_post($post_array);
		$post_save = false;
		$meta_save = false;
		if( !is_wp_error($post_id) && !empty($post_id) ){
			$post_save = true;
			//refresh this event with wp post info we'll put into the db
			$post_data = get_post($post_id);
			$this->post_id = $this->ID = $post_id;
			$this->post_type = $post_data->post_type;
			$this->event_slug = $post_data->post_name;
			$this->event_owner = $post_data->post_author;
			$this->post_status = $post_data->post_status;
			$this->get_status();
			$this->get_categories()->event_id = $this->event_id;
			$this->categories->post_id = $this->post_id;
			$this->categories->save();
		
		
			//save the image, errors here will surface during $this->save_meta()
			//now save the meta
			$meta_save = $this->save_meta();
		}
		$result = $meta_save && $post_save;
		if($result) $this->load_postdata($post_data, $blog_id); //reload post info
		//do a dirty update for location too if it's not published
		if( $this->is_published() && !empty($this->location_id) ){
			$EM_Location = $this->get_location();
			if( $EM_Location->location_status !== 1 ){
				//let's also publish the location
				$EM_Location->set_status(1, true);
			}
		}
		
		$return = apply_filters('em_event_save', $result, $this);
		$EM_SAVING_EVENT = false;
		//reload post data and add this event to the cache, after any other hooks have done their thing
		//cache refresh when saving via admin area is handled in EM_Event_Post_Admin::save_post/refresh_cache
		if( $result && $this->is_published() ){ 
			//we won't depend on hooks, if we saved the event and it's still published in its saved state, refresh the cache regardless
			$this->load_postdata($this);
			wp_cache_set($this->event_id, $this, 'em_events');
			wp_cache_set($this->post_id, $this->event_id, 'em_events_ids');
		}
		return $return;
	}
	
	function save_meta(){
		global $wpdb, $EM_SAVING_EVENT;
		$EM_SAVING_EVENT = true;
		
		//trigger setting of event_end and event_start in case it hasn't been set already
		$this->start();
		$this->end();
		//continue with saving if permissions allow
		if( $this->can_manage('edit_events', 'edit_others_events') ){
			do_action('em_event_save_meta_pre', $this);
			//language default
			if( !$this->event_language ) $this->event_language = EM_ML::$current_language;
			//first save location
			if( empty($this->location_id) ) $this->location_id = 0;
			//Update Post Meta
			$current_meta_values = $this->get_event_meta();
			foreach( $this->fields as $key => $field_info ){
				//certain keys will not be saved if not needed, including flags with a 0 value. Older databases using custom WP_Query calls will need to use an array of meta_query items using NOT EXISTS - OR - value=0
				if( !in_array($key, $this->post_fields) && $key != 'event_attributes' ){
					//ignore certain fields and delete if not new
					$save_meta_key = true;
					if( !$this->is_recurring() && in_array($key, $this->recurrence_fields) ) $save_meta_key = false;
					if( !$this->is_recurrence() && $key == 'recurrence_id' ) $save_meta_key = false;
					if( !EM_ML::$is_ml && $key == 'event_language' ) $save_meta_key = false;
					$ignore_zero_keys = array('group_id', 'event_all_day', 'event_parent', 'event_translation');
					if( in_array($key, $ignore_zero_keys) && empty($this->$key) ) $save_meta_key = false;
					if( $key == 'blog_id' ) $save_meta_key = false; //not needed, given postmeta is stored on the actual blog table in MultiSite
					//we don't need rsvp info if rsvp is not set, including the RSVP flag too
					if( empty($this->event_rsvp) && in_array($key, array('event_rsvp','event_rsvp_date', 'event_rsvp_time', 'event_rsvp_spaces', 'event_spaces')) ) $save_meta_key = false;
					//save key or ignore/delete key
					if( $save_meta_key ){
						update_post_meta($this->post_id, '_'.$key, $this->$key);
					}elseif( array_key_exists('_'.$key, $current_meta_values) ){
						//delete if this event already existed, in case this event already had the values before
						delete_post_meta($this->post_id, '_'.$key);
					}
				}elseif( array_key_exists('_'.$key, $current_meta_values) && $key != 'event_attributes' ){ //we should delete event_attributes, but maybe something else uses it without us knowing
					delete_post_meta($this->post_id, '_'.$key);
				}
			}
			
			
			//update timestamps, dates and times
			update_post_meta($this->post_id, '_event_start_local', $this->start()->getDateTime());
			update_post_meta($this->post_id, '_event_end_local', $this->end()->getDateTime());
			update_post_meta($this->post_id, '_event_start', $this->start()->getDateTime());
			update_post_meta($this->post_id, '_event_end', $this->end()->getDateTime());
			
			//sort out event status			
			$result = count($this->errors) == 0;
			$this->get_status();
			$this->event_status = ($result) ? $this->event_status:null; //set status at this point, it's either the current status, or if validation fails, null
			//Save to em_event table
			$event_array = $this->to_array(true);
			unset($event_array['event_id']);
			//decide whether or not event is private at this point
			$event_array['event_private'] = ( $this->post_status == 'private' ) ? 1:0;
			
			
			//check if event truly exists, meaning the event_id is actually a valid event id
			
			//save all the meta
			
			if( empty($this->event_id) || !$this->event_exists() ){
				$this->previous_status = 0; //for sure this was previously status 0
				$this->event_date_created = $event_array['event_date_created'] = current_time('mysql');
				if ( !$wpdb->insert(EM_EVENTS_TABLE, $event_array) ){
					$this->add_error( sprintf(__('Something went wrong saving your %s to the index table. Please inform a site administrator about this.','events-manager'),__('event','events-manager')));
				}else{
					//success, so link the event with the post via an event id meta value for easy retrieval
					$this->event_id = $wpdb->insert_id;
					update_post_meta($this->post_id, '_event_id', $this->event_id);
					$this->feedback_message = sprintf(__('Successfully saved %s','events-manager'),__('Event','events-manager'));
					$this->just_added_event = true; //make an easy hook
					$this->get_bookings()->bookings = array(); //set bookings array to 0 to avoid an extra DB query
					do_action('em_event_save_new', $this);
				}
			}else{
			    $event_array['post_content'] = $this->post_content; //in case the content was removed, which is acceptable
				
			    $this->get_previous_status();
				$this->event_date_modified = $event_array['event_date_modified'] = current_time('mysql');
				if ( $wpdb->update(EM_EVENTS_TABLE, $event_array, array('event_id'=>$this->event_id) ) === false ){
					$this->add_error( sprintf(__('Something went wrong updating your %s to the index table. Please inform a site administrator about this.','events-manager'),__('event','events-manager')));			
				}else{
					//Also set the status here if status != previous status
					if( $this->previous_status != $this->get_status() ) $this->set_status($this->get_status());
					$this->feedback_message = sprintf(__('Successfully saved %s','events-manager'),__('Event','events-manager'));
				}
				
			}
			
			//Add/Delete Tickets
			if($this->event_rsvp == 0){
				if( !$this->just_added_event ){
					$this->get_bookings()->delete();
					$this->get_tickets()->delete();
				}
			}elseif( $this->can_manage('manage_bookings','manage_others_bookings') ){
				if( !$this->get_bookings()->get_tickets()->save() ){
					$this->add_error( $this->get_bookings()->get_tickets()->get_errors() );
				}
			}
			$result = count($this->errors) == 0;
			//deal with categories
			
		    $this->compat_keys(); //compatability keys, loaded before saving recurrences
			//build recurrences if needed
			if( $this->is_recurring() && $result && ($this->is_published() || $this->post_status == 'future' ) ){ //only save events if recurring event validates and is published or set for future
				global $EM_EVENT_SAVE_POST;
				//If we're in WP Admin and this was called by EM_Event_Post_Admin::save_post, don't save here, it'll be done later in EM_Event_Recurring_Post_Admin::save_post
				if( empty($EM_EVENT_SAVE_POST) ){
					if( $this->just_added_event ) $this->recurring_reschedule = true;
				 	if( !$this->save_events() ){
						$this->add_error(__ ( 'Something went wrong with the recurrence update...', 'events-manager'). __ ( 'There was a problem saving the recurring events.', 'events-manager'));
				 	}
				}
			}
			if( !empty($this->just_added_event) ){
				do_action('em_event_added', $this);
			}
		}
		$EM_SAVING_EVENT = false;
		return apply_filters('em_event_save_meta', count($this->errors) == 0, $this);
	}


	function event_exists() {
		global $wpdb;
		if (empty($this->event_id)) return false;
		if( !empty($this->orphaned_event ) && !empty($this->post_id) ) return true; 
		return $wpdb->get_var('SELECT post_id FROM '.EM_EVENTS_TABLE." WHERE event_id={$this->event_id}") == $this->post_id;
	}
	
	/**
	 * Duplicates this event and returns the duplicated event. Will return false if there is a problem with duplication.
	 * @return EM_Event
	 */
	function duplicate(){
		global $wpdb;
		//First, duplicate.
		if( !$this->can_manage('edit_events','edit_others_events') ) return apply_filters('em_event_duplicate', false, $this);
		
		$EM_Event = clone $this;
		$EM_Event->get_categories(); //before we remove event/post ids
		$EM_Event->get_bookings()->get_tickets(); //in case this wasn't loaded and before we reset ids
		$EM_Event->event_id = null;
		$EM_Event->post_id = null;
		$EM_Event->ID = null;
		$EM_Event->post_name = '';
		$EM_Event->location_id = $EM_Event->location_id ?? 0;
		$EM_Event->get_bookings()->event_id = null;
		$EM_Event->get_bookings()->get_tickets()->event_id = null;
		//if bookings reset ticket ids and duplicate tickets
		foreach($EM_Event->get_bookings()->get_tickets()->tickets as $EM_Ticket){
			$EM_Ticket->ticket_id = null;
			$EM_Ticket->event_id = null;
		}
		do_action('em_event_duplicate_pre', $EM_Event, $this);
		$EM_Event->duplicated = true;
		$EM_Event->force_status = 'draft';
		if( !$EM_Event->save() ) return;
		
		$EM_Event->feedback_message = sprintf(__("%s successfully duplicated.", 'events-manager'), __('Event','events-manager'));
		//save tags here - eventually will be moved into part of $this->save();
		
		$EM_Tags = new EM_Tags($this);
		$EM_Tags->event_id = $EM_Event->event_id;
		$EM_Tags->post_id = $EM_Event->post_id;
		$EM_Tags->save();
	
		//other non-EM post meta inc. featured image
		$event_meta = $this->get_event_meta($this->blog_id);
		$new_event_meta = $EM_Event->get_event_meta($EM_Event->blog_id);
		$event_meta_inserts = array();
		//Get custom fields and post meta - adapted from $this->load_post_meta()
		foreach($event_meta as $event_meta_key => $event_meta_vals){
			if( $event_meta_key == '_wpas_' ) continue; //allow JetPack Publicize to detect this as a new post when published
			if( is_array($event_meta_vals) ){
				if( !array_key_exists($event_meta_key, $new_event_meta) &&  !in_array($event_meta_key, array('_event_attributes', '_edit_last', '_edit_lock')) ){
					foreach($event_meta_vals as $event_meta_val){
						$event_meta_inserts[] = "({$EM_Event->post_id}, '{$event_meta_key}', '{$event_meta_val}')";
					}
				}
			}
		}
		//save in one SQL statement
		if( !empty($event_meta_inserts) ){
			$wpdb->query('INSERT INTO '.$wpdb->postmeta." (post_id, meta_key, meta_value) VALUES ".implode(', ', $event_meta_inserts));
		}
		if( array_key_exists('_event_approvals_count', $event_meta) ) update_post_meta($EM_Event->post_id, '_event_approvals_count', 0);
		//copy anything from the em_meta table too
		$wpdb->query('INSERT INTO '.EM_META_TABLE." (object_id, meta_key, meta_value) SELECT '{$EM_Event->event_id}', meta_key, meta_value FROM ".EM_META_TABLE." WHERE object_id='{$this->event_id}'");
		//set event to draft status
		return apply_filters('em_event_duplicate', $EM_Event, $this);

		//TODO add error notifications for duplication failures.
		
	}
	
	function duplicate_url($raw = false){
	    $url = add_query_arg(array('action'=>'event_duplicate', 'event_id'=>$this->event_id, '_wpnonce'=> wp_create_nonce('event_duplicate_'.$this->event_id)));
	    $url = apply_filters('em_event_duplicate_url', $url, $this);
	    $url = $raw ? esc_url_raw($url):esc_url($url);
	    return $url;
	}
	
	/**
	 * Delete whole event, including bookings, tickets, etc.
	 * @param boolean $force_delete
	 * @return boolean
	 */
	function delete( $force_delete = false ){
		if( $this->can_manage('delete_events', 'delete_others_events') ){
		    if( !is_admin() ){
				include_once('EventPostAdmin.php');
				if( !defined('EM_EVENT_DELETE_INCLUDE') ){
					EM_Event_Post_Admin::init();
					EM_Event_Recurring_Post_Admin::init();
					define('EM_EVENT_DELETE_INCLUDE',true);
				}
		    }
		    do_action('em_event_delete_pre', $this);
			if( $force_delete ){
				$result = wp_delete_post($this->post_id,$force_delete);
			}else{
				$result = wp_trash_post($this->post_id);
				if( !$result && $this->post_status == 'trash' ){
				    //we're probably dealing with a trashed post already, but the event_status is null from < v5.4.1
				    $this->set_status(-1);
				    $result = true;
				}
			}
			if( !$result && !empty($this->orphaned_event) ){
			    //this is an orphaned event, so the wp delete posts would have never worked, so we just delete the row in our events table
				$result = $this->delete_meta();
			}
		}else{
			$result = false;
		}
		return apply_filters('em_event_delete', $result != false, $this);
	}
	
	function delete_meta(){
		global $wpdb;
		$result = false;
		if( $this->can_manage('delete_events', 'delete_others_events') ){
			do_action('em_event_delete_meta_event_pre', $this);
			$result = $wpdb->query ( $wpdb->prepare("DELETE FROM ". EM_EVENTS_TABLE ." WHERE event_id=%d", $this->event_id) );
			if( $result !== false ){
				$this->get_bookings()->delete();
				$this->get_tickets()->delete();
				if( $this->has_event_location() ) {
					$this->get_event_location()->delete();
				}
				//Delete the recurrences then this recurrence event
				if( $this->is_recurring() ){
					$result = $this->delete_events(); //was true at this point, so false if fails
				}
			}
		}
		return apply_filters('em_event_delete_meta', $result !== false, $this);
	}
	
	
	/**
	 * Change the status of the event. This will save to the Database too. 
	 * @param int $status 				A number to change the status to, which may be -1 for trash, 1 for publish, 0 for pending or null if draft.
	 * @param boolean $set_post_status 	If set to true the wp_posts table status will also be changed to the new corresponding status.
	 * @return string
	 */
	function set_status($status, $set_post_status = false){
		global $wpdb;
		//decide on what status to set and update wp_posts in the process
		if($status === null){ 
			$set_status='NULL'; //draft post
			if($set_post_status){
				//if the post is trash, don't untrash it!
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $this->post_id ) );
			}
			$this->post_status = 'draft'; //set post status in this instance
		}elseif( $status == -1 ){ //trashed post
			$set_status = -1;
			if($set_post_status){
				//set the post status of the location in wp_posts too
				$wpdb->update( $wpdb->posts, array( 'post_status' => $this->post_status ), array( 'ID' => $this->post_id ) );
			}
			$this->post_status = 'trash'; //set post status in this instance
		}else{
			$set_status = $status ? 1:0; //published or pending post
			$post_status = $set_status ? 'publish':'pending';
			if( empty($this->post_name) ){
				//published or pending posts should have a valid post slug
				$slug = sanitize_title($this->post_title);
				$this->post_name = wp_unique_post_slug( $slug, $this->post_id, $post_status, EM_POST_TYPE_EVENT, 0);
				$set_post_name = true;
			}
			if($set_post_status){
				$wpdb->update( $wpdb->posts, array( 'post_status' => $post_status, 'post_name' => $this->post_name ), array( 'ID' => $this->post_id ) );
			}elseif( !empty($set_post_name) ){
				//if we've added a post slug then update wp_posts anyway
				$wpdb->update( $wpdb->posts, array( 'post_name' => $this->post_name ), array( 'ID' => $this->post_id ) );
			}
			$this->post_status = $post_status;
		}
		//save in the wp_em_locations table
		$this->get_previous_status();
		$result = $wpdb->query( $wpdb->prepare("UPDATE ".EM_EVENTS_TABLE." SET event_status=$set_status, event_slug=%s WHERE event_id=%d", array($this->post_name, $this->event_id)) );
		$this->get_status(); //reload status
		return apply_filters('em_event_set_status', $result !== false, $status, $this);
	}
	
	public function set_timezone( $timezone = false ){
		//reset UTC times and objects so they're recreated with local time and new timezone
		$this->event_start = $this->event_end = $this->rsvp_end = null;
		$EM_DateTimeZone = EM_DateTimeZone::create($timezone);
		//modify the timezone string name itself
		$this->event_timezone = $EM_DateTimeZone->getName();
	}
	
	public function get_timezone(){
		return $this->start()->getTimezone();
	}
	
	function is_published(){
		return apply_filters('em_event_is_published', ($this->post_status == 'publish' || $this->post_status == 'private'), $this);
	}
	
	/**
	 * Returns an EM_DateTime object of the event start date/time in local timezone of event.
	 * @param bool $utc_timezone Returns EM_DateTime with UTC timezone if set to true, returns local timezone by default.
	 * @return EM_DateTime
	 * @see EM_Event::get_datetime()
	 */
	public function start( $utc_timezone = false ){
		return apply_filters('em_event_start', $this->get_datetime('start', $utc_timezone), $this);
	}
	
	/**
	 * Returns an EM_DateTime object of the event end date/time in local timezone of event
	 * @param bool $utc_timezone Returns EM_DateTime with UTC timezone if set to true, returns local timezone by default.
	 * @return EM_DateTime
	 * @see EM_Event::get_datetime()
	 */
	public function end( $utc_timezone = false ){
		return apply_filters('em_event_end', $this->get_datetime('end', $utc_timezone), $this);
	}
	
	/**
	 * Returns an EM_DateTime representation of when bookings close in local event timezone. If no valid date defined, event start date/time will be used.
	 * @param bool $utc_timezone Returns EM_DateTime with UTC timezone if set to true, returns local timezone by default.
	 * @return EM_DateTime
	 */
	public function rsvp_end( $utc_timezone = false ){
		if( empty($this->rsvp_end) || !$this->rsvp_end->valid ){
			if( !empty($this->event_rsvp_date ) ){
				$rsvp_time = !empty($this->event_rsvp_time) ? $this->event_rsvp_time : $this->event_start_time;
			    $this->rsvp_end = new EM_DateTime($this->event_rsvp_date." ".$rsvp_time, $this->event_timezone);
			    if( !$this->rsvp_end->valid ){
			    	//invalid date will revert to start time
			    	$this->rsvp_end = $this->start()->copy();
			    }
			}else{
				//no date defined means event start date/time is used
		    	$this->rsvp_end = $this->start()->copy();
		    }
		}
		//Set to UTC timezone if requested, local by default
		$tz = $utc_timezone ? 'UTC' : $this->event_timezone;
		$this->rsvp_end->setTimezone($tz);
		return $this->rsvp_end;
	}
	
	/**
	 * Generates an EM_DateTime for the the start/end date/times of the event in local timezone, as well as setting a valid flag if dates and times are valid.
	 * The generated object will be derived from the local date and time values. If no date exists, then 1970-01-01 will be used, and 00:00:00 if no valid time exists. 
	 * If date is invalid but time is, only use local timezones since a UTC conversion will provide inaccurate timezone differences due to unknown DST status.	 * 
	 * @param string $when 'start' or 'end' date/time
	 * @param bool $utc_timezone Returns EM_DateTime with UTC timezone if set to true, returns local timezone by default. Do not use if EM_DateTime->valid is false. 
	 * @return EM_DateTime
	 */
	public function get_datetime( $when = 'start', $utc_timezone = false ){

		if( $when != 'start' && $when != 'end') return new EM_DateTime(); //currently only start/end dates are relevant
		//Initialize EM_DateTime if not already initialized, or if previously initialized object is invalid (e.g. draft event with invalid dates being resubmitted)
		$when_date = 'event_'.$when.'_date';
		$when_time = 'event_'.$when.'_time';

		if(!$this->$when_date || !$this->$when_time) {
			$time = $this->$when_time ?: '00:00:00';
			return new EM_DateTime('2023-01-01 '.$time, $this->event_timezone);
		}		
			/* @var EM_DateTime $EM_DateTime */
		$EM_DateTime = new EM_DateTime($this->$when_date . " " . $this->$when_time);
		
		//Set to UTC timezone if requested, local by default
		$tz = $utc_timezone ? 'UTC' : $this->event_timezone;
		$EM_DateTime->setTimezone($tz);
		return $EM_DateTime;
		
	}
	
	function get_status($db = false){
		switch( $this->post_status ){
			case 'private':
				$this->event_private = 1;
				$this->event_status = $status = 1;
				break;
			case 'publish':
				$this->event_private = 0;
				$this->event_status = $status = 1;
				break;
			case 'pending':
				$this->event_private = 0;
				$this->event_status = $status = 0;
				break;
			case 'trash':
				$this->event_private = 0;
				$this->event_status = $status = -1;
				break;
			default: //draft or unknown
				$this->event_private = 0;
				$status = $db ? 'NULL':null;
				$this->event_status = null;
				break;
		}
		return $status;
	}
	
	function get_previous_status( $force = false ){
		global $wpdb;
		if( $this->event_id > 0 && ($this->previous_status === false || $force) ){
			$this->previous_status = $wpdb->get_var('SELECT event_status FROM '.EM_EVENTS_TABLE.' WHERE event_id='.$this->event_id); //get status from db, not post_status, as posts get saved quickly
		}
		return $this->previous_status;
	}
	
	/**
	 * Returns an EM_Categories object of the EM_Event instance.
	 * @return EM_Categories
	 */
	function get_categories() {
		if( empty($this->categories) ){
			$this->categories = new EM_Categories($this);
		}elseif(empty($this->categories->event_id)){
			$this->categories->event_id = $this->event_id;
			$this->categories->post_id = $this->post_id;			
		}
		return apply_filters('em_event_get_categories', $this->categories, $this);
	}
	
	
	/**
	 * Returns the physical location object this event belongs to.
	 * @return EM_Location
	 */
	function get_location() {
		global $EM_Location;
		if( is_object($EM_Location) && $EM_Location->location_id == $this->location_id ){
			$this->location = $EM_Location;
		}else{
			if( !is_object($this->location) || $this->location->location_id != $this->location_id ){
				$this->location = apply_filters('em_event_get_location', EM_Location::get($this->location_id), $this);
			}
		}
		return $this->location;
	}
	
	/**
	 * Returns whether this event has a phyisical location assigned to it.
	 * @return bool
	 */
	public function has_location(){
		return !empty($this->location_id);
	}
	
	/**
	 * Gets the event's event location (note, different from a regular event location, which uses get_location())
	 * Returns implementation of Event_Location or false if no event location assigned.
	 * @return EM_Event_Locations\URL|Event_Location|false
	 */
	public function get_event_location(){
		if( is_object($this->event_location) ) return $this->event_location;
		$Event_Location = false;
		if( $this->has_event_location() ){
			$this->event_location = $Event_Location = Event_Locations::get( $this->event_location_type, $this );
		}
		return apply_filters('em_event_get_event_location', $Event_Location, $this);
	}

	public function can_book(){
		$can_book = true;

		$now = new DateTime();

		if( $this->event_rsvp && $this->rsvp_end() && $this->rsvp_end()->getTimestamp() < $now->getTimestamp() ){
			$can_book = false;
		}
		if( $this->get_spaces() <= 0 ){
			
			$can_book = false;
		}
		
		return apply_filters('em_event_can_book', $can_book, $this);
	}
	
	/**
	 * Returns whether the event has an event location associated with it (different from a physical location). If supplied, can check against a specific type.
	 * @param string $event_location_type
	 * @return bool
	 */
	public function has_event_location( $event_location_type = null ){
		if( $event_location_type !== null ){
			return !empty($this->event_location_type) && $this->event_location_type === $event_location_type && Event_Locations::is_enabled($event_location_type);
		}
		return !empty($this->event_location_type) && Event_Locations::is_enabled($this->event_location_type);
	}
	
	/**
	 * Returns the location object this event belongs to.
	 * @return EM_Person
	 */	
	function get_contact(){
		if( !is_object($this->contact) ){
			$this->contact = new EM_Person($this->event_owner);
			//if this is anonymous submission, change contact email and name
		}
		return $this->contact;
	}
	
	/**
	 * Retrieve and save the bookings belonging to instance. If called again will return cached version, set $force_reload to true to create a new EM_Bookings object.
	 * @param boolean $force_reload
	 * @return EM_Bookings
	 */
	function get_bookings( $force_reload = false ){
		if( get_option('dbem_rsvp_enabled') ){
			if( (!$this->bookings || $force_reload) ){
				$this->bookings = new EM_Bookings($this);
			}
			$this->bookings->event_id = $this->event_id; //always refresh event_id
			$this->bookings = apply_filters('em_event_get_bookings', $this->bookings, $this);
		}else{
			return new EM_Bookings();
		}
		//TODO for some reason this returned instance doesn't modify the original, e.g. try $this->get_bookings()->add($EM_Booking) and see how $this->bookings->feedback_message doesn't change
		return $this->bookings;
	}
	
	/**
	 * Get the tickets related to this event.
	 * @param boolean $force_reload
	 * @return EM_Tickets
	 */
	function get_tickets( $force_reload = false ){
		return $this->get_bookings($force_reload)->get_tickets();
	}

	/* Provides the tax rate for this event.
	 * @see EM_Object::get_tax_rate()
	 */
	function get_tax_rate( $decimal = false ){
		$tax_rate = apply_filters('em_event_get_tax_rate', parent::get_tax_rate( false ), $this); //we get tax rate but without decimal
		$tax_rate = ( $tax_rate > 0 ) ? $tax_rate : 0;
		if( $decimal && $tax_rate > 0 ) $tax_rate = $tax_rate / 100;
		return $tax_rate;
	}
	
	/**
	 * Deprecated - use $this->get_bookings()->get_spaces() instead.
	 * Gets number of spaces in this event, dependent on ticket spaces or hard limit, whichever is smaller.
	 * @param boolean $force_refresh
	 * @return int 
	 */
	function get_spaces($force_refresh=false){
		return $this->get_bookings()->get_spaces($force_refresh);
	}
	
	/* 
	 * Extends the default EM_Object function by switching blogs as needed if in MS Global mode  
	 * @param string $size
	 * @return string
	 * @see EM_Object::get_image_url()
	 */
	function get_image_url($size = 'full'){
		$return = parent::get_image_url($size);
		if( !empty($switch_back) ){ restore_current_blog(); }
		return $return;
	}
	
	function get_edit_reschedule_url(){
		if( $this->is_recurrence() ){
			$EM_Event = EM_Event::find($this->recurrence_id);
			return $EM_Event->get_edit_url();
		}
	}
	
	function get_edit_url(){
		if( $this->can_manage('edit_events','edit_others_events') ){
			
			if( empty($link))
				$link = admin_url()."post.php?post={$this->post_id}&action=edit";
			
			return apply_filters('em_event_get_edit_url', $link, $this);
		}
	}
	
	function get_bookings_url(){
		return is_admin() ? EM_ADMIN_URL. "&page=events-manager-bookings&event_id=".$this->event_id : '';
	}
	
	function get_permalink(){
		if( empty($event_link) ){
			$event_link = get_post_permalink($this->post_id);
		}
		return apply_filters('em_event_get_permalink', $event_link, $this);
	}
	
	function get_ical_url(){
		global $wp_rewrite;
		if( !empty($wp_rewrite) && $wp_rewrite->using_permalinks() ){
			$return = trailingslashit($this->get_permalink()).'ical/';
		}else{
			$return = em_add_get_params($this->get_permalink(), array('ical'=>1));
		}
		return apply_filters('em_event_get_ical_url', $return);
	}
	
	function is_free( $now = false ){
		return $this->get_price() == 0;
	}

	function get_price(){
		$price = 0;
		
		foreach($this->get_tickets() as $EM_Ticket){
		    /* @var $EM_Ticket EM_Ticket */
			if( $EM_Ticket->get_price() > 0 ){	
				
				$price = $EM_Ticket->get_price();
			}
		}

		
		return apply_filters('em_event_get_price',$price, $this);
	}

	function get_formatted_price(){
		$price = $this->get_price();
		return new Contexis\Events\Intl\Price($price);
	}
	
	/**
	 * Will output a event in the format passed in $format by replacing placeholders within the format.
	 * @param string $format
	 * @param string $target
	 * @return string
	 */	
	function output($format, $target="html") {	
		//$format = do_shortcode($format); //parse shortcode first, so that formats within shortcodes are parsed properly, however uncommenting this will break shortcode containing placeholders for arguments
	 	$event_string = $format;
		//Time place holder that doesn't show if empty.
		preg_match_all('/#@?__?\{[^}]+\}/', $format, $results);
		foreach($results[0] as $result) {
			if(substr($result, 0, 3 ) == "#@_"){
				$date = 'end';
				if( substr($result, 0, 4 ) == "#@__" ){
					$offset = 5;
					$show_site_timezone = true;
				}else{
					$offset = 4;
				}
			}else{
				$date = 'start';
				if( substr($result, 0, 3) == "#__" ){
					$offset = 4;
					$show_site_timezone = true;
				}else{
					$offset = 3;
				}
			}
			if( $date == 'end' && $this->event_start_date == $this->event_end_date ){
				$replace = apply_filters('em_event_output_placeholder', '', $this, $result, $target, array($result));
			}else{
				$date_format = substr( $result, $offset, (strlen($result)-($offset+1)) );
				if( !empty($show_site_timezone) ){
					$date_formatted = $this->$date()->copy()->setTimezone()->i18n($date_format);
				}else{
					$date_formatted = $this->$date()->i18n($date_format);
				}
				$replace = apply_filters('em_event_output_placeholder', $date_formatted, $this, $result, $target, array($result));
			}
			$event_string = str_replace($result,$replace,$event_string );
		}
		//This is for the custom attributes
		preg_match_all('/#_ATT\{([^}]+)\}(\{([^}]+\}?)\})?/', $event_string, $results);
		$attributes =  array('names'=>array(), 'values'=>array());
		foreach($results[0] as $resultKey => $result) {
			//check that we haven't mistakenly captured a closing bracket in second bracket set
			if( !empty($results[3][$resultKey]) && $results[3][$resultKey][0] == '/' ){
				$result = $results[0][$resultKey] = str_replace($results[2][$resultKey], '', $result);
				$results[3][$resultKey] = $results[2][$resultKey] = '';
			}
			//Strip string of placeholder and just leave the reference
			$attRef = substr( substr($result, 0, strpos($result, '}')), 6 );
			$attString = '';
			$placeholder_atts = array('#_ATT', $results[1][$resultKey]);
			if( is_array($this->event_attributes) && array_key_exists($attRef, $this->event_attributes) ){
				$attString = $this->event_attributes[$attRef];
			}elseif( !empty($results[3][$resultKey]) ){
				//Check to see if we have a second set of braces;
				$placeholder_atts[] = $results[3][$resultKey];
				$attStringArray = explode('|', $results[3][$resultKey]);
				$attString = $attStringArray[0];
			}elseif( !empty($attributes['values'][$attRef][0]) ){
			    $attString = $attributes['values'][$attRef][0];
			}
			$attString = apply_filters('em_event_output_placeholder', $attString, $this, $result, $target, $placeholder_atts);
			$event_string = str_replace($result, $attString ,$event_string );
		}
	 	
		preg_match_all('/\{([a-zA-Z0-9_\-,]+)\}(.+?)\{\/\1\}/s', $event_string, $conditionals);
		if( count($conditionals[0]) > 0 ){
			//Check if the language we want exists, if not we take the first language there
			foreach($conditionals[1] as $key => $condition){

				$show_condition = match($condition) {
					'has_bookings' => $this->event_rsvp && get_option('dbem_rsvp_enabled'),
					'no_bookings' => (!$this->event_rsvp && get_option('dbem_rsvp_enabled')),
					'no_location' => !$this->has_event_location() && !$this->has_location(),
					'has_location' => ( $this->has_location() && $this->get_location()->location_status ) || $this->has_event_location(),
					'has_event_location' => $this->has_event_location(),
					'has_image' => $this->get_image_url() != '',
					'has_time' => ( $this->event_start_time != $this->event_end_time && !$this->event_all_day ),
					'all_day' => ($condition == 'all_day'),
					'has_spaces' => $this->event_rsvp && $this->get_bookings()->get_available_spaces() > 0,
					'fully_booked' => $this->event_rsvp && $this->get_bookings()->get_available_spaces() <= 0,
					'is_free' => !$this->event_rsvp || $this->is_free( $condition == 'is_free_now' ),
					'is_recurrence' => $this->is_recurrence(),
					'is_private' => $this->event_private == 1,
					default => false
				};

				$show_condition = apply_filters('em_event_output_show_condition', $show_condition, $condition, $conditionals[0][$key], $this);

				$replacement = '';
				
				if($show_condition){
					$placeholder_length = strlen($condition)+2;
					$replacement = substr($conditionals[0][$key], $placeholder_length, strlen($conditionals[0][$key])-($placeholder_length *2 +1));
				}

				$event_string = str_replace($conditionals[0][$key], apply_filters('em_event_output_condition', $replacement, $condition, $conditionals[0][$key], $this), $event_string);
			}
		}
	 	
		//Now let's check out the placeholders.
	 	preg_match_all("/(#@?_?[A-Za-z0-9_]+)({([^}]+)})?/", $event_string, $placeholders);
	 	$replaces = array();
		foreach($placeholders[1] as $key => $result) {
			$match = true;
			$replace = '';
			$full_result = $placeholders[0][$key];
			$placeholder_atts = array($result);
			if( !empty($placeholders[3][$key]) ) $placeholder_atts[] = $placeholders[3][$key];
			switch( $result ){
				//Event Details
				case '#_EVENTID':
					$replace = $this->event_id;
					break;
				case '#_EVENTPOSTID':
					$replace = $this->post_id;
					break;
				case '#_EVENTNAME':
					$replace = $this->event_name;
					break;
				case '#_EVENTNOTES':
					$replace = $this->post_content;
					break;				
				case '#_EVENTIMAGEURL':
					$replace =  esc_url($this->image_url);
					break;
				case '#_EVENTIMAGE':
	        		if($this->get_image_url() == '') break;

					$replace = "<img src='".esc_url($this->image_url)."' alt='".esc_attr($this->event_name)."'/>";

					if( empty($placeholders[3][$key])) break;

					$image_size = explode(',', $placeholders[3][$key]);
					
					if( is_array($image_size) && !(array_is_list($image_size) && count($image_size) > 1) ) break;

					
					$replace = get_the_post_thumbnail($this->ID, $image_size, array('alt' => esc_attr($this->event_name)) );
					
					$image_attr = '';
					$image_args = array();
					if( empty($image_size[1]) && !empty($image_size[0]) ){    
						$image_attr = 'width="'.$image_size[0].'"';
						$image_args['w'] = $image_size[0];
					}elseif( empty($image_size[0]) && !empty($image_size[1]) ){
						$image_attr = 'height="'.$image_size[1].'"';
						$image_args['h'] = $image_size[1];
					}elseif( !empty($image_size[0]) && !empty($image_size[1]) ){
						$image_attr = 'width="'.$image_size[0].'" height="'.$image_size[1].'"';
						$image_args = array('w'=>$image_size[0], 'h'=>$image_size[1]);
					}
					$replace = "<img src='".esc_url(em_add_get_params($this->image_url, $image_args))."' alt='".esc_attr($this->event_name)."' $image_attr />";
				
					break;
				//Times & Dates
				case '#_STARTTIME':
				case '#_ENDTIME':
					$replace = ($result == '#_24HSTARTTIME') ? $this->start()->format('H:i'):$this->end()->format('H:i');
					break;
				case '#_EVENTTIMES':
					//get format of time to show
					$replace = $this->output_times();
					break;
				case '#_EVENTPRICE':
					$replace = $this->get_formatted_price();
					break;
				case '#_EVENTDATES':
					//get format of time to show
					$replace = $this->output_dates();
					break;
				case '#_RECURRINGDATERANGE': //Outputs the #_EVENTDATES equivalent of the recurring event template pattern.
					$replace = $this->get_event_recurrence()->output_dates(); //if not a recurrence, we're running output_dates on $this
					break;
				case '#_RECURRINGPATTERN':
					$replace = '';
					if( $this->is_recurrence() || $this->is_recurring() ){
						$replace = $this->get_event_recurrence()->get_recurrence_description();
					}
					break;
				case '#_RECURRINGID':
					$replace = $this->recurrence_id;
					break;
				//Links
				case '#_EVENTPAGEURL': //deprecated	
				case '#_LINKEDNAME': //deprecated
				case '#_EVENTURL': //Just the URL
				case '#_EVENTLINK': //HTML Link
					$event_link = esc_url($this->get_permalink());
					if($result == '#_LINKEDNAME' || $result == '#_EVENTLINK'){
						$replace = '<a href="'.$event_link.'">'.esc_attr($this->event_name).'</a>';
					}else{
						$replace = $event_link;	
					}
					break;
				case '#_EDITEVENTURL':
				case '#_EDITEVENTLINK':
					if( $this->can_manage('edit_events','edit_others_events') ){
						$link = esc_url($this->get_edit_url());
						if( $result == '#_EDITEVENTLINK'){
							$replace = '<a href="'.$link.'">'.esc_html(sprintf(__('Edit Event','events-manager'))).'</a>';
						}else{
							$replace = $link;
						}
					}	 
					break;
				case '#_AVAILABLESPACES':
					$replace = $this->event_rsvp && get_option('dbem_rsvp_enabled') ? $this->get_bookings()->get_available_spaces() : "0";
					break;
				case '#_BOOKEDSPACES':
					//This placeholder is actually a little misleading, as it'll consider reserved (i.e. pending) bookings as 'booked'
					if ($this->event_rsvp && get_option('dbem_rsvp_enabled')) {
						$replace = $this->get_bookings()->get_booked_spaces();
						if( get_option('dbem_bookings_approval_reserved') ){
							$replace += $this->get_bookings()->get_pending_spaces();
						}
					} else {
						$replace = "0";
					}
					break;
				case '#_PENDINGSPACES':
					$replace = $this->event_rsvp && get_option('dbem_rsvp_enabled') ? $this->get_bookings()->get_pending_spaces() : "0";
					break;
				case '#_SPACES':
					$replace = $this->get_spaces();
					break;
				case '#_BOOKINGSURL':
				case '#_BOOKINGSLINK':
					if( $this->can_manage('manage_bookings','manage_others_bookings') ){
						$bookings_link = esc_url($this->get_bookings_url());
						if($result == '#_BOOKINGSLINK'){
							$replace = '<a href="'.$bookings_link.'" title="'.esc_attr($this->event_name).'">'.esc_html($this->event_name).'</a>';
						}else{
							$replace = $bookings_link;	
						}
					}
					break;
				case '#_BOOKINGSCUTOFF':
				case '#_BOOKINGSCUTOFFDATE':
				
				//Contact Person
				case '#_CONTACTNAME':
				case '#_CONTACTPERSON': //deprecated (your call, I think name is better)
					$replace = $this->get_contact()->display_name;
					break;
				case '#_CONTACTUSERNAME':
					$replace = $this->get_contact()->user_login;
					break;
				case '#_CONTACTEMAIL':
				case '#_CONTACTMAIL': //deprecated
					$replace = $this->get_contact()->user_email;
					break;
				case '#_CONTACTURL':
					$replace = $this->get_contact()->user_url;
					break;
				case '#_CONTACTID':
					$replace = $this->get_contact()->ID;
					break;
				case '#_CONTACTPHONE':
		      		$replace = ( $this->get_contact()->phone != '') ? $this->get_contact()->phone : __('N/A', 'events-manager');
					break;
				case '#_CONTACTMETA':
					if( !empty($placeholders[3][$key]) ){
						$replace = get_user_meta($this->event_owner, $placeholders[3][$key], true);
					}
					break;
				case '#_ATTENDEES':
					ob_start();
					$template = em_locate_template('placeholders/attendees.php', true, array('EM_Event'=>$this));
					
					$replace = ob_get_clean();
					break;
				case '#_ATTENDEESLIST':
					ob_start();
					$template = em_locate_template('placeholders/attendeeslist.php', true, array('EM_Event'=>$this));
					$replace = ob_get_clean();
					break;
				case '#_ATTENDEESPENDINGLIST':
					ob_start();
					$template = em_locate_template('placeholders/attendeespendinglist.php', true, array('EM_Event'=>$this));
					$replace = ob_get_clean();
					break;
				case '#_EVENTTAGS':
				    $replace = '';
                    
					ob_start();
					$template = em_locate_template('placeholders/eventtags.php', true, array('EM_Event'=>$this));
					$replace = ob_get_clean();
				
					break;
				case '#_EVENTCATEGORIES':
				    $replace = '';
				    ob_start();
    				$template = em_locate_template('placeholders/categories.php', true, array('EM_Event'=>$this));
    				$replace = ob_get_clean();
					break;
				//Ical Stuff
				case '#_EVENTICALURL':
				case '#_EVENTICALLINK':
					$replace = $this->get_ical_url();
					if( $result == '#_EVENTICALLINK' ){
						$replace = '<a href="'.esc_url($replace).'">iCal</a>';
					}
					break;
				case '#_EVENTWEBCALURL':
				case '#_EVENTWEBCALLINK':
					$replace = $this->get_ical_url();
					$replace = str_replace(array('http://','https://'), 'webcal://', $replace);
					if( $result == '#_EVENTWEBCALLINK' ){
						$replace = '<a href="'.esc_url($replace).'">webcal</a>';
					}
					break;
				//Event location (not physical location)
				case '#_EVENTLOCATION':
					if( $this->has_event_location() ) {
						if (!empty($placeholders[3][$key])) {
							$replace = $this->get_event_location()->output( $placeholders[3][$key], $target );
						} else {
							$replace = $this->get_event_location()->output( null, $target );
						}
					}
					break;
				default:
					$replace = $full_result;
					break;
			}
			$replaces[$full_result] = apply_filters('em_event_output_placeholder', $replace, $this, $full_result, $target, $placeholder_atts);
		}
		//sort out replacements so that during replacements shorter placeholders don't overwrite longer varieties.
		krsort($replaces);
		foreach($replaces as $full_result => $replacement){
			if( !in_array($full_result, array('#_NOTES','#_EVENTNOTES')) ){
				$event_string = str_replace($full_result, $replacement , $event_string );
			}else{
			    $new_placeholder = str_replace('#_', '__#', $full_result); //this will avoid repeated filters when locations/categories are parsed
			    $event_string = str_replace($full_result, $new_placeholder , $event_string );
				$desc_replace[$new_placeholder] = $replacement;
			}
		}
		//Time placeholders
		foreach($placeholders[1] as $result) {
			// matches all PHP START date and time placeholders
			if (preg_match('/^#[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]$/', $result)) {
				$replace = $this->start()->i18n(ltrim($result, "#"));
				$replace = apply_filters('em_event_output_placeholder', $replace, $this, $result, $target, array($result));
				$event_string = str_replace($result, $replace, $event_string );
			}
			// matches all PHP END time placeholders for endtime
			if (preg_match('/^#@[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]$/', $result)) {
				$replace = $this->end()->i18n(ltrim($result, "#@"));
				$replace = apply_filters('em_event_output_placeholder', $replace, $this, $result, $target, array($result));
				$event_string = str_replace($result, $replace, $event_string ); 
		 	}
		}
		//Now do dependent objects
		if( get_option('dbem_locations_enabled') ){
			if( !empty($this->location_id) && $this->get_location()->location_status ){
				$event_string = $this->get_location()->output($event_string, $target);
			}else{
				$EM_Location = new EM_Location();
				$event_string = $EM_Location->output($event_string, $target);
			}
		}
		
	
		//for backwards compat and easy use, take over the individual category placeholders with the frirst cat in th elist.
		if( count($this->get_categories()) > 0 ){
			$EM_Category = $this->get_categories()->get_first();
		}
		if( empty($EM_Category) ) $EM_Category = new EM_Category();
		$event_string = $EM_Category->output($event_string, $target);
		
		
		
		$EM_Tags = new EM_Tags($this);
		if( count($EM_Tags) > 0 ){
			$EM_Tag = $EM_Tags->get_first();
		}
		if( empty($EM_Tag) ) $EM_Tag = new EM_Tag();
		$event_string = $EM_Tag->output($event_string, $target);
	
		
		//Finally, do the event notes, so that previous placeholders don't get replaced within the content, which may use shortcodes
		if( !empty($desc_replace) ){
			foreach($desc_replace as $full_result => $replacement){
				$event_string = str_replace($full_result, $replacement , $event_string );
			}
		}
		
		//do some specific formatting
		//TODO apply this sort of formatting to any output() function
		if( $target == 'ical' ){
		    //strip html and escape characters
		    $event_string = str_replace('\\','\\\\',strip_tags($event_string));
		    $event_string = str_replace(';','\;',$event_string);
		    $event_string = str_replace(',','\,',$event_string);
		    //remove and define line breaks in ical format
		    $event_string = str_replace('\\\\n','\n',$event_string);
		    $event_string = str_replace("\r\n",'\n',$event_string);
		    $event_string = str_replace("\n",'\n',$event_string);
		}
		return apply_filters('em_event_output', $event_string, $this, $format, $target);
	}
	
	function output_times() {
		return \Contexis\Events\Intl\Date::get_time($this->start()->getTimestamp(), $this->end()->getTimestamp());
	}
	
	function output_dates() {
		return \Contexis\Events\Intl\Date::get_date($this->start()->getTimestamp(), $this->end()->getTimestamp());
	}
	
	/**********************************************************
	 * RECURRENCE METHODS
	 ***********************************************************/
	
	/**
	 * Returns true if this is a recurring event.
	 * @return boolean
	 */
	function is_recurring(){
		return $this->post_type == 'event-recurring' && get_option('dbem_recurrence_enabled');
	}	
	/**
	 * Will return true if this individual event is part of a set of events that recur
	 * @return boolean
	 */
	function is_recurrence(){
		return ( $this->recurrence_id > 0 && get_option('dbem_recurrence_enabled') );
	}
	/**
	 * Returns if this is an individual event and is not a recurrence
	 * @return boolean
	 */
	function is_individual(){
		return ( !$this->is_recurring() && !$this->is_recurrence() );
	}
	
	/**
	 * Gets the event recurrence template, which is an EM_Event object (based off an event-recurring post)
	 * @return EM_Event
	 */
	function get_event_recurrence(){
		if(!$this->is_recurring()){
			return EM_Event::find($this->recurrence_id, 'event_id');
		}else{
			return $this;
		}
	}
	
	function get_detach_url(){
		return admin_url().'admin.php?event_id='.$this->event_id.'&amp;action=event_detach&amp;_wpnonce='.wp_create_nonce('event_detach_'.get_current_user_id().'_'.$this->event_id);
	}
	
	function get_attach_url($recurrence_id){
		return admin_url().'admin.php?undo_id='.$recurrence_id.'&amp;event_id='.$this->event_id.'&amp;action=event_attach&amp;_wpnonce='.wp_create_nonce('event_attach_'.get_current_user_id().'_'.$this->event_id);
	}
	
	/**
	 * Returns if this is an individual event and is not recurring or a recurrence
	 * @return boolean
	 */
	function detach(){
		global $wpdb;
		if( $this->is_recurrence() && !$this->is_recurring() && $this->can_manage('edit_recurring_events','edit_others_recurring_events') ){
			//remove recurrence id from post meta and index table
			$url = $this->get_attach_url($this->recurrence_id);
			$wpdb->update(EM_EVENTS_TABLE, array('recurrence_id'=>null), array('event_id' => $this->event_id));
			delete_post_meta($this->post_id, '_recurrence_id');
			$this->feedback_message = __('Event detached.','events-manager') . ' <a href="'.$url.'">'.__('Undo','events-manager').'</a>';
			$this->recurrence_id = 0;
			return apply_filters('em_event_detach', true, $this);
		}
		$this->add_error(__('Event could not be detached.','events-manager'));
		return apply_filters('em_event_detach', false, $this);
	}
	
	/**
	 * Returns if this is an individual event and is not recurring or a recurrence
	 * @return boolean
	 */
	function attach($recurrence_id){
		global $wpdb;
		if( !$this->is_recurrence() && !$this->is_recurring() && is_numeric($recurrence_id) && $this->can_manage('edit_recurring_events','edit_others_recurring_events') ){
			//add recurrence id to post meta and index table
			$wpdb->update(EM_EVENTS_TABLE, array('recurrence_id'=>$recurrence_id), array('event_id' => $this->event_id));
			update_post_meta($this->post_id, '_recurrence_id', $recurrence_id);
			$this->feedback_message = __('Event re-attached to recurrence.','events-manager');
			return apply_filters('em_event_attach', true, $recurrence_id, $this);
		}
		$this->add_error(__('Event could not be attached.','events-manager'));
		return apply_filters('em_event_attach', false, $recurrence_id, $this);
	}

	
	
	/**
	 * Saves events and replaces old ones. Returns true if sucecssful or false if not.
	 * @return boolean
	 */
	function save_events() {
		global $wpdb;
		
		if( !$this->can_manage('edit_events','edit_others_events') ) return apply_filters('em_event_save_events', false, $this, array(), array());
		$event_ids = $post_ids = $event_dates = $events = array();
		if( $this->is_published() || 'future' == $this->post_status ){
			$result = false;
			//check if there's any events already created, if not (such as when an event is first submitted for approval and then published), force a reschedule.
			if( $wpdb->get_var('SELECT COUNT(event_id) FROM '.EM_EVENTS_TABLE.' WHERE recurrence_id='. absint($this->event_id)) == 0 ){
				$this->recurring_reschedule = true;
			}
			do_action('em_event_save_events_pre', $this); //actions/filters only run if event is recurring
			//Make template event index, post, and meta (we change event dates, timestamps, rsvp dates and other recurrence-relative info whilst saving each event recurrence)
			$event = $this->to_array(true); //event template - for index
			if( !empty($event['event_attributes']) ) $event['event_attributes'] = serialize($event['event_attributes']);
			$post_fields = $wpdb->get_row('SELECT * FROM '.$wpdb->posts.' WHERE ID='.$this->post_id, ARRAY_A); //post to copy
			$post_fields['post_type'] = 'event'; //make sure we'll save events, not recurrence templates
			$meta_fields_map = $wpdb->get_results('SELECT meta_key,meta_value FROM '.$wpdb->postmeta.' WHERE post_id='.$this->post_id, ARRAY_A);
			$meta_fields = array();
			//convert meta_fields into a cleaner array
			foreach($meta_fields_map as $meta_data){
				$meta_fields[$meta_data['meta_key']] = $meta_data['meta_value'];
			}
			if( isset($meta_fields['_edit_last']) ) unset($meta_fields['_edit_last']);
			if( isset($meta_fields['_edit_lock']) ) unset($meta_fields['_edit_lock']);
			//remove id and we have a event template to feed to wpdb insert
			unset($event['event_id'], $event['post_id']); 
			unset($post_fields['ID']);
			unset($meta_fields['_event_id']);
			if( isset($meta_fields['_post_id']) ) unset($meta_fields['_post_id']); //legacy bugfix, post_id was never needed in meta table
			//remove recurrence meta info we won't need in events
			foreach( $this->recurrence_fields as $recurrence_field){
				$event[$recurrence_field] = null;
				if( isset($meta_fields['_'.$recurrence_field]) ) unset($meta_fields['_'.$recurrence_field]);
			}
			//Set the recurrence ID
			$event['recurrence_id'] = $meta_fields['_recurrence_id'] = $this->event_id;
			$event['recurrence'] = 0;
			
			//Let's start saving!
			$event_saves = $meta_inserts = array();
			$recurring_date_format = apply_filters('em_event_save_events_format', 'Y-m-d');
			$post_name = $this->sanitize_recurrence_slug( $post_fields['post_name'], $this->start()->format($recurring_date_format)); //template sanitized post slug since we'll be using this
			//First thing - times. If we're changing event times, we need to delete all events and recreate them with the right times, no other way
			
			if( $this->recurring_reschedule ){
				
				$this->delete_events(); //Delete old events beforehand, this will change soon
				$matching_days = $this->get_recurrence_days(); //Get days where events recur
				
				$event['event_date_created'] = current_time('mysql'); //since the recurrences are recreated
				unset($event['event_date_modified']);
				if( count($matching_days) > 0 ){
					//first save event post data
					$EM_DateTime = $this->start()->copy();
					foreach( $matching_days as $day ) {
						//set start date/time to $EM_DateTime for relative use further on
						$EM_DateTime->setTimestamp($day)->setTimeString($event['event_start_time']);
						$start_timestamp = $EM_DateTime->getTimestamp(); //for quick access later
						//rewrite post fields if needed
						//set post slug, which may need to be sanitized for length as we pre/postfix a date for uniqueness
						$event_slug_date = $EM_DateTime->format( $recurring_date_format );
						$event_slug = $this->sanitize_recurrence_slug($post_name, $event_slug_date);
						$event_slug = apply_filters('em_event_save_events_recurrence_slug', $event_slug.'-'.$event_slug_date, $event_slug, $event_slug_date, $day, $this); //use this instead
						$post_fields['post_name'] = $event['event_slug'] = apply_filters('em_event_save_events_slug', $event_slug, $post_fields, $day, $matching_days, $this); //deprecated filter
						//set start date
						$event['event_start_date'] = $meta_fields['_event_start_date'] = $EM_DateTime->getDate();
						$event['event_start'] = $meta_fields['_event_start'] = $EM_DateTime->getDateTime(true);
						//add rsvp date/time restrictions
						if( !empty($this->recurrence_rsvp_days) && is_numeric($this->recurrence_rsvp_days) ){
							if( $this->recurrence_rsvp_days > 0 ){
								$event_rsvp_date = $EM_DateTime->copy()->add(new DateInterval('P'.absint($this->recurrence_rsvp_days).'D'))->getDate(); //cloned so original object isn't modified
							}elseif($this->recurrence_rsvp_days < 0 ){
								$event_rsvp_date = $EM_DateTime->copy()->sub(new DateInterval('P'.absint($this->recurrence_rsvp_days).'D'))->getDate(); //cloned so original object isn't modified
							}else{
								$event_rsvp_date = $EM_DateTime->getDate();
							}
				 			$event['event_rsvp_date'] = $meta_fields['_event_rsvp_date'] = $event_rsvp_date;
						}else{
							$event['event_rsvp_date'] = $meta_fields['_event_rsvp_date'] = $event['event_start_date'];
						}
						$event['event_rsvp_time'] = $meta_fields['_event_rsvp_time'] = $event['event_rsvp_time'];
						//set end date
						$EM_DateTime->setTimeString($event['event_end_time']);
						if($this->recurrence_days > 0){
							//$EM_DateTime modified here, and used further down for UTC end date
							$event['event_end_date'] = $meta_fields['_event_end_date'] = $EM_DateTime->add(new DateInterval('P'.$this->recurrence_days.'D'))->getDate();
						}else{
							$event['event_end_date'] = $meta_fields['_event_end_date'] = $event['event_start_date'];
						}
						$end_timestamp = $EM_DateTime->getTimestamp(); //for quick access later
						$event['event_end'] = $meta_fields['_event_end'] = $EM_DateTime->getDateTime(true);
						//add extra date/time post meta
						$meta_fields['_event_start_local'] = $event['event_start_date'].' '.$event['event_start_time'];
						$meta_fields['_event_end_local'] = $event['event_end_date'].' '.$event['event_end_time'];
						
						//create the event
						if( $wpdb->insert($wpdb->posts, $post_fields ) ){
							$event['post_id'] = $post_id = $post_ids[$start_timestamp] = $wpdb->insert_id; //post id saved into event and also as a var for later user
							// Set GUID and event slug as per wp_insert_post
							$wpdb->update( $wpdb->posts, array( 'guid' => get_permalink( $post_id ) ), array('ID'=>$post_id) );
					 		//insert into events index table
							$event_saves[] = $wpdb->insert(EM_EVENTS_TABLE, $event);
							$event_ids[$post_id] = $event_id = $wpdb->insert_id;
							$event_dates[$event_id] = $start_timestamp;
					 		//create the meta inserts for each event
					 		$meta_fields['_event_id'] = $event_id;
					 		foreach($meta_fields as $meta_key => $meta_val){
					 			$meta_inserts[] = $wpdb->prepare("(%d, %s, %s)", array($post_id, $meta_key, $meta_val));
					 		}
						}else{
							$event_saves[] = false;
						}
						
				 	}
				 	//insert the metas in one go, faster than one by one
				 	if( count($meta_inserts) > 0 ){
					 	$result = $wpdb->query("INSERT INTO ".$wpdb->postmeta." (post_id,meta_key,meta_value) VALUES ".implode(',',$meta_inserts));
					 	if($result === false){
					 		$this->add_error(esc_html__('There was a problem adding custom fields to your recurring events.','events-manager'));
					 	}
				 	}
				}else{
			 		$this->add_error(esc_html__('You have not defined a date range long enough to create a recurrence.','events-manager'));
			 		$result = false;
			 	}
			}else{
				//we go through all event main data and meta data, we delete and recreate all meta data
				//now unset some vars we don't need to deal with since we're just updating data in the wp_em_events and posts table
				unset( $event['event_date_created'], $event['recurrence_id'], $event['recurrence'], $event['event_start_date'], $event['event_end_date'], $event['event_parent'] );
				$event['event_date_modified'] = current_time('mysql'); //since the recurrences are modified but not recreated
				unset( $post_fields['comment_count'], $post_fields['guid'], $post_fields['menu_order']);
				unset( $meta_fields['_event_parent'] ); // we'll ignore this and add it manually
				// clean the meta fields array to contain only the fields we actually need to overwrite i.e. delete and recreate, to avoid deleting unecessary individula recurrence data
				$exclude_meta_update_keys = apply_filters('em_event_save_events_exclude_update_meta_keys', array('_parent_id'), $this);
				//now we go through the recurrences and check whether things relative to dates need to be changed
				$events = EM_Events::get( array('recurrence'=>$this->event_id, 'scope'=>'all', 'status'=>'everything', 'array' => true ) );
			 	foreach($events as $event_array){ /* @var $EM_Event EM_Event */
			 		//set new start/end times to obtain accurate timestamp according to timezone and DST
			 		$EM_DateTime = $this->start()->copy()->modify($event_array['event_start_date']. ' ' . $event_array['event_start_time']);
			 		$start_timestamp = $EM_DateTime->getTimestamp();
			 		$event['event_start'] = $meta_fields['_event_start'] = $EM_DateTime->getDateTime(true);
			 		$end_timestamp = $EM_DateTime->modify($event_array['event_end_date']. ' ' . $event_array['event_end_time'])->getTimestamp();
			 		$event['event_end'] = $meta_fields['_event_end'] = $EM_DateTime->getDateTime(true);
			 		//set indexes for reference further down
			 		$event_ids[$event_array['post_id']] = $event_array['event_id'];
			 		$event_dates[$event_array['event_id']] = $start_timestamp;
			 		$post_ids[$start_timestamp] = $event_array['post_id'];
			 		//do we need to change the slugs?
				    //(re)set post slug, which may need to be sanitized for length as we pre/postfix a date for uniqueness
				    $EM_DateTime->setTimestamp($start_timestamp);
				    $event_slug_date = $EM_DateTime->format( $recurring_date_format );
				    $event_slug = $this->sanitize_recurrence_slug($post_name, $event_slug_date);
				    $event_slug = apply_filters('em_event_save_events_recurrence_slug', $event_slug.'-'.$event_slug_date, $event_slug, $event_slug_date, $start_timestamp, $this); //use this instead
				    $post_fields['post_name'] = $event['event_slug'] = apply_filters('em_event_save_events_slug', $event_slug, $post_fields, $start_timestamp, array(), $this); //deprecated filter
			 		//adjust certain meta information relative to dates and times
			 		if( !empty($this->recurrence_rsvp_days) && is_numeric($this->recurrence_rsvp_days) ){
			 			$event_rsvp_days = $this->recurrence_rsvp_days >= 0 ? '+'. $this->recurrence_rsvp_days: $this->recurrence_rsvp_days;
			 			$event_rsvp_date = $EM_DateTime->setTimestamp($start_timestamp)->modify($event_rsvp_days.' days')->getDate();
			 			$event['event_rsvp_date'] = $meta_fields['_event_rsvp_date'] = $event_rsvp_date;
			 		}else{
			 			$event['event_rsvp_date'] = $meta_fields['_event_rsvp_date'] = $event_array['event_start_date'];
			 		}
			 		$event['event_rsvp_time'] = $meta_fields['_event_rsvp_time'] = $event['event_rsvp_time'];
			 		//add meta fields we deleted and are specific to this event
			 		$meta_fields['_event_start_date'] = $event_array['event_start_date'];
			 		$meta_fields['_event_start_local'] = $event_array['event_start_date']. ' ' . $event_array['event_start_time'];
			 		$meta_fields['_event_end_date'] = $event_array['event_end_date'];
			 		$meta_fields['_event_end_local'] = $event_array['event_end_date']. ' ' . $event_array['event_end_time'];
					
			 		//overwrite event and post tables
			 		$wpdb->update(EM_EVENTS_TABLE, $event, array('event_id' => $event_array['event_id']));
			 		$wpdb->update($wpdb->posts, $post_fields, array('ID' => $event_array['post_id']));
			 		//save meta field data for insertion in one go
			 		foreach($meta_fields as $meta_key => $meta_val){
			 			$meta_inserts[] = $wpdb->prepare("(%d, %s, %s)", array($event_array['post_id'], $meta_key, $meta_val));
			 		}
			 	}
			 	// delete all meta we'll be updating
			 	if( !empty($post_ids) ){
			 		$sql = "DELETE FROM {$wpdb->postmeta} WHERE post_id IN (".implode(',', $post_ids).")";
			 		if( !empty($exclude_meta_update_keys) ){
			 			$sql .= " AND meta_key NOT IN (";
			 			$i = 0;
			 			foreach( $exclude_meta_update_keys as $k ){
			 				$sql.= ( $i > 0 ) ? ',%s' : '%s';
						    $i++;
					    }
					    $sql .= ")";
			 			$sql = $wpdb->prepare($sql, $exclude_meta_update_keys);
				    }
			 		$wpdb->query($sql);
			 	}
			 	// insert the metas in one go, faster than one by one
			 	if( count($meta_inserts) > 0 ){
				 	$result = $wpdb->query("INSERT INTO ".$wpdb->postmeta." (post_id,meta_key,meta_value) VALUES ".implode(',',$meta_inserts));
				 	if($result === false){
				 		$this->add_error(esc_html__('There was a problem adding custom fields to your recurring events.','events-manager'));
				 	}
			 	}
			}
		 	//Next - Bookings. If we're completely rescheduling or just recreating bookings, we're deleting them and starting again
		 	if( ($this->recurring_reschedule || $this->recurring_recreate_bookings) && $this->recurring_recreate_bookings !== false ){ //if set specifically to false, we skip bookings entirely (ML translations for example)
			 	//first, delete all bookings & tickets if we haven't done so during the reschedule above - something we'll want to change later if possible so bookings can be modified without losing all data
			 	if( !$this->recurring_reschedule ){
				 	//create empty EM_Bookings and EM_Tickets objects to circumvent extra loading of data and SQL queries
			 		$EM_Bookings = new EM_Bookings();
			 		$EM_Tickets = new EM_Tickets();
			 		foreach($events as $event){ //$events was defined in the else statement above so we reuse it
			 			if($event['recurrence_id'] == $this->event_id){
			 				//trick EM_Bookings and EM_Tickets to think it was loaded, and make use of optimized delete functions since 5.7.3.4
			 				$EM_Bookings->event_id = $EM_Tickets->event_id = $event['event_id'];
			 				$EM_Bookings->delete();
			 				$EM_Tickets->delete();
			 			}
			 		}
			 	}
			 	//if bookings hasn't been disabled, delete it all
			 	if( $this->event_rsvp ){
			 		$meta_inserts = array();
			 		foreach($this->get_tickets() as $EM_Ticket){
			 			/* @var $EM_Ticket EM_Ticket */
			 			//get array, modify event id and insert
			 			$ticket = $EM_Ticket->to_array();
			 			//empty cut-off dates of ticket, add them at per-event level
			 			unset($ticket['ticket_start']); unset($ticket['ticket_end']);
		 				if( !empty($ticket['ticket_meta']['recurrences']) ){
		 					$ticket_meta_recurrences = $ticket['ticket_meta']['recurrences'];
		 					unset($ticket['ticket_meta']['recurrences']);
		 				}
		 				//unset id
			 			unset($ticket['ticket_id']);
		 				$ticket['ticket_parent'] = $EM_Ticket->ticket_id;
					    //clean up ticket values
			 			foreach($ticket as $k => $v){
			 				if( empty($v) && $k != 'ticket_name' ){ 
			 					$ticket[$k] = 'NULL';
			 				}else{
			 					$data_type = !empty($EM_Ticket->fields[$k]['type']) ? $EM_Ticket->fields[$k]['type']:'%s';
			 					if(is_array($ticket[$k])) $v = serialize($ticket[$k]);
			 					$ticket[$k] = $wpdb->prepare($data_type,$v);
			 				}
			 			}
			 			//prep ticket meta for insertion with relative info for each event date
			 			$EM_DateTime = $this->start()->copy();
			 			foreach($event_ids as $event_id){
			 				$ticket['event_id'] = $event_id;
			 				$ticket['ticket_start'] = $ticket['ticket_end'] = 'NULL';
			 				//sort out cut-of dates
			 				if( !empty($ticket_meta_recurrences) ){
			 					$EM_DateTime->setTimestamp($event_dates[$event_id]); //by using EM_DateTime we'll generate timezone aware dates
			 					if( array_key_exists('start_days', $ticket_meta_recurrences) && $ticket_meta_recurrences['start_days'] !== false && $ticket_meta_recurrences['start_days'] !== null  ){
			 						$ticket_start_days = $ticket_meta_recurrences['start_days'] >= 0 ? '+'. $ticket_meta_recurrences['start_days']: $ticket_meta_recurrences['start_days'];
			 						$ticket_start_date = $EM_DateTime->modify($ticket_start_days.' days')->getDate();
			 						$ticket['ticket_start'] = "'". $ticket_start_date . ' '. $ticket_meta_recurrences['start_time'] ."'";
			 					}
			 					if( array_key_exists('end_days', $ticket_meta_recurrences) && $ticket_meta_recurrences['end_days'] !== false && $ticket_meta_recurrences['end_days'] !== null ){
			 						$ticket_end_days = $ticket_meta_recurrences['end_days'] >= 0 ? '+'. $ticket_meta_recurrences['end_days']: $ticket_meta_recurrences['end_days'];
			 						$EM_DateTime->setTimestamp($event_dates[$event_id]);
			 						$ticket_end_date = $EM_DateTime->modify($ticket_end_days.' days')->getDate();
			 						$ticket['ticket_end'] = "'". $ticket_end_date . ' '. $ticket_meta_recurrences['end_time'] . "'";
			 					}
			 				}
			 				//add insert data
			 				$meta_inserts[] = "(".implode(",",$ticket).")";
			 			}
			 		}
			 		$keys = "(".implode(",",array_keys($ticket)).")";
			 		$values = implode(',',$meta_inserts);
			 		$sql = "INSERT INTO ".EM_TICKETS_TABLE." $keys VALUES $values";
			 		$result = $wpdb->query($sql);
			 	}
		 	}elseif( $this->recurring_delete_bookings ){
		 		//create empty EM_Bookings and EM_Tickets objects to circumvent extra loading of data and SQL queries
		 		$EM_Bookings = new EM_Bookings();
		 		$EM_Tickets = new EM_Tickets();
		 		foreach($events as $event){ //$events was defined in the else statement above so we reuse it
		 			if($event['recurrence_id'] == $this->event_id){
		 				//trick EM_Bookings and EM_Tickets to think it was loaded, and make use of optimized delete functions since 5.7.3.4
		 				$EM_Bookings->event_id = $EM_Tickets->event_id = $event['event_id'];
		 				$EM_Bookings->delete();
		 				$EM_Tickets->delete();
		 			}
		 		}
		 	}
		 	//copy the event tags and categories, which are automatically deleted/recreated by WP and EM_Categories
		 	foreach( self::get_taxonomies() as $tax_name => $tax_data ){
		 		//In MS Global mode, we also save category meta information for global lookups so we use our objects
				if( $tax_name == 'category' ){
					//we save index data for each category in in MS Global mode
					foreach($event_ids as $post_id => $event_id){
						//set and trick category event and post ids so it saves to the right place
						$this->get_categories()->event_id = $event_id;
						$this->get_categories()->post_id = $post_id;
						$this->get_categories()->save();
					}
				 	$this->get_categories()->event_id = $this->event_id;
				 	$this->get_categories()->post_id = $this->post_id;
				}else{
					//general taxonomies including event tags
				 	$terms = get_the_terms( $this->post_id, $tax_data['name']);
			 		$term_slugs = array();
			 		if( is_array($terms) ){
						foreach($terms as $term){
							if( !empty($term->slug) ) $term_slugs[] = $term->slug; //save of category will soft-fail if slug is empty
						}
			 		}
				 	foreach($post_ids as $post_id){
						wp_set_object_terms($post_id, $term_slugs, $tax_data['name']);
				 	}
				}
		 	}
			if( 'future' == $this->post_status ){
				$time = strtotime( $this->post_date_gmt . ' GMT' );
				foreach( $post_ids as $post_id ){
					if( !$this->recurring_reschedule ){
						wp_clear_scheduled_hook( 'publish_future_post', array( $post_id ) ); // clear anything else in the system
					}
					wp_schedule_single_event( $time, 'publish_future_post', array( $post_id ) );
				}
			}
			return apply_filters('em_event_save_events', !in_array(false, $event_saves) && $result !== false, $this, $event_ids, $post_ids);
		}
		return apply_filters('em_event_save_events', false, $this, $event_ids, $post_ids);
	}
	
	/**
	 * Ensures a post slug is the correct length when the date postfix is added, which takes into account multibyte and url-encoded characters and WP unique suffixes.
	 * If a url-encoded slug is nearing 200 characters (the data character limit in the db table), adding a date to the end will cause issues when saving to the db.
	 * This function checks if the final slug is longer than 200 characters and removes one entire character rather than part of a hex-encoded character, until the right size is met.
	 * @param string $post_name
	 * @param string $post_slug_postfix
	 * @return string
	 */
	public function sanitize_recurrence_slug( $post_name, $post_slug_postfix ){
		if( strlen($post_name.'-'.$post_slug_postfix) > 200 ){
			if( preg_match('/^(.+)(\-[0-9]+)$/', $post_name, $post_name_parts) ){
				$post_name_decoded = urldecode($post_name_parts[1]);
				$post_name_suffix =  $post_name_parts[2];
			}else{
				$post_name_decoded = urldecode($post_name);
				$post_name_suffix = '';
			}
			$post_name_maxlength = 200 - strlen( $post_name_suffix . '-' . $post_slug_postfix);
			if ( $post_name_parts[0] === $post_name_decoded.$post_name_suffix ){
				$post_name = substr( $post_name_decoded, 0, $post_name_maxlength );
			}else{
				$post_name = utf8_uri_encode( $post_name_decoded, $post_name_maxlength );
			}
			$post_name = rtrim( $post_name, '-' ). $post_name_suffix;
		}
		return apply_filters('em_event_sanitize_recurrence_slug', $post_name, $post_slug_postfix, $this);
	}
	
	/**
	 * Removes all recurrences of a recurring event.
	 * @return null
	 */
	function delete_events(){
		global $wpdb;
		do_action('em_event_delete_events_pre', $this);
		//So we don't do something we'll regret later, we could just supply the get directly into the delete, but this is safer
		$result = false;
		$events_array = array();
		if( $this->can_manage('delete_events', 'delete_others_events') ){
			//delete events from em_events table
			$sql = $wpdb->prepare('SELECT event_id FROM '.EM_EVENTS_TABLE.' WHERE (recurrence!=1 OR recurrence IS NULL)  AND recurrence_id=%d', $this->event_id);
			$event_ids = $wpdb->get_col( $sql );
			// go through each event and delete individually so individual hooks are fired appropriately
			foreach($event_ids as $event_id){
				$EM_Event = EM_Event::find( $event_id );
				if($EM_Event->recurrence_id == $this->event_id){
					$EM_Event->delete(true);
					$events_array[] = $EM_Event;
				}
			}
			$result = !empty($events_array) || (is_array($event_ids) && empty($event_ids)); // success if we deleted something, or if there was nothing to delete in the first place
		}
		$result = apply_filters('delete_events', $result, $this, $events_array); //Deprecated, use em_event_delete_events
		return apply_filters('em_event_delete_events', $result, $this, $events_array);
	}
	
	/**
	 * Returns the days that match the recurrance array passed (unix timestamps)
	 * @param array $recurrence
	 * @return array
	 */
	function get_recurrence_days(){
		//get timestampes for start and end dates, both at 12AM
		$start_date = $this->start()->copy()->setTime(0,0,0)->getTimestamp();
		$end_date = $this->end()->copy()->setTime(0,0,0)->getTimestamp();
			
		$weekdays = explode(",", $this->recurrence_byday); //what days of the week (or if monthly, one value at index 0)
		$matching_days = array(); //the days we'll be returning in timestamps
		
		//generate matching dates based on frequency type
		switch ( $this->recurrence_freq ){ /* @var EM_DateTime $current_date */
			case 'daily':
				//If daily, it's simple. Get start date, add interval timestamps to that and create matching day for each interval until end date.
				$current_date = $this->start()->copy()->setTime(0,0,0);
				while( $current_date->getTimestamp() <= $end_date ){
					$matching_days[] = $current_date->getTimestamp();
					$current_date->add(new DateInterval('P'. $this->recurrence_interval .'D'));
				}
				break;
			case 'weekly':
				//sort out week one, get starting days and then days that match time span of event (i.e. remove past events in week 1)
				$current_date = $this->start()->copy()->setTime(0,0,0);
				//then get the timestamps of weekdays during this first week, regardless if within event range
				$start_weekday_dates = array(); //Days in week 1 where there would events, regardless of event date range
				for($i = 0; $i < 7; $i++){
					
					if( in_array( $current_date->format('w'), $weekdays) ){
						$start_weekday_dates[] = $current_date->getTimestamp(); //it's in our starting week day, so add it
					}
					$current_date->add(new DateInterval('P1D')); //add a day
				}	
							
				//for each day of eventful days in week 1, add 7 days * weekly intervals
				foreach ($start_weekday_dates as $weekday_date){
					//Loop weeks by interval until we reach or surpass end date
					
					$current_date->setTimestamp($weekday_date);
					while($current_date->getTimestamp() <= $end_date){
						
						if( $current_date->getTimestamp() >= $start_date && $current_date->getTimestamp() <= $end_date ){
							if(count($matching_days) > 100) break; //limit to 1000 days (just in case
							$matching_days[] = $current_date->getTimestamp();
							
						}
						$current_date->add(new DateInterval('P'. ($this->recurrence_interval * 7 ) .'D'));
					}
				}//done!
				break;  
			case 'monthly':
				//loop months starting this month by intervals
				$current_date = $this->start()->copy();
				$current_date->modify($current_date->format('Y-m-01 00:00:00')); //Start date on first day of month, done this way to avoid 'first day of' issues in PHP < 5.6
				while( $current_date->getTimestamp() <= $this->end()->getTimestamp() ){
					$last_day_of_month = $current_date->format('t');
					//Now find which day we're talking about
					$current_week_day = $current_date->format('w');
					$matching_month_days = array();
					//Loop through days of this years month and save matching days to temp array
					for($day = 1; $day <= $last_day_of_month; $day++){
						if((int) $current_week_day == $this->recurrence_byday){
							$matching_month_days[] = $day;
						}
						$current_week_day = ($current_week_day < 6) ? $current_week_day+1 : 0;							
					}
					//Now grab from the array the x day of the month
					$matching_day = false;
					if( $this->recurrence_byweekno > 0 ){
						//date might not exist (e.g. fifth Sunday of a month) so only add if it exists
						if( !empty($matching_month_days[$this->recurrence_byweekno-1]) ){
							$matching_day = $matching_month_days[$this->recurrence_byweekno-1];
						}
					}else{
						//last day of month, so we pop the last matching day
						$matching_day = array_pop($matching_month_days);
					}
					//if we have a matching day, get the timestamp, make sure it's within our start/end dates for the event, and add to array if it is
					if( !empty($matching_day) ){
						$matching_date = $current_date->setDate( $current_date->format('Y'), $current_date->format('m'), $matching_day )->getTimestamp();
						if($matching_date >= $start_date && $matching_date <= $end_date){
							$matching_days[] = $matching_date;
						}
					}
					//add the monthly interval to the current date, but set to 1st of current month first so we don't jump months where $current_date is 31st and next month there's no 31st (so a month is skipped)
					$current_date->modify($current_date->format('Y-m-01')); //done this way to avoid 'first day of ' PHP < 5.6 issues
					$current_date->add(new DateInterval('P'.$this->recurrence_interval.'M'));
				}
				break;
			case 'yearly':
				//Yearly is easy, we get the start date as a cloned EM_DateTime and keep adding a year until it surpasses the end EM_DateTime value. 
				$EM_DateTime = $this->start()->copy();
				while( $EM_DateTime <= $this->end() ){
					$matching_days[] = $EM_DateTime->getTimestamp();
					$EM_DateTime->add(new DateInterval('P'.absint($this->recurrence_interval).'Y'));
				}			
				break;
		}
		sort($matching_days);
		
		
		return apply_filters('em_events_get_recurrence_days', $matching_days, $this);
	}
	
	/**
	 * If event is recurring, set recurrences to same status as template
	 * @param $status
	 */
	function set_status_events($status){
		//give sub events same status
		global $wpdb;
		//get post and event ids of recurrences
		$post_ids = $event_ids = array();
		$events_array = EM_Events::get( array('recurrence'=>$this->event_id, 'scope'=>'all', 'status'=>false, 'array'=>true) ); //only get recurrences that aren't trashed or drafted
		foreach( $events_array as $event_array ){
			$post_ids[] = absint($event_array['post_id']);
			$event_ids[] = absint($event_array['event_id']);
		}
		if( !empty($post_ids) ){
			//decide on what status to set and update wp_posts in the process
			if($status === null){ 
				$set_status='NULL'; //draft post
				$post_status = 'draft'; //set post status in this instance
			}elseif( $status == -1 ){ //trashed post
				$set_status = -1;
				$post_status = 'trash'; //set post status in this instance
			}else{
				$set_status = $status ? 1:0; //published or pending post
				$post_status = $set_status ? 'publish':'pending';
			}

			$result = $wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status=%s WHERE ID IN (". implode(',', $post_ids) .')', array($post_status)) );
			restore_current_blog();
			$result = $result && $wpdb->query( $wpdb->prepare("UPDATE ".EM_EVENTS_TABLE." SET event_status=%s WHERE event_id IN (". implode(',', $event_ids) .')', array($set_status)) );
		}
		return apply_filters('em_event_set_status_events', $result !== false, $status, $this, $event_ids, $post_ids);
	}
	
	/**
	 * Returns a string representation of this recurrence. Will return false if not a recurrence
	 * @return string
	 */
	function get_recurrence_description() {
		$EM_Event_Recurring = $this->get_event_recurrence(); 
		$recurrence = $this->to_array();
		$weekdays_name = array( translate('Sunday'),translate('Monday'),translate('Tuesday'),translate('Wednesday'),translate('Thursday'),translate('Friday'),translate('Saturday'));
		$monthweek_name = array('1' => __('the first %s of the month', 'events-manager'),'2' => __('the second %s of the month', 'events-manager'), '3' => __('the third %s of the month', 'events-manager'), '4' => __('the fourth %s of the month', 'events-manager'), '5' => __('the fifth %s of the month', 'events-manager'), '-1' => __('the last %s of the month', 'events-manager'));
		$output = sprintf (__('From %1$s to %2$s', 'events-manager'),  $EM_Event_Recurring->event_start_date, $EM_Event_Recurring->event_end_date).", ";
		if ($EM_Event_Recurring->recurrence_freq == 'daily')  {
			$freq_desc =__('everyday', 'events-manager');
			if ($EM_Event_Recurring->recurrence_interval > 1 ) {
				$freq_desc = sprintf (__("every %s days", 'events-manager'), $EM_Event_Recurring->recurrence_interval);
			}
		}elseif ($EM_Event_Recurring->recurrence_freq == 'weekly')  {
			$weekday_array = explode(",", $EM_Event_Recurring->recurrence_byday);
			$natural_days = array();
			foreach($weekday_array as $day){
				array_push($natural_days, $weekdays_name[$day]);
			}
			$output .= implode(", ", $natural_days);
			$freq_desc = " " . __("every week", 'events-manager');
			if ($EM_Event_Recurring->recurrence_interval > 1 ) {
				$freq_desc = " ".sprintf (__("every %s weeks", 'events-manager'), $EM_Event_Recurring->recurrence_interval);
			}
			
		}elseif ($EM_Event_Recurring->recurrence_freq == 'monthly')  {
			$weekday_array = explode(",", $EM_Event_Recurring->recurrence_byday);
			$natural_days = array();
			foreach($weekday_array as $day){
				if( is_numeric($day) ){
					array_push($natural_days, $weekdays_name[$day]);
				}
			}
			$freq_desc = sprintf (($monthweek_name[$EM_Event_Recurring->recurrence_byweekno]), implode(" and ", $natural_days));
			if ($EM_Event_Recurring->recurrence_interval > 1 ) {
				$freq_desc .= ", ".sprintf (__("every %s months",'events-manager'), $EM_Event_Recurring->recurrence_interval);
			}
		}elseif ($EM_Event_Recurring->recurrence_freq == 'yearly')  {
			$freq_desc = __("every year", 'events-manager');
			if ($EM_Event_Recurring->recurrence_interval > 1 ) {
				$freq_desc .= sprintf (__("every %s years",'events-manager'), $EM_Event_Recurring->recurrence_interval);
			}
		}else{
			$freq_desc = "[ERROR: corrupted database record]";
		}
		$output .= $freq_desc;
		return  $output;
	}	
	
	/**********************************************************
	 * UTILITIES
	 ***********************************************************/
	function to_array( $db = false ){
		$event_array = parent::to_array($db);
		//we reset start/end datetimes here, based on the EM_DateTime objects if they are valid
		$event_array['event_start'] = $this->start()->valid ? $this->start(true)->format('Y-m-d H:i:s') : null;
		$event_array['event_end'] = $this->end()->valid ? $this->end(true)->format('Y-m-d H:i:s') : null;
		return apply_filters('em_event_to_array', $event_array, $this);
	}
	
	/**
	 * Can the user manage this? 
	 */
	function can_manage( $owner_capability = false, $admin_capability = false, $user_to_check = false ){
		return apply_filters('em_event_can_manage', parent::can_manage($owner_capability, $admin_capability, $user_to_check), $this, $owner_capability, $admin_capability, $user_to_check);
	}
}

function em_ascii_encode($e){
	$output = '';
    for ($i = 0; $i < strlen($e); $i++) { $output .= '&#'.ord($e[$i]).';'; }
    return $output;
}

//TODO placeholder targets filtering could be streamlined better
/**
 * This is a temporary filter function which mimicks the old filters in the old 2.x placeholders function
 * @param string $result
 * @param EM_Event $event
 * @param string $placeholder
 * @param string $target
 * @return mixed
 */
function em_event_output_placeholder($result,$event,$placeholder,$target='html'){
	if( $target == 'raw' ) return $result;
	if( in_array($placeholder, array("#_EXCERPT",'#_EVENTEXCERPT','#_EVENTEXCERPTCUT', "#_LOCATIONEXCERPT")) && $target == 'html' ){
		$result = apply_filters('dbem_notes_excerpt', $result);
	}elseif( $placeholder == '#_CONTACTEMAIL' && $target == 'html' ){
		$result = em_ascii_encode($event->get_contact()->user_email);
	}elseif( in_array($placeholder, array('#_EVENTNOTES','#_NOTES','#_DESCRIPTION','#_LOCATIONNOTES','#_CATEGORYNOTES','#_CATEGORYDESCRIPTION')) ){
		if($target == 'rss'){
			$result = apply_filters('dbem_notes_rss', $result);
			$result = apply_filters('the_content_rss', $result);
		}elseif($target == 'map'){
			$result = apply_filters('dbem_notes_map', $result);
		}elseif($target == 'ical'){
			$result = apply_filters('dbem_notes_ical', $result);
		}elseif ($target == "email"){    
			$result = apply_filters('dbem_notes_email', $result); 
	  	}else{ //html
			$result = apply_filters('dbem_notes', $result);
		}
	}elseif( in_array($placeholder, array("#_NAME",'#_LOCATION','#_TOWN','#_ADDRESS','#_LOCATIONNAME',"#_EVENTNAME","#_LOCATIONNAME",'#_CATEGORY')) ){
		if ($target == "rss"){
			$result = apply_filters('dbem_general_rss', $result);
	  	}elseif ($target == "ical"){    
			$result = apply_filters('dbem_general_ical', $result); 
	  	}elseif ($target == "email"){    
			$result = apply_filters('dbem_general_email', $result); 
	  	}else{ //html
			$result = apply_filters('dbem_general', $result); 
	  	}				
	}
	return $result;
}
add_filter('em_category_output_placeholder','em_event_output_placeholder',1,4);
add_filter('em_event_output_placeholder','em_event_output_placeholder',1,4);
add_filter('em_location_output_placeholder','em_event_output_placeholder',1,4);
// FILTERS
// filters for general events field (corresponding to those of  "the _title")
add_filter('dbem_general', 'wptexturize');
add_filter('dbem_general', 'convert_chars');
add_filter('dbem_general', 'trim');
// filters for the notes field in html (corresponding to those of  "the _content")
add_filter('dbem_notes', 'wptexturize');
add_filter('dbem_notes', 'convert_smilies');
add_filter('dbem_notes', 'convert_chars');
add_filter('dbem_notes', 'wpautop');
add_filter('dbem_notes', 'prepend_attachment');
add_filter('dbem_notes', 'do_shortcode');
// filters for the notes field in html (corresponding to those of  "the _content")
add_filter('dbem_notes_excerpt', 'wptexturize');
add_filter('dbem_notes_excerpt', 'convert_smilies');
add_filter('dbem_notes_excerpt', 'convert_chars');
add_filter('dbem_notes_excerpt', 'wpautop');
add_filter('dbem_notes_excerpt', 'prepend_attachment');
add_filter('dbem_notes_excerpt', 'do_shortcode');
// RSS content filter
add_filter('dbem_notes_rss', 'convert_chars', 8);
add_filter('dbem_general_rss', 'esc_html', 8);
// Notes map filters
add_filter('dbem_notes_map', 'convert_chars', 8);
add_filter('dbem_notes_map', 'js_escape');
//embeds support if using placeholders
if ( is_object($GLOBALS['wp_embed']) ){
	add_filter( 'dbem_notes', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );
	add_filter( 'dbem_notes', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
}

/**
 * This function replaces the default gallery shortcode, so it can check if this is a recurring event recurrence and pass on the parent post id as the default post. 
 * @param array $attr
 */
function em_event_gallery_override( $attr = array() ){
	global $post;
	if( !empty($post->post_type) && $post->post_type == EventPost::TYPE && empty($attr['id']) && empty($attr['ids']) ){
		//no id specified, so check if it's recurring and override id with recurrence template post id
		$EM_Event = EM_Event::find($post->ID, 'post_id');
		if( $EM_Event->is_recurrence() ){
			$attr['id'] = $EM_Event->get_event_recurrence()->post_id;
		}
	}
	return gallery_shortcode($attr);
}
function em_event_gallery_override_init(){
	remove_shortcode('gallery');
	add_shortcode('gallery', 'em_event_gallery_override');
}
add_action('init','em_event_gallery_override_init', 1000); //so that plugins like JetPack don't think we're overriding gallery, we're not i swear!
?>
