<?php
/**
 * Plugin Name:       Slim Mobile Theme Switcher
 * Plugin URI:        https://www.sethcreates.com/plugins-for-wordpress/slim-mobile-theme-switcher/
 * Description:       Serve a mobile theme to phones while keeping desktops/tablets on the primary theme. Lightweight mobile theme switcher with modern device detection. 
 * Version:           1.0.0
 * Author:            Seth Smigelski
 * Author URI:        https://www.sethcreates.com/plugins-for-wordpress/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       slim-mobile-theme-switcher
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Slim_Mobile_Theme_Switcher {
    
    private $option_name = 'smts_settings';
    private $mobile_theme = '';
    private $desktop_theme = '';
    private $persistent_override = false;
    
    public function __construct() {
        // Handle manual theme switch FIRST, before anything else
        add_action('init', array($this, 'handle_manual_switch'));
        
        // Load settings
        $settings = get_option($this->option_name, array());
        $this->mobile_theme = isset($settings['mobile_theme']) ? $settings['mobile_theme'] : '';
        $this->desktop_theme = isset($settings['desktop_theme']) ? $settings['desktop_theme'] : '';
        $this->persistent_override = isset($settings['persistent_override']) && $settings['persistent_override'] === 'yes';
        
        // Hook into theme selection
        add_filter('template', array($this, 'switch_theme'));
        add_filter('stylesheet', array($this, 'switch_theme'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add admin notice if not configured
        add_action('admin_notices', array($this, 'admin_notice'));
        
        // Add query var for manual theme switching
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Detect if device is a mobile phone (not tablet)
     * 
     * @return bool True if mobile phone, false for tablet/desktop
     */
    private function is_mobile_phone() {
        // Check for query parameter override FIRST (before checking cookie)
        // This ensures URL parameter works immediately
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Theme switching via URL is not a privileged action
        if (isset($_GET['theme'])) {
            $theme_param = sanitize_text_field(wp_unslash($_GET['theme']));
            if ($theme_param === 'handheld') {
                return true;
            } elseif ($theme_param === 'active') {
                return false;
            }
            // If theme parameter is something else, ignore it and continue with normal detection
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        
        // Check cookie if persistent override is enabled
        if ($this->persistent_override && isset($_COOKIE['smts_theme_override'])) {
            return ($_COOKIE['smts_theme_override'] === 'mobile');
        }
        
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        
        $user_agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
        
        // First, explicitly detect and exclude tablets
        // Tablets should get desktop theme
        $tablet_pattern = '/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i';
        if (preg_match($tablet_pattern, $user_agent)) {
            return false;
        }
        
        // Now check for mobile phones
        $mobile_pattern = '/Mobile|iP(hone|od)|Android.*Mobile|BlackBerry|IEMobile|Kindle|NetFront|Silk-Accelerated|(hpw|web)OS|Fennec|Minimo|Opera M(obi|ini)|Blazer|Dolfin|Dolphin|Skyfire|Zune/i';
        
        if (preg_match($mobile_pattern, $user_agent)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Switch theme based on device detection
     * 
     * @param string $theme Current theme
     * @return string Theme to use
     */
    public function switch_theme($theme) {
        // Don't switch in admin or if themes not configured
        if (is_admin() || empty($this->mobile_theme)) {
            return $theme;
        }
        
        if ($this->is_mobile_phone()) {
            return $this->mobile_theme;
        }
        
        // Use desktop theme if configured, otherwise use current
        if (!empty($this->desktop_theme)) {
            return $this->desktop_theme;
        }
        
        return $theme;
    }
    
    /**
     * Add query vars for theme switching
     */
    public function add_query_vars($vars) {
        $vars[] = 'theme';
        return $vars;
    }
    
    /**
     * Handle manual theme switching via URL parameter
     * Sets cookie if persistent override is enabled in settings
     */
    public function handle_manual_switch() {
        if (isset($_GET['theme'])) {
            // It is good practice to check a nonce if this were a form, 
            // but for a URL-based switch, simple sanitizing is used.
            $theme_param = sanitize_text_field(wp_unslash($_GET['theme']));
            
            // Only set cookie if persistent override is enabled
            if ($this->persistent_override) {
                if ($theme_param === 'handheld') {
                    setcookie('smts_theme_override', 'mobile', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
                } elseif ($theme_param === 'active') {
                    setcookie('smts_theme_override', 'desktop', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
                }
            }
            // Note: Theme switch happens immediately in is_mobile_phone() via URL parameter
            // Cookie (if enabled) persists the choice for future page loads
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Mobile Theme Switcher Settings',
            'Mobile Theme Switcher',
            'manage_options',
            'slim-mobile-theme-switcher',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'smts_settings_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['mobile_theme'])) {
            $sanitized['mobile_theme'] = sanitize_text_field($input['mobile_theme']);
        }
        
        if (isset($input['desktop_theme'])) {
            $sanitized['desktop_theme'] = sanitize_text_field($input['desktop_theme']);
        }
        
        if (isset($input['persistent_override'])) {
            $sanitized['persistent_override'] = ($input['persistent_override'] === 'yes') ? 'yes' : 'no';
        } else {
            $sanitized['persistent_override'] = 'no';
        }
        
        return $sanitized;
    }
    
    /**
     * Display admin notice if not configured
     */
    public function admin_notice() {
        if (empty($this->mobile_theme) && current_user_can('manage_options')) {
            $settings_url = admin_url('options-general.php?page=slim-mobile-theme-switcher');
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . esc_html__('Slim Mobile Theme Switcher:', 'slim-mobile-theme-switcher') . '</strong> ';
            
            echo wp_kses(
                sprintf(
                    /* translators: %s: URL to settings page */
                    __('Please <a href="%s">configure your mobile theme</a> to activate theme switching.', 'slim-mobile-theme-switcher'),
                    esc_url($settings_url)
                ),
                array(
                    'a' => array(
                        'href' => array(),
                    ),
                )
            );
            
            echo '</p></div>';
        }
    }
    
    /**
     * Settings page
     */
public function settings_page() {
        $themes = wp_get_themes();
        $current_theme = wp_get_theme()->get_stylesheet();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Slim Mobile Theme Switcher Settings', 'slim-mobile-theme-switcher'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('smts_settings_group');
                $settings = get_option($this->option_name, array());
                $mobile_theme = isset($settings['mobile_theme']) ? $settings['mobile_theme'] : '';
                $desktop_theme = isset($settings['desktop_theme']) ? $settings['desktop_theme'] : $current_theme;
                $persistent_override = isset($settings['persistent_override']) ? $settings['persistent_override'] : 'no';
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mobile_theme"><?php esc_html_e('Mobile Theme (Phones Only)', 'slim-mobile-theme-switcher'); ?></label>
                        </th>
                        <td>
                            <select name="<?php echo esc_attr($this->option_name); ?>[mobile_theme]" id="mobile_theme" class="regular-text">
                                <option value=""><?php esc_html_e('-- Select Mobile Theme --', 'slim-mobile-theme-switcher'); ?></option>
                                <?php foreach ($themes as $theme_slug => $theme_obj): ?>
                                    <option value="<?php echo esc_attr($theme_slug); ?>" <?php selected($mobile_theme, $theme_slug); ?>>
                                        <?php 
                                            echo esc_html($theme_obj->get('Name')); 
                                            if ($theme_slug === $current_theme) echo esc_html__(' (Currently Active)', 'slim-mobile-theme-switcher'); 
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="desktop_theme"><?php esc_html_e('Desktop Theme (Tablets & Desktops)', 'slim-mobile-theme-switcher'); ?></label>
                        </th>
                        <td>
                            <select name="<?php echo esc_attr($this->option_name); ?>[desktop_theme]" id="desktop_theme" class="regular-text">
                                <?php foreach ($themes as $theme_slug => $theme_obj): ?>
                                    <option value="<?php echo esc_attr($theme_slug); ?>" <?php selected($desktop_theme, $theme_slug); ?>>
                                        <?php 
                                            echo esc_html($theme_obj->get('Name')); 
                                            if ($theme_slug === $current_theme) echo esc_html__(' (Currently Active)', 'slim-mobile-theme-switcher'); 
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="persistent_override"><?php esc_html_e('Remember Visitor’s Choice', 'slim-mobile-theme-switcher'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[persistent_override]" id="persistent_override" value="yes" <?php checked($persistent_override, 'yes'); ?> />
                                    <?php esc_html_e("Remember user's theme choice across pages", 'slim-mobile-theme-switcher'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2><?php esc_html_e('Testing & Usage', 'slim-mobile-theme-switcher'); ?></h2>
            
            <div class="notice notice-info inline">
                <h3><?php esc_html_e('How It Works', 'slim-mobile-theme-switcher'); ?></h3>
                <ul>
                    <li><strong><?php esc_html_e('Mobile Phones:', 'slim-mobile-theme-switcher'); ?></strong> <?php esc_html_e('Get the mobile theme', 'slim-mobile-theme-switcher'); ?></li>
                    <li><strong><?php esc_html_e('Tablets:', 'slim-mobile-theme-switcher'); ?></strong> <?php esc_html_e('Get the desktop theme (iPads, Android tablets, etc.)', 'slim-mobile-theme-switcher'); ?></li>
                    <li><strong><?php esc_html_e('Desktops:', 'slim-mobile-theme-switcher'); ?></strong> <?php esc_html_e('Get the desktop theme', 'slim-mobile-theme-switcher'); ?></li>
                </ul>
                
                <h3><?php esc_html_e('Manual Theme Switching', 'slim-mobile-theme-switcher'); ?></h3>
                <p><?php esc_html_e('Users can manually switch themes by adding parameters to the URL:', 'slim-mobile-theme-switcher'); ?></p>
                <ul>
                    <li><code>?theme=handheld</code> - <?php esc_html_e('Force mobile theme', 'slim-mobile-theme-switcher'); ?></li>
                    <li><code>?theme=active</code> - <?php esc_html_e('Force desktop theme', 'slim-mobile-theme-switcher'); ?></li>
                </ul>

                <?php if ($this->persistent_override): ?>
                    <p><strong><?php esc_html_e('Current behavior (Persistent Override: ON):', 'slim-mobile-theme-switcher'); ?></strong> <?php esc_html_e('The override persists for 30 days.', 'slim-mobile-theme-switcher'); ?></p>
                <?php else: ?>
                    <p><strong><?php esc_html_e('Current behavior (Persistent Override: OFF):', 'slim-mobile-theme-switcher'); ?></strong> <?php esc_html_e('The override only applies to the current page.', 'slim-mobile-theme-switcher'); ?></p>
                <?php endif; ?>
                
                <h3><?php esc_html_e('Current Detection Status', 'slim-mobile-theme-switcher'); ?></h3>
                <p>
                    <strong><?php esc_html_e('Your device:', 'slim-mobile-theme-switcher'); ?></strong> 
                    <?php 
                    if ($this->is_mobile_phone()) {
                        printf(
                            /* translators: %s: Theme name */
                            esc_html__('Mobile Phone (would see: %s)', 'slim-mobile-theme-switcher'),
                            esc_html($mobile_theme ?: __('not configured', 'slim-mobile-theme-switcher'))
                        );
                    } else {
                        printf(
                            /* translators: %s: Theme name */
                            esc_html__('Desktop/Tablet (would see: %s)', 'slim-mobile-theme-switcher'),
                            esc_html($desktop_theme ?: $current_theme)
                        );
                    }
                    ?>
                </p>
            </div>
        </div>
        <?php
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    new Slim_Mobile_Theme_Switcher();
});
