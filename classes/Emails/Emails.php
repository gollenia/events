<?php
class EM_Emails {
	/**
	 * Sets up email cron and filters/actions
	 */
	public static function init() {
		//enable custom emails
		if( get_option('dbem_custom_emails') ){
			include('CustomEmails.php');
		}
		//add booking email icals
		add_filter('em_booking_email_messages', 'EM_Emails::booking_email_ical_attachments', 10, 2);
		add_filter('em_multiple_booking_email_messages', 'EM_Emails::booking_email_ical_attachments', 10, 2);
	    //email reminders
	    add_action('update_option_dbem_emp_emails_reminder_time', array('EM_Emails','clear_crons'));
		if( get_option('dbem_cron_emails', 1) ) {
			//set up cron for addint to email queue
			if( !wp_next_scheduled('emp_cron_emails_queue') ){
			    $todays_time_to_run = strtotime(date('Y-m-d', current_time('timestamp')).' '.  get_option('dbem_emp_emails_reminder_time'), current_time('timestamp'));
			    $tomorrows_time_to_run = strtotime(date('Y-m-d', current_time('timestamp')+(86400)).' '. get_option('dbem_emp_emails_reminder_time'), current_time('timestamp'));
			    $time = $todays_time_to_run > current_time('timestamp') ? $todays_time_to_run:$tomorrows_time_to_run;
			    $time -= ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ); //offset time to run at UTC time for WP Cron
				$result = wp_schedule_event( $time,'daily','emp_cron_emails_queue');
			}
			add_action('emp_cron_emails_queue', array('EM_Emails','queue_emails') );
			//set up cron for clearing email queue
			if( !wp_next_scheduled('emp_cron_emails_process_queue') ){
				$result = wp_schedule_event( time(),'em_minute','emp_cron_emails_process_queue');
			}
			add_action('emp_cron_emails_process_queue', array('EM_Emails','process_queue') );
			if( get_option('dbem_emp_emails_reminder_ical') ){
				//set up emails for ical cleaning
				if( !wp_next_scheduled('emp_cron_emails_ical_cleanup') ){
				    $todays_time_to_run = strtotime(date('Y-m-d', current_time('timestamp')).' '.  get_option('dbem_emp_emails_reminder_time'), current_time('timestamp'));
				    $tomorrows_time_to_run = strtotime(date('Y-m-d', current_time('timestamp')+(86400)).' '. get_option('dbem_emp_emails_reminder_time'), current_time('timestamp'));
				    $time = $todays_time_to_run > current_time('timestamp') ? $todays_time_to_run:$tomorrows_time_to_run;
				    $time -= ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ); //offset time to run at UTC time for WP Cron
					$result = wp_schedule_event( $time,'daily','emp_cron_emails_ical_cleanup');
				}
				add_action('emp_cron_emails_ical_cleanup', array('EM_Emails','clean_icals') );
			}else{
				wp_clear_scheduled_hook('emp_cron_emails_ical_cleanup');
			}
		}else{
			//unschedule the crons
			wp_clear_scheduled_hook('emp_cron_emails_process_queue');
			wp_clear_scheduled_hook('emp_cron_emails_queue');
			wp_clear_scheduled_hook('emp_cron_emails_ical_cleanup');
		}
		//admin area
		if( is_admin() ){
		    include('EmailsAdmin.php');
		}
	}
	
	public static function clear_crons(){
	    wp_clear_scheduled_hook('emp_cron_emails_queue');
	    wp_clear_scheduled_hook('emp_cron_emails_ical_cleanup');
	}
	
	public static function booking_email_ical_attachments( $msg, $EM_Booking ){
		//add email ical attachment
		$event_ids = array();
		
		if( !get_option('dbem_bookings_ical_attachments') ){
			return $msg;
		}
		$event_ids[] = $EM_Booking->event_id;
		
		ob_start();
		em_locate_template('templates/ical.php', true, array('args'=>array('event'=>$event_ids, 'scope'=>'all')));
		$icalcontent = preg_replace("/([^\r])\n/", "$1\r\n", ob_get_clean());
		try{
			$ical_filename = 'ical_'.md5($EM_Booking->booking_id . $EM_Booking->date()->getTimestamp()).'.ics';
		}catch( Exception $e ){
			$ical_filename = 'ical_'.md5($EM_Booking->booking_id . $EM_Booking->booking_date).'.ics';
		}
		$ical_attachment = EM_Mailer::add_email_attachment($ical_filename, $icalcontent);
		$ical_file_array = array('name'=>'invite.ics', 'type'=>'text/calendar','path'=>$ical_attachment, 'delete'=>true);
		$msg['user']['attachments'] = array($ical_file_array);
		return $msg;
	}
	
	/**
	 * Run on cron and prep emails to go out
	 */
	public static function queue_emails(){
	    global $wpdb;
	    //For each event x days on
	    $days = get_option('dbem_emp_emails_reminder_days',1);
	    $scope = ($days > 0) ? date('Y-m-d', current_time('timestamp') + (86400*$days)):date('Y-m-d', current_time('timestamp')+86400);
	    //make sure we don't get past events, only events starting that specific date
	    add_filter('pre_option_dbem_events_current_are_past', '__return_true');
		$output_type = get_option('dbem_smtp_html') ? 'html':'email';
	    foreach( EM_Events::get(array('scope'=>$scope,'private'=>1,'blog'=>get_current_blog_id())) as $EM_Event ){
	        /* @var $EM_Event EM_Event */
	        $emails = array();
	    	//get ppl attending
	    	foreach( $EM_Event->get_bookings()->get_bookings()->bookings as $EM_Booking ){ //get confirmed bookings
	    	    /* @var $EM_Booking EM_Booking */
	    	    if( is_email($EM_Booking->get_person()->user_email) ){
	    	    	do_action('em_booking_email_before_send', $EM_Booking);
	    	    	if( EM_ML::$is_ml ){
		    	    	if( $EM_Booking->language && EM_ML::$current_language != $EM_Booking->language ){
		    	    		$lang = $EM_Booking->language;
		    	    		$subject_format = EM_ML_Options::get_option('dbem_emp_emails_reminder_subject', $lang);
		    	    		$message_format = EM_ML_Options::get_option('dbem_emp_emails_reminder_body', $lang);
		    	    	}
	    	    	}
	    	    	if( empty($subject_format) ){
		    	    	$subject_format = get_option('dbem_emp_emails_reminder_subject');
		    	    	$message_format = get_option('dbem_emp_emails_reminder_body');
	    	    	}
	    	    	$subject = $EM_Booking->output($subject_format,'raw');
	    	    	$message = $EM_Booking->output($message_format,$output_type);
		    	    $emails[] = array($EM_Booking->get_person()->user_email, $subject, $message, $EM_Booking->booking_id);
		    	    do_action('em_booking_email_after_send', $EM_Booking);
	    	    }
	    	}
	    	if(count($emails) > 0){
	    	    $attachments = serialize(array());
	    	    if( get_option('dbem_emp_emails_reminder_ical') ){
		    	    //create invite ical
		    	    $upload_dir = wp_upload_dir();
		    	    if( file_exists(trailingslashit($upload_dir['basedir'])."em-cache") || mkdir(trailingslashit($upload_dir['basedir'])."em-cache") ){
		    	    $icalfilename = trailingslashit($upload_dir['basedir'])."em-cache/invite_".$EM_Event->event_id.".ics";
		    	    $icalfile = fopen($icalfilename,'w+');
		    	    if( $icalfile ){
						ob_start();
						em_locate_template('templates/ical.php', true, array('args'=>array('event'=>$EM_Event->event_id)));
						$icalcontent = preg_replace("/([^\r])\n/", "$1\r\n", ob_get_clean());
						fwrite($icalfile, $icalcontent);
						fclose($icalfile);
						$ical_file_array = array('name'=>'invite.ics', 'type'=>'text/calendar','path'=>$icalfilename);
						$attachments = serialize(array($ical_file_array));
		    	    }
		    	    }
	    	    }
	    	    foreach($emails as $email){
			    	$wpdb->insert(EM_EMAIL_QUEUE_TABLE, array('email'=>$email[0],'subject'=>$email[1],'body'=>$email[2],'attachment'=>$attachments,'event_id'=>$EM_Event->event_id,'booking_id'=>$email[3]));
	    	    }
	    	}
	    }
	    //cleanup
		remove_filter('pre_option_dbem_events_current_are_past', '__return_true');
	}
	
	public static function process_queue(){
		//check that this isn't doing cron already - if this is MultiSite Global, then we place a lock at Network level
		$doing_emails = get_option('em_cron_doing_emails');
		if( $doing_emails ){
			//if process has been running for over 15 minutes or 900 seconds (e.g. likely due to a php error or timeout), let it proceed
			if( $doing_emails > (time() - 900 ) ){
				return false;
			}
		}
		update_option('em_cron_doing_emails', time());
	    //init phpmailer
		global $EM_Mailer, $wpdb;
		if( !is_object($EM_Mailer) ){
			$EM_Mailer = new EM_Mailer();
		}
		//get queue
		$limit = get_option('dbem_cron_emails_limit', 100);
		$count = 0;
		$sql = "SELECT * FROM ".EM_EMAIL_QUEUE_TABLE." ORDER BY queue_id  ASC LIMIT 100";
		$results = $wpdb->get_results($sql);
		//loop through results of query whilst results exist
		while( $wpdb->num_rows > 0 ){
			//go through current results set
			foreach($results as $email){
				//if we reach a limit (provided limit is > 0, remove lock and exit this function
				if( $count >= $limit && $limit > 0 ){
					update_option('em_cron_doing_emails', 0);
					return true;
				}
				//send email, immediately delete after from queue
			    if( $EM_Mailer->send($email->subject, $email->body, $email->email, unserialize($email->attachment)) ){
			    	$wpdb->query("DELETE FROM ".EM_EMAIL_QUEUE_TABLE.' WHERE queue_id ='.$email->queue_id);
			    }
				//add to the count and move onto next email
				$count++;
			}
			//if we haven't reached a limit, load up new results
			$results = $wpdb->get_results($sql);
		}
		//remove the lock on this cron
		update_option('em_cron_doing_emails', 0);
	}

	/**
	 * Cleans unused ical files 
	 */
	public static function clean_icals(){
	    global $wpdb;
	    //get theme CSS files
	    $upload_dir = wp_upload_dir();
	    $icalsearch = trailingslashit($upload_dir['basedir'])."em-cache/invite_*.ics";
	    foreach( glob( $icalsearch ) as $css_file ){
	        if( preg_match('/invite_([0-9]+)\.ics$/', $css_file, $matches) ){
		        $event_id = $matches[1];
		        //count number of matches
		        $count = $wpdb->get_var("SELECT COUNT(*) FROM ".EM_EMAIL_QUEUE_TABLE." WHERE event_id=$event_id");
		        if($count == 0){
		            unlink($css_file);
		        }
	        }
	    }
	}

}
add_action('init',array('EM_Emails','init'), 9);