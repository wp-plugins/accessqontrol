<?php
/* -------------------------------------------------------------
 Name:      aqontrol_header

 Purpose:   Checks if the website is private or not and if users 
 			are allowed on.
 Receive:   -none-
 Return:	-none-
------------------------------------------------------------- */
function aqontrol_header() {
	global $wpdb, $aqontrol_access, $user_ID, $userdata;

	if (strlen($aqontrol_access['except']) != 0) {
		$buffer0 = str_ireplace(" ", "", $aqontrol_access['except']);
		$buffer1 = explode(',', $buffer0);
		$buffer2 = array(0 => 'admin');
		$buffer = array_merge($buffer1, $buffer2);
	} else { 
		$buffer = array(0 => 'admin');
	}

	if ($aqontrol_access['allow'] == 'nobanned' AND aqontrol_check_bans() == true AND !in_array($userdata->user_login, $buffer)) {
		aqontrol_redirect_template();
		exit;
	} else if ($aqontrol_access['allow'] == 'registered' AND ($user_ID == '' OR aqontrol_check_bans() == true)) {
		aqontrol_redirect_template();
		exit;		
	} else if ($aqontrol_access['allow'] == 'nobody' AND !in_array($userdata->user_login, $buffer)) {
		aqontrol_redirect_template();
		exit;
	}
}

/* -------------------------------------------------------------
 Name:      aqontrol_check_bans

 Purpose:   Sees if you are banned
 Receive:   -None-
 Return:	Boolean
------------------------------------------------------------- */
function aqontrol_check_bans() {
	global $wpdb;
	
	$aqontrol_remote_ip = aqontrol_remote_ip();
	$remote_ip_long = sprintf("%u", ip2long($aqontrol_remote_ip));
	$remote_addr 	= gethostbyaddr($aqontrol_remote_ip);

	$banned = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."accessqontrol` ORDER BY `id` ASC");

	foreach ($banned AS $ban) {
		if (strlen($ban->reason) > 0) {
			$banned_reason = $ban->reason;	
		} else {
			$banned_reason = "Not given";
		}
		
		if ($ban->timespan > 0) { 
			$banned_until = date("l, j F Y", $ban->timespan);
		} else {
			$banned_until = "indefinite";
		}

		if ($remote_ip_long >= $ban->address AND $remote_ip_long <= $ban->range) {
			return true;	
		} else {
			return false;
		}
	}		
}

/* -------------------------------------------------------------
 Name:      aqontrol_remote_ip

 Purpose:   Determine ones remote IP even if it's via a proxy
 Receive:   -None-
 Return:	-None-
------------------------------------------------------------- */
function aqontrol_remote_ip() {
		
	if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		$remote_ip = $_SERVER["REMOTE_ADDR"];
	} else {
		$buffer = split(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
		$buffer = array_reverse($buffer);
		$remote_ip = $buffer[0];
	}
	return $remote_ip;
}

/* -------------------------------------------------------------
 Name:      aqontrol_redirect_template

 Purpose:   Shows to people when site is restricted.
 Receive:   $type, $banned_until, $banned_reason
 Return:	-None-
------------------------------------------------------------- */
function aqontrol_redirect_template($type = 'everyone', $banned_until = null, $banned_reason = null) {
	global $aqontrol_template;
	
	$template_title 	= stripslashes(html_entity_decode($aqontrol_template['title'], ENT_QUOTES));
	$template_content 	= stripslashes(html_entity_decode($aqontrol_template['content'], ENT_QUOTES));
	$template_content 	= str_replace('%login_link%', '<a href="'.get_option('siteurl').'/wp-login.php">Login form</a>', $template_content);
	if ($type == "nobanned") {
		$template_content 	= str_replace('%reason%', $banned_reason, $template_content);
		$template_content 	= str_replace('%until%', $banned_until, $template_content);
	} else { 
		$template_content 	= str_replace('%reason%', '', $template_content);
		$template_content 	= str_replace('%until%', '', $template_content);
	}

?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php print strip_tags($template_title); ?></title>
		<link rel="stylesheet" href="http://meandmymac.net/wp-admin/css/install.css" type="text/css" />
	</head>
	<body id="error-page">
		<?php print $template_title; ?>
		<?php print $template_content; ?>	 
	</body>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </body>
	</html>
<?php	
}

/*-------------------------------------------------------------
 Name:      aqontrol_check_config

 Purpose:   Create or update the options
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_check_config() {
	if ( !$access = get_option('aqontrol_access') ) {
		$access['allow'] 					= 'nobanned';
		$access['except']					= '';
		update_option('aqontrol_access', $access);
	}
	
	if ( !$template = get_option('aqontrol_template') ) {
		$template['title'] 					= '<h2>Access Prohibited</h2>';
		$template['content'] 				= 'If you wish to access this website you need an account. Please contact the system administrator if you do not have an account.';
		update_option('aqontrol_template', $template);
	}
}

/*-------------------------------------------------------------
 Name:      aqontrol_return

 Purpose:   Redirect to various pages
 Receive:   $action, $arg
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_return($action, $arg = null) {
	switch($action) {
		case "new" :
			wp_redirect('admin.php?page=accessqontrol&message=new');
		break;

		case "no_access" :
			wp_redirect('admin.php?page=accessqontrol&message=no_access');
		break;

		case "delete" :
			wp_redirect('admin.php?page=accessqontrol&message=deleted');
		break;

		case "error" :
			wp_redirect('admin.php?page=accessqontrol2&message=error&specific='.$arg[0]);
		break;
	}
}

/*-------------------------------------------------------------
 Name:      aqontrol_widget_dashboard_init

 Purpose:   Add a WordPress dashboard widget
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_widget_dashboard_init() {
	wp_add_dashboard_widget( 'meandmymac_rss_widget', 'Meandmymac.net RSS Feed', 'meandmymac_rss_widget' );
}

/*-------------------------------------------------------------
 Name:      meandmymac_rss_widget

 Purpose:   Shows the Meandmymac RSS feed on the dashboard
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
if(!function_exists('meandmymac_rss_widget')) {
	function meandmymac_rss_widget() {
		?>
			<style type="text/css" media="screen">
			#meandmymac_rss_widget .text-wrap {
				padding-top: 5px;
				margin: 0.5em;
				display: block;
			}
			#meandmymac_rss_widget .text-wrap .rsserror {
				color: #f00;
				border: none;
			}
			</style>
		<?php meandmymac_rss('http://meandmymac.net/feed/');
	}
}

/*-------------------------------------------------------------
 Name:      meandmymac_rss

 Purpose:   A very simple RSS parser for Meandmymac.net
 Receive:   $rss, $count
 Return:    -none-
-------------------------------------------------------------*/
if(!function_exists('meandmymac_rss')) {
	function meandmymac_rss($rss, $count = 10) {
		if ( is_string( $rss ) ) {
			require_once(ABSPATH . WPINC . '/rss.php');
			if ( !$rss = fetch_rss($rss) ) {
				echo '<div class="text-wrap"><span class="rsserror">The feed could not be fetched, try again later!</span></div>';
				return;
			}
		}

		if ( is_array( $rss->items ) && !empty( $rss->items ) ) {
			$rss->items = array_slice($rss->items, 0, $count);
			foreach ( (array) $rss->items as $item ) {
				while ( strstr($item['link'], 'http') != $item['link'] ) {
					$item['link'] = substr($item['link'], 1);
				}

				$link = clean_url(strip_tags($item['link']));
				$desc = attribute_escape(strip_tags( $item['description']));
				$title = attribute_escape(strip_tags($item['title']));
				if ( empty($title) ) {
					$title = __('Untitled');
				}
				
				if ( empty($link) ) {
					$link = "#";
				}

				if (isset($item['pubdate'])) {
					$date = $item['pubdate'];
				} elseif (isset($item['published'])) {
					$date = $item['published'];
				}

				if ($date) {
					if ($date_stamp = strtotime($date)) {
						$date = date_i18n( get_option('date_format'), $date_stamp);
					} else {
						$date = '';
					}
				}
				echo '<div class="text-wrap"><a href="'.$link.'" target="_blank">'.$title.'</a> on '.$date.'</div>';
			}
		} else {
			echo '<div class="text-wrap"><span class="rsserror">The feed appears to be invalid or corrupt!</span></div>';
		}
		return;
	}
}
?>