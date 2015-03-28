<?php
/*
Plugin Name: Cache Buddy
Plugin URI:
Description: Minimizes the situations in which logged-in users appear logged-in to WordPress, which increases the cacheability of your site.
Version: 0.2.1-beta-2
Author:      Mark Jaquith
Author URI:  http://markjaquith.com/
License:     GPLv2+
Text Domain: cache-buddy
Domain Path: /languages
*/

/**
 * Copyright (c) 2015 Mark Jaquith (email : mark@jaquith.me)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

defined( 'WPINC' ) or die;

include( dirname( __FILE__ ) . '/lib/requirements-check.php' );

$cache_buddy_requirements_check = new Cache_Buddy_Requirements_Check( array(
	'title' => 'Cache Buddy',
	'php'   => '5.3',
	'wp'    => '4.0',
	'file'  => __FILE__,
));

if ( $cache_buddy_requirements_check->passes() ) {
	// Pull in the plugin classes and initialize
	include( dirname( __FILE__ ) . '/lib/wp-stack-plugin.php' );
	include( dirname( __FILE__ ) . '/classes/plugin.php' );
	Cache_Buddy_Plugin::start( __FILE__ );
}

unset( $cache_buddy_requirements_check );
