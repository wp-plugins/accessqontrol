<?php
/*
Plugin Name: AccessQontrol
Plugin URI: http://meandmymac.net/
Description: To make your site private, or not...
Author: Arnan de Gans
Version: 0.2
Author URI: http://meandmymac.net/plugins/accessqontrol/
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
	add_options_page('AccessQontrol', 'AccessQontrol', 'manage_options', basename(__FILE__), 'aqontrol_options_page');
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
?>
	<div class="wrap">
	  	<h2>AccessQontrol options</h2>
	  	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'];?>">
	    	<input type="hidden" name="aqontrol_submit_options" value="true" />
			<?php wp_nonce_field('update-options') ?>

	    	<h3>Main config</h3>
	    	
	    	<table class="form-table">
			<tr valign="top">
				<th scope="row">Make website private</th>
		        <td><select name="aqontrol_enable">';
			        <option value="yes" <?php if($aqontrol_config['enable'] == "yes") { echo 'selected'; } ?>>Yes</option>
			        <option value="no" <?php if($aqontrol_config['enable'] == "no") { echo 'selected'; } ?>>No</option>
				</select></td>
			</tr>
			<tr valign="top">
				<th scope="row">Block everyone</th>
		        <td><select name="aqontrol_block_registered">';
			        <option value="yes" <?php if($aqontrol_config['block_registered'] == "yes") { echo 'selected'; } ?>>Yes</option>
			        <option value="no" <?php if($aqontrol_config['block_registered'] == "no") { echo 'selected'; } ?>>No</option>
				</select> <em>The Dashboard remains available at all times!</em></td>
			</tr>
			<tr valign="top">
				<th scope="row">Except for these users</th>
				<td><input name="aqontrol_except" type="text" value="<?php echo $aqontrol_config['except'];?>" size="40" /> <em>Type login names, comma seperated. 'admin' always has access!</em></td>
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

	$buffer1 = explode(',', $aqontrol_config['except']);
	$buffer2 = array(1 => 'admin');
	$buffer = array_combine($buffer1, $buffer2);
	 
	if($user_ID == '' OR ($aqontrol_config['block_registered'] == 'yes' AND !in_array($userdata->user_login, $buffer))) {
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
		<div class="entry">
			<?php print $template_content; ?>
		</div>
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
		$option['enable'] 					= 'yes';
		$option['block_registered']			= 'no';
		$option['except']					= '';
		update_option('aqontrol_config', $option);
	}

	// If value not assigned insert default (upgrades)
	// "except" may be empty!!
	if (strlen($option['enable']) < 1 
	or strlen($option['block_registered']) < 1) {
		$option['enable'] 					= 'yes';
		$option['block_registered']			= 'no';
		$option['except']					= ''; // may be left empty!!
		update_option('aqontrol_config', $option);
	}
	
	// Template
	if ( !$template = get_option('aqontrol_template') ) {
		// Default Options
		$template['title'] 					= '<h2>You need to log in to enter this website</h2>';
		$template['content'] 				= 'If you wish to log in you need an account. Please contact the system administrator if you do not have an account.';
		update_option('aqontrol_template', $template);
	}

	// If value not assigned insert default (upgrades)
	if ( strlen($template['title']) < 1 or strlen($template['content'] ) < 1) {
		$template['title'] 					= '<h2>You need to log in to enter this website</h2>';
		$template['content'] 				= 'If you wish to log in you need an account. Please contact the system administrator if you do not have an account.';
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
	$option['enable']	 			= $_POST['aqontrol_enable'];
	$option['block_registered']		= $_POST['aqontrol_block_registered'];
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