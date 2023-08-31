=== SL User Create ===
Contributors: gwynethllewelyn
Donate link: http://gwynethllewelyn.net/
Tags: second life, login, registration, sl
Requires at least: 3.0
Tested up to: 3.2-RC1
Stable tag: trunk

Allows Second Life® users to get automatically registered to a WordPress site by touching an object with a special script.

== Description ==

Need to automatically register new users on a WordPress site with their Second Life® avatar names? This plugin allows you to do so, by exhibiting a script that you can copy and place into an in-world object. Users just need to touch the object to get automatically registered; if they are already registered, they will just get a link to your site.

New users will receive a password via the Second Life Instant Messaging Service, as well as a link to tell them the URL for your site. The new profile will include their avatar name as a login and their SL profile picture (if available via Web) will become their WordPress profile picture. If you have some special meta fields enabled on your WordPress profile, they will be filled in with some data from SL as well (e.g. location).

== Installation ==

1. After installing the plugin, 
2. If you're using a cache manager (e.g. W3 Total Cache) make sure you add **register-avatar\.php** and **register-object\.php** to the exception list, or you'll get multiple registrations with the same name!
2. Go to the Settings menu and look at the option for "SL User Create". You should be shown a pre-formatted LSL script.
2. Launch SL.
3. Create an object in your land.
4. Open the object, and go to the Contents tab.
5. Create a new script inside (just click on the button).
6. Delete everything in that script.
7. Now go back to the WordPress admin page you've opened, and copy the script and paste it inside your LSL script in SL.
8. Save the LSL script in SL; it should recompile.
9. The LSL script will now try to contact your blog and register itself.

== Frequently Asked Questions ==

= Can I place multiple objects for registration? =

Yes! The admin panel will list the currently active objects, where you can also delete the ones that are inactive. Each object will count how many avatars have been registered through it, so that you can keep track of which objects have been attracting more registrations.

You're also welcome to edit the LSL script, if you're talented enough, to personalise the user experience.

== Screenshots ==

1. None so far

== Changelog ==

= 0.0.1 =
* First release. Lots of code borrowed from my other SL plugin, [Online Status inSL](http://wordpress.org/extend/plugins/online-status-insl/)

== Upgrade Notice ==

First release.
