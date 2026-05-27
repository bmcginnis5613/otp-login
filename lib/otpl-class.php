<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('OtpLoginFront')) {
    class OtpLoginFront
    {
        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            $isEnable = get_option('otpl_enable') ? get_option('otpl_enable') : 0;
            if (!$isEnable) {
                return;
            }

            add_action('wp_footer', array(&$this, 'otpl_popup_html'), 100);
            add_action('wp_ajax_nopriv_otplaction', array(&$this, 'otpl_login_action'));
            add_action('wp_enqueue_scripts', array(&$this, 'otpl_enqueue_scripts_hooks'));
            add_shortcode('otp_login', array(&$this, 'otp_login_func'));

            add_action('login_enqueue_scripts', array(&$this, 'otpl_enqueue_scripts_hooks'));
            add_action('login_footer', array(&$this, 'otpl_popup_html'), 100);
            add_action('login_form', array(&$this, 'otpl_login_page_link'));
        }

        public function otp_login_func($atts)
        {
            $title = isset($atts['title']) ? $atts['title'] : __('Login with OTP', 'otp-login');

            return '<span class="otplogin-shortcode otpl-popup"><a href="#">' . esc_html($title) . '</a></span>';
        }

        public function otpl_login_page_link()
        {
            if (is_user_logged_in() || !get_option('otpl_login_page_enable', 1)) {
                return;
            }

            echo '<p class="otpl-wp-login-link otpl-popup"><a href="#">' . esc_html__('Login with a one-time password', 'otp-login') . '</a></p>';
        }

        public function otpl_enqueue_scripts_hooks()
        {
            if (is_user_logged_in()) {
                return;
            }

            wp_enqueue_script('jquery');

            $script_data = array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'messages' => array(
                    'invalidEmail' => __('Invalid email address.', 'otp-login'),
                    'invalidOtp' => __('Enter the 6 digit code from your email.', 'otp-login'),
                    'requestFailed' => __('The request could not be completed. Please try again.', 'otp-login'),
                ),
            );

            $otplscript = 'window.otplSettings = ' . wp_json_encode($script_data) . ';';
            $otplscript .= <<<'JS'
jQuery(function($) {
    var settings = window.otplSettings || {};
    var messages = settings.messages || {};

    function getMessage(key, fallback) {
        return messages[key] || fallback;
    }

    function setStatus($element, message, className) {
        $element
            .removeClass("error text-danger success text-success")
            .addClass(className)
            .show()
            .html(message);
    }

    function clearFormMessages($form) {
        $form.find(".emailerror, .otperror, .otpestatus")
            .removeClass("error text-danger success text-success")
            .html("");
    }

    function positionLoginPageOtpLink() {
        var $loginForm = $("#loginform");
        var $otpLink = $loginForm.find(".otpl-wp-login-link");
        var $submit = $loginForm.find("p.submit, .submit").last();

        if ($otpLink.length && $submit.length) {
            $otpLink.insertAfter($submit);
        }
    }

    function sendOtp($form) {
        var email = $.trim($form.find("#email").val());
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailPattern.test(email)) {
            setStatus($form.find(".emailerror"), getMessage("invalidEmail", "Invalid email address."), "error text-danger");
            return;
        }

        clearFormMessages($form);

        $.ajax({
            url: settings.ajaxUrl,
            type: "POST",
            data: $form.serialize() + "&action=otplaction&validateotp=0",
            dataType: "json"
        }).done(function(data) {
            if (data && data.status && data.sendotp) {
                $form.find("#sendotp").hide();
                $form.find("#submitotpsec").show();
                $form.find("#email_otp").val("").focus();
                $form.find("#sbmitedemail").text(email);
                setStatus($form.find(".otpestatus"), data.message, "success text-success");
                return;
            }

            setStatus(
                $form.find(".emailerror"),
                data && data.message ? data.message : getMessage("requestFailed", "The request could not be completed. Please try again."),
                "error text-danger"
            );
        }).fail(function() {
            setStatus($form.find(".emailerror"), getMessage("requestFailed", "The request could not be completed. Please try again."), "error text-danger");
        });
    }

    function submitOtp($form) {
        var emailOtp = $.trim($form.find("#email_otp").val());
        var otpPattern = /^[0-9]{6}$/;

        if (!otpPattern.test(emailOtp)) {
            setStatus($form.find(".otperror"), getMessage("invalidOtp", "Enter the 6 digit code from your email."), "error text-danger");
            return;
        }

        $form.find(".otperror").html("");

        $.ajax({
            url: settings.ajaxUrl,
            type: "POST",
            data: $form.serialize() + "&action=otplaction&validateotp=1",
            dataType: "json"
        }).done(function(data) {
            $form.find("#email_otp").val("");

            if (data && data.status) {
                setStatus($form.find(".otpestatus"), data.message, "success text-success");

                if (data.redirect) {
                    window.location.href = data.redirect;
                }

                return;
            }

            setStatus(
                $form.find(".otpestatus"),
                data && data.message ? data.message : getMessage("invalidOtp", "Enter the 6 digit code from your email."),
                "error text-danger"
            );
        }).fail(function() {
            setStatus($form.find(".otpestatus"), getMessage("requestFailed", "The request could not be completed. Please try again."), "error text-danger");
        });
    }

    $(document).on("submit", "#optl-form", function(event) {
        event.preventDefault();

        var $form = $(this);
        if ($form.find("#submitotpsec").is(":visible")) {
            submitOtp($form);
            return;
        }

        sendOtp($form);
    });

    $(document).on("click", "#submitOtp", function(event) {
        event.preventDefault();
        submitOtp($(this).closest("form"));
    });

    $(document).on("click", "#submitotpsec .generateOtp", function(event) {
        event.preventDefault();
        sendOtp($(this).closest("form"));
    });

    $(document).on("click", ".loginback", function() {
        var $form = $(this).closest("form");
        $form.find("#email, #email_otp").val("");
        clearFormMessages($form);
        $form.find("#sendotp").show();
        $form.find("#submitotpsec").hide();
    });

    $(document).on("click", "#otpl_lightbox .close span", function() {
        $("#otpllightbox").empty().hide();
    });

    $(document).on("click", ".otpl-popup a, a.otpl-popup", function(event) {
        event.preventDefault();

        var content = $("#otpl_contact").html();
        if (!content) {
            return;
        }

        var lightboxContent =
            "<div id=\"otpl_lightbox\">" +
                "<div id=\"otpl_content\">" +
                    "<div class=\"close\"><span aria-label=\"Close\" role=\"button\" tabindex=\"0\"></span></div>" +
                    content +
                "</div>" +
            "</div>";

        $("#otpllightbox").html(lightboxContent).hide().fadeIn(200);
    });

    positionLoginPageOtpLink();
    setTimeout(positionLoginPageOtpLink, 250);
});
JS;

            wp_add_inline_script('jquery-core', $otplscript);

            $branding = $this->otpl_get_email_branding();
            $accent_color = esc_attr($branding['accent_color']);

            $otplcss = '
body.logged-in .otpl-popup { display: none; }
form#optl-form { position: relative; }
#otpl-body { background: #f9f9f9; padding: 28px; }
#submitotpsec, .otpinvisible { display: none; }
#otpl_lightbox { position: fixed; inset: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.58); text-align: center; z-index: 999999 !important; clear: both; }
#otpl_lightbox #otpl_content { background: #fff; color: #555; margin: 10vh auto 0; position: relative; z-index: 999999; padding: 0; font-size: 15px !important; max-width: 450px; width: min(90%, 450px); box-shadow: 0 18px 50px rgba(0, 0, 0, 0.22); }
#otpl_lightbox #otpl_content form label { color: #222; display: block; font-size: 16px; line-height: 1.45; margin-bottom: 8px; text-align: left; }
#otpl_lightbox #otpl_content form .req { color: #b32d2e; font-size: 14px; display: inline-block; margin-left: 4px; }
#otpl_lightbox #otpl_content form input, #otpl_lightbox #otpl_content form textarea { border: 1px solid #ccc; color: #333 !important; display: inline-block !important; width: 100% !important; min-height: 42px; padding: 0 10px; box-sizing: border-box; }
#otpl_lightbox #otpl_content form input[type=submit], .login .otpl-wp-login-link a { background: ' . $accent_color . '; border: 0; border-radius: 5px; color: #fff !important; cursor: pointer; display: block; font-size: 16px !important; font-weight: 700 !important; line-height: 1.4; padding: 12px 18px; text-align: center; text-decoration: none; width: 100% !important; box-sizing: border-box; }
#otpl_lightbox #otpl_content form input[type=submit] { margin-top: 12px; }
#otpl_lightbox #otpl_content form #submitotpsec input[type=submit].generateOtp { background: transparent !important; border: 0; color: ' . $accent_color . ' !important; display: inline-block !important; font-size: 14px !important; margin: 8px 0 0; padding: 0; text-decoration: underline; width: auto !important; }
#otpl_lightbox #otpl_content form input[type="submit"]:disabled { background: #ccc; cursor: initial; }
#otpl_lightbox .close { cursor: pointer; position: absolute; top: 10px; right: 10px; z-index: 9; }
#otpl_lightbox #otpl_content .close span { color: #555; display: block; font-size: 28px; height: 28px; line-height: 24px; text-align: center; width: 28px; }
#otpl_lightbox #otpl_content .close span:before { content: "x"; }
#otpl_lightbox .heading { padding: 22px 45px 12px; margin: 0 !important; }
#otpl_lightbox .heading h3 { font-size: 1.35rem; line-height: 1.25; margin: 0; }
#otpl_lightbox #otpl_content p { padding: 0; text-align: left; margin: 0 !important; line-height: 20px; }
span.loginback { color: ' . $accent_color . '; cursor: pointer; display: inline-block; margin-bottom: 14px; text-align: left; width: 100%; }
span.otplogin-shortcode.otpl-popup { border: 1px solid #ccc; padding: 8px 10px; border-radius: 5px; }
.otpl-wp-login-link { clear: both; margin: 14px 0; }
.otpestatus { margin-top: 10px; }
.otpl-register-wrap { margin: 14px 28px 24px !important; text-align: center !important; }
.otpl-register-wrap a { color: ' . $accent_color . '; }
@media (max-width: 767px) {
    #otpl-body { padding: 20px; }
    #otpl_lightbox #otpl_content { margin-top: 6vh; }
    #otpl_lightbox .heading { padding-left: 36px; padding-right: 36px; }
}';

            wp_register_style('otpl-inlinecss', false);
            wp_enqueue_style('otpl-inlinecss');
            wp_add_inline_style('otpl-inlinecss', $otplcss);
        }

        /**
         * @hooks wp_footer, login_footer
         * Add hidden OTP form markup for the popup.
         */
        public function otpl_popup_html()
        {
            if (is_user_logged_in()) {
                return;
            }

            $register_url = get_option('otpl_register_url', '');
            $redirect_to = '';

            if (isset($_REQUEST['redirect_to'])) {
                $redirect_to = esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
            }
            ?>
            <div id="otpllightbox"></div>
            <div id="otplBox" style="display:none" class="otpinvisible">
                <div id="otpl_contact">
                    <div class="otplmsg"></div>
                    <form name="clfrom" id="optl-form" class="otpl-section" action="" method="post" novalidate autocomplete="off" role="form">
                        <div class="otpinvisible">
                            <input type="hidden" name="otplsecurity" value="<?php echo esc_attr(wp_create_nonce('otpl_filed_once_val')); ?>">
                            <input type="hidden" name="otplzplussecurity" value="">
                            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
                        </div>

                        <div class="heading"><h3><?php esc_html_e('One-Time Password Verification', 'otp-login'); ?></h3></div>
                        <div id="otpl-body">
                            <div id="sendotp">
                                <label for="email">
                                    <?php esc_html_e('Enter your email to receive a one-time password', 'otp-login'); ?>
                                    <span class="req">*</span><span class="emailerror req"></span>
                                </label>
                                <input type="email" name="email" id="email" value="" class="otpl-req-fields" size="40">
                                <input type="submit" class="otpl_submit_btn generateOtp" id="generateOtp" value="<?php esc_attr_e('Next', 'otp-login'); ?>">
                            </div>

                            <div id="submitotpsec">
                                <span class="loginback" type="button">&lt; <?php esc_html_e('Back', 'otp-login'); ?></span>
                                <span class="email-otp">
                                    <label for="email_otp">
                                        <?php esc_html_e('Please enter the 6 digit code we sent to your email:', 'otp-login'); ?><br>
                                        <span id="sbmitedemail"></span><span class="req"><span class="otperror"></span></span>
                                    </label>
                                    <input type="text" name="email_otp" id="email_otp" value="" maxlength="6" inputmode="numeric" pattern="[0-9]*">
                                </span>
                                <div class="otpl-submit-sec">
                                    <input type="submit" class="submitOtp" id="submitOtp" value="<?php esc_attr_e('Submit', 'otp-login'); ?>">
                                    <span class="otpestatus req d-inline-block"></span>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php if (!empty($register_url)) : ?>
                        <p class="otpl-register-wrap"><a href="<?php echo esc_url($register_url); ?>"><?php esc_html_e('Register', 'otp-login'); ?></a></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }

        /*
         * Send OTP Email on User Email
         */
        public function otpl_send_otp($email, $otp)
        {
            $otp_message = $this->otpl_get_otp_email_html($email, $otp);
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $from = sanitize_email(get_bloginfo('admin_email'));

            if ($from) {
                $headers[] = 'From: ' . wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES) . ' <' . $from . '>';
            }

            return wp_mail($email, $this->otpl_get_otp_email_subject(), $otp_message, $headers);
        }

        /*
         * Handle all login form requests.
         */
        public function otpl_login_action()
        {
            if (wp_doing_ajax()) {
                check_ajax_referer('otpl_filed_once_val', 'otplsecurity');
            }

            $otplzplussecurity = isset($_POST['otplzplussecurity']) ? sanitize_text_field(wp_unslash($_POST['otplzplussecurity'])) : '';
            $email_otp = isset($_POST['email_otp']) ? sanitize_text_field(wp_unslash($_POST['email_otp'])) : '';
            $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
            $validateotp = isset($_POST['validateotp']) ? sanitize_text_field(wp_unslash($_POST['validateotp'])) : '0';

            if ($otplzplussecurity !== '') {
                wp_send_json(array(
                    'status' => 0,
                    'message' => __('Request has been cancelled.', 'otp-login'),
                    'response' => __('Request has been rejected due to security. Please contact the administrator.', 'otp-login'),
                ));
            }

            if ($validateotp !== '1') {
                $this->otpl_handle_send_otp($email);
            }

            $this->otpl_handle_validate_otp($email, $email_otp);
        }

        private function otpl_handle_send_otp($email)
        {
            if (empty($email) || !is_email($email)) {
                wp_send_json(array('status' => 0, 'message' => __('Enter a valid email address.', 'otp-login'), 'response' => 'Enter email'));
            }

            $user = get_user_by('email', $email);
            if (!$user) {
                wp_send_json(array('status' => 0, 'message' => __('User does not exist.', 'otp-login'), 'response' => 'User does not exist'));
            }

            $lockout_message = $this->otpl_get_lockout_message($user->ID);
            if ($lockout_message) {
                wp_send_json(array('status' => 0, 'message' => $lockout_message, 'response' => 'Account locked'));
            }

            $newotp = wp_rand(100000, 999999);
            update_user_meta($user->ID, 'emilotp', $newotp);

            $otpmail = $this->otpl_send_otp($email, $newotp);
            if (!$otpmail) {
                update_user_meta($user->ID, 'emilotp', '');
                wp_send_json(array(
                    'status' => 0,
                    'sendotp' => 0,
                    'message' => __('The one-time password email could not be sent. Please try again.', 'otp-login'),
                    'response' => 'Email failed',
                ));
            }

            wp_send_json(array(
                'response' => 'Success',
                'message' => sprintf(
                    /* translators: %s: user email address */
                    __('The one-time password has been sent to: %s', 'otp-login'),
                    esc_html($email)
                ),
                'status' => 1,
                'sendotp' => 1,
            ));
        }

        private function otpl_handle_validate_otp($email, $email_otp)
        {
            if (empty($email) || !is_email($email)) {
                wp_send_json(array('status' => 0, 'message' => __('Enter a valid email address.', 'otp-login'), 'response' => 'Enter email'));
            }

            $user = get_user_by('email', $email);
            if (!$user) {
                wp_send_json(array('status' => 0, 'message' => __('User does not exist.', 'otp-login'), 'response' => 'User does not exist'));
            }

            $lockout_message = $this->otpl_get_lockout_message($user->ID);
            if ($lockout_message) {
                wp_send_json(array('status' => 0, 'message' => $lockout_message, 'response' => 'Account locked'));
            }

            if (!preg_match('/^[0-9]{6}$/', $email_otp)) {
                wp_send_json(array('status' => 0, 'message' => __('One-time password does not match.', 'otp-login'), 'response' => 'Invalid OTP'));
            }

            $db_otp = (string) get_user_meta($user->ID, 'emilotp', true);

            if ($db_otp !== '' && hash_equals($db_otp, (string) $email_otp)) {
                wp_set_current_user($user->ID, $user->user_login);
                wp_set_auth_cookie($user->ID);
                do_action('wp_login', $user->user_login, $user);

                $url = $this->otpl_get_redirect_url();

                if (is_user_logged_in()) {
                    update_user_meta($user->ID, 'emilotp', '');
                    update_user_meta($user->ID, 'otpl_login_attempts', 0);

                    wp_send_json(array(
                        'status' => 1,
                        'message' => __('You have successfully logged in.', 'otp-login'),
                        'response' => 'OTP Matched',
                        'sendotp' => 0,
                        'redirect' => $url,
                    ));
                }

                wp_send_json(array(
                    'status' => 0,
                    'message' => __('OTP matched, but WordPress did not complete the login.', 'otp-login'),
                    'response' => 'OTP Matched but not logged in',
                    'sendotp' => 0,
                ));
            }

            $this->otpl_increment_failed_attempts($user->ID);

            wp_send_json(array(
                'status' => 0,
                'message' => __('One-time password does not match.', 'otp-login') . ' <input type="submit" class="generateOtp" value="' . esc_attr__('Resend OTP', 'otp-login') . '" name="resendotp">',
                'response' => 'OTP does not exist',
                'sendotp' => 1,
            ));
        }

        private function otpl_increment_failed_attempts($user_id)
        {
            $failed_attempts = (int) get_user_meta($user_id, 'otpl_login_attempts', true);
            update_user_meta($user_id, 'otpl_login_attempts', $failed_attempts + 1);
            update_user_meta($user_id, 'otpl_last_failed_time', time());
        }

        private function otpl_get_lockout_message($user_id)
        {
            $failed_attempts = (int) get_user_meta($user_id, 'otpl_login_attempts', true);
            $last_failed_time = (int) get_user_meta($user_id, 'otpl_last_failed_time', true);
            $max_attempts = (int) get_option('otpl_login_attempt', 3);
            $lockout_period = (int) get_option('otpl_login_locktime', 3600);

            if ($max_attempts <= 0) {
                $max_attempts = 3;
            }

            if ($lockout_period <= 0) {
                $lockout_period = 3600;
            }

            if ($failed_attempts < $max_attempts) {
                return '';
            }

            $remaining_time = $lockout_period - (time() - $last_failed_time);
            if ($remaining_time <= 0) {
                update_user_meta($user_id, 'otpl_login_attempts', 0);
                return '';
            }

            $remaining_hours = floor($remaining_time / 3600);
            $remaining_minutes = floor(($remaining_time % 3600) / 60);
            $remaining_seconds = $remaining_time % 60;

            return sprintf(
                /* translators: 1: hours, 2: minutes, 3: seconds */
                __('Too many failed attempts. Please try again after %1$d hour(s), %2$d minute(s), and %3$d second(s).', 'otp-login'),
                $remaining_hours,
                $remaining_minutes,
                $remaining_seconds
            );
        }

        private function otpl_get_redirect_url()
        {
            $fallback = get_option('otpl_redirect_url') ? get_option('otpl_redirect_url') : home_url();
            $fallback = wp_validate_redirect($fallback, home_url());
            $redirect_to = '';

            if (isset($_POST['redirect_to'])) {
                $redirect_to = esc_url_raw(wp_unslash($_POST['redirect_to']));
            }

            if (!$redirect_to) {
                return $fallback;
            }

            return wp_validate_redirect($redirect_to, $fallback);
        }

        private function otpl_get_otp_email_subject()
        {
            $subject = get_option('otpl_email_subject', 'Your One-Time Password for {site_name}');
            if (!$subject) {
                $subject = 'Your One-Time Password for {site_name}';
            }

            return strtr($subject, array(
                '{site_name}' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
                '{site_domain}' => parse_url(site_url(), PHP_URL_HOST),
            ));
        }

        private function otpl_get_otp_email_html($email, $otp)
        {
            $branding = $this->otpl_get_email_branding();
            $site_url = site_url();
            $site_name = get_bloginfo('name');

            ob_start();
            ?>
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; background: <?php echo esc_attr($branding['background_color']); ?>;">
                <?php if (!empty($branding['logo_url'])) : ?>
                    <div style="text-align: center; margin-bottom: 30px;">
                        <img src="<?php echo esc_url($branding['logo_url']); ?>"
                            alt="<?php echo esc_attr($site_name); ?>"
                            style="height: <?php echo esc_attr($branding['logo_max_height']); ?>px; max-height: <?php echo esc_attr($branding['logo_max_height']); ?>px; width: auto; display: block; margin: 0 auto;"
                            height="<?php echo esc_attr($branding['logo_max_height']); ?>"
                            width="auto">
                    </div>
                <?php endif; ?>
                <p style="font-size: 16px; color: <?php echo esc_attr($branding['text_color']); ?>;">
                    <?php
                    echo wp_kses_post(sprintf(
                        /* translators: 1: site name, 2: user email address */
                        __('We received a request to sign in to %1$s for the account associated with %2$s.', 'otp-login'),
                        '<strong>' . esc_html($site_name) . '</strong>',
                        '<strong>' . esc_html($email) . '</strong>'
                    ));
                    ?>
                </p>
                <p style="font-size: 16px; color: <?php echo esc_attr($branding['text_color']); ?>;">
                    <?php esc_html_e('Enter this code on the login screen to finish signing in:', 'otp-login'); ?>
                </p>
                <p style="text-align: center; margin: 30px 0;">
                    <span style="background-color: <?php echo esc_attr($branding['accent_color']); ?>; color: #fff; display: inline-block; padding: 12px 25px; border-radius: 5px; text-decoration: none; font-size: 24px; font-weight: bold; letter-spacing: 4px;">
                        <?php echo esc_html($otp); ?>
                    </span>
                </p>
                <p style="font-size: 16px; color: <?php echo esc_attr($branding['text_color']); ?>;">
                    <?php esc_html_e('If you did not request this code, you can safely ignore this email.', 'otp-login'); ?>
                </p>
                <p style="font-size: 16px; color: <?php echo esc_attr($branding['text_color']); ?>;">
                    <?php esc_html_e('Website:', 'otp-login'); ?>
                    <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_url); ?></a>
                </p>
            </div>
            <?php
            return ob_get_clean();
        }

        private function otpl_get_email_branding()
        {
            $password_reset_settings = get_option('custom_password_reset_settings', array());
            if (!is_array($password_reset_settings)) {
                $password_reset_settings = array();
            }

            $logo_url = get_option('otpl_email_logo_url', '');
            if (!$logo_url && !empty($password_reset_settings['logo_url'])) {
                $logo_url = $password_reset_settings['logo_url'];
            }

            $logo_max_height = (int) get_option('otpl_email_logo_max_height', 0);
            if ($logo_max_height <= 0 && !empty($password_reset_settings['logo_max_height'])) {
                $logo_max_height = (int) $password_reset_settings['logo_max_height'];
            }

            if ($logo_max_height <= 0) {
                $logo_max_height = 60;
            }

            $accent_color = get_option('otpl_email_accent_color', '');
            if (!$accent_color && !empty($password_reset_settings['button_color'])) {
                $accent_color = $password_reset_settings['button_color'];
            }

            return array(
                'logo_url' => esc_url_raw($logo_url),
                'logo_max_height' => min($logo_max_height, 200),
                'accent_color' => $this->otpl_normalize_hex_color($accent_color, '#0073aa'),
                'background_color' => $this->otpl_normalize_hex_color(get_option('otpl_email_background_color', '#f9f9f9'), '#f9f9f9'),
                'text_color' => $this->otpl_normalize_hex_color(get_option('otpl_email_text_color', '#555555'), '#555555'),
            );
        }

        private function otpl_normalize_hex_color($color, $fallback)
        {
            $color = sanitize_hex_color($color);
            return $color ? $color : $fallback;
        }
    }
}

if (class_exists('OtpLoginFront')) {
    $OtpLoginFront = new OtpLoginFront();
}
