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
    wp_redirect($url);
    exit;
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
        wp_die('Did not obtain user info from SSO server');
    }

    $user = get_user_by('login', $user_number);

    if ($user == false) {
        // User hasn't been created yet, auto provision one
        $password = wp_generate_password(12, true);
        $user_id = wpmu_create_user($user_number, $password, $email_address);
        if ($user_id == false) {
            wp_die("Failed to create new user $user_number/$email_address");
        }

        $user = get_user_by('login', $user_number);
    }

    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);

    if (!$user->primary_blog) {
        $path = '/' . $user_number . 'blog';
        $result = wpmu_create_blog(DOMAIN_CURRENT_SITE, $path, 'Title', $user->ID, array('public' => 1), 1);
        if (is_wp_error($result)) {
            $msg = $result->get_error_message();
            wp_die("Failed to create new blog at $path for user $user_number: $msg");
        }
    }

    // Redirect URL
    if ($user->primary_blog) {
        $primary_url = get_blogaddress_by_id($user->primary_blog) . 'wp-admin/';
        if ($primary_url) {
            wp_redirect($primary_url);
            exit;
        }
    }
}

add_action('init', 'sso_login', 1);
add_action('wp_logout', 'sso_logout');
?>
