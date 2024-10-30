<?php

/*
Plugin Name: Kontera
Plugin URI: http://www.kontera.com
Description: This plugin will add your Kontera JavaScript code to your blog
Version: 2.2
Author: Kontera
Author URI: http://www.kontera.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
if (is_plugin_active("kontera")){
	die("You must deactivate and delete the previous version of the Kontera plugin!");
}
else {
	include_once "main.php";
	register_activation_hook(__FILE__, 'kontera_add_defaults');
	register_uninstall_hook(__FILE__, 'kontera_delete_plugin_options');
}

?>
