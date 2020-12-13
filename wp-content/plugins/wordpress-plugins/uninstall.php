<?php
/**
 * documentation => https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
 * ACTIONS FOR THIS HOOK
 * Remove Options from {$wpdb->prefix}_options
 * Remove Tables from wpdb
 */ 
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

 
// drop a custom database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}email_list");