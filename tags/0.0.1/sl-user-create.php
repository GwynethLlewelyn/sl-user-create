<?php
/*
Plugin Name: SL User Create
Plugin URI: http://gwynethllewelyn.net/sl-user-create/
Version: 0.0.1
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

function sl_user_create_admin_menu_options()
{
	add_options_page(__('SL User Create', 'sl-user-create'), __('SL User Create', 'sl-user-create'), 1,
		'sl_user_create', 'sl_user_create_menu');
}

function sl_user_create_menu()
{
?>
<div class="wrap">
<h2><?php _e('SL User Create', 'sl-user-create'); ?></h2>
<?php	
	$settings = get_option('sl_user_create_settings');

		// Check if we have to delete some of the registration objects
		if ($_POST["sl_user_create_form"])
		{
			check_admin_referer('delete-online-registration-objects');
					
			// emit "updated" class showing we have deleted some things
?>
	<div id="message-updated" class="updated"><p><?php _e("Online registration objects <strong>deleted</strong>", 'sl-user-create'); ?><br /><br />
<?php	
			// Aye, we should have some things to delete first
		
			// loop through settings to delete; we get objectKeys and not the avatar name
			// _e("Dumping "); echo "\$_POST: "; var_dump($_POST);
			
			$newSettings = array();
			foreach ($settings as $registrationObjects)
			{
				if (in_array($registrationObjects["objectKey"], $_POST["deletedRegistrationObjects"]))
				{
					_e("Deleting "); 
					echo $registrationObjects["avatarDisplayName"], ", ",
						__("Object Name: ", 'sl-user-create'), $registrationObjects["objectName"], 
						" (", $registrationObjects["objectKey"], "), ",
						__("Location: ", 'sl-user-create'), $registrationObjects["objectRegion"],
						"<br />";
				}
				else 
				{
					$newSettings[$registrationObjects["avatarDisplayName"]] = $registrationObjects;
					// _e("Keeping "); echo $registrationObjects["avatarDisplayName"], ",",
					//	$registrationObjects["objectKey"], "<br>";
				}
			}
			$settings = $newSettings;
			
			// update options with new settings; gets serialized automatically
			update_option('sl_user_create_settings', $settings);
?>
			</p></div>
<?php
		}

		// Figure out plugin version
		$plugin_data = get_plugin_data( __FILE__ );
		$plugin_version = $plugin_data['Version'];

		_e("Please create an object in Second Life on a plot owned by you, and drop the following script inside:", 'sl-user-create'); ?>
<p>
<hr />
<textarea name="osinsl-lsl-script" cols="120" rows="12" readonly style="font-family: monospace">
// Code by Gwyneth Llewelyn to register avatars on WordPress sites
//
// Needs some more protection! Anyone can register!
//
// Global Variables
key avatar;
string avatarName;
key registrationResponse;   // to send the PermURL to the blog
key webResponse;            // to send avatar requests to the blog
string objectVersion = "<?php echo $plugin_version;?>";

// modified by SignpostMarv
string http_host = "<?php esc_attr_e($_SERVER['HTTP_HOST']); ?>";

default
{
    state_entry()
    {
        avatar = llGetOwner();
        avatarName = llKey2Name(avatar);
        llSetText("Registering with your blog at http://" + http_host + "\nand requesting PermURL from SL...", <0.8, 0.8, 0.1>, 1.0);
        llRequestURL();  // this sets the object up to accept external HTTP-in calls
    }   

    on_rez(integer startParam)
    {
        llResetScript();
    }
    
    touch(integer howmany)  // Allow owner to reset this
    {
        //if (llDetectedKey(0) == avatar)
        //{
        //    llResetScript();
        //}
    
        llSetText("Sending registration request to http://" + http_host + "...", <0.6, 0.6, 0.1>, 1.0);

        string regAvatarName = llKey2Name(llDetectedKey(0));
        string regAvatarKey = llDetectedKey(0);
        string message = 
            "avatar_name=" + llEscapeURL(regAvatarName) +
            "&avatar_key=" + llEscapeURL(regAvatarKey);
            // llOwnerSay("DEBUG: Message to send to blog is: " + message);
        webResponse = llHTTPRequest("http://" + http_host + "/wp-content/plugins/sl-user-create/register-avatar.php",
            [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], 
            message);      
    }

    changed(integer what)
    {
        if (what & CHANGED_OWNER)
            llResetScript();    // make sure the new owner gets a fresh PermURL!
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
                "&PermURL=" + llEscapeURL(body);
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
            else // Just sends object version back
            {
                llHTTPResponse(id, 200, objectVersion);
            }
        } 
    }    
}
</textarea>
<p>
<hr />

<?php 
	if (count($settings) > 0)
	{
?>
<h2><?php _e("Current registration objects being tracked", 'sl-user-create'); ?>:</h2>
<?php


	// $url = $_SERVER['PHP_SELF'] . "?page=" . $_GET["page"]. "&action=delete-online-registration-objects";
	// $action = "sl-user-create-delete-online-registration-objects";
	// $link = wp_nonce_url($url, $action);
?>
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
			<th scope='col' class='manage-column column-cb check-column'></th>
		</tr>
	</thead>
	<tbody>
<?php
		foreach ($settings as $oneWidget)
		{
			// needed for linking to SL's structure
			$avatarNameSanitised = sanitise_avatarname($oneWidget["avatarDisplayName"]);
	?>
	<tr class="format-default <?php echo ($alternate ? "" : "alternate"); $alternate = !$alternate; ?>">
		<td><?php echo $oneWidget["objectName"]; ?></td>
		<td><?php echo $oneWidget["objectKey"]; ?></td>
		<td><?php echo $oneWidget["objectVersion"]; ?></td>
		<td>
<?php 
			// parse name of the region and coordinates to create a link to maps.secondlife.com
			$regionName = substr($oneWidget["objectRegion"], 0, strpos($oneWidget["objectRegion"], "(") - 1);
			$coords = trim($oneWidget["objectLocalPosition"], "() \t\n\r");
			$xyz = explode(",", $coords);
			
			printf('<a href="http://maps.secondlife.com/secondlife/%s/%F/%F/%F?title=%s&amp;msg=%s&amp;img=%s" target="_blank">%s (%d,%d,%d)</a>',
				$regionName, $xyz[0], $xyz[1], $xyz[2], 
				rawurlencode($oneWidget["objectName"]),
				rawurlencode(__("Registration object for ", 'sl-user-create') . home_url()),
				rawurlencode("http://s.wordpress.org/about/images/logos/wordpress-logo-stacked-rgb.png"),
				$regionName, $xyz[0], $xyz[1], $xyz[2]);
?>
		</td>
		<td><?php echo $oneWidget["PermURL"]; ?></td>
		<td><?php echo $oneWidget["avatarDisplayName"]; ?></td>
		<td><?php echo $oneWidget["avatarKey"]; ?></td>
		<td><?php echo $oneWidget["count"]; ?></td>

		<td class="date column-date"><?php echo date(__("Y M j H:i:s", 'sl-user-create'), $oneWidget["timeStamp"]); ?></td>
		<td ><input type="checkbox" name="deletedRegistrationObjects[]" value="<?php echo $oneWidget["objectKey"]; ?>" /></td>
	</tr>
<?		
		} //foreach	
?>
	</tbody>
</table>
<input type='submit' class='button-primary alignleft' name='sl_user_create_form' value='<?php _e('Delete', 'sl-user-create'); ?>' />
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
	}
}
	
// Add a settings group, which hopefully makes it easier to delete later on
function sl_user_create_register_settings()
{
	register_setting('sl_user_create', 'sl_user_create_settings');
	// it's a huge serialised array for now, stored as a WP option in the database; 
	//  if performance drops, this might change in the future
}

// Deal with translations. Portuguese only for now.
load_plugin_textdomain('sl-user-create', false, dirname( plugin_basename( __FILE__ ) ));

add_action('admin_menu', 'sl_user_create_admin_menu_options');
add_action('admin_init', 'sl_user_create_register_settings' );
?>