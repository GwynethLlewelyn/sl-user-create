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

// This gets called from a Second Life object when registering an object or when it changes status
// Most of the data will come from the headers (e.g. avatar UUID)
// PermURL is the SL-assigned URL during registration to call the object back 
// Our script will also send object_version

require_once('../../../wp-config.php');

if (!$_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'])
{
	header(__("HTTP/1.0 405 Method Not Allowed", 'sl-user-create'));
	header("Content-type: text/plain; charset=utf-8");
	die(__("Request has to come from Second Life", 'sl-user-create'));
}

// now get the whole serialised array for this plugin's option
// we need it to check the simple signature
$settings = get_option('sl_user_create_settings');

if (!$settings['disable_signature']) // check if user has disabled signature validation
{
	if ($_REQUEST['signature'] != md5($_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'] . $settings['secret'] .":" . $settings['secret_number']))
	{
		header("HTTP/1.0 403 Forbidden");
		header("Content-type: text/plain; charset=utf-8");
		die(__("Invalid signature", 'sl-user-create'));
	}
}

// Now check permissions
// Is the owner of this object allowed to register it with us?
// We check first if we actually have a list of valid avatars (empty means all are allowed)
if (count($settings['allowed_avatars']) > 0)
{
	if (!in_array($_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'], $settings['allowed_avatars']))
	{
		header("HTTP/1.0 403 Forbidden");
		header("Content-type: text/plain; charset=utf-8");
		die(sprintf(__("%s not allowed to register objects with %s", 'sl-user-create'), $_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'], home_url()));
	}
}

// More complex validation: is the request coming from a valid address?
// We have to check first if we have a permission list with DNS entries
if (count($settings['allowed_simdns']) > 0)
{
	$passThru = false;
	
	// check IP address and DNS name...
	$addr = $_SERVER['REMOTE_ADDR'];
	$host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
	
	foreach($settings['allowed_simdns'] as $dnsEntry)
	{
		// trivial case first; the address or hostname matches
		/*if ($dnsEntry == $addr || $dnsEntry == $host)
		{
			$passThru == true;
			break;
		}*/
		// reverse check: match the end bit of our entry with the host/addr
		// this ought to allow people to have secondlife.com as an entry
		// and validate all requests coming from SL

		if (substr_compare($host, $dnsEntry, -strlen($dnsEntry)) == 0)
		{
			$passThru = true;
			break;
		}
		
		// do the same for IP addresses
		if (substr_compare($addr, $dnsEntry, -strlen($dnsEntry)) == 0)
		{
			$passThru = true;
			break;
		}		
	}
	
	if (!$passThru)
	{
		header("HTTP/1.0 403 Forbidden");
		header("Content-type: text/plain; charset=utf-8");
		die(sprintf(__("Host %s (%s) is not allowed to register objects with %s", 'sl-user-create'), $host, $addr, home_url()));
	}
}

// Clear to register!
if ($permURL = $_REQUEST['PermURL']) // assume it's a registration
{
	$objects = get_option('sl_user_create_objects');

	// Extract information from in-world object
	// Everything comes from the headers, except PemURL and object_version
	$avatarKey = $_SERVER['HTTP_X_SECONDLIFE_OWNER_KEY'];
	$avatarDisplayName = $_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'];
	$objectVersion = $_REQUEST['object_version']; // we'll ignore versions for now
	$objectKey = $_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'];
	$objectName = $_SERVER['HTTP_X_SECONDLIFE_OBJECT_NAME']; // to do some fancy editing
	$objectRegion = $_SERVER['HTTP_X_SECONDLIFE_REGION'];
	$objectLocalPosition = $_SERVER['HTTP_X_SECONDLIFE_LOCAL_POSITION'];
	
	// change what we need to track this object
	$objects[$objectKey] = array(
		"PermURL"				=> $_REQUEST['PermURL'], 
		"avatarKey"				=> $avatarKey, 
		"avatarDisplayName" 	=> $avatarDisplayName, 
		"objectVersion" 		=> $objectVersion, 
		"objectKey"				=> $objectKey, 
		"objectName"			=> $objectName, 
		"objectRegion"			=> $objectRegion, 
		"objectLocalPosition"	=> $objectLocalPosition,
		"timeStamp"				=> time()
	);
	
	update_option('sl_user_create_objects', $objects);
	
	header("HTTP/1.0 200 OK");
	header("Content-type: text/plain; charset=utf-8");
	printf(__("PermURL <%s> saved for object '%s' (%s)", 'sl-user-create'), $objects[$objectKey]["PermURL"], $objectName, $objectKey);
}
else
{
	header(__("HTTP/1.0 405 Method Not Allowed", 'sl-user-create'));
	_e("No PermURL specified on registration", 'sl-user-create');
}
?>