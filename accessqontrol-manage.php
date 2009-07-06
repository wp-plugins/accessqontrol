<?php
/* -------------------------------------------------------------
 Name:      aqontrol_insert_input

 Purpose:   Insert the new ban into the database
 Receive:   -None-
 Return:	-None-
------------------------------------------------------------- */
function aqontrol_insert_input() {
	global $wpdb;

	$aqontrol_remote_ip = aqontrol_remote_ip();
	$thetime	= current_time('timestamp');
	$type	 	= htmlentities(trim($_POST['type'], "\t\n "), ENT_QUOTES);
	$address 	= htmlentities(trim($_POST['address'], "\t\n "), ENT_QUOTES);
	$range	 	= htmlentities(trim($_POST['range'], "\t\n "), ENT_QUOTES);
	$reason 	= htmlentities(trim($_POST['reason'], "\t\n "), ENT_QUOTES);
	$redirect 	= htmlentities(trim($_POST['redirect'], "\t\n "), ENT_QUOTES);
	$timetype 	= htmlentities(trim($_POST['timetype'], "\t\n "), ENT_QUOTES);
	$timeset 	= htmlentities(trim($_POST['timeset'], "\t\n "), ENT_QUOTES);

	if (strlen($address)!=0 AND strlen($address)<=255 AND strlen($redirect)<=255 AND strlen($reason)<=255 AND strlen($range)<=15) {
		if ($timetype == "") 		$timetype = "permanent";
		if (strlen($timeset) < 1) 	$timeset = 1;
		if (!is_int($timeset)) 		$timeset = 1;
		
		if ($timetype == "day") {
			$duration = ($timeset * 86400) + $thetime;
		} else if ($timetype == "week") {
			$duration = ($timeset * 604800) + $thetime;
		} else {
			$duration = 0;
		}

		$reserved = array ("127.0.0.1", "0.0.0.0", "localhost", "::1", $aqontrol_remote_ip);

		if (!in_array(strtolower($address), $reserved) AND !in_array(strtolower($range), $reserved)) {		
			if ($type == "range") {
				$address 	= gethostbyname($address);
				$range 		= gethostbyname($range);
				$address 	= sprintf("%u", ip2long($address));
				$range 		= sprintf("%u", ip2long($range));
			} else if ($type == "single") {
				$address 	= gethostbyname($address);
				$range 		= gethostbyname($address);
				$address 	= sprintf("%u", ip2long($address));
				$range 		= sprintf("%u", ip2long($address));
			} else {
				aqontrol_return('error', array('wrong_ban_type'));			
			}
		
			$postquery = "INSERT INTO `".$wpdb->prefix."accessqontrol` (`address`, `range`, `reason`, `redirect`, `thetime`, `duration`) VALUES ('$address', '$range', '$reason', '$redirect', '$thetime', '$duration')";
			if ($wpdb->query($postquery) !== FALSE) {
				aqontrol_return('new');
			} else {
				die(mysql_error());
			}
		} else {
			aqontrol_return('error', array('reserved_address_forbidden'));
		}

	} else {
		aqontrol_return('error', array('not_all_required_fields'));
	}
}

/* -------------------------------------------------------------
 Name:      aqontrol_remove_expired

 Purpose:   Removes expired bans
 Receive:   -none-
 Return:	-none-
------------------------------------------------------------- */
function aqontrol_remove_expired() {
	global $wpdb;

	$thetime = current_time('timestamp');
	
	$old_bans = $wpdb->get_results("DELETE FROM `".$wpdb->prefix."accessqontrol` WHERE `duration` >= '$thetime'");
}

/*-------------------------------------------------------------
 Name:      aqontrol_request_action

 Purpose:   Prepare action for ban management
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_request_action() {
	global $wpdb, $userdata;

	if(isset($_POST['bancheck'])) $ban_ids = $_POST['bancheck'];
	$action = strtolower($_POST['aqontrol_action']);

	if(current_user_can('manage_options')) {
		if($ban_ids != '') {
			foreach($ban_ids as $ban_id) {
				if($action == 'delete') {
					if($wpdb->query("DELETE FROM `".$wpdb->prefix."accessqontrol` WHERE `id` = $ban_id") == FALSE) {
						die(mysql_error());
					}
				}
			}
		}
		aqontrol_return($action, array($ban_id));
	} else {
		aqontrol_return('no_access');
	}
}

/*-------------------------------------------------------------
 Name:      aqontrol_options_submit

 Purpose:   Save options
 Receive:   $_POST
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_options_submit() {
	$template['title'] 				= htmlspecialchars(trim($_POST['aqontrol_title'], "\t\n "), ENT_QUOTES);
	$template['content'] 			= htmlspecialchars(trim($_POST['aqontrol_content'], "\t\n "), ENT_QUOTES);
	update_option('aqontrol_template', $template);
}

/*-------------------------------------------------------------
 Name:      aqontrol_access_submit

 Purpose:   Save options
 Receive:   $_POST
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_access_submit() {
	$access['allow']	 			= $_POST['aqontrol_allow'];
	$access['except'] 				= trim($_POST['aqontrol_except'], "\t\n ");
	update_option('aqontrol_access', $access);
}
?>