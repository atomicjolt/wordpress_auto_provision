<?php
/**
Plugin Name: Autoprovision and login wordpress plugin

Copyright (C) 2017 Atomic Jolt

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU Affero General Public License as published by the Free
Software Foundation, either version 3 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License along
with this program.  If not, see <http://www.gnu.org/licenses/>.


This plugin assumes that you have already set up and configured mod_auth_openidc
and that you're login page is at a location protected by that plugin. This also
autoprovisions accounts when needed.
*/
defined('ABSPATH') or die("No script kiddies please!");

function sso_logout()
{
    $url = 'https://wordpress.openlmshost.com/wp-admin?logout=https%3A%2F%2Fwordpress.openlmshost.com%2Floggedout.html';
    wp_clear_auth_cookie();
    wp_redirect($url);
    exit();
}

function sso_login()
{
    if (is_user_logged_in()) {
        return;
    }

    /*
    $email_address = explode("/", $_SERVER['REMOTE_USER'])[0];
    $user_number = explode("@", $email_address)[0];
    /*/
    $email_address = 'testmonkey@example.com';
    $user_number = 9999;
    //*/

    if ($email_address == '') {
        echo("Did not obtain user info from SSO server");
        return;
    }

    if (null == username_exists($user_number)) {
        // User hasn't been created yet, auto provision one
        $password = wp_generate_password(12, true);
        $user_id = wpmu_create_user($user_number, $password, $email_address);
        $domain = get_home_url(1, '', null) . '/';
        $domain = str_replace('http://', '', $domain);
        echo $domain;
        $path = '/' . $user_number . 'blog';
        $result = wpmu_create_blog($domain, $path, 'Title', $user_id, array('public' => 1), 1);
        if (is_wp_error($result)) {
            echo $result->get_error_message();
        }
    }

    $user = get_user_by('email', $email_address);

    if ($user != false) {
        clean_user_cache($user->ID);
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        // Redirect URL
        $user_info = get_userdata($user->ID);
        if ($user_info->primary_blog) {
            $primary_url = get_blogaddress_by_id($user_info->primary_blog) . 'wp-admin/';
            if ($primary_url) {
                wp_redirect($primary_url);
                die();
            }
        }
    } else {
        echo("Failed login (even with autoprovisioning)");
    }
}

add_action('init', 'sso_login', 1);
add_action('wp_logout', 'sso_logout');
?>
