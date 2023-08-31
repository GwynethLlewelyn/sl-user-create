=== SL User Create ===
Contributors: gwynethllewelyn
Donate link: http://gwynethllewelyn.net/
Tags: second life, login, registration, sl
Requires at least: 3.0
Tested up to: 3.2-RC3
Stable tag: trunk

Allows Second Life® users to get automatically registered to a WordPress site by touching an object with a special script.

== Description ==

Need to automatically register new users on a WordPress site with their Second Life® avatar names? This plugin allows you to do so, by exhibiting a script that you can copy and place into an in-world object. Users just need to touch the object to get automatically registered; if they are already registered, they will just get a link to your site.

New users will receive a password via the Second Life Instant Messaging Service, as well as a link to tell them the URL for your site. The new profile will include their avatar name as a login and their SL profile picture (if available via Web) will become their WordPress profile picture. If you have some special meta fields enabled on your WordPress profile, they will be filled in with some data from SL as well (e.g. location).

== Installation ==

1. After installing the plugin, if you're using a cache manager (e.g. W3 Total Cache) make sure you add **register-avatar\.php** and **register-object\.php** to the exception list, or you'll get multiple registrations with the same name!
2. Go to the Settings menu and look at the option for "SL User Create". You should be shown a pre-formatted LSL script.
3. Launch Second Life.
4. Create an object in your land. Make sure that scripts are active!
5. Right-click to open the object's Build popup, and go to the Contents tab.
6. Create a new script inside (just click on the button).
7. Delete everything in that script.
8. Now go back to the WordPress admin page you've opened, and copy the script and paste it inside your LSL script in Second Life.
9. Save the LSL script in Second Life; it should recompile.
10. The LSL script will now try to contact your blog and register itself.

Now any avatar wishing to register for their blog will only need to touch this object and get immediately registered.

To-do:
* Limit requests from certain domain names only (tricky if DNS is not enabled)

== Frequently Asked Questions ==

= Can I place multiple objects for registration? =

Yes! The admin panel will list the currently active objects, where you can also delete the ones that are inactive. Each object will count how many avatars have been registered through it, so that you can keep track of which objects have been attracting more registrations.

You're also welcome to edit the LSL script, if you're talented enough, to personalise the user experience.

= One user forgot the password. What now? =

Ask them to touch the object again. It will give them a popup box where they can reset the password and have it sent to them by Instant Message.

= Can I use this on my OpenSim grid, too? =

Yes, you can. This was tested on an OpenSim grid running 0.7.0.2; earlier versions might not work. Please note that if you have registered on one grid with your avatar name, and then register on a **different** grid with the **same** avatar name, you'll just change the password — this plugin assumes that avatar names are unique across grids (because WordPress logins have to be unique) 

== Screenshots ==

1. Backoffice, settings menu and LSL script
2. Backoffice, interface to deal with registration objects

== Changelog ==

= 0.1.6 =

* Fixed minor script issues to work with OpenSim 0.7.0.2+

= 0.1.5 =

* Users already registered, when touching the object again, will now get a popup allowing them to reset the password instead of silently failing to register them again
* Now new users will have an email address with the site's domain name appended. This might be useful for some services that will provide SL residents with mailboxes
* Updated registration object timestamp every time a new avatar registers or changes their password

= 0.1.0 =

* Added required screenshots for WP
* Retrieves avatar profile information from world.secondlife.com/resident/<AVATAR UUID> and puts it into WP profile
* Simplified admin page: now when deleting an object, it also sends a llDie() to it
* Added instructions tab
* Translations for Portuguese included

= 0.0.5 =
* Avatars get registered with fake email address, because logins wouldn't work if they hadn't one
* Registration IM now shows URL of the website they've registered to
* Added two additional strings to allow for minimal security; they still cannot be saved thanks to a nasty bug...
* Added support to multisite installs (via a meta key)

= 0.0.1 =
* First release. Lots of code borrowed from my other SL plugin, [Online Status inSL](http://wordpress.org/extend/plugins/online-status-insl/)

== Upgrade Notice ==

Fixed minor script issues to work with OpenSim 0.7.0.2+