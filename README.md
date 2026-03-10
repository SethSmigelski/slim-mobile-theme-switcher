# Slim Mobile Theme Switcher
Contributors: sethsm
Tags: mobile, theme switcher, device theme, responsive, mobile theme
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/donate/?hosted_button_id=M3B2Q94PGVVWL
Plugin URI:  https://www.sethcreates.com/plugins-for-wordpress/slim-mobile-theme-switcher/
Author URI:  https://www.sethcreates.com/plugins-for-wordpress/

Serve a mobile theme to phones while keeping desktops/tablets on the primary theme. Lightweight mobile theme switcher with modern device detection.

## Description

Slim Mobile Theme Switcher is a developer-friendly, lightweight solution for sites that need a dedicated mobile experience without the overhead of a heavy mobile plugin. This plugin targets mobile handhelds, ensuring tablet users may still enjoy your full desktop-grade layout.

## Key Features
* **True Mobile Detection:** Uses refined regex to separate small-screen phones from tablets (iPads/Android tablets).
* **Manual Overrides:** Allow users or testers to force a view using `?theme=handheld` or `?theme=active`.
* **Persistent Choice:** Option to remember a user's manual theme choice for 30 days via cookies.
* **No Bloat:** Zero front-end CSS or JS added by the plugin itself.
* **Developer Friendly:** Built by a developer for developers.

## How It Work
The plugin uses a precise, two-step detection process to ensure the best user experience:
1. **Exclude Tablets:** High-resolution devices (iPads, Android tablets, Kindle Fire) are served the Desktop Theme to take advantage of their screen size.
2. **Detect Phones:** Handheld mobile devices (iPhone, Android Mobile) are served the Mobile Theme.
3. **Manual Choice:** If a user manually switches via URL, their choice is honored for 30 days via cookies.

## Installation

1. Upload the `slim-mobile-theme-switcher` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Mobile Theme Switcher** to select your mobile and desktop themes.

## Frequently Asked Questions

# How do I add a "View Full Site" link?
You can add a link to your mobile theme's footer: `<a href="?theme=active">View Full Site</a>`

# What should I configure in my caching plugin?
For best results, exclude the `theme` query parameter from your cache. If your host supports it, enable "Vary by User-Agent" or "Mobile Caching".

# Why isn't my theme switching?
The most common cause is **Page Caching**. If your host (like WP Engine or Kinsta) or a plugin (like WP Rocket) caches the desktop version of a page, it may serve that cached HTML to mobile users regardless of this plugin. Please ensure "Mobile Caching" or "User-Agent Vary" is enabled in your caching setup.

# Does this work with tablets?
By default, tablets (iPads, etc.) are served the **Desktop Theme**. This is a deliberate design choice to ensure high-resolution tablet screens receive the most robust version of your site.

# How do I link to the mobile version manually?
Simply add `?theme=handheld` to any URL on your site. To go back to the default/desktop view, use `?theme=active`.

## Screenshots

1. The settings page where you can choose your mobile and desktop themes.

## Changelog 

# 1.0.0
* Initial release.

## Upgrade Notice 

# 1.0.0
* Initial release.
