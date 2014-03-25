=== Domain Sharding ===
Contributors: sultanicq
Tags: cdn, wpo, domain sharding, speed, optimization
Requires at least: 2.8
Tested up to: 3.8.1
Stable tag: 1.1.1

This plugin modify the url of the images to speed up the page browsing.

== Description ==

We need to determine the subdomain pattern (ex: http://cdn-#.domain.tld ) substitution and the maximum number of subdomains we want to work with (ex: 5).

The pattern will transform an url like this 'http://www.domain.tld/img/source1.jpg' to this new url structure 'http://cdn-X.domain.tld/img/source1.jpg' where X ranges from 1 to 5 (max).

NOTE: If you have trouble accessing images using the new address because Wordpress asks you to register the domain then you must insert the following line in the file wp-config.php

	include_once(ABSPATH.'wp-content/plugins/domain-sharding/domain-sharding-alias.php'); 

Then you need to be sure to set write permissions to the aliases folder located within the plugin directory.

NOTE: You'll need to manually create the new A records for the subdomains in your DNS panel. They should have the same ip address of your main domain.

Visit the <a href="http://www.seocom.es/">Seocom website</a> for more information about SEO or WPO optimization

== Installation ==

1. Install "Domain Sharding" either via the WordPress.org plugin directory, or by uploading the files to your server inside the wp-content/plugins folder of your WordPress installation.
2. Activate "Domain Sharding" plugin via WordPress Settings.
3. It's done. Easy, isn't it?

NOTE: If you have trouble accessing images using the new address because Wordpress asks you to register the domain then you must insert the following line in the file wp-config.php

	include_once(ABSPATH.'wp-content/plugins/domain-sharding/domain-sharding-alias.php');

NOTE: You'll need to manually create the new A records for the subdomains in your DNS panel. They should have the same ip address of your main domain.

== Changelog ==

= 1.1.1 =
* Readme update with description and installation instructions.

= 1.1.0 =
* Completely rewritten to conform with multisite blogs.
* Full domain pattern specification not just the subdomain.
* Note: Old users must review settings to avoid misconfiguration problems.

= 1.0.5 =
* Added the option to force a 301 to the main site domain if the blog is visited using a different address. This option tries to solve the SEO issue of visiting the blog using the Domain Sharding subdomains.

= 1.0.4 =
* Added some tests to help us check for the validity of the desired subdomains. They are located in the Settings page.

= 1.0.3 =
* BugFix. The plugin now works with blogs not installed in the root domain.

= 1.0.2 =
* Added exclusions. Now we can ignore some urls to avoid transforming them.

= 1.0.1 =
* Added instruccions to create A records for the new domains

= 1.0.0 =
* Initial Release.
