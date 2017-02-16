<?php
/*
Plugin Name: SSO wordpress plugin

This plugin assumes that you have already set up and configured mod_auth_openidc
and that you're login page is at a location protected by that plugin. This also
autoprovisions accounts when needed.
*/
defined('ABSPATH') or die("No script kiddies please!");

function sso_logout(){
    $url = 'https://wordpress.openlmshost.com/wp-admin?logout=https%3A%2F%2Fwordpress.openlmshost.com%2Floggedout.html';
    wp_clear_auth_cookie();
    wp_redirect($url);
    exit();
    //var_dump( $current_user);     
}

function sso_login()
{  $email_address = explode("/",$_SERVER['REMOTE_USER'])[0];
   $user_number = explode("@" $email_address)[0];
   if ($email_address == '')
  {
    echo("Did not obtain user info from SSO server");
    return;
  }
  else if (null == username_exists($email_address))
  {
    //User hasn't been created yet, auto provision one
    $password = wp_generate_password( 12, true );
    $user_id = wpmu_create_user( $user_number, $password, $email_address );
    //wp_update_user(
    //array(
    //  'ID'       => $user_id,
    //  'nickname' => $email_address
    //));
    // autoprovisioned as contributers (this can change if needed)
    // $user = new WP_User( $user_id );
    //$user->set_role( 'contributor' );
    wpmu_create_blog( $user_number, '', '', $user_id , array( 'public' => 1 ), $current_site->id );
  }
  $user = get_user_by('login', $email_address);
  // Redirect URL //
  if ( !is_wp_error( $user ) )
  {
    wp_clear_auth_cookie();
    wp_set_current_user ( $user->ID );
    wp_set_auth_cookie  ( $user->ID );
    $redirect_to = user_admin_url();
    wp_safe_redirect( $redirect_to );
    exit();
  }
  else
  {
    echo("Failed login (even with autoprosioning)");
  }
}

function redirectUnauthenticated()
{
    $current_url = home_url(add_query_arg(array(),$wp->request));
    if (current_url == 'https://wordpress.openlmshost.com/wp-login.php')
	exit;
    if (is_user_logged_in() == false){
        wp_redirect( 'https://wordpress.openlmshost.com/wp-login.php');
	exit;
     }
}
add_action('get_header', 'redirectUnauthenticated');
add_action( 'login_header', 'sso_login' );
add_action('wp_logout','sso_logout');
?>
