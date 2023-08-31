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
	die(__("Request has to come from Second Life", 'sl-user-create'));
}

if ($permURL = $_REQUEST['PermURL']) // assume it's a registration
{
	// Extract information from in-world object
	// Everything comes from the headers, except PemURL and object_version
	$avatarKey = $_SERVER['HTTP_X_SECONDLIFE_OWNER_KEY'];
	$avatarDisplayName = $_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'];
	$objectVersion = $_REQUEST['object_version']; // we'll ignore versions for now
	$objectKey = $_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'];
	$objectName = $_SERVER['HTTP_X_SECONDLIFE_OBJECT_NAME']; // to do some fancy editing
	$objectRegion = $_SERVER['HTTP_X_SECONDLIFE_REGION'];
	$objectLocalPosition = $_SERVER['HTTP_X_SECONDLIFE_LOCAL_POSITION'];
	
	// now get the whole serialised array for this plugin's option
	$settings = get_option('sl_user_create_settings');

	// change what we need to track this object
	$settings[$objectKey] = array(
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
	
	update_option('sl_user_create_settings', $settings);
	
	header("HTTP/1.0 200 OK");
	header(__("Content-type: text/plain; charset=utf-8", 'sl-user-create'));
	printf(__("PermURL <%s> saved for object '%s' (%s)", 'sl-user-create'), $settings[$objectKey]["PermURL"], $objectName, $objectKey);
}
else
{
	header(__("HTTP/1.0 405 Method Not Allowed", 'sl-user-create'));
	_e("No PermURL specified on registration", 'sl-user-create');
}
?>