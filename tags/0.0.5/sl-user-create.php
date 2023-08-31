<?php
/*
Plugin Name: SL User Create
Plugin URI: http://gwynethllewelyn.net/sl-user-create/
Version: 0.0.5
License: Simplified BSD License
Author: Gwyneth Llewelyn
Author URI: http://gwynethllewelyn.net/
Description: Allows Second Life® users to get automatically registered to a WordPress site by touching an object with a special script. 

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

include_once(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . "functions.php");

if (!class_exists('WP_Http'))
	include_once(ABSPATH . WPINC . '/class-http.php');

// Setting up the panels
function sl_user_create_get_settings_page_tabs() {
	 $tabs = array(
			'main'			=> __('Options', 'sl-user-create'),
			'objects'			=> __('Objects', 'sl-user-create'),
			'instructions'	=> __('Instructions', 'sl-user-create')
	 );
	 return $tabs;
} // end sl_user_create_get_settings_page_tabs()

// Set up styling for page tabs
function sl_user_create_admin_options_page_tabs( $current = 'main' ) {
	 if ( isset ( $_GET['tab'] ) ) :
			$current = $_GET['tab'];
	 else:
			$current = 'main';
	 endif;
	 $tabs = sl_user_create_get_settings_page_tabs();
	 $links = array();
	 foreach( $tabs as $tab => $name ) :
			if ( $tab == $current ) :
				  $links[] = "<a class='nav-tab nav-tab-active' href='?page=sl_user_create&amp;tab=$tab'>$name</a>";
			else :
				  $links[] = "<a class='nav-tab' href='?page=sl_user_create&amp;tab=$tab'>$name</a>";
			endif;
	 endforeach;
	 echo '<div id="icon-themes" class="icon32"><br /></div>';
	 echo '<h2 class="nav-tab-wrapper">';
	 foreach ( $links as $link )
			echo $link;
	 echo '</h2>';
} // end sl_user_create_admin_options_page_tabs()

function sl_user_create_admin_menu_options()
{
	add_options_page(__('SL User Create', 'sl-user-create'), __('SL User Create', 'sl-user-create'), 1,
		'sl_user_create', 'sl_user_create_menu');
}

function sl_user_create_menu()
{
?>
<div class="wrap">
<?php sl_user_create_admin_options_page_tabs(); ?>
<?php $tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'main' ); ?>
<h2><?php _e('SL User Create', 'sl-user-create'); ?></h2>

<?php 
	if ($tab == 'main') 
	{ 
?>
<form method="post" action="options.php">
<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Settings', 'sl-user-create'); ?>" />
<?php
	// automated settings. This uses the "modern" way of setting things,
	// but for now it only applies to the secrets

	settings_fields('sl_user_create_settings');
	do_settings_sections('sl_user_create');
?>
<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Settings', 'sl-user-create'); ?>" />
</form>
<?php
	}
	else if ($tab == 'objects')
	{
		$objects = get_option('sl_user_create_objects');

		// Check if we have to delete some of the registration objects
		if ($_POST["sl_user_create_form"])
		{
			check_admin_referer('delete-online-registration-objects');

			//$newSettings = $settings;
							
			// loop through settings to delete; we have objectKeys for each
			
			$statusMessage = ""; // add to this string as we find objects to delete	

			foreach ($objects as $registrationObjects)
			{
				if (isset($_POST["deletedRegistrationObjects"]) && in_array($registrationObjects["objectKey"], $_POST["deletedRegistrationObjects"]))
				{
					$statusMessage .= __("Deleting registration object: ", 'sl-user-create') . $registrationObjects["objectName"] . 
						" (" . $registrationObjects["objectKey"] . "), " .
						__("Owned by ", 'sl-user-create') . $registrationObjects["avatarDisplayName"] . ", " .
						__("Location: ", 'sl-user-create') . $registrationObjects["objectRegion"] .
						"<br />\n";
					unset($objects[$registrationObjects["objectKey"]]);
				}
				
				// send llDie() if box checked
				if (isset($_POST["die"]) && in_array($registrationObjects["objectKey"], $_POST["die"]))
				{
					$statusMessage .= __("Sending llDie() to registration object: ", 'sl-user-create') . $registrationObjects["objectName"] . 
						" (" . $registrationObjects["objectKey"] . "), " .
						__("Owned by ", 'sl-user-create') . $registrationObjects["avatarDisplayName"] . ", " .
						__("Location: ", 'sl-user-create') . $registrationObjects["objectRegion"];
											
					// call the PermURL for this object

					$body = array('command' => 'die');
					
					$url = $registrationObjects['PermURL'];
					$request = new WP_Http;
					$result = $request->request($url, 
						array('method' => 'POST', 'body' => $body));
					// test $result['response'] and if OK do something with $result['body']
					if ($result['response']['code'] == 200)
					{
						$statusMessage .= "(OK - " . $result['body']. ")";
						unset($objects[$registrationObjects["objectKey"]]); // remove it from list
					}
					else
					{
						$statusMessage .= "(Failed - " . $result['body']. ")";
					}
				}
			}
			/*
			$statusMessage .= __("Dumping original settings: <pre style=\"border: 1px solid #000; overflow: auto; margin: 0.5em;\">") .
				print_r($objects, true) . "</pre><br />\n";
			*/
			// update options with new settings; gets serialized automatically
			if (!update_option('sl_user_create_objects', $objects))
				$statusMessage .= __("<strong>Not saved!!</strong><br \>\n", 'sl-user-create');
			
			// emit "updated" class showing we have deleted some things
			if ($statusMessage)
			{
?>
			<div id="message-updated" class="updated"><p><?php _e("Online registration objects <strong>deleted</strong>", 'sl-user-create'); ?><br /><br /><?php echo $statusMessage; ?>
			</p></div>
<?php
			} // endif ($statusMessage)
		} // endif ($_POST["sl_user_create_form"])
 
		if (is_array($objects) && count($objects) > 0)
		{
?>
<h2><?php _e("Current registration objects being tracked", 'sl-user-create'); ?>:</h2>
<form method='post' id="sl_user_create_form">
<?php wp_nonce_field("delete-online-registration-objects"); ?>
<!--<input type="hidden" name="action" value="delete-online-registration-objects">-->
<table class="wp-list-table widefat fixed" cellspacing="0">
	<thead>
		<tr>
			<th scope='col' class='manage-column column-objname'><?php _e("Object Name", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-objkey'><?php _e("Object Key", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-obversion'><?php _e("Object Version", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-location'><?php _e("Location", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-permurl'><?php _e("PermURL", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-avatarname'><?php _e("Avatar Owner Name", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-avatarkey'><?php _e("Avatar Owner Key", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-avatarcount'><?php _e("# avatars registered", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-date'><?php _e("Last time checked", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-cb check-column'><?php _e("Del?", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-die check-column'><?php _e("Die?", 'sl-user-create'); ?></th>
		</tr>
	</thead>
	<tbody>
<?php
		foreach ($objects as $oneRegObject)
		{
	?>
	<tr class="format-default <?php echo ($alternate ? "" : "alternate"); $alternate = !$alternate; ?>">
		<td><?php echo $oneRegObject["objectName"]; ?></td>
		<td><?php echo $oneRegObject["objectKey"]; ?></td>
		<td><?php echo $oneRegObject["objectVersion"]; ?></td>
		<td>
<?php 
			// parse name of the region and coordinates to create a link to maps.secondlife.com
			$regionName = substr($oneRegObject["objectRegion"], 0, strpos($oneRegObject["objectRegion"], "(") - 1);
			$coords = trim($oneRegObject["objectLocalPosition"], "() \t\n\r");
			$xyz = explode(",", $coords);
			
			printf('<a href="http://maps.secondlife.com/secondlife/%s/%F/%F/%F?title=%s&amp;msg=%s&amp;img=%s" target="_blank">%s (%d,%d,%d)</a>',
				$regionName, $xyz[0], $xyz[1], $xyz[2], 
				rawurlencode($oneRegObject["objectName"]),
				rawurlencode(__("Registration object for ", 'sl-user-create') . home_url()),
				rawurlencode("http://s.wordpress.org/about/images/logos/wordpress-logo-stacked-rgb.png"),
				$regionName, $xyz[0], $xyz[1], $xyz[2]);
?>
		</td>
		<td><?php echo $oneRegObject["PermURL"]; ?></td>
		<td><?php echo $oneRegObject["avatarDisplayName"]; ?></td>
		<td><?php echo $oneRegObject["avatarKey"]; ?></td>
		<td><?php echo $oneRegObject["count"]; ?></td>

		<td class="date column-date"><?php echo date(__("Y M j H:i:s", 'sl-user-create'), $oneRegObject["timeStamp"]); ?></td>
		<td><input type="checkbox" name="deletedRegistrationObjects[]" value="<?php echo $oneRegObject["objectKey"]; ?>" /></td>
		<td><input type="checkbox" name="die[]" value="<?php echo $oneRegObject["objectKey"]; ?>" /></td>
	</tr>
<?		
		} // end foreach	
?>
	</tbody>
</table>
<input type='submit' class='button-primary alignleft' name='sl_user_create_form' value='<?php _e('Delete/Die', 'sl-user-create'); ?>' />
</form>
<?php
	} // if settings not empty
	else
	{
?>
<br /><br /><strong>
<?php
	_e("No registration objects are being tracked.", 'sl-user-create');
?></strong>
<?php
	} // end if (count($settings['registrationObjects']) > 0)
	} // end tab == 'objects'
	else if ($tab == 'instructions')
	{
		settings_fields('sl_user_create-instructions');
		do_settings_sections('sl_user_create-instructions');
	}
	else {
?>
<h3><?php _e('Error', 'sl-user-create'); ?></h3>

<?php _e('This page should have never been displayed!', 'sl-user-create'); ?>

<?php
	}
} // end sl_user_create_menu()
	
// Add a settings group, which hopefully makes it easier to delete later on
function sl_user_create_register_settings()
{
	register_setting('sl_user_create_settings', 'sl_user_create_settings', 'sl_user_create_validate');
	// it's a huge serialised array for now, stored as a WP option in the database; 
	//	 if performance drops, this might change in the future
	
	add_settings_section( 'sl_user_create_main_section', __('Options', 'sl-user-create'), 'sl_user_create_main_section_text', 'sl_user_create');
	add_settings_field( 'secret', __('Secret string', 'sl-user-create'), 'sl_user_create_secret', 'sl_user_create', 'sl_user_create_main_section');
	add_settings_field( 'secret_number', __('Secret number', 'sl-user-create'), 'sl_user_create_secret_number', 'sl_user_create', 'sl_user_create_main_section');
	add_settings_field( 'text_area', __('LSL Script', 'sl-user-create'), 'sl_user_create_text_area', 'sl_user_create', 'sl_user_create_main_section');
	
	// Instructions
	add_settings_section( 'sl_user_create_instructions_section', __('Instructions', 'sl-user-create'), 'sl_user_create_instructions_section_text', 'sl_user_create-instructions');

	// Registration objects - separate setting because it wreaks havoc otherwise 
	register_setting('sl_user_create_objects', 'sl_user_create_objects');

} // end sl_user_create_register_settings()

function sl_user_create_add_defaults()
{
	$sl_user_create_settings = get_option('sl_user_create_settings');
	if ( false === $sl_user_create_settings ) {
		$sl_user_create_settings = array(
			'secret' => wp_generate_password(36, false),
			'secret_number' => date("dm"), // simple way to get a 4-digit number
			'registrationObjects' => array()
		);
	}
	
	// Figure out plugin version
	$plugin_data = get_plugin_data( __FILE__ );

	$sl_user_create_settings['plugin_version'] = $plugin_data['Version'];
	
	update_option('sl_user_create_settings', $sl_user_create_settings); 
} // end sl_user_create_add_defaults()

function sl_user_create_validate($input)
{
	// no output if things get changed because the Settings API doesn't support error messages yet
	$mysettings = get_option('sl_user_create_settings');
	
	if (!isset($input['secret']) || strlen($input['secret']) < 4)
	{
		$mysettings['secret'] = wp_generate_password(36, false);
	}
	else $mysettings['secret'] = $input['secret'];
 	
 	if (!isset($input['secret_number']) || strlen($input['secret_number']) != 4 || !is_numeric($input['secret_number']))
	{
		$mysettings['secret_number'] = date("dm");
 	}
 	else $mysettings['secret_number'] = $input['secret_number'];
 	
 	if (!isset($input['plugin_version']))
 	{
	 	$plugin_data = get_plugin_data( __FILE__ );

		$mysettings['plugin_version'] = $plugin_data['Version'];
 	}
 	
 	return $mysettings;
} // end sl_user_create_validate()

/* Main text */

// Text before the option
function sl_user_create_main_section_text()
{
?>
	<p><?php _e('Settings for ', 'sl-user-create'); _e('SL User Create', 'sl-user-create'); ?></p>
<?php
} // end sl_user_create_main_section_text()


function sl_user_create_secret() {
	$options = get_option('sl_user_create_settings');
	echo "<input id='secret' name='sl_user_create_settings[secret]' size='36' type='text' value='{$options['secret']}' />";
	echo '<span class="description">' . __('Secret string','sl-user-create') . '</span>';
} // end sl_user_create_secret()

function sl_user_create_secret_number() {
	$options = get_option('sl_user_create_settings');
	echo "<input id='secret_number' name='sl_user_create_settings[secret_number]' size='4' type='text' value='{$options['secret_number']}' />";
	echo '<span class="description">' . __('4-digit secret salt','sl-user-create') . '</span>';
} // end sl_user_create_secret_number()

function sl_user_create_text_area() {
	$settings = get_option('sl_user_create_settings');
	_e("Please create an object in Second Life on a plot owned by you, and drop the following script inside:", 'sl-user-create'); ?>
<p>
<textarea name="osinsl-lsl-script" cols="120" rows="12" readonly style="font-family: monospace">
// Code by Gwyneth Llewelyn to register avatars on WordPress sites
//
// Slight additional protection
//
// Global Variables
key avatar;
string avatarName;
key registrationResponse;	// to send the PermURL to the blog
key webResponse;			// to send avatar requests to the blog
string objectVersion = "<?php echo $settings['plugin_version']; ?>";
string secret = "<?php esc_attr_e($settings['secret']); ?>";
integer secretNumber = <?php esc_attr_e($settings['secret_number']); ?>;

// modified by SignpostMarv
string http_host = "<?php esc_attr_e($_SERVER['HTTP_HOST']); ?>";

default
{
	state_entry()
	{
		avatar = llGetOwner();
		avatarName = llKey2Name(avatar);
		llSetText("Registering with your blog at http://" + http_host + "\nand requesting PermURL from SL...", <0.8, 0.8, 0.1>, 1.0);
		llMinEventDelay(2.0);
		llRequestURL();	 // this sets the object up to accept external HTTP-in calls
	}	

	on_rez(integer startParam)
	{
		llResetScript();
	}
	
	touch_start(integer howmany)  // Allow owner to reset this
	{
		//if (llDetectedKey(0) == avatar)
		//{
		//	  llResetScript();
		//}
	
		llSetText("Sending registration request to http://" + http_host + "...", <0.6, 0.6, 0.1>, 1.0);

		string regAvatarName = llKey2Name(llDetectedKey(0));
		string regAvatarKey = llDetectedKey(0);
		string message = 
			"avatar_name=" + llEscapeURL(regAvatarName) +
			"&avatar_key=" + llEscapeURL(regAvatarKey) +
			"&signature=" + llMD5String((string)llGetKey() + secret, secretNumber);
			// llOwnerSay("DEBUG: Message to send to blog is: " + message);
		webResponse = llHTTPRequest("http://" + http_host + "/wp-content/plugins/sl-user-create/register-avatar.php",
			[HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], 
			message);	   
	}

	changed(integer what)
	{
		if (what & CHANGED_OWNER)
			llResetScript();	// make sure the new owner gets a fresh PermURL!
		if (what & (CHANGED_REGION | CHANGED_REGION_START | CHANGED_TELEPORT) )
		{
			llSetText("Requesting PermURL from SL...", <0.8, 0.8, 0.1>, 1.0);
			llRequestURL();
		}
	}

	// This is just to catch that our website has the widget active
	http_response(key request_id, integer status, list metadata, string body)
	{
		if (request_id == registrationResponse)
		{
			if (status == 200)
			{
				llOwnerSay("PermURL sent to gateway! Msg. id is " + body);
			}
			else if (status == 499)
			{
				llOwnerSay("Timeout waiting for gateway! Your PermURL might still be sent, please be patient");
			}
			else
			{
				llOwnerSay("PermURL NOT sent, registration object not activated. Status was " + (string)status + "; error message: " + body);
			}
		}
		else if (request_id == webResponse)
		{
			if (status == 200)
			{
				llOwnerSay("New avatar registration activated on WordPress site! Msg. received is " + body);
				// parse result to send user the password

				list result = llParseString2List(body, ["|"], []);
				key IMuser = llList2Key(result, 0);
				string message = llList2String(result, 2);
				llInstantMessage(IMuser, message);
			}
			else if (status == 499)
			{
				llOwnerSay("Timeout waiting for WordPress site!");
			}
			else
			{
				llOwnerSay("Avatar NOT registered. Request to WordPress site returned " + (string)status + "; error message: " + body);
			}
		}
		llSetText("", <0.0, 0.0, 0.0>, 1.0);  
	}

	// These are requests made from our blog to this object
	http_request(key id, string method, string body)
	{
		if (method == URL_REQUEST_GRANTED)
		{
			llSetText("Sending PermURL to blog...", <0.6, 0.6, 0.1>, 1.0);

			string avatarName = llKey2Name(llGetOwner());
			string message = 
				"object_version=" + llEscapeURL(objectVersion) +
				"&PermURL=" + llEscapeURL(body) +
				"&signature=" + llMD5String((string)llGetKey() + secret, secretNumber);
			// llOwnerSay("DEBUG: Message to send to blog is: " + message);
			registrationResponse = llHTTPRequest("http://" + http_host + "/wp-content/plugins/sl-user-create/register-object.php",
				[HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], 
				message);		
		}
		else if (method == "POST" || method == "GET")
		{
			if (body == "") // weird, no request
			{
				llHTTPResponse(id, 403, "Empty message received");
			}
			else
			{
				list params = llParseStringKeepNulls(body, ["&", "="], []);
	
				if (llList2String(params, 0) == "command" && llList2String(params, 1) == "die") {
					llHTTPResponse(id, 200, "Attempting to kill object in-world");
					llDie();

				}
				else
				{
					llHTTPResponse(id, 403, "Command not found");
				}
			}
		} 
	}	 
}
</textarea>
<p>
<hr />
<?php
} // end sl_user_create_text_area()

function sl_user_create_instructions_section_text()
{
?>
<p><?php _e('SL User Create', 'sl-user-create'); _e('Under construction.', 'sl-user-create'); ?>
</p>
<?php
} // end sl_user_create_instructions_section_text()

// Deal with translations. Portuguese only for now.
load_plugin_textdomain('sl-user-create', false, dirname( plugin_basename( __FILE__ ) ));

register_activation_hook(__FILE__, 'sl_user_create_add_defaults');
add_action('admin_menu', 'sl_user_create_admin_menu_options');
add_action('admin_init', 'sl_user_create_register_settings' );
?>