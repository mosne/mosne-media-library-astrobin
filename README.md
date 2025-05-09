# Mosne Media Library AstroBin

![osne Media Library AstroBin](https://github.com/mosne/mosne-media-library-astrobin/blob/main/.wordpress-org/banner-1544x500.png)


WordPress plugin to integrate AstroBin images into WordPress using the Media Library in the Gutenberg editor. This plugin uses the AstroBin API but is not endorsed or certified by AstroBin.

## Features

- Integration with AstroBin API for searching and browsing images
- Secure storage of AstroBin API credentials
- Media Library integration within Gutenberg editor
- Multiple browsing options:
  - Your own AstroBin pictures
  - Public pictures (search by title)
  - Users' galleries (search by username)
  - Image Hash (ID)  
  - Image of the Day (browse current and past featured images)

## Installation

1. Download or clone this repository to your WordPress plugins directory
2. Activate the plugin through the WordPress admin interface
3. Navigate to Settings > AstroBin to configure your API credentials

## AstroBin API Credentials

To use this plugin, you need to have an AstroBin account and create API credentials:

1. Go to [AstroBin](https://www.astrobin.com/)
2. Register or log in to your account
3. Request an API Key from the [API Key request form](https://welcome.astrobin.com/application-programming-interface#request-key)
4. Copy the API Key and API Secret to the plugin settings

## External services

This plugin connects to the [AstroBin API](https://www.astrobin.com/api/v1/) to retrieve image data.
This API will be fetch via the WordPress REST API and it will send the following information:
* your credentials (api-key and api-secret)
* the url of your website
* our requested terms

This is required to fetch image files and related information such as title, author, and license.
Please note that "All rights reserved" is the most commonly used license. This means you must obtain the author's permission before publishing any of their images on your website.

This service is provided by the [AstroBin API](https://welcome.astrobin.com/application-programming-interface). For more details, please refer to their [terms of use](https://welcome.astrobin.com/terms-of-service), [privacy policy](https://welcome.astrobin.com/privacy-policy).

## Usage

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

### Image of the Day

The Image of the Day feature allows you to browse through AstroBin's featured images:
- The first page shows the most recent Images of the Day
- Navigate to other pages to see older featured images
- Each image displays its featured date in the caption

## Development

This plugin uses @wordpress/scripts for development. To set up the development environment:

```bash
# Install dependencies
npm install

# Start development mode
npm run start

# Build for production
npm run build
```

## API Reference

This plugin uses the AstroBin REST API using this [documentation](https://welcome.astrobin.com/application-programming-interface).

The plugin specifically uses these endpoints:
- `image/` - For browsing and searching images
- `imageoftheday/` - For accessing Image of the Day features

## License

This plugin is licensed under the GNU GPL 2.0 licence 
This plugin uses the AstroBin API but is not endorsed or certified by AstroBin.

