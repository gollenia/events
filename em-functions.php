<?php

if(!function_exists('em_paginate')){ //overridable e.g. in you mu-plugins folder.
/**
 * Takes a few params and determines a pagination link structure
 * @param string $link
 * @param int $total
 * @param int $limit
 * @param int $page
 * @param array $data If supplied and EM_USE_DATA_ATTS is true/defined, this set of data will be stripped from the URL and added as a data-em-ajax attribute containing data AJAX can use
 * @return string
 */
function em_paginate($link, $total, $limit, $page=1, $data=array()){
	if($limit > 0){
		$pagesToShow = defined('EM_PAGES_TO_SHOW') ? EM_PAGES_TO_SHOW : 10;
		$url_parts = explode('?', $link);
		$base_link = $url_parts[0];
		$base_querystring = '';
		$data_atts = '';
    	//Get querystring for first page without page
    	if( count($url_parts) > 0 ){
	    	$query_arr = array();
	    	parse_str($url_parts[1], $query_arr);
	    	//if $data was passed, strip any of these vars from both the $query_arr and $link for inclusion in the data-em-ajax attribute
	    	if( !empty($data) && is_array($data) && (!defined('EM_USE_DATA_ATTS') || EM_USE_DATA_ATTS) ){
	    		//remove the data attributes from $query_arr
	    		foreach( array_keys($data) as $key){
	    			if( array_key_exists($key, $query_arr) ){
	    				unset($query_arr[$key]);
	    			}
	    		}
	    		//rebuild the master link, without these data attributes
	    		if( count($query_arr) > 0 ){
	    			$link = $base_link .'?'. build_query($query_arr);
	    		}else{
	    			$link = $base_link;
	    		}
	    		$data_atts = 'data-em-ajax="'.esc_attr(build_query($data)).'"'; //for inclusion later on
	    	}
	    	//proceed to build the base querystring without pagination arguments
	    	unset($query_arr['page']); unset($query_arr['pno']);
	    	$base_querystring = esc_attr(build_query($query_arr));
	    	if( !empty($base_querystring) ) $base_querystring = '?'.$base_querystring;
    	}
    	//calculate
		$maxPages = ceil($total/$limit); //Total number of pages
		$startPage = ($page <= $pagesToShow) ? 1 : $pagesToShow * (floor($page/$pagesToShow)) ; //Which page to start the pagination links from (in case we're on say page 12 and $pagesToShow is 10 pages)
		$placeholder = urlencode('%PAGE%');
		$link = str_replace('%PAGE%', $placeholder, esc_url($link)); //To avoid url encoded/non encoded placeholders
	    //Add the back and first buttons
		    $string = ($page>1 && $startPage != 1) ? '<a class="prev page-numbers" href="'.str_replace($placeholder,1,$link).'" title="1">&lt;&lt;</a> ' : '';
		    if($page == 2){
		    	$string .= ' <a class="prev page-numbers" href="'.esc_url($base_link.$base_querystring).'" title="2">&lt;</a> ';
		    }elseif($page > 2){
		    	$string .= ' <a class="prev page-numbers" href="'.str_replace($placeholder,$page-1,$link).'" title="'.($page-1).'">&lt;</a> ';
		    }
		//Loop each page and create a link or just a bold number if its the current page
		    for ($i = $startPage ; $i < $startPage+$pagesToShow && $i <= $maxPages ; $i++){
	            if($i == $page || (empty($page) && $startPage == $i)) {
	                $string .= ' <strong><span class="page-numbers current">'.$i.'</span></strong>';
	            }elseif($i=='1'){
	                $string .= ' <a class="page-numbers" href="'.esc_url($base_link.$base_querystring).'" title="'.$i.'">'.$i.'</a> ';
	            }else{
	                $string .= ' <a class="page-numbers" href="'.str_replace($placeholder,$i,$link).'" title="'.$i.'">'.$i.'</a> ';
	            }
		    }
		//Add the forward and last buttons
		    $string .= ($page < $maxPages) ? ' <a class="next page-numbers" href="'.str_replace($placeholder,$page+1,$link).'" title="'.($page+1).'">&gt;</a> ' :' ' ;
		    $string .= ($i-1 < $maxPages) ? ' <a class="next page-numbers" href="'.str_replace($placeholder,$maxPages,$link).'" title="'.$maxPages.'">&gt;&gt;</a> ' : ' ';
		//Return the string
		    return apply_filters('em_paginate', '<span class="em-pagination" '.$data_atts.'>'.$string.'</span>');
	}
}
}

/**
 * Creates a wp-admin style navigation.
 * @param string $link
 * @param int $total
 * @param int $limit
 * @param int $page
 * @param int $pagesToShow
 * @return string
 * @uses paginate_links()
 * @uses add_query_arg()
 */
function em_admin_paginate($total, $limit, $page=1, $vars=false, $base = false, $format = ''){
	$return = '<div class="tablenav-pages em-tablenav-pagination">';
	$base = !empty($base) ? $base:esc_url_raw(add_query_arg( 'pno', '%#%' ));
	$events_nav = paginate_links( array(
		'base' => $base,
		'format' => $format,
		'total' => ceil($total / $limit),
		'current' => $page,
		'add_args' => $vars
	));
	$return .= sprintf( '<span class="displaying-num">' . __( 'Displaying %1$s&#8211;%2$s of %3$s', 'events-manager') . ' </span>%4$s',
		number_format_i18n( ( $page - 1 ) * $limit + 1 ),
		number_format_i18n( min( $page * $limit, $total ) ),
		number_format_i18n( $total ),
		$events_nav
	);
	$return .= '</div>';
	return apply_filters('em_admin_paginate',$return,$total,$limit,$page,$vars);
}

/**
 * Takes a url and appends GET params (supplied as an assoc array), it automatically detects if you already have a querystring there
 * @param string $url
 * @param array $params
 * @param bool $html
 * @param bool $encode
 * @return string
 */
function em_add_get_params($url, $params=array(), $html=true, $encode=true){
	//Splig the url up to get the params and the page location
	$url_parts = explode('?', $url);
	$url = $url_parts[0];
	$url_params_dirty = array();
	if(count($url_parts) > 1){
		$url_params_dirty = $url_parts[1];
		//get the get params as an array
		if( !is_array($url_params_dirty) ){
			if( strstr($url_params_dirty, '&amp;') !== false ){
				$url_params_dirty = explode('&amp;', $url_params_dirty);
			}else{
				$url_params_dirty = explode('&', $url_params_dirty);
			}
		}
		//split further into associative array
		$url_params = array();
		foreach($url_params_dirty as $url_param){
			if( !empty($url_param) ){
				$url_param = explode('=', $url_param);
				if(count($url_param) > 1){
					$url_params[$url_param[0]] = $url_param[1];
				}
			}
		}
		//Merge it together
		$params = array_merge($url_params, $params);
	}
	//Now build the array back up.
	$count = 0;
	foreach($params as $key=>$value){
		if( $value !== null ){
			if( is_array($value) ) $value = implode(',',$value);
			$value = ($encode) ? urlencode($value):$value;
			if( $count == 0 ){
				$url .= "?{$key}=".$value;
			}else{
				$url .= ($html) ? "&amp;{$key}=".$value:"&{$key}=".$value;
			}
			$count++;
		}
	}
	return $html ? esc_url($url):esc_url_raw($url);
}

/**
 * Get a array of countries, __d. Keys are 2 character country iso codes. If you supply a string or array that will be the first value in the array (if array, the array key is the first key in the returned array)
 * @param mixed $add_blank
 * @return array
 */
function em_get_countries($add_blank = false, $sort = true){
	global $em_countries_array;
	if( !is_array($em_countries_array) ){
		$em_countries_array = array ('AT' => __('Austria', 'events-manager'), 'DE' => __('Germany', 'events-manager'), 'CH' => __('Switzerland', 'events-manager'), 'FR' => __('France', 'events-manager'), 'NL' => __('Netherlands', 'events-manager') );
	}
	if($sort){ asort($em_countries_array); }
	if($add_blank !== false){
		if(is_array($add_blank)){
			$em_countries_array = $add_blank + $em_countries_array;
		}else{
		    $em_countries_array = array(0 => $add_blank) + $em_countries_array;
		}
	}
	return apply_filters('em_get_countries', $em_countries_array);
}

/**
 * Returns an array of scopes available to events manager. Hooking into this function's em_get_scopes filter will allow you to add scope options to the event pages.
 */
function em_get_scopes(){
	global $wp_locale;
	$start_of_week = get_option('start_of_week');
	$end_of_week_name = $start_of_week > 0 ? $wp_locale->get_weekday($start_of_week-1) : $wp_locale->get_weekday(6);
	$start_of_week_name = $wp_locale->get_weekday($start_of_week);
	$scopes = array(
		'all' => __('All events','events-manager'),
		'future' => __('Future events','events-manager'),
		'past' => __('Past events','events-manager'),
		'today' => __('Today\'s events','events-manager'),
		'tomorrow' => __('Tomorrow\'s events','events-manager'),
		'week' => sprintf(__('Events this whole week (%s to %s)','events-manager'), $wp_locale->get_weekday_abbrev($start_of_week_name), $wp_locale->get_weekday_abbrev($end_of_week_name)),
		'this-week' => sprintf(__('Events this week (today to %s)','events-manager'), $wp_locale->get_weekday_abbrev($end_of_week_name)),
		'month' => __('Events this month','events-manager'),
		'this-month' => __('Events this month (today onwards)', 'events-manager'),
		'next-month' => __('Events next month','events-manager'),
		'1-months'  => __('Events current and next month','events-manager'),
		'2-months'  => __('Events within 2 months','events-manager'),
		'3-months'  => __('Events within 3 months','events-manager'),
		'6-months'  => __('Events within 6 months','events-manager'),
		'12-months' => __('Events within 12 months','events-manager')
	);
	return apply_filters('em_get_scopes',$scopes);
}

function em_get_currencies(){
	$currencies = new stdClass();
	$currencies->names = array('EUR' => 'EUR - Euros','USD' => 'USD - U.S. Dollars','GBP' => 'GBP - British Pounds','CAD' => 'CAD - Canadian Dollars','AUD' => 'AUD - Australian Dollars','BRL' => 'BRL - Brazilian Reais','CZK' => 'CZK - Czech koruna','DKK' => 'DKK - Danish Kroner','HKD' => 'HKD - Hong Kong Dollars','HUF' => 'HUF - Hungarian Forints','ILS' => 'ILS - Israeli New Shekels','JPY' => 'JPY - Japanese Yen','MYR' => 'MYR - Malaysian Ringgit','MXN' => 'MXN - Mexican Pesos','TWD' => 'TWD - New Taiwan Dollars','NZD' => 'NZD - New Zealand Dollars','NOK' => 'NOK - Norwegian Kroner','PHP' => 'PHP - Philippine Pesos','PLN' => 'PLN - Polish Zlotys','SGD' => 'SGD - Singapore Dollars','SEK' => 'SEK - Swedish Kronor','CHF' => 'CHF - Swiss Francs','THB' => 'THB - Thai Baht','TRY' => 'TRY - Turkish Liras', 'RUB'=>'RUB - Russian Ruble');
	$currencies->symbols = array( 'EUR' => '&euro;','USD' => '$','GBP' => '&pound;','CAD' => '$','AUD' => '$','BRL' => 'R$','CZK' => 'K&#269;','DKK' => 'kr','HKD' => '$','HUF' => 'Ft','JPY' => '&#165;','MYR' => 'RM','MXN' => '$','TWD' => '$','NZD' => '$','NOK' => 'kr','PHP' => 'Php', 'PLN' => '&#122;&#322;','SGD' => '$','SEK' => 'kr','CHF' => 'CHF','TRY' => 'TL','RUB'=>'&#8381;');
	$currencies->true_symbols = array( 'EUR' => '€','USD' => '$','GBP' => '£','CAD' => '$','AUD' => '$','BRL' => 'R$','CZK' => 'Kč','DKK' => 'kr','HKD' => '$','HUF' => 'Ft','JPY' => '¥','MYR' => 'RM','MXN' => '$','TWD' => '$','NZD' => '$','NOK' => 'kr','PHP' => 'Php','PLN' => 'zł','SGD' => '$','SEK' => 'kr','CHF' => 'CHF','TRY' => 'TL', 'RUB'=>'₽');
	return apply_filters('em_get_currencies',$currencies);
}



function em_get_currency_symbol($true_symbol = false, $currency = false){
	$formatter = new \Contexis\Events\Intl\PriceFormatter(0);
	return $formatter->get_currency();
	
}

function em_get_currency_name($currency = false){
	if( !$currency ) $currency = get_option('dbem_bookings_currency');
	return apply_filters('em_get_currency_name', em_get_currencies()->names[$currency]);
}

function em_get_hour_format(){
	return get_option('dbem_time_24h') ? "H:i":"h:i A";
}

function em_get_days_names(){
	return array (1 => __( 'Mon' ), 2 => __( 'Tue' ), 3 => __( 'Wed' ), 4 => __( 'Thu' ), 5 => __( 'Fri' ), 6 => __( 'Sat' ), 0 => __( 'Sun' ) );
}

/**
 * Works like check_admin_referrer(), but also in public mode. If in admin mode, it triggers an error like in check_admin_referrer(), if outside admin it just exits with an error.
 * @param string $action
 */
function em_verify_nonce($action, $nonce_name='_wpnonce'){
	if( is_admin() ){
		if( !wp_verify_nonce($_REQUEST[$nonce_name], $action) ) check_admin_referer('trigger_error');
	}else{
		if( !wp_verify_nonce($_REQUEST[$nonce_name], $action) ) exit( __('Trying to perform an illegal action.','events-manager') );
	}
}

/**
 * Since WP 4.5 em_wp_get_referer() returns false if URL is the same. We use it to get a safe referrer url, so we use the new wp_get_raw_referer() argument instead.
 * @since 5.6.3
 * @return string 
 */
function em_wp_get_referer(){
	if( function_exists('wp_get_raw_referer') ){
		//do essentially what em_wp_get_referer does, but potentially returning the same url as before
		return wp_validate_redirect(wp_get_raw_referer(), false );
	}else{
		return wp_get_referer();
	}
}

/**
 * Copied straight from wp-login.php, only change atm is a function renaming.
 * Handles registering a new user.
 *
 * @param array associative array of user values to insert
 * @return int|WP_Error Either user's ID or error on failure.
 */
/*function em_register_new_user( $user_data ) {
	$user_data = apply_filters('em_register_new_user_pre',$user_data);
	$errors = new WP_Error();
	if( !empty($user_data['user_name']) ){
		$name = explode(' ', $user_data['user_name']);
		$user_data['first_name'] = array_shift($name);
		$user_data['last_name'] = implode(' ',$name);
	}
	$sanitized_user_login = sanitize_user( $user_data['user_login'] );
	$user_data['user_login'] = $sanitized_user_login;
	$user_email = apply_filters( 'user_registration_email', $user_data['user_email'] );

	// Check the username
	if ( $sanitized_user_login == '' ) {
		$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Please enter a username.', 'events-manager') );
	} elseif ( ! validate_username( $user_data['user_login'] ) ) {
		$errors->add( 'invalid_username', __( '<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.', 'events-manager') );
		$sanitized_user_login = '';
	} elseif ( username_exists( $sanitized_user_login ) ) {
		$errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered, please choose another one.', 'events-manager') );
	}

	// Check the e-mail address
	if ( $user_email == '' ) {
		$errors->add( 'empty_email', __( '<strong>ERROR</strong>: Please type your e-mail address.', 'events-manager') );
	} elseif ( ! is_email( $user_email ) ) {
		$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.', 'events-manager') );
		$user_email = '';
	} elseif ( email_exists( $user_email ) ) {
		$errors->add( 'email_exists', __( '<strong>ERROR</strong>: This email is already registered, please choose another one.', 'events-manager') );
	}

	do_action( 'register_post', $sanitized_user_login, $user_email, $errors );

	//custom registration filter to prevent things like SI Captcha and other plugins of this kind interfering with EM
	$errors = apply_filters( 'em_registration_errors', $errors, $sanitized_user_login, $user_email );
	
	if ( $errors->get_error_code() ) return $errors;

	if(empty($user_data['user_pass'])){
		$user_data['user_pass'] =  wp_generate_password( 12, false);
	}

	$user_id = wp_insert_user( $user_data );
	if( is_numeric($user_id) && !empty($user_data['dbem_phone']) ){
		update_user_meta($user_id, 'dbem_phone', $user_data['dbem_phone']);
	}

	if ( ! $user_id ) {
		$errors->add( 'registerfail', sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'events-manager'), get_option( 'admin_email' ) ) );
		return $errors;
	}

	update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.

	global $em_temp_user_data;
	$em_temp_user_data = $user_data; //for later useage
	$em_temp_user_data['user_id'] = $user_id;

	return apply_filters('em_register_new_user',$user_id);
}*/

/*
 * UI Helpers
 * previously dbem_UI_helpers.php functions
 * @todo deprecate and move to own (static?) Class
 */
function em_option_items($array, $saved_value) {
	$output = "";
	foreach($array as $key => $item) {
		$selected ='';
		if ($key == $saved_value)
			$selected = "selected='selected'";
		$output .= "<option value='".esc_attr($key)."' $selected >".esc_html($item)."</option>\n";

	}
	echo $output;
}

function em_checkbox_items($name, $array, $saved_values, $horizontal = true) {
	$output = "";
	foreach($array as $key => $item) {
		$checked = "";
		if (in_array($key, $saved_values)) $checked = "checked='checked'";
		$output .= "<label><input type='checkbox' name='".esc_attr($name)."' value='".esc_attr($key)."' $checked /> ".esc_html($item)."</label>&nbsp; ";
		if(!$horizontal)
			$output .= "<br/>\n";
	}
	echo $output;

}
function em_options_input_text($title, $name, $description ='', $default='') {
    $__ = EM_ML::is_option_translatable($name);
    if( preg_match('/^(.+)\[(.+)?\]$/', $name, $matches) ){
    	$value = EM_Options::get($matches[2], $default, $matches[1]);
		var_dump($matches);
    }else{
        $value = get_option($name, $default);
    }
	?>
	<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
		<th scope="row"><?php echo esc_html($title); ?></th>
	    <td>
			<input name="<?php echo esc_attr($name) ?>" type="text" id="<?php echo esc_attr($name) ?>" value="<?php echo esc_attr($value, ENT_QUOTES); ?>" size="45" />
	    	<?php if( $__ ): ?><span class="em-translatable dashicons dashicons-admin-site"></span><?php endif; ?>
	    	<br />
			<?php 
				if( $__ ){
					echo '<div class="em-ml-options"><table class="form-table">';
					foreach( EM_ML::get_langs() as $lang => $lang_name ){
						if( $lang != EM_ML::$wplang ){
							?>
							<tr>
								<td class="lang"><?php echo $lang_name; ?></td>
								<td class="lang-text"><input name="<?php echo esc_attr($name) ?>_ml[<?php echo $lang ?>]" type="text" id="<?php echo esc_attr($name.'_'.$lang) ?>" style="width: 100%" value="<?php echo esc_attr(EM_ML::get_option($name, $lang, false), ENT_QUOTES); ?>" size="45" /></td>
							</tr>
							<?php
						}else{
							$default_lang = '<input name="'.esc_attr($name).'_ml['.EM_ML::$wplang.']" type="hidden" id="'. esc_attr($name.'_'. EM_ML::$wplang) .'" value="'. esc_attr($value, ENT_QUOTES).'" />';
						}
					}
					echo '</table>';
					echo '<em>'.__('If translations are left blank, the default value will be used.','events-manager').'</em>';
					echo $default_lang.'</div>';
				}
			?>
			<p class="description"><?php echo $description; ?></p>
		</td>
	</tr>
	<?php
}

function em_options_input_password($title, $name, $description ='') {
	?>
	<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
		<th scope="row"><?php echo esc_html($title); ?></th>
	    <td>
			<input name="<?php echo esc_attr($name) ?>" type="password" id="<?php echo esc_attr($title) ?>" style="width: 95%" value="<?php echo esc_attr(get_option($name)); ?>" size="45" /><br />
			<p class="description"><?php echo $description; ?></p>
		</td>
	</tr>
	<?php
}

function em_options_textarea($title, $name, $description ='') {
	$__ = EM_ML::is_option_translatable($name);
	?>
	<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
		<th scope="row"><?php echo esc_html($title); ?></th>
			<td><fieldset>
				<?php if($description) { ?><p><label for="<?php echo esc_attr($name) ?>"><?php echo $description; ?></label></p><?php } ?>
				<p><textarea name="<?php echo esc_attr($name) ?>" id="<?php echo esc_attr($name) ?>" rows="6"><?php echo esc_attr(get_option($name), ENT_QUOTES);?></textarea></p>
		    	<?php if( $__ ): ?><span class="em-translatable  dashicons dashicons-admin-site"></span><?php endif; ?>
		    	<br />
				<?php 
					if( $__ ){
						echo '<div class="em-ml-options"><table class="form-table">';
						foreach( EM_ML::get_langs() as $lang => $lang_name ){
							if( $lang != EM_ML::$wplang ){
								?>
								<tr>
									<td class="lang"><?php echo $lang_name; ?></td>
									<td class="lang-text"><textarea name="<?php echo esc_attr($name) ?>_ml[<?php echo $lang ?>]" id="<?php echo esc_attr($name.'_'.$lang) ?>" style="width: 100%" size="45"><?php echo esc_attr(EM_ML::get_option($name, $lang, false), ENT_QUOTES); ?></textarea></td>
								</tr>
								<?php
							}else{
								$default_lang = '<input name="'.esc_attr($name).'_ml['.EM_ML::$wplang.']" type="hidden" id="'. esc_attr($name.'_'. EM_ML::$wplang) .'" value="'. esc_attr(get_option($name), ENT_QUOTES).'" />';
							}
						}
						echo '</table>';
						echo '<em>'.__('If left blank, the default value will be used.','events-manager').'</em>';
						echo $default_lang.'</div>';
					}
				?>
			</fieldset></td>
		</tr>
	<?php
}

function em_options_radio($name, $options, $title='') {
		$option = get_option($name);
		?>
	   	<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
	   		<?php if( !empty($title) ): ?>
	   		<th scope="row"><?php  echo esc_html($title); ?></th>
	   		<td>
	   		<?php else: ?>
	   		<td colspan="2">
	   		<?php endif; ?>
	   			<table>
	   			<?php foreach($options as $value => $text): ?>
	   				<tr>
	   					<td><input id="<?php echo esc_attr($name) ?>_<?php echo esc_attr($value); ?>" name="<?php echo esc_attr($name) ?>" type="radio" value="<?php echo esc_attr($value); ?>" <?php if($option == $value) echo "checked='checked'"; ?> /></td>
	   					<td><?php echo $text ?></td>
	   				</tr>
				<?php endforeach; ?>
				</table>
			</td>
	   	</tr>
<?php
}

function em_options_radio_binary($title, $name, $description='', $option_names = '', $trigger='', $untrigger=false) {
	if( empty($option_names) ) $option_names = array(0 => __('No','events-manager'), 1 => __('Yes','events-manager'));
	if( substr($name, 0, 7) == 'dbem_ms' ){
		$list_events_page = get_site_option($name);
	}else{
		$list_events_page = get_option($name);
	}
	if( $untrigger ){
		$trigger_att = ($trigger) ? 'data-trigger="'.esc_attr($trigger).'" class="em-untrigger"':'';
	}else{
		$trigger_att = ($trigger) ? 'data-trigger="'.esc_attr($trigger).'" class="em-trigger"':'';
	}
	?>
   	<tr valign="top" id='<?php echo $name;?>_row'>
   		<th scope="row"><?php echo esc_html($title); ?></th>
   		<td>
   			<?php echo $option_names[1]; ?> <input id="<?php echo esc_attr($name) ?>_yes" name="<?php echo esc_attr($name) ?>" type="radio" value="1" <?php if($list_events_page) echo "checked='checked'"; echo $trigger_att; ?> />&nbsp;&nbsp;&nbsp;
			<?php echo $option_names[0]; ?> <input  id="<?php echo esc_attr($name) ?>_no" name="<?php echo esc_attr($name) ?>" type="radio" value="0" <?php if(!$list_events_page) echo "checked='checked'"; echo $trigger_att; ?> />
			<br/><p class="description"><?php echo $description; ?></p>
		</td>
   	</tr>
	<?php
}

function em_options_checkbox($title, $name, $description='') {
	if( empty($option_names) ) $option_names = array(0 => __('No','events-manager'), 1 => __('Yes','events-manager'));
	?>
   	<tr valign="top" id='<?php echo $name;?>_row'>
   		<th scope="row"><?php echo esc_html($title); ?></th>
   		<td>
			<label for="<?php esc_attr($name) ?>">
   			<input id="<?php echo esc_attr($name) ?>_yes" name="<?php echo esc_attr($name) ?>" type="checkbox" <?php echo get_option($name) ? 'checked="checked"' : "" ?> />
			<?php echo $description; ?>
			</label>
		</td>
   	</tr>
	<?php
}

function em_options_select($title, $name, $list, $description='', $default='') {
	$option_value = get_option($name, $default);
	
	?>
   	<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
   		<th scope="row"><?php echo esc_html($title); ?></th>
   		<td>
			<select name="<?php echo esc_attr($name); ?>" >
				<?php 
				foreach($list as $key => $value) {
					if( is_array($value) ){
						?><optgroup label="<?php echo $key; ?>"><?php
						foreach( $value as $key_group => $value_group ){
							?>
			 				<option value='<?php echo esc_attr($key_group) ?>' <?php echo ("$key_group" == $option_value) ? "selected='selected' " : ''; ?>>
			 					<?php echo esc_html($value_group); ?>
			 				</option>
							<?php 
						}
						?></optgroup><?php
					}else{
						?>
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ("$key" == $option_value) ? "selected='selected' " : ''; ?>>
		 					<?php echo esc_html($value); ?>
		 				</option>
						<?php 
					} 
				}
				?>
			</select> <br/>
			<p class="description"><?php echo $description; ?></p>
		</td>
   	</tr>
	<?php
}
