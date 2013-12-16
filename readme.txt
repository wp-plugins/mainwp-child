=== MainWP Child ===
Contributors: MainWP
Donate link: 
Tags: WordPress Management, WordPress Controller
Author URI: http://mainwp.com
Plugin URI: http://mainwp.com
Requires at least: 3.4
Tested up to: 3.8
Stable tag: 0.14
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to manage multiple blogs from one dashboard by providing a secure connection between your child site and your MainWP dashboard.

== Description ==

[MainWP](http://mainwp.com) is a self-hosted WordPress management system that allows you to manage an endless amount of WordPress blogs from one dashboard on your server.

The MainWP Child plugin is used so the installed blog can be securely managed remotely by your Network.

**Features include:**

* Connect and control all your WordPress installs even those on different hosts!
* Update all WordPress installs, Plugins and Themes from one location
* Manage and Add all your Posts from one location
* Manage and Add all your Pages from one location
* Run everything from 1 Dashboard that you host!


== Installation ==

1. Upload the MainWP Child folder to the /wp-content/plugins/ directory
2. Activate the MainWP Child plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What is the purpose of this plugin? =

It allows the connection between the MainWP main dashboard plugin and the site it is installed on.

To see full documentation and FAQs please visit [MainWP Documentation](http://docs.mainwp.com/)

== Screenshots ==

1. The Dashboard Screen
2. The Posts Screen
3. The Comments Screen
4. The Sites Screen
5. The Plugins Screen
6. The Themes Screen
7. The Groups Screen
8. The Offline Checks Screen
9. The Clone Screen
10. The Extension Screen

== Changelog ==

= 0.14 =
* Fixed redirection issue with wrongly encoded HTTP request

= 0.13 =
* Added restore function

= 0.12 =
* Fixed conflict with main dashboard on same site

= 0.11 =
* Plugin localisation
* Extra check for readme.html file
* Added child server information
* Fixed restore issue: not all previous plugins/themes were removed
* Fixed backup issue: not all files are being backed up

= 0.10 =
* Fixed plugin conflict
* Fixed backup issue with database names with dashes
* Fixed date formatting
* Tags are now being saved to new posts
* Fixed issue when posting an image with a link

= 0.9 =
* Fixed delete permanently bug
* Fixed plugin conflict

= 0.8 =
* Fixed issue with Content Extension
* Added feature to add sticky posts

= 0.7 =
* Fixed the message "This site already contains a link" even after reactivating the plugin

= 0.6 =
* Fixed plugin conflict with WooCommerce plugin for cloning
* Fixed backups having double the size

= 0.5 =
* Fixed issue with importing database with custom foreign key references
* Fixed issue with disabled functions from te "suhosin" extension
* Fixed issue with click-heatmap

= 0.4 =
Fixed cloning issue with custom prefix

= 0.3 =
* Fixed issues with cloning (not cloning the correct source if the source was cloned)

= 0.2 =
* Added unfix option for security issues

= 0.1 =
* Initial version