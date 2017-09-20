<?php 
if( !defined('WP_UNINSTALL_PLUGIN') )
	exit;

global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'sites' );
delete_option('telegram_bot_id');
delete_option('telegram_chat_id');
