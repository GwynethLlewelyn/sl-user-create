<?php
/*
Copyright 2011 Gwyneth Llewelyn. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY GWYNETH LLEWELYN ``AS IS'' AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL GWYNETH LLEWELYN OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the
authors and should not be interpreted as representing official policies, either expressed
or implied, of Gwyneth Llewelyn.

---

Based on my own code for Online Status inSL, http://wordpress.org/extend/plugins/online-status-insl/

*/

require_once('../../../wp-config.php');
include_once(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . "functions.php");
require_once(ABSPATH . WPINC . '/registration.php');

// This gets called from a Second Life object when an avatar wants to register for the site
// Most of the data will come from the headers (e.g. avatar UUID) except for avatar_name

if (!$_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'])
{
	header(__("HTTP/1.0 405 Method Not Allowed", 'sl-user-create'));
	die(__("Request has to come from Second Life", 'sl-user-create'));
}

// now get the whole serialised array for this plugin's option
// we need it to check the simple signature
$settings = get_option('sl_user_create_settings');

if ($_REQUEST['signature'] != md5($_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'] . $settings['secret'] .":" . $settings['secret_number']))
{
	header("HTTP/1.0 403 Forbidden");
	die(__("Invalid signature", 'sl-user-create'));
}

if (!$_REQUEST['avatar_name'] || !$_REQUEST['avatar_key'])
{
	header(__("HTTP/1.0 405 Method Not Allowed", 'sl-user-create'));
	die(__("Registration requires a valid avatar name and key", 'sl-user-create'));
}

if (!function_exists("username_exists"))
{
	header(__("HTTP/1.0 404 Function Not Found", 'sl-user-create'));
	die(__("username_exists not found", 'sl-user-create'));
}

$avatarKey = $_REQUEST['avatar_key'];
$avatarDisplayName = sanitize_user(sanitise_avatarname($_REQUEST['avatar_name']));
$objectKey = $_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'];

$objects = get_option('sl_user_create_objects');

// see if this object is registered; if not, abort
if (!isset($objects[$objectKey]))
{
	header(__("HTTP/1.0 404 Not found", 'sl-user-create'));
	_e("This object has never been registered to this WordPress site before!", 'sl-user-create');
	printf(__("Please contact the owner of %s", 'sl-user-create'), home_url());
	die();
}

header("HTTP/1.0 200 OK");
header("Content-type: text/plain; charset=utf-8");

// register user with WordPress

$user_id = username_exists($avatarDisplayName);
if (!$user_id)
{
	$random_password = wp_generate_password(12, false);
	
	// Attempt to deal with the new avatar names, which have no last name
	$getDot = stripos($_REQUEST['avatar_name'], " ");
	$avatarFirstName = substr($_REQUEST['avatar_name'], 0, $getDot);
	$avatarLastName = substr($_REQUEST['avatar_name'], $getDot + 1); // may be empty
	if ($avatarLastName == 'Resident') $avatarLastName = "";
	$avatarFullName = $avatarFirstName . ($avatarLastName ? " " . $avatarLastName : "");
	
	// would be nice to get the avatar's secription from the old profile site; to-do for now
	
	$new_user_id = wp_insert_user(array(
		'user_login'	=> $avatarDisplayName,
		'user_pass'		=> $random_password,
		'user_nicename'	=> $avatarFullName,
		'nickname'		=> $avatarFullName,
		'displayname'	=> $avatarFullName,
		'first_name'	=> $avatarFirstName,
		'last_name'		=> $avatarLastName,
		'user_email'	=> $avatarDisplayName . "@secondlife.com",
		'description'	=> sprintf(__('Registered via "%s" (Shard: %s) - Object name: %s', 'sl-user-create'), $_SERVER['REMOTE_HOST'] ? $_SERVER['REMOTE_HOST'] : $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_SECONDLIFE_SHARD'], $_SERVER['HTTP_X_SECONDLIFE_OBJECT_NAME']),
		'user_url'		=> "http://my.secondlife.com/" . $avatarDisplayName,
		//'role'			=> 'Subscriber'
		)
	);
	
	if (is_wp_error($new_user_id)) // new user insertion failed? We don't know why...
	{
		echo $avatarKey . "|fail|" . sprintf(__('Registration to %s failed. Error: %s', 'sl-user-create'),  home_url(), $new_user_id->get_error_message());
	}
	else
	{
		// is this wp_mu or wp on network mode? Then we have to set source_domain
		
		if (is_multisite())
			add_user_meta($new_user_id, 'source_domain', home_url(), false);
	
		echo $avatarKey . "|ok|" . sprintf(__("Registration on %s successful! Your login is %s (user id %d) with password %s", 'sl-user-create'), home_url(), $avatarDisplayName, $new_user_id, $random_password); // this will be IMed

		$objects[$objectKey]["count"]++; // for statistics
	
		update_option('sl_user_create_objects', $objects);
	}
}
else // Registration failed because user_id was already present
{
	echo $avatarKey . "|fail|" . sprintf(__('Registration to %s failed. User already exists with user id %d', 'sl-user-create'), home_url(), $user_id);
}
?>