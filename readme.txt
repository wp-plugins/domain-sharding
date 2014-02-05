=== Domain Sharding ===
Contributors: sultanicq
Tags: cdn, wpo, domain sharding, speed, optimization
Requires at least: 2.8
Tested up to: 3.8.1
Stable tag: 1.0.1

This plugin modify the url of the images to speed up the page browsing.

== Description ==

We need to determine the subdomain pattern (ex: cdn ) and the maximum number of subdomains we want to work with (ex: 5).

The pattern will transform an url like this 'http://www.domain.tld/img/source1.jpg' to this new url structure 'http://cdn-X.domain.tld/img/source1.jpg' where X ranges from 1 to 5 (max).

NOTE: You'll need to manually create the new A records for the subdomains in your DNS panel. They should have the same ip address of your main domain.

== Installation ==

1. Install "Domain Sharding" either via the WordPress.org plugin directory, or by uploading the files to your server inside the wp-content/plugins folder of your WordPress installation.
2. Activate "Domain Sharding" plugin via WordPress Settings.
3. It's done. Easy, isn't it?

== Changelog ==

= 1.0.1 =
* Added instruccions to create A records for the new domains

= 1.0.0 =
* Initial Release.
