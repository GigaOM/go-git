<?php
/**
 * Plugin Name: Gigaom Git
 * Version: 0.1
 * Plugin URI: http://gigaom.com
 * Description: Handy-dandy plugin to expose git information to a WordPress install
 * Author: Matthew Batchelder
 * Author URI: http://gigaom.com
 * Tags: git
 * Tested up to: 3.4.2
 * License: GPL2
 */

if ( is_admin() )
{
	include_once __DIR__ . '/components/class-go-git.php';
	include_once __DIR__ . '/components/functions.php';

	go_git();
}//end if
