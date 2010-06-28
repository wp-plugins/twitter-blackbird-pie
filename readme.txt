=== Twitter Blackbird Pie ===
Contributors: bradvin
Donate link: http://themergency.com/twitter-blackbird-pie-wordpress-plugin/
Tags: twitter, blackbird pie
Requires at least: 2.9.2
Tested up to: 3.0
Stable tag: 0.2.4

Add awesome looking embedded HTML representations of actual tweets in your blog posts just by adding simple shortcodes.

== Description ==

Add awesome looking embedded HTML representations of actual tweets in your blog posts just by adding simple shortcodes. Please read the blog post at http://themergency.com/twitter-blackbird-pie-wordpress-plugin/ for more info and to see the plugin in action.

The plugins has the following features:

*   UPDATE : Now supports non-english tweets!
*   Exact same look and feel as the respective Twitter profile.
*   Allows for multiple "pies" in a single post (as seen in the examples below).
*   Stores the generated HTML in a custom field (if possible), so the Twitter API is only called the first time.
*   Slightly better styling than the original Blackbird Pie
    *   Better use of the Twitter profile background image and color and tiling.
    *   Uses the Twitter profile text color.
    *   Uses the Twitter profile link color.
*   Dates are displayed like on Twitter i.e. "real time" datetime of when the tweet was tweeted. (see changelog)
*   Auto-linking of URLs, hashtags, usernames within the tweet text.
*   Use either the id or full URL of the tweet.

== Installation ==

1. Upload the plugin folder 'twitter-blackbird-pie' to your `/wp-content/plugins/` folder
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Insert shortcodes into your pages or posts e.g. [blackbirdpie id="13794126295"] or [blackbirdpie url="http://twitter.com/themergency/status/13968912427"]

== Screenshots ==

1. Example output. See it in action at http://themergency.com/twitter-blackbird-pie-wordpress-plugin/

== Changelog ==

= 0.1 =
* Initial Relase. First version.

= 0.1.5 =
* Updated the CSS incl. adding a few "!important" rules to make sure the theme CSS does not override it.
* Fixed bug for profile background image tile not working.
* Fixed bug for the date or the tweet. It now takes into account the timezone.

= 0.2 =
* Removed dependency on Jquery TimeAgo plugin and using a php function instead

= 0.2.1 =
* Fixed bug introduced in ver 0.2 where the time was not updating (e.g. "1 hour ago" was being saved into the custom field
* Fixed JSON encoding bug

= 0.2.2 =
* Fixed bug introduced in ver 0.2.1 where Twitter API was being called on every request

= 0.2.3 =
* Fixed bug with non english characters showing as numbers in the tweet text

= 0.2.4 =
* Fixed a bug where the tweet was blank when it included quotes (")
* Removed some debugging echos (DOH!!!)
* PLEASE UPGRADE!

== Frequently Asked Questions ==

= How do I use this plugin? =
You insert shortcodes into your blog posts or pages, e.g. [blackbirdpie id="13794126295"] or [blackbirdpie url="http://twitter.com/themergency/status/13968912427"]

== Upgrade Notice ==

There is no upgrade notice