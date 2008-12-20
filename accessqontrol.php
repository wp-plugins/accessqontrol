<?php
/*
Plugin Name: AccessQontrol
Plugin URI: http://meandmymac.net/plugins/accessqontrol/
Description: To make your site private, or not...
Author: Arnan de Gans
Version: 1.3.1
Author URI: http://meandmymac.net/
*/

#---------------------------------------------------
# Load plugin and values
#---------------------------------------------------
add_action('template_redirect', 'aqontrol_header');
add_action('admin_menu', 'aqontrol_menu_pages');

if(isset($_POST['aqontrol_submit_options'])) {
	add_action('init', 'aqontrol_options_submit');
}
if(isset($_POST['aqontrol_uninstall'])) {
	add_action('init', 'aqontrol_uninstall');
}

aqontrol_check_config();
$aqontrol_config 	= get_option('aqontrol_config');
$aqontrol_template 	= get_option('aqontrol_template');

/*-------------------------------------------------------------
 Name:      aqontrol_menu_pages

 Purpose:   Dashboard pages
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_menu_pages() {
	add_submenu_page('tools.php', 'AccessQontrol', 'AccessQontrol', 'manage_options', 'accessqontrol', 'aqontrol_options_page');
}

/*-------------------------------------------------------------
 Name:      aqontrol_options_page

 Purpose:   Admin options page
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_options_page() {
	$aqontrol_config = get_option('aqontrol_config');
	$aqontrol_template = get_option('aqontrol_template');
	$action = $_POST['aqontrol_action'];

	if ($action == 'update') { ?>
		<div id="message" class="updated fade"><p>Settings <strong>saved</strong></p></div>
	<?php } ?>

	<div class="wrap">
	  	<h2>AccessQontrol options</h2>
	  	<form method="post" action="options-general.php?page=accessqontrol">
	    	<input type="hidden" name="aqontrol_submit_options" value="true" />
	    	<input type="hidden" name="aqontrol_action" value="update" />
			<?php wp_nonce_field('update-options') ?>

	    	<h3>Main config</h3>
	    	
	    	<table class="form-table">
			<tr valign="top">
				<th scope="row">Allow...</th>
		        <td><select name="aqontrol_allow">';
			        <option value="everyone" <?php if($aqontrol_config['allow'] == "everyone") { echo 'selected'; } ?>>everyone (default, your site is open)</option>
			        <option value="registered" <?php if($aqontrol_config['allow'] == "registered") { echo 'selected'; } ?>>registered people only (members only)</option>
			        <option value="nobody" <?php if($aqontrol_config['allow'] == "nobody") { echo 'selected'; } ?>>nobody (just you are allowed on, maintanance mode)</option>
				</select> <em>The dashboard stays available at all times!</em></td>
			</tr>
			<tr valign="top">
				<th scope="row">Never block these users</th>
				<td><textarea name="aqontrol_except" type="text" cols="50" rows="4"><?php echo $aqontrol_config['except'];?></textarea><br /><em>Type login names, comma seperated (user1,user2,etc.). 'admin' cannot be blocked and does not need to be excluded!</em></td>
			</tr>
			</table>

	    	<h3>Template</h3>
	    	
	    	<table class="form-table">
			<tr valign="top">
				<th scope="row">Template Title</th>
				<td><input name="aqontrol_title" type="text" value="<?php echo stripslashes($aqontrol_template['title']);?>" size="80" /> <em>HTML allowed.</em></td>
			</tr>
			<tr valign="top">
				<th scope="row">Template Content</th>
				<td><textarea name="aqontrol_content" cols="80" rows="5"><?php echo stripslashes($aqontrol_template['content']); ?></textarea><br />
				<em>Available options: %login_link%. HTML allowed.</em></td>
			</tr>
			</table>

			<p class="submit">
				<input type="hidden" name="action" value="update" />
				<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	  	
	  	<h2>AccessQontrol Uninstall</h2>

	  	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'];?>">
	    	<input type="hidden" name="aqontrol_uninstall" value="true" />
	    	<table class="form-table">
			<tr valign="top">
		  		<th scope="row">AccessQontrol adds some options to the database. When you disable the plugin these will not be deleted. To delete the options use the button below.</th>
			</tr>
			<tr valign="top">
		  		<th scope="row"><b style="color: #f00;">WARNING! -- This process is irreversible and will delete ALL saved options related to AccessQontrol!</b></th>
			</tr>
			</table>
			
	  		<p class="submit">
		    	<input onclick="return confirm('You are about to uninstall the AccessQontrol plugin.\n\nThis leaves your website open and exposed!\n\nAll related options will be lost!\n\'OK\' to continue, \'Cancel\' to stop.')" type="submit" name="Submit" value="Uninstall Plugin" />
	  		</p>
	  	</form>
	</div>
<?php
}	

/* -------------------------------------------------------------
 Name:      aqontrol_header

 Purpose:   Checks if the website is private or not and if users 
 			are allowed on.
 Receive:   -none-
 Return:	-none-
------------------------------------------------------------- */
function aqontrol_header() {
	global $aqontrol_config, $user_ID, $userdata;

	get_currentuserinfo();

	if(strlen($aqontrol_config['except']) != 0) {
		$buffer1 = explode(',', $aqontrol_config['except']);
		$buffer2 = array(0 => 'admin');
		$buffer = array_merge($buffer1, $buffer2);
	} else { 
		$buffer = array(0 => 'admin');
	}

	if((($aqontrol_config['allow'] == 'registered' AND $user_ID == '') OR $aqontrol_config['allow'] == 'nobody') AND !in_array($userdata->user_login, $buffer)) {
		aqontrol_login_template();
		exit;
	} 
}

/* -------------------------------------------------------------
 Name:      aqontrol_login_template

 Purpose:   Shows to people when site is restricted.
 Receive:   -None-
 Return:	-None-
------------------------------------------------------------- */
function aqontrol_login_template() {
	global $aqontrol_template;
	
	get_header();
	
	$template_title 	= stripslashes(html_entity_decode($aqontrol_template['title'], ENT_QUOTES));
	$template_content 	= stripslashes(html_entity_decode($aqontrol_template['content'], ENT_QUOTES));
	$template_content 	= str_replace('%login_link%', '<a href="'.get_option('siteurl').'/wp-login.php">Login form</a>', $template_content);
?>

	<div id="content" class="widecolumn">
		<?php print $template_title; ?>
		<?php print $template_content; ?>
	</div>
	
	<?php get_footer(); ?>
<?php
}

/*-------------------------------------------------------------
 Name:      aqontrol_check_config

 Purpose:   Create or update the options
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_check_config() {
	// Configuration
	if ( !$option = get_option('aqontrol_config') ) {
		// Default Options
		$option['allow'] 					= 'everyone';
		$option['except']					= '';
		update_option('aqontrol_config', $option);
	}

	// If value not assigned insert default (upgrades)
	// "except" may be empty!!
	if (strlen($option['allow']) < 1) {
		$option['allow'] 					= 'everyone';
		$option['except']					= ''; // may be left empty!!
		update_option('aqontrol_config', $option);
	}
	
	// Template
	if ( !$template = get_option('aqontrol_template') ) {
		// Default Options
		$template['title'] 					= '<h2>You need to log in to enter this website</h2>';
		$template['content'] 				= '<div class="entry">If you wish to log in you need an account. Please contact the system administrator if you do not have an account.</div>';
		update_option('aqontrol_template', $template);
	}

	// If value not assigned insert default (upgrades)
	if ( strlen($template['title']) < 1 or strlen($template['content'] ) < 1) {
		$template['title'] 					= '<h2>You need to log in to enter this website</h2>';
		$template['content'] 				= '<div class="entry">If you wish to log in you need an account. Please contact the system administrator if you do not have an account.</div>';
		update_option('aqontrol_template', $template);
	}
}

/*-------------------------------------------------------------
 Name:      aqontrol_options_submit

 Purpose:   Save options
 Receive:   $_POST
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_options_submit() {
	//options page
	$option['allow']	 			= $_POST['aqontrol_allow'];
	$option['except'] 				= trim($_POST['aqontrol_except'], "\t\n ");
	$template['title'] 				= htmlspecialchars(trim($_POST['aqontrol_title'], "\t\n "), ENT_QUOTES);
	$template['content'] 			= htmlspecialchars(trim($_POST['aqontrol_content'], "\t\n "), ENT_QUOTES);
	update_option('aqontrol_config', $option);
	update_option('aqontrol_template', $template);
}

/*-------------------------------------------------------------
 Name:      aqontrol_uninstall

 Purpose:   Delete the entire database table and remove the 
 			options on uninstall.
 Receive:   -none-
 Return:	-none-
-------------------------------------------------------------*/
function aqontrol_uninstall() {
	// Delete Option
	delete_option('aqontrol_config');
	delete_option('aqontrol_template');

	// Deactivate Plugin
	$current = get_settings('active_plugins');
    array_splice($current, array_search( "accessqontrol.php", $current), 1);
	update_option('active_plugins', $current);
	do_action('deactivate_' . trim( $_GET['plugin'] ));

	die();
}

?>