# ExtraChill Newsletter Plugin

A comprehensive WordPress plugin for newsletter management and Sendy integration, extracted from the ExtraChill theme for improved modularity and maintainability.

## Features

- **Custom Newsletter Post Type**: Complete newsletter management with archive and single page templates
- **Sendy Integration**: Full API integration for campaign creation, updates, and subscription management
- **Multiple Subscription Forms**: Archive page, homepage, navigation menu, popup, content, and footer forms
- **Festival Wire Tip System**: Community-driven festival tip submissions with anti-spam protection
- **Template Override System**: Plugin-provided templates that override theme templates
- **AJAX-Powered Forms**: Seamless subscription experience without page reloads
- **Integration System**: Declarative form registration via WordPress filters
- **Security Features**: Cloudflare Turnstile anti-spam, rate limiting, and nonce verification
- **Responsive Design**: Mobile-optimized forms and layouts
- **Admin Integration**: Campaign management meta box with push-to-Sendy functionality

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active Sendy installation for email functionality
- Write permissions for plugin directory

## Installation

### Via WordPress Admin (Recommended)
1. Download the latest release ZIP from the plugin repository
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the ZIP file and click "Install Now"
4. Activate the plugin

### Manual Installation
1. Extract the plugin files to `wp-content/plugins/extrachill-newsletter/`
2. Go to WordPress Admin → Plugins and activate "ExtraChill Newsletter"

### Development Installation
1. Navigate to plugin directory and create production build:
   ```bash
   cd extrachill-plugins/extrachill-newsletter
   ./build.sh
   ```
2. Install the generated ZIP file from `/build` directory

## Configuration

### Sendy API Settings

The plugin provides a comprehensive admin settings interface for configuration. Navigate to **Newsletter → Settings** on newsletter.extrachill.com to configure:

- **Global Sendy Settings**: API key, Sendy URL, from email, reply-to email, brand ID
- **Integration Management**: Enable/disable specific newsletter subscription contexts
- **List ID Configuration**: Sendy list IDs for each integration context

All settings are stored network-wide in `get_site_option('extrachill_newsletter_settings')` and accessible from all sites in the multisite network.

**Zero Hardcoded Credentials**: All API keys and list IDs are configurable through the admin UI.

### Integration Contexts

The plugin supports multiple subscription contexts through a declarative registration system:

- **Navigation**: Navigation menu subscription form
- **Homepage**: Main homepage subscription form
- **Popup**: Modal popup newsletter subscription
- **Archive**: Newsletter archive page subscription
- **Content**: Newsletter form after post content
- **Footer**: Newsletter form above site footer
- **Festival Wire Tip**: Festival tip submission with newsletter signup

### Theme Integration

The plugin includes a homepage section that can be displayed by adding this to your theme:

```php
do_action('extrachill_newsletter_homepage_section');
```

## Usage

### Creating Newsletters

1. Go to WordPress Admin → Newsletters → Add New
2. Write your newsletter content using the WordPress editor
3. Add a featured image if desired
4. Publish the newsletter
5. Use the "Push to Sendy" button in the Sendy Integration meta box to send the campaign

### Subscription Forms

The plugin provides multiple subscription forms that integrate via WordPress hooks:

- **Archive Page**: Automatically displays on the newsletter archive page
- **Homepage**: Main homepage subscription form
- **Navigation Menu**: Automatically integrates with theme navigation
- **Popup**: Modal popup newsletter subscription
- **Content**: Newsletter form displayed after post content
- **Footer**: Newsletter form displayed above site footer
- **Festival Wire Tip**: Festival tip submission form with newsletter signup

All forms use the centralized `extrachill_multisite_subscribe()` function for consistent subscription handling.



### Template Customization

The plugin uses its own templates but allows theme overrides. To customize templates:

1. Copy templates from `plugins/extrachill-newsletter/templates/`
2. Place them in your theme's root directory
3. Modify as needed (plugin templates will be used as fallback)

## AJAX Endpoints

The plugin registers these AJAX endpoints for form handling:

- `submit_newsletter_form` - Archive page subscription
- `submit_newsletter_popup_form` - Popup subscription
- `subscribe_to_sendy_home` - Homepage subscription
- `subscribe_to_sendy` - Navigation subscription
- `submit_newsletter_content_form` - Content form subscription
- `submit_newsletter_footer_form` - Footer form subscription
- `newsletter_festival_wire_tip_submission` - Festival wire tip submission
- `push_newsletter_to_sendy_ajax` - Admin campaign management

All endpoints include security verification, input sanitization, and rate limiting where appropriate.

## Hooks and Filters

### Actions
- `extrachill_navigation_before_social_links` - Navigation menu subscription form
- `extrachill_home_grid_bottom_right` - Latest newsletters grid display
- `extrachill_after_post_content` - Content subscription form
- `extrachill_above_footer` - Footer subscription form
- `extrachill_after_news_wire` - Festival wire tip form
- `extrachill_sidebar_bottom` - Recent newsletters sidebar widget
- `extrachill_archive_below_description` - Archive page subscription form
- `newsletter_subscription_logged` - After subscription attempt logging

### Filters
- `newsletter_form_integrations` - Register newsletter integration contexts
- `template_include` - Template override system
- `extrachill_post_meta` - Customize post meta display for newsletters

## Development

### Building the Plugin

```bash
# Create production ZIP
./build.sh

# Output: /build/extrachill-newsletter/ directory and /build/extrachill-newsletter.zip file
```

### File Structure

```
extrachill-newsletter/
├── extrachill-newsletter.php          # Main plugin file
├── inc/
│   ├── newsletter-post-type.php       # Custom post type registration
│   ├── newsletter-sendy-integration.php # Sendy API integration
│   ├── newsletter-ajax-handlers.php   # AJAX request handlers
│   ├── newsletter-hooks.php           # WordPress hook integrations
│   ├── newsletter-popup.php           # Newsletter popup functionality
│   ├── newsletter-subscribe.php       # Subscription functions
│   └── admin/
│       └── newsletter-settings.php    # Admin settings page
├── templates/
│   ├── navigation-form.php            # Navigation menu form
│   ├── content-form.php               # Post content form
│   ├── footer-form.php                # Footer form
│   ├── festival-wire-tip-form.php     # Festival tip form
│   └── homepage-section.php           # Homepage section template
├── assets/
│   ├── newsletter.css                 # Newsletter page styles
│   ├── newsletter-forms.css           # Form-specific styles
│   ├── newsletter.js                  # JavaScript functionality
│   ├── newsletter-popup.js            # Popup-specific JavaScript
│   └── sidebar.css                    # Sidebar widget styles
├── build.sh                           # Production build script (symlink)
├── .buildignore                       # Build exclusion patterns
└── README.md                          # This file
```

### Architecture

The plugin follows WordPress best practices:

- **Modular Structure**: Functionality split into focused include files
- **Template Override System**: Plugin templates with theme override capability
- **Conditional Asset Loading**: CSS/JS only loads when needed
- **Security First**: Nonce verification, capability checks, input sanitization
- **Translation Ready**: All strings properly internationalized
- **Hook-Based Integration**: Extensible via WordPress actions and filters

## Changelog

### Version 1.0.0
- Initial release with modular architecture
- Extracted newsletter functionality from ExtraChill theme
- Complete Sendy integration with centralized API configuration
- Multiple subscription forms (navigation, homepage, popup, archive)
- Template override system with plugin fallback
- Admin campaign management with push-to-Sendy functionality
- Integration system with declarative form registration
- Enhanced security with nonce verification and input sanitization
- Festival wire tip submission system with anti-spam protection
- Content and footer newsletter forms
- Cloudflare Turnstile integration for spam prevention
- Rate limiting for tip submissions
- Responsive design and mobile optimization

## Support

For support and development questions:

- Review the code comments for technical details
- Check WordPress error logs for debugging information
- Verify Sendy API configuration and connectivity
- Ensure proper WordPress permissions and capabilities

## License

This plugin is developed for the ExtraChill platform. All rights reserved.

---

**Developed by Chris Huber for ExtraChill**
Website: https://extrachill.com
GitHub: https://github.com/chubes4