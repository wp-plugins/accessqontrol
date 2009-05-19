<?php
/*-------------------------------------------------------------
 Name:      aqontrol_activate

 Purpose:   Activation script
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_activate() {
	global $wpdb;
	
	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";
	}

	$table_name = $wpdb->prefix . "accessqontrol";
	$sql = "CREATE TABLE ".$table_name." (
		`id` mediumint(8) unsigned NOT NULL auto_increment,
		`address` varchar(255) NOT NULL default '',
		`range` varchar(255) NOT NULL default '',
		`reason` varchar(255) NOT NULL default '',
		`redirect` varchar(255) NOT NULL default '',
		`thetime` int(15) NOT NULL default '0',
		`duration` int(15) NOT NULL default '0',
		PRIMARY KEY (`id`)
		) ".$charset_collate;

	mysql_query($sql);

	if ( !aqontrol_mysql_table_exists($table_name)) {
		aqontrol_mysql_warning($table_name);
	}
}

/*-------------------------------------------------------------
 Name:      aqontrol_deactivate

 Purpose:   Deactivation script
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function aqontrol_deactivate() {

}

/*-------------------------------------------------------------
 Name:      aqontrol_mysql_table_exists

 Purpose:   Check if the table exists in the database
 Receive:   -none-
 Return:	-none-
-------------------------------------------------------------*/
function aqontrol_mysql_table_exists($tablename) {
	global $wpdb;

	foreach ($wpdb->get_col("SHOW TABLES",0) as $table ) {
		if ($table == $tablename) {
			return true;
		}
	}
	return false;
}

/*-------------------------------------------------------------
 Name:      aqontrol_mysql_warning

 Purpose:   Database errors if things go wrong
 Receive:   $tablename
 Return:	-none-
-------------------------------------------------------------*/
function aqontrol_mysql_warning($tablename = null) {
	echo '<div class="updated"><h3>WARNING! There was an error with MySQL! One or more queries failed. The table '.$tablename.' does not exist. This means the database has not been created or only partly. Seek support at the <a href="http://forum.at.meandmymac.net">meandmymac.net support forums</a>. Please include any errors you saw or anything that might have caused this issue. This helps speed up the process greatly!</div>';
}

/*-------------------------------------------------------------
 Name:      aqontrol_uninstall

 Purpose:   Delete the entire database table and remove the 
 			options on uninstall.
 Receive:   -none-
 Return:	-none-
-------------------------------------------------------------*/
function aqontrol_uninstall() {
	global $wpdb;
	
	// Deactivate Plugin
	$current = get_settings('active_plugins');
    array_splice($current, array_search("accessqontrol.php", $current), 1);
	update_option('active_plugins', $current);
	do_action('deactivate_' . trim( $_GET['plugin'] ));

	// Drop MySQL Tables
	$SQL = "DROP TABLE `".$wpdb->prefix."accessqontrol`";
	mysql_query($SQL) or die("An unexpected error occured.<br />".mysql_error());

	// Delete Option
	delete_option('aqontrol_template');
	delete_option('aqontrol_access');

	wp_redirect(get_option('siteurl').'/wp-admin/plugins.php?deactivate=true');
}
?>