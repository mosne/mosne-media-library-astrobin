# Mosne Media Library AstroBin

WordPress plugin to integrate AstroBin images into WordPress using the Media Library in the Gutenberg editor.

## Features

- Integration with AstroBin API for searching and browsing images
- Media Library integration within Gutenberg editor
- Multiple browsing options:
  - Your own AstroBin pictures
  - Public pictures (search by title)
  - Users' galleries (search by username)
  - Image of the Day (browse current and past featured images)
  - Top Picks
- Secure storage of AstroBin API credentials

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

## Usage

1. Configure your AstroBin credentials in Settings > AstroBin
2. Edit a post or page using the Gutenberg editor
3. Open the Media Library and look for the AstroBin categories
4. Choose one of the browse options:
   - "My pictures" to browse your own images
   - "Public pictures" to search public images by title
   - "Users' galleries" to browse images by username
   - "Image of the day" to see current and past featured images
   - "Top Picks" to browse top-rated images
5. Select an image to insert it into your post

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

This plugin uses the AstroBin REST API as documented at [welcome.astrobin.com/application-programming-interface](https://welcome.astrobin.com/application-programming-interface).

The plugin specifically uses these endpoints:
- `image/` - For browsing and searching images
- `imageoftheday/` - For accessing Image of the Day features
- `toppick/` - For accessing Top Picks

## License

This plugin is licensed under the ISC License.
