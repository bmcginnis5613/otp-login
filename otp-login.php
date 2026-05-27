<?php
/*
Plugin Name: Login with OTP
Description: Allows login with one-time password (OTP). It also allows WooCommerce customers to login with OTP.
Author: FirstTracks Marketing
Author URI: https://firsttracksmarketing.com/
Version: 1.1.0
Update URI: false
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'OtpLogin' ) ) {
	
    class OtpLogin   {
        /**
         * Construct the plugin object
         */
        public function __construct()  {
			// Installation and uninstallation hooks
			register_activation_hook(__FILE__, array(&$this, 'otpl_activate'));
			register_deactivation_hook(__FILE__, array(&$this, 'otpl_deactivate'));
			//backend hooks action
			add_filter("plugin_action_links_".plugin_basename(__FILE__), array(&$this,'otpl_settings_link'));
			add_action('admin_init', array(&$this, 'otpl_admin_init'));
			add_action('admin_menu', array(&$this, 'otpl_add_menu'));
			add_action('admin_enqueue_scripts', array(&$this, 'otpl_admin_enqueue_scripts'));
			add_action( 'admin_bar_menu', array(&$this,'toolbar_link_to_otpl'), 999 );
            
        } // END public function __construct
		/**
		 * hook to add link under adminmenu bar
		 */		
		public function toolbar_link_to_otpl( $wp_admin_bar ) {
			
			$user = wp_get_current_user();
			if ( !current_user_can( 'administrator' ) && is_admin() ) {
				return;
			}
			
			$args = array(
				'id'    => 'otpl_menu_bar',
				'title' => 'OTP Login',
				'href'  => admin_url('options-general.php?page=otp-login'),
				'meta'  => array( 'class' => 'otpl-toolbar-page' )
			);
			$wp_admin_bar->add_node( $args );
			//second lavel
			$wp_admin_bar->add_node( array(
				'id'    => 'otpl-second-sub-item',
				'parent' => 'otpl_menu_bar',
				'title' => 'Settings',
				'href'  => admin_url('options-general.php?page=otp-login'),
				'meta'  => array(
					'title' => __('Settings','otp-login'),
					'target' => '_self',
					'class' => 'otpl_menu_item_class'
				),
			));
		}
		/**
		 * hook into WP's admin_init action hook
		 */
		public function otpl_admin_init()
		{
			// Set up the settings for this plugin
			$this->otpl_init_settings();
			// Possibly do additional admin_init tasks
		} // END public static function activate
		/**
		 * Initialize some custom settings
		 */     
		public function otpl_init_settings() {
			
			// Register plugin settings with proper sanitization
			register_setting('otpl', 'otpl_enable', array(
				'sanitize_callback' => 'absint'
			));

			register_setting('otpl', 'otpl_redirect_url', array(
				'sanitize_callback' => 'esc_url_raw'
			));

			register_setting('otpl', 'otpl_message', array(
				'sanitize_callback' => 'sanitize_text_field'
			));

			register_setting('otpl', 'otpl_register_url', array(
				'sanitize_callback' => 'esc_url_raw'
			));

			register_setting('otpl', 'otpl_login_attempt', array(
				'sanitize_callback' => 'absint'
			));

			register_setting('otpl', 'otpl_login_locktime', array(
				'sanitize_callback' => 'absint'
			));

			register_setting('otpl', 'otpl_login_page_enable', array(
				'sanitize_callback' => 'absint'
			));

			register_setting('otpl', 'otpl_email_logo_url', array(
				'sanitize_callback' => 'esc_url_raw'
			));

			register_setting('otpl', 'otpl_email_logo_max_height', array(
				'sanitize_callback' => array(&$this, 'otpl_sanitize_logo_height')
			));

			register_setting('otpl', 'otpl_email_accent_color', array(
				'sanitize_callback' => array(&$this, 'otpl_sanitize_hex_color')
			));

			register_setting('otpl', 'otpl_email_background_color', array(
				'sanitize_callback' => array(&$this, 'otpl_sanitize_hex_color')
			));

			register_setting('otpl', 'otpl_email_text_color', array(
				'sanitize_callback' => array(&$this, 'otpl_sanitize_hex_color')
			));

			register_setting('otpl', 'otpl_email_subject', array(
				'sanitize_callback' => 'sanitize_text_field'
			));

		} // END public function otpl_init_settings()

		public function otpl_sanitize_logo_height($height) {
			$height = absint($height);
			if ($height < 1) {
				return 60;
			}

			return min($height, 200);
		}

		public function otpl_sanitize_hex_color($color) {
			$color = sanitize_hex_color($color);
			return $color ? $color : '';
		}

		public function otpl_admin_enqueue_scripts($hook) {
			if ($hook !== 'settings_page_otp-login') {
				return;
			}

			wp_enqueue_media();
			wp_enqueue_script('wp-color-picker');
			wp_enqueue_style('wp-color-picker');
			wp_enqueue_style('otpl_admin_style', plugins_url('css/otpl-admin.css', __FILE__));
			wp_enqueue_script('otpl_admin_script', plugins_url('js/otpl-admin.js', __FILE__), array('jquery'), false, true);

			wp_add_inline_script('wp-color-picker', '
				jQuery(function($) {
					$(".otpl-color-field").wpColorPicker();

					$("#otpl_upload_logo_button").on("click", function(e) {
						e.preventDefault();

						var frame = wp.media({
							title: "Choose OTP Email Logo",
							button: {
								text: "Use this image"
							},
							multiple: false
						});

						frame.on("select", function() {
							var attachment = frame.state().get("selection").first().toJSON();
							$("#otpl_email_logo_url").val(attachment.url);

							if (!$("#otpl_logo_preview").length) {
								$("#otpl_email_logo_url").closest("th").append("<div id=\"otpl_logo_preview\" style=\"margin-top: 10px;\"></div>");
							}

							$("#otpl_logo_preview").html("<img src=\"" + attachment.url + "\" style=\"max-height: 60px; border: 1px solid #ddd; padding: 5px; background: #fff;\" />");
						});

						frame.open();
					});
				});
			');
		}
		/**
		 * add a menu
		 */     
		public function otpl_add_menu()	{
			add_options_page('OTP Login Settings', 'OTP Login', 'manage_options', 'otp-login', array(&$this, 'otpl_settings_page'));
		} // END public function add_menu()

		/**
		 * Menu Callback
		 */     
		public function otpl_settings_page()
		{
			if (!current_user_can('manage_options')) {
               wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'otp-login'));
            }

			// Render the settings template
			include(sprintf("%s/lib/settings.php", dirname(__FILE__)));
		} // END public function plugin_settings_page()
        /**
         * Activate the plugin
         */
        public function otpl_activate()
        {
            // Do nothing
        } // END public static function activate
    
        /**
         * Deactivate the plugin
         */     
        public function otpl_deactivate()
        {
            // Do nothing
        } // END public static function deactivate
        // Add the settings link to the plugins page
		public function otpl_settings_link($links)
		{ 
			$settings_link = '<a href="options-general.php?page=otp-login">Settings</a>'; 
			array_unshift($links, $settings_link); 
			return $links; 
		}
    } // END class wp_optimize_site
} // END if(!class_exists('OtpLogin'))

if(class_exists('OtpLogin'))
{
    // instantiate the plugin class
    $OtpLogintemplate = new OtpLogin();
}
// Render the hooks functions
include(sprintf("%s/lib/otpl-class.php", dirname(__FILE__)));
