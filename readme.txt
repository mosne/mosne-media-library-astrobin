=== Mosne Media Library AstroBin ===
Contributors:      mosne
Tags:              image,library, astrobin, astronomy, astrophotography
Requires at least: 6.5
Requires PHP:      7.4
Tested up to:      6.8
Stable tag:        1.0.3
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin to integrate AstroBin images into WordPress using the Media Library in the Gutenberg editor.

== Description ==

WordPress integration with AstroBin API to browse and insert astronomy images directly from the Gutenberg editor.
The images will be complete with title, author attribution, copyright information, and links to both the original image and the author's profile.
This plugin has been created for my astronomy club website to easily retrieve images from AstroBin with all their associated information.
Since "All rights reserved" is the most commonly used license, I'd like to remind you that this means you need the author's permission before publishing any images on your website.
This plugin uses the AstroBin API but is not endorsed or certified by AstroBin.


== Key Features ==

* Integration with AstroBin API for searching and browsing images
* Secure storage of AstroBin API credentials
* Media Library integration within Gutenberg editor
* Automatic attribution and copyright information maintenance for all inserted images 
* Multiple browsing options:
  * Your own AstroBin pictures
  * Public pictures (search by title)
  * Users' galleries (search by username)
  * Image Hash (ID)
  * Image of the Day (browse current and past featured images)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/mosne-media-library-astrobin` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the WordPress admin interface
3. Navigate to Settings > AstroBin to configure your API credentials

== Frequently Asked Questions ==

= How do I get AstroBin API credentials? =

To use this plugin, you need to have an AstroBin account and create API credentials:

1. Go to [AstroBin](https://www.astrobin.com/)
2. Register or log in to your account
3. Request an API Key from the [API Key request form](https://welcome.astrobin.com/application-programming-interface#request-key)
4. Copy the API Key and API Secret to the plugin settings

= Can I use image under "All rights reserved" license ? =
Since "All rights reserved" is the most commonly used license, I'd like to remind you that this means you need the author's permission before publishing any images on your website.

= How do I use the plugin? =

1. Configure your AstroBin credentials in Settings > AstroBin
2. Edit a post or page using the Gutenberg editor
3. Open the Media Library and look for the AstroBin categories
4. Choose one of the browse options:
   - "My pictures" to browse your own images
   - "Images by username"
   - "Images by subject"
   - "Images by hash (id)"
   - "Image of the day"
5. Select an image to insert it into your post
6. You will also find the image in the Media Library complete with title, author attribution, copyright information, and links to both the original image and the author's profile.

= What is the Image of the Day feature? =

The Image of the Day feature allows you to browse through AstroBin's featured images:
* The first page shows the most recent Images of the Day
* Navigate to other pages to see older featured images
* Each image displays its featured date in the caption

== Screenshots ==

1. AstroBin API settings page
2. Media Library integration
3. Browsing AstroBin images
4. Image of the Day section

== Changelog ==

= 1.0.3 - 2025-04-21 =
* Documentation update

= 1.0.2 - 2025-04-15 =
* fixed api secret storage

= 1.0.1 - 2025-04-13 =
* Added a new feature to allow users to browse AstroBin images in the Media Library
* Added a new feature to allow users to search for AstroBin images by title
* Added a new feature to allow users to search for AstroBin images by username
* Added a new feature to allow users to search for AstroBin images by image hash
* Added a new feature to allow users to search for AstroBin images of the day

= 1.0.0 - 2025-04-10 =
* Initial release

== External services ==

This plugin connects to the [AstroBin API](https://www.astrobin.com/api/v1/) to retrieve image data.
This API will be fetch via the WordPress REST API and it will send the following information:
* your credentials (api-key and api-secret)
* the url of your website
* our requested terms

This is required to fetch image files and related information such as title, author, and license.
Please note that “All rights reserved” is the most commonly used license. This means you must obtain the author’s permission before publishing any of their images on your website.

This service is provided by the [AstroBin API](https://welcome.astrobin.com/application-programming-interface). For more details, please refer to their [terms of use](https://welcome.astrobin.com/terms-of-service), [privacy policy](https://welcome.astrobin.com/privacy-policy).

=== Credits ===
This plugin uses the AstroBin API but is not endorsed or certified by AstroBin.
[astrobin.com](https://app.astrobin.com/)