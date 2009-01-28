<?php
/*
Plugin Name: AccessQontrol
Plugin URI: http://meandmymac.net/plugins/accessqontrol/
Description: To make your site private, or not...
Author: Arnan de Gans
Version: 1.4
Author URI: http://meandmymac.net/
*/

#---------------------------------------------------
# Load plugin and values
#---------------------------------------------------
register_activation_hook(__FILE__, 'aqontrol_activate');
register_deactivation_hook(__FILE__, 'aqontrol_deactivate');

add_action('template_redirect', 'aqontrol_header');
add_action('admin_menu', 'aqontrol_dashboard');

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
 Name:      aqontrol_dashboard

 Purpose:   Dashboard pages
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_dashboard() {
	add_submenu_page('tools.php', 'AccessQontrol', 'AccessQontrol', 'manage_options', 'accessqontrol', 'aqontrol_options');
}

/*-------------------------------------------------------------
 Name:      aqontrol_options

 Purpose:   Admin options page
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_options() {
	$aqontrol_config = get_option('aqontrol_config');
	$aqontrol_template = get_option('aqontrol_template');
	$aqontrol_tracker = get_option('aqontrol_tracker');
	$action = $_POST['aqontrol_action'];

	if ($action == 'update') { ?>
		<div id="message" class="updated fade"><p>Settings <strong>saved</strong></p></div>
	<?php } ?>

	<div class="wrap">
	  	<h2>AccessQontrol options</h2>
	  	<form method="post" action="tools.php?page=accessqontrol">
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

	    	<h3>Registration</h3>	    	
	
	    	<table class="form-table">
			<tr>
				<th scope="row" valign="top">Why</th>
				<td>For fun and as an experiment i would like to gather some information and develop a simple stats system for it. I would like to ask you to participate in this experiment. All it takes for you is to not opt-out. More information is found <a href="http://meandmymac.net/plugins/data-project/" title="http://meandmymac.net/plugins/data-project/ - New window" target="_blank">here</a>. Any questions can be directed to the <a href="http://forum.at.meandmymac.net/" title="http://forum.at.meandmymac.net/ - New window" target="_blank">forum</a>.</td>
				
			</tr>
			<tr>
				<th scope="row" valign="top">Participate</th>
				<td><input type="checkbox" name="aqontrol_register" <?php if($aqontrol_tracker['register'] == 'Y') { ?>checked="checked" <?php } ?> /> Allow Meandmymac.net to collect some data about the plugin usage and your blog.<br /><em>This includes your blog name, blog address, email address and a selection of triggered events as well as the name and version of this plugin.</em></td>
			</tr>
			<tr>
				<th scope="row" valign="top">Anonymously</th>
				<td><input type="checkbox" name="aqontrol_anonymous" <?php if($aqontrol_tracker['anonymous'] == 'Y') { ?>checked="checked" <?php } ?> /> Your blog name, blog address and email will not be send.</td>
			</tr>
			<tr>
				<th scope="row" valign="top">Agree</th>
				<td><strong>Upon activating the plugin you agree to the following:</strong>

				<br />- All gathered information, but not your email address, may be published or used in a statistical overview for reference purposes.
				<br />- You're free to opt-out or to make any to be gathered data anonymous at any time.
				<br />- All acquired information remains in my database and will not be sold, made public or otherwise spread to third parties.
				<br />- If you opt-out or go anonymous, all previously saved data will remain intact.
				<br />- Requests to remove your data or make everything you sent anonymous will not be granted unless there are pressing issues.
				<br />- Anonymously gathered data cannot be removed since it's anonymous.
				</td>
			</tr>
	    	</table>

			<p class="submit">
				<input type="hidden" name="action" value="update" />
				<input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
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

<?php	
	get_footer();
}

/*-------------------------------------------------------------
 Name:      aqontrol_send_data

 Purpose:   Register events at meandmymac.net's database
 Receive:   $action
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_send_data($action) {
	$aqontrol_tracker = get_option('aqontrol_tracker');
	
	// Prepare data
	$date			= date('U');
	$plugin			= 'AccessQontrol';
	$version		= '1.4';
	//$action -> pulled from function args
	
	// User choose anonymous?
	if($aqontrol_tracker['anonymous'] == 'Y') {
		$ident 		= 'Anonymous';
		$blogname 	= 'Anonymous';
		$blogurl	= 'Anonymous';
		$email		= 'Anonymous';
	} else {
		$ident 		= md5(get_option('siteurl'));
		$blogname	= get_option('blogname');
		$blogurl	= get_option('siteurl');
		$email		= get_option('admin_email');			
	}
	
	// Build array of data
	$post_data = array (
		'headers'	=> null,
		'body'		=> array(
			'ident'		=> $ident,
			'blogname' 	=> base64_encode($blogname),
			'blogurl'	=> base64_encode($blogurl),
			'email'		=> base64_encode($email),
			'date'		=> $date,
			'plugin'	=> $plugin,
			'version'	=> $version,
			'action'	=> $action,
		),
	);

	// Destination
	$url = 'http://stats.meandmymac.net/receiver.php';

	wp_remote_post($url, $post_data);
}

/*-------------------------------------------------------------
 Name:      aqontrol_activate

 Purpose:   Activation script
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_activate() {
	aqontrol_send_data('Activate');
}

/*-------------------------------------------------------------
 Name:      aqontrol_deactivate

 Purpose:   Deactivation script
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_deactivate() {
	aqontrol_send_data('Deactivate');
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
	
	if ( !$tracker = get_option('aqontrol_tracker') ) {
		$tracker['register']				= 'Y';
		$tracker['anonymous']				= 'N';
		update_option('aqontrol_tracker', $tracker);
	}
}

/*-------------------------------------------------------------
 Name:      aqontrol_options_submit

 Purpose:   Save options
 Receive:   $_POST
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_options_submit() {
	$buffer = get_option('acontrol_tracker');

	//options page
	$option['allow']	 			= $_POST['aqontrol_allow'];
	$option['except'] 				= trim($_POST['aqontrol_except'], "\t\n ");
	$template['title'] 				= htmlspecialchars(trim($_POST['aqontrol_title'], "\t\n "), ENT_QUOTES);
	$template['content'] 			= htmlspecialchars(trim($_POST['aqontrol_content'], "\t\n "), ENT_QUOTES);
	$tracker['register']			= $_POST['aqontrol_register'];
	$tracker['anonymous'] 			= $_POST['aqontrol_anonymous'];
	if($tracker['register'] == 'N' AND $buffer['register'] == 'Y') { aqontrol_send_data('Opt-out'); }
	update_option('aqontrol_config', $option);
	update_option('aqontrol_template', $template);
	update_option('aqontrol_tracker', $tracker);
}

/*-------------------------------------------------------------
 Name:      aqontrol_uninstall

 Purpose:   Delete the entire database table and remove the 
 			options on uninstall.
 Receive:   -none-
 Return:	-none-
-------------------------------------------------------------*/
function aqontrol_uninstall() {
	
	aqontrol_send_data('Uninstall');
	
	// Delete Option
	delete_option('aqontrol_config');
	delete_option('aqontrol_template');
	delete_option('aqontrol_tracker');

	// Deactivate Plugin
	$current = get_settings('active_plugins');
    array_splice($current, array_search( "accessqontrol.php", $current), 1);
	update_option('active_plugins', $current);
	do_action('deactivate_' . trim( $_GET['plugin'] ));

	die();
}

?>