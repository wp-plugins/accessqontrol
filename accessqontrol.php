<?php
/*
Plugin Name: AccessQontrol
Plugin URI: http://meandmymac.net/plugins/accessqontrol/
Description: To make your site private, or not...
Author: Arnan de Gans
Version: 2.0
Author URI: http://meandmymac.net/
*/

#---------------------------------------------------
# Load other plugin files and configuration
#---------------------------------------------------
include_once(ABSPATH.'wp-content/plugins/accessqontrol/accessqontrol-setup.php');
include_once(ABSPATH.'wp-content/plugins/accessqontrol/accessqontrol-manage.php');
include_once(ABSPATH.'wp-content/plugins/accessqontrol/accessqontrol-functions.php');

register_activation_hook(__FILE__, 'aqontrol_activate');
register_deactivation_hook(__FILE__, 'aqontrol_deactivate');

aqontrol_check_config();
aqontrol_remove_expired();

add_action('template_redirect', 'aqontrol_header');
add_action('admin_menu', 'aqontrol_dashboard');

if(isset($_POST['aqontrol_submit'])) {
	add_action('init', 'aqontrol_insert_input');
}

if(isset($_POST['aqontrol_action'])) {
	add_action('init', 'aqontrol_request_action');
}

if(isset($_POST['aqontrol_submit_options'])) {
	add_action('init', 'aqontrol_options_submit');
}

if(isset($_POST['aqontrol_submit_access'])) {
	add_action('init', 'aqontrol_access_submit');
}

if(isset($_POST['aqontrol_uninstall'])) {
	add_action('init', 'aqontrol_uninstall');
}

$aqontrol_access 	= get_option('aqontrol_access');
$aqontrol_template 	= get_option('aqontrol_template');

if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
	$aqontrol_remote_ip = $_SERVER["REMOTE_ADDR"];
} else {
	$aqontrol_remote_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
}

/*-------------------------------------------------------------
 Name:      aqontrol_dashboard

 Purpose:   Dashboard pages
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_dashboard() {
	add_object_page('AccessQontrol', 'AccessQontrol', 'manage_options', 'accessqontrol', 'aqontrol_manage');
		add_submenu_page('accessqontrol', 'AccessQontrol > Bans', 'Manage Bans', 'manage_options', 'accessqontrol', 'aqontrol_manage');
		add_submenu_page('accessqontrol', 'AccessQontrol > Bans', 'Add Ban', 'manage_options', 'accessqontrol2', 'aqontrol_edit');
		add_submenu_page('accessqontrol', 'AccessQontrol > Site access', 'Site access', 'manage_options', 'accessqontrol3', 'aqontrol_site');

	add_options_page('AccessQontrol', 'AccessQontrol', 'manage_options', 'accessqontrol4', 'aqontrol_options');
}

/* -------------------------------------------------------------
 Name:      aqontrol_manage

 Purpose:   Admin management page
 Receive:   -none-
 Return:    -none-
------------------------------------------------------------- */
function aqontrol_manage() {
	global $wpdb, $aqontrol_remote_ip;

	$message = $_GET['message'];
	$specific = $_GET['specific'];
	if(isset($_POST['aqontrol_order'])) { $order = $_POST['aqontrol_order']; } else { $order = 'id ASC'; }
	?>
	
	<div class="wrap">
  		<h2>Manage Bans</h2>
 		
		<?php if ($message == 'new') { ?>
			<div id="message" class="updated fade"><p>Ban <strong>created</strong></p></div>
		<?php } else if ($message == 'deleted') { ?>
			<div id="message" class="updated fade"><p>Ban(s) <strong>deleted</strong></p></div>
		<?php } else if ($message == 'no_access') { ?>
			<div id="message" class="updated fade"><p>Action prohibited</p></div>
		<?php } ?>

		<form name="groups" id="post" method="post" action="admin.php?page=accessqontrol">
 
		<div class="tablenav">
			<div class="alignleft actions">
				<select name='aqontrol_action' id='cat' class='postform' >
			        <option value="">Bulk Actions</option>
			        <option value="delete">Delete</option>
				</select>
				<input type="submit" id="post-action-submit" value="Go" class="button-secondary" />
				Sort by <select name='aqontrol_order' id='cat' class='postform' >
			        <option value="id ASC" <?php if($order == "id ASC") { echo 'selected'; } ?>>Order of creation (ascending)</option>
			        <option value="id DESC" <?php if($order == "id DESC") { echo 'selected'; } ?>>Order of creation  (descending)</option>
			        <option value="thetime ASC" <?php if($order == "thetime ASC") { echo 'selected'; } ?>>Date set (ascending)</option>
			        <option value="thetime DESC" <?php if($order == "thetime DESC") { echo 'selected'; } ?>>Date set (descending)</option>
			        <option value="redirect ASC" <?php if($order == "redirect ASC") { echo 'selected'; } ?>>Redirect (A-Z)</option>
			        <option value="redirect DESC" <?php if($order == "redirect DESC") { echo 'selected'; } ?>>Redirect (Z-A)</option>
				</select>
				<input type="submit" id="post-query-submit" value="Sort" class="button-secondary" />
			</div>

			<br class="clear" />
		</div>

	   	<table class="widefat" style="margin-top: .5em">
  			<thead>
  				<tr>
					<th scope="col" class="check-column">&nbsp;</th>
			    	<th scope="col" width="25%">Victims</th>
				    <th scope="col">Reason</th>
					<th scope="col" width="20%">Date set</th>
					<th scope="col" width="20%">Expiry date</th>
				</tr>
  			</thead>
  			<tbody>
		<?php
		if(aqontrol_mysql_table_exists($wpdb->prefix.'accessqontrol')) {
			$bans = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix . "accessqontrol` ORDER BY ".$order);
			if ($bans) {
				foreach($bans as $ban) {
					if(is_numeric($ban->address) and is_numeric($ban->range)) { 
						$ban->address	= long2ip($ban->address); 
						$ban->range		= long2ip($ban->range);
					} else {
						$ban->address	= $ban->address;
						$ban->range		= $ban->range;
					}
					if($ban->address == $ban->range) {
						$final_address = $ban->address;
					} else {
						$final_address = $ban->address." - ".$ban->range;
					}
					$class = ('alternate' != $class) ? 'alternate' : ''; ?>
				  	<tr id='ban-<?php echo $ban->id; ?>' class='<?php echo $class; ?>'>
						<th scope="row" class="check-column"><input type="checkbox" name="bancheck[]" value="<?php echo $ban->id; ?>" /></th>
						<td><?php echo $final_address; ?></td>
						<td><?php echo $ban->reason; ?></td>
						<td><?php echo date("M d, Y H:i", $ban->thetime); ?></td>
						<td><?php if($ban->duration > 0) {echo date("M d, Y H:i", $ban->duration); } else { echo 'Never'; } ?></td>
				  	</tr>
	 			<?php } ?>
		 	<?php } else { ?>
				<tr><td colspan="5">No bans set.</td></tr>
			<?php }	?>
		<?php } else { ?>
			<tr id='no-id'><td scope="row" colspan="6"><span style="font-weight: bold; color: #f00;">There was an error locating the database table for AccessQontrol. Please deactivate and re-activate AccessQontrol from the plugin page!!<br />If this does not solve the issue please seek support at <a href="http://forum.at.meandmymac.net">http://forum.at.meandmymac.net</a></span></td></tr>
		<?php }	?>
 			</tbody>
		</table>
		</form>
	</div>
<?php
}

/* -------------------------------------------------------------
 Name:      aqontrol_edit

 Purpose:   Add a ban
 Receive:   -none-
 Return:    -none-
------------------------------------------------------------- */
function aqontrol_edit() {
	global $wpdb, $aqontrol_config, $aqontrol_remote_ip;

	$message = $_GET['message'];
	$specific = $_GET['specific'];
	?>
	
	<div class="wrap">
		<h2>Ban Someone</h2>
		
		<?php if ($message == 'error') { ?>
			<div id="message" class="updated fade"><p>An error occured: <strong><?php echo $specific; ?></strong></p></div>
		<?php } ?>

	  	<form method="post" action="admin.php?page=accessqontrol2">
	    	<table class="form-table">
		      	<tr valign="top">
			        <th scope="row">You:</th>
			        <td>IP: <?php echo $aqontrol_remote_ip; ?><br />Hostname: <?php echo gethostbyaddr($aqontrol_remote_ip); ?></td>
		      	</tr>
		      	<tr valign="top">
			        <th scope="row">Select method:</th>
			        <td><input name="type" type="radio" value="single" checked="checked" /> Single IP or hostname<br /><input name="type" type="radio" value="range" /> IP Range</td>
		      	</tr>
		      	<tr valign="top">
			        <th scope="row">Address/Range:</th>
			        <td><input name="address" type="text" size="20" tabindex="1"/> / <input name="range" type="text" size="20" tabindex="2" /> <em>(IP address or hostname)</em></td>
		      	</tr>
		      	<tr valign="top">
			        <th scope="row">Possibilities:</th>
			        <td>- Single IP/Hostname: fill in either a hostname or IP address in the first field.<br />
			        - IP Range: Put the starting IP address in the left and the ending IP address in the right field.
		      	</tr>
		      	<tr valign="top">
			        <th scope="row">Reason:</th>
			        <td><input name="reason" type="text" size="50" maxlength="255" tabindex="3" /> <em>(optional, shown to victim)</em></td>
		      	</tr>
		      	<tr valign="top">
			        <th scope="row">Redirect to (Full URL):</th>
			        <td><input name="redirect" type="text" size="50" maxlength="255" tabindex="4" /> <em>(optional)</em></td>
		      	</tr valign="top">
			        <th  scope="row">How long:</th>
			        <td><select name="timetype" tabindex="5">
						<option value="permanent">permanent</option>
						<option value="day">day(s)</option>
						<option value="week">week(s)</option>
					</select> <input name="timeset" type="text" size="6" tabindex="6" /><br /><em>Leave field empty when using permanent. Fill in a number higher than 0 when using another option!</em></td>
		      	</tr>
		      	<tr valign="top">
			        <th scope="row">Hints and tips:</th>
			        <td>
						- Banning hosts in the 10.x.x.x / 169.254.x.x / 172.16.x.x or 192.168.x.x range probably won't work.<br />
						- Banning by internet hostname might work unexpectedly and resulting in banning multiple people from the same ISP!<br />
						- Wildcards on IP addresses are allowed. Block 84.234. to block the whole 84.234.x.x range!<br />
						- An IP address <strong>always</strong> contains 4 parts with numbers no higher than 254 separated by a dot!<br />
						- If a ban does not seem to work try to find out if the person you're trying to ban doesn't use <a href="http://en.wikipedia.org/wiki/DHCP" target="_blank" title="Wikipedia - DHCP, new window">DHCP</a>.<br />
						- A temporary ban is automatically removed when it expires.<br />
						- For more questions please seek help at my <a href="http://forum.at.meandmymac.net/" target="_blank" title="Support, new window">support pages</a>.<br />
			        </td>
		      	</tr>
	    	</table>
	    	<p class="submit">
	      		<input type="submit" name="aqontrol_submit" class="button-primary" value="Proceed" tabindex="7" />
	      		<a href="admin.php?page=accessqontrol" class="button">Cancel</a>
	    	</p>
	  	</form>
	</div>
<?php
}

/* -------------------------------------------------------------
 Name:      aqontrol_site

 Purpose:   Control access to your site
 Receive:   -none-
 Return:    -none-
------------------------------------------------------------- */
function aqontrol_site() {
	$aqontrol_access = get_option('aqontrol_access');

	$action = $_POST['action'];

	if ($action == 'update') { ?>
		<div id="message" class="updated fade"><p>Settings <strong>saved</strong></p></div>
	<?php } ?>

	<div class="wrap">
  	
  		<h2>Website access</h2>
	  	<form method="post" action="admin.php?page=accessqontrol3">
	    	<input type="hidden" name="aqontrol_submit_access" value="true" />
	    	<input type="hidden" name="action" value="update" />
  	
	    	<table class="form-table">
			<tr valign="top">
				<th scope="row">Allow...</th>
		        <td><select name="aqontrol_allow">';
			        <option value="nobanned" <?php if($aqontrol_access['allow'] == "nobanned") { echo 'selected'; } ?>>Everyone except for banned people (default)</option>
			        <option value="everyone" <?php if($aqontrol_access['allow'] == "everyone") { echo 'selected'; } ?>>Everyone, even banned people</option>
			        <option value="registered" <?php if($aqontrol_access['allow'] == "registered") { echo 'selected'; } ?>>Logged in users only</option>
			        <option value="nobody" <?php if($aqontrol_access['allow'] == "nobody") { echo 'selected'; } ?>>Closed for everyone but 'admin'</option>
				</select> <em>The dashboard stays available at all times!</em></td>
			</tr>
			<tr valign="top">
				<th scope="row">Never block these users</th>
				<td><textarea name="aqontrol_except" type="text" cols="50" rows="4"><?php echo $aqontrol_access['except'];?></textarea><br /><em>Type login names, comma seperated (user1,user2,etc.). 'admin' cannot be blocked and does not need to be excluded!</em></td>
			</tr>
			</table>

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="Save Access Settings" />
			</p>
		</form>
	</div>
<?php
}

/*-------------------------------------------------------------
 Name:      aqontrol_options

 Purpose:   Admin options page
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_options() {
	$aqontrol_template = get_option('aqontrol_template');

	$action = $_POST['action'];

	if ($action == 'update') { ?>
		<div id="message" class="updated fade"><p>Settings <strong>saved</strong></p></div>
	<?php } ?>

	<div class="wrap">
	  	<h2>AccessQontrol options</h2>
	  	<form method="post" action="options-general.php?page=accessqontrol4">
	    	<input type="hidden" name="aqontrol_submit_options" value="true" />
	    	<input type="hidden" name="action" value="update" />
			
	    	<h3>Template</h3>
	    	
	    	<table class="form-table">
			<tr valign="top">
				<th scope="row">Template Title</th>
				<td><input name="aqontrol_title" type="text" value="<?php echo stripslashes($aqontrol_template['title']);?>" size="60" /> <em>HTML allowed.</em></td>
			</tr>
			<tr valign="top">
				<th scope="row">Template Content</th>
				<td><textarea name="aqontrol_content" cols="80" rows="5"><?php echo stripslashes($aqontrol_template['content']); ?></textarea><br />
				<em>Since this is a somewhat preformatted page less HTML is better. Stick to text as much as you can. Only use HTML for small markup things.<br />Available options: %login_link%. HTML allowed.<br />Options for banned people (Skipped for other settings): %until% %reason%.</em></td>
			</tr>
			</table>

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="Save Settings" />
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
?>