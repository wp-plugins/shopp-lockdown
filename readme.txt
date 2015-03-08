=== Plugin Name ===
Contributors: adamsewell
Donate link: http://tinyelk.com/
Tags: shopp, security, payment gateway, lockdown
Requires at least: 3.0.1
Tested up to: 4.1.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The Shopp Lockdown plugin watches for failed transactions on any Shopp payment gateway and limits the transactions to X number of tries.

== Description ==

The Shopp Lockdown plugin watches for failed transactions on any Shopp payment gateway and limits the transactions to X number of tries.

This comes in handy when gateways like Authorize.Net limits your account after so many failed transactions.

Without this plugin, an attacker could initiate a temporary DoS attack by trying too many fraudulent cards in a short period of time.

This plugin has been tested with Shopp 1.2.x and Shopp 1.3.x


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. View settings under Shopp Toolbox -> Shopp Lockdown


== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).


== Changelog ==

= 1.0 =
* Initial Release

== Upgrade Notice ==
