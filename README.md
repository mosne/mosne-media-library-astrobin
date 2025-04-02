# Mosne Media Library AstroBin

WordPress plugin to integrate your AstroBin images into WordPress using the Gutenberg editor.

## Features

- Secure storage of AstroBin API credentials
- Gutenberg editor integration with custom block
- Search and insert AstroBin images directly in the editor
- Configurable image sizes

## Installation

1. Download or clone this repository to your WordPress plugins directory
2. Activate the plugin through the WordPress admin interface
3. Navigate to Settings > AstroBin to configure your API credentials

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

## AstroBin API Credentials

To use this plugin, you need to have an AstroBin account and create API credentials:

1. Go to [AstroBin](https://www.astrobin.com/)
2. Register or log in to your account
3. Navigate to Settings > API Keys
4. Create a new API key
5. Copy the API Key and API Secret to the plugin settings

## Usage

1. Configure your AstroBin credentials in Settings > AstroBin
2. Edit a post or page using the Gutenberg editor
3. Add a new block and search for "AstroBin"
4. Insert the AstroBin Image block
5. Search for your images using the search box
6. Select an image to insert it into your post
7. Configure image size and caption in the block settings

## License

This plugin is licensed under the ISC License.
