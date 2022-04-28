<?php
/**
 * Handles MultiLingual stuff for Events Manager Pro
 *
 */
class EMP_ML {
    public static function init(){
        //translatable options
        add_filter('em_ml_translatable_options','EMP_ML::em_ml_translatable_options');
        add_filter('em_ml_admin_settings_pages', 'EMP_ML::em_ml_admin_settings_pages');
    }
    
    public static function em_ml_admin_settings_pages( $settings ){
    	return array_merge( $settings, array('events-manager-gateways', 'events-manager-forms-editor', 'events-manager-coupons') );
    }
    
    /**
     * Translate options specific to EM Pro
     * @param array $options
     * @return array:
     */
    public static function em_ml_translatable_options( $options ){
        $options[] = 'dbem_emp_booking_form_error_required';
		//email reminders
		$options[] = 'dbem_emp_emails_reminder_subject';
		$options[] = 'dbem_emp_emails_reminder_body';
        
		//payment gateway options (pro, move out asap)
		$options[] = 'dbem_gateway_label';
        //gateway translateable options
        if( get_option('dbem_rsvp_enabled') ){ 
			foreach ( EM_Gateways::gateways_list() as $gateway => $gateway_name ){
			    $EM_Gateway = EM_Gateways::get_gateway($gateway);
			    $options[] = 'em_'.$gateway.'_option_name';
			    $options[] = 'em_'.$gateway.'_booking_feedback';
			    $options[] = 'em_'.$gateway.'_booking_feedback_free';
			    $options[] = 'em_'.$gateway.'_booking_feedback_completed';
			    $options[] = 'em_'.$gateway.'_form';
			    if( $EM_Gateway->button_enabled ){
			        $options[] = 'em_'.$gateway.'_button';
			    }
			}
        }
        return $options;
    }
}
add_action('em_ml_pre_init', 'EMP_ML::init');