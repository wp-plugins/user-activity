<?php
/*
Plugin Name: User Activity
Plugin URI: http://jonasnordstrom.se/plugins/
Description: List number of posts per user. You can limit the search by date, post type and user name.
Version: 0.9
Author: Jonas Nordström
Author URI: http://jonasnordstrom.se/
*/
/**
 * Copyright (c) 2013 Jonas Nordström, Burning Umbrellas AB. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

require_once( plugin_dir_path( __FILE__ ) . 'classes/class-bu-plugin-base.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/class-bu-user-activity.php' );
Bu_User_Activity::Init();

