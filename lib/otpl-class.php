<?php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (!class_exists('OtpLoginFront')) {
    class OtpLoginFront
    {
        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            // Is enable settings from admin
            $isEnable = get_option('otpl_enable') ? get_option('otpl_enable') : 0;
            if (!$isEnable) {
                return;
            }
            //front-end hooks action
            add_action('wp_footer', array(&$this, 'otpl_popup_html'), 100);
            add_action('wp_ajax_nopriv_otplaction', array(&$this, 'otpl_login_action'));
            add_action('wp_enqueue_scripts', array(&$this, 'otpl_enqueue_scripts_hooks'));
            add_action('init', array(&$this, 'otpl_handle_magic_login'));
            //add_action( 'wp_ajax_otplaction', array(&$this, 'otpl_login_action') );

            add_shortcode('otp_login', array(&$this, 'otp_login_func'));
        } // END public function __construct

        public function otp_login_func($atts)
        {
            $title = isset($atts['title']) ? $atts['title'] : 'Login with OTP';

            $button = '<span class="otplogin-shortcode otpl-popup"><a href="javascript:">' . $title . '</a></span>';

            return $button;
        }

        public function otpl_enqueue_scripts_hooks()
        {
            //check user logged or not
            if (is_user_logged_in())
                return;

            $otplscript = ' jQuery(document).ready(function() {
                
                jQuery(document).on("click", "#otpl_lightbox .close span", function() { 
                    jQuery("#otpllightbox").html("");
                    jQuery("#otpl_lightbox").hide().fadeOut(1000);
                });
                
                jQuery(document).on("submit", "#optl-form", function(event) {
                    var formid = "#optl-form";
                    event.preventDefault();
                    var email = jQuery("#optl-form #email").val();
                    var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                    
                    if(!regex.test(email)) {
                        jQuery(".emailerror").text(" Invalid Email");
                        return false;
                    } else {
                        jQuery(".emailerror").text("");
                    }
                    
                    var post_url = "' . admin_url('admin-ajax.php') . '";
                    var request_method = jQuery(formid).attr("method");
                    var form_data = jQuery(formid).serialize()+ "&action=otplaction&sendmagiclink=1";
                    
                    // Disable submit button during processing
                    jQuery("#generateLink").prop("disabled", true).val("Sending...");
                    
                    jQuery.ajax({
                        url : post_url,
                        type: request_method,
                        data : form_data,
                        cache: false,             
                        processData: false, 
                    }).done(function(response){ 
                        var data = JSON.parse(response);
                        var divclass = "error text-danger";
                        
                        if(data.status) {
                            divclass = "success text-success";
                            jQuery("#sendlink").hide();
                            jQuery("#linksent").show();
                            jQuery("#senttoemail").text(email);
                            jQuery(formid+" .linkstatus").addClass(divclass).show("slow").html(data.message);
                        } else {
                            jQuery(formid+" .emailerror").addClass(divclass).show().html(data.message);
                            jQuery("#generateLink").prop("disabled", false).val("Send Magic Link");
                        }
                    });
                });
                
                jQuery(".otpl-popup a").click(function(e) {
                    e.preventDefault();
                    var content = jQuery("#otpl_contact").html();
                    var otpl_lightbox_content = 
                        "<div id=\"otpl_lightbox\">" +
                            "<div id=\"otpl_content\">" +
                            "<div class=\"close\"><span></span></div>"  + content  +
                            "</div>" +	
                        "</div>";
                    jQuery("#otpllightbox").append(otpl_lightbox_content).hide().fadeIn(1000);
    });
    
});';

            wp_add_inline_script('jquery-core', $otplscript);

            // CSS 
            $otplcss = 'body.logged-in .otpl-popup { display: none; } 
                        form#optl-form {position: relative;}
                        #otpl-body {background: #f9f9f9;padding: 3rem;}
                        #linksent, .otpinvisible{display:none;}
                        #otpl_lightbox #otpl_content form label{color:#000;display:block;font-size:18px;}
                        #otpl_lightbox #otpl_content form .req{color:red;font-size:14px; display:inline-block;}
                        #otpl_lightbox #otpl_content form input,#otpl_lightbox #otpl_content form textarea{border:1px solid #ccc;color:#666!important;display:inline-block!important;width:100%!important; min-height:40px;padding:0px 10px;}
                        #otpl_lightbox #otpl_content form input[type=submit]{background: #E73E34;color: #FFF !important;font-size: 100% !important;font-weight: 700 !important;width: 100% !important;padding: 10px 0px;margin-top: 10px;}
                        #otpl_lightbox #otpl_content form input[type="submit"]:disabled {background: #ccc;cursor: initial;}
                        #otpl_lightbox #otpl_content form input.cswbfs_submit_btn:hover{background:#000;cursor:pointer}
                        .link-sent-message {text-align: center; padding: 20px;}
                        .link-sent-message h4 {color: #E73E34; margin-bottom: 10px;}
                        .link-sent-message p {margin-bottom: 10px; line-height: 1.5;}
                        #otpl_lightbox .close {cursor: pointer; position: absolute; top: 10px; right: 10px; left: 0px; z-index: 9;}
                        
                        /* Lightbox positioning styles */
                        #otpl_lightbox{position:fixed;top:0;left:0;width:100%;height:100%;background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAA9JREFUeNpiYGBg2AwQYAAAuAC01qHx9QAAAABJRU5ErkJggg==);text-align:center;z-index:999999!important;clear:both}
                        #otpl_lightbox #otpl_content{background: #FFF;color: #666;margin: 10% auto 0;position: relative;z-index: 999999;padding: 0px;font-size: 15px !important;height: auto;overflow: initial;max-width: 450px;}
                        #otpl_lightbox #otpl_content p{padding:1%;text-align:left;margin:0!important;line-height: 20px;}
                        #otpl_lightbox #otpl_content .close span{background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABmJLR0QA/wD/AP+gvaeTAAAAjklEQVRIie2Vyw2AIBQER3uQaIlarhwsRy+Y4AfCPuTmnEx0dwg+FH4MzIAz5FzIZlmAHfCixIXMHjqSDMAaHtyAqaD8nhnVQE4ilysSc3mJpLo8J/ms/CSeEH+7tozzK/GqpZX3FdKuInuh6Ra9vVDLYSwuT92TJSWjaJYocy5LLIdIkjT/XEPjH87PgwNng1K28QMLlAAAAABJRU5ErkJggg==) right 0 no-repeat;display:block;float:right;height:36px;height:36px;width:100%}
                        #otpl_lightbox #otpl_content .close span:hover,#otpl_lightbox .otplmsg:hover{cursor:pointer}
                        #otpl_lightbox .heading {padding: 10px 5px;margin: 0 !important;}
                        #otpl_lightbox .heading h3{font-size:1.5rem;} 
                        span.otplogin-shortcode.otpl-popup {border: 1px solid #ccc;padding: 8px 10px;border-radius: 10px;}
                        
                        /* Responsive styles */
                        @media (max-width:767px){#otpl-body {padding: 1rem;}#otpl_lightbox #otpl_content{width:90%}#otpl_lightbox #otpl_content p{font-size:12px!important}}
                        @media (max-width:800px) and (min-width:501px){#otpl_lightbox #otpl_content{width:70%}#otpl_lightbox #otpl_content p{font-size:12px!important}}
                        @media (max-width:2200px) and (min-width:801px){#otpl_lightbox #otpl_content{width:60%}#otpl_lightbox #otpl_content p{font-size:15px!important}}';

            // register css  
            wp_register_style('otpl-inlinecss', false);
            wp_enqueue_style('otpl-inlinecss');
            wp_add_inline_style('otpl-inlinecss', $otplcss);
        }

        /**
         * @hooks wp_footer
         * hook to add html into site footer
         */
        public function otpl_popup_html()
        {
            // Exit early if the user is logged in.
            if (is_user_logged_in()) {
                return;
            }

            // Get options from the database
            $enable_login = get_option('otpl_enable', 0);
            $register_url = get_option('otpl_register_url', '');

            // Build the form HTML
            $otpl_form_html = '';

            // Lightbox and main box wrapper
            $otpl_form_html .= '<div id="otpllightbox"></div>';
            $otpl_form_html .= '<div id="otplBox" style="display:none" class="otpinvisible">';
            $otpl_form_html .= '<div id="otpl_contact">';
            $otpl_form_html .= '<div class="otplmsg"></div>';

            // Begin the form
            $otpl_form_html .= '<form name="clfrom" id="optl-form" class="otpl-section" action="" method="post" novalidate autocomplete="off" role="form">';

            // Add security fields
            $otpl_form_html .= '<div class="otpinvisible">';
            $otpl_form_html .= '<input type="hidden" name="otplsecurity" value="' . esc_attr(wp_create_nonce('otpl_filed_once_val')) . '">';
            $otpl_form_html .= '<input type="hidden" name="otplzplussecurity" value="">';
            $otpl_form_html .= '</div>';

            // Magic Link Form Fields
            $otpl_form_html .= '<div class="heading"><h3>' . esc_html__('Magic Link Login', 'otp-login') . '</h3></div>';
            $otpl_form_html .= '<div id="otpl-body">';
            
            // Send Link Section
            $otpl_form_html .= '<div id="sendlink">';
            $otpl_form_html .= '<label for="email">' . esc_html__('Enter your email to receive a magic login link', 'otp-login') . '<span class="req">*</span><span class="emailerror req"></span></label>';
            $otpl_form_html .= '<input type="email" name="email" id="email" value="" class="otpl-req-fields" size="40"> ';
            $otpl_form_html .= '<input type="submit" class="otpl_submit_btn generateLink" id="generateLink" value="' . esc_attr__('Send Magic Link', 'otp-login') . '">';
            $otpl_form_html .= '</div>';

            // Link Sent Confirmation
            $otpl_form_html .= '<div id="linksent" style="display:none;">';
            $otpl_form_html .= '<div class="link-sent-message">';
            $otpl_form_html .= '<h4>' . esc_html__('Check your email!', 'otp-login') . '</h4>';
            $otpl_form_html .= '<p>' . esc_html__('We\'ve sent a magic login link to:', 'otp-login') . '<br><strong><span id="senttoemail"></span></strong></p>';
            $otpl_form_html .= '<p>' . esc_html__('Click the link in your email to log in. The link will expire in 15 minutes.', 'otp-login') . '</p>';
            $otpl_form_html .= '<span class="linkstatus req d-inline-block"></span>';
            $otpl_form_html .= '</div>';
            $otpl_form_html .= '</div>';
            
            $otpl_form_html .= '</div>'; // End of otpl-body
            $otpl_form_html .= '</form>'; // End of form
            $otpl_form_html .= '</div>'; // End of otpl_contact

            // Add registration URL if it exists
            if (!empty($register_url)) {
                $otpl_form_html .= '<a href="' . esc_url($register_url) . '" class="otpl-register">' . esc_html__('Register', 'otp-login') . '</a>';
            }

            $otpl_form_html .= '</div>'; // End of otplBox

            $allowed_html = array(
                'form' => array('action' => true, 'method' => true, 'id' => true, 'class' => true),
                'input' => array('type' => true, 'name' => true, 'value' => true, 'id' => true, 'class' => true, 'checked' => true, 'placeholder' => true),
                'label' => array('for' => true, 'class' => true),
                'select' => array('name' => true, 'id' => true, 'class' => true),
                'option' => array('value' => true, 'selected' => true),
                'textarea' => array('name' => true, 'id' => true, 'class' => true, 'rows' => true, 'cols' => true, 'placeholder' => true),
                'div' => array('class' => true, 'id' => true, 'style' => true),
                'span' => array('class' => true, 'id' => true),
                'br' => array(),
                'p' => array(),
                'h4' => array(),
                'strong' => array(),
                'a' => array('href' => true, 'class' => true),
            );

            echo wp_kses($otpl_form_html, $allowed_html);
        }

        /*
         * Send a magic link to the users's email address
         * 
         * */
        public function otpl_send_magic_link($email, $token, $user_id)
        {
            $magic_link = add_query_arg(array(
                'action' => 'otpl_magic_login',
                'token' => $token,
                'user_id' => $user_id
            ), home_url());

            $magic_link_message = '<table width="50%" cellpadding="0" cellspacing="0" align="center" bgcolor="f5f5f5">
                                <tr>
                                    <td>
                                        <table width="650" align="center">
                                            <tr>
                                                <td>
                                                    <p class="font_18 pd_lft_25">We have received a login request for your account.</p>
                                                    <p class="font_17">Click the link below to log in:</p>
                                                    <p style="text-align: center; margin: 20px 0;">
                                                        <a href="' . esc_url($magic_link) . '" style="background: #E73E34; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Log in to ' . get_bloginfo('name') . '</a>
                                                    </p>
                                                    <p class="font_17">Or copy and paste this link into your browser:</p>
                                                    <p style="word-break: break-all;">' . esc_url($magic_link) . '</p>
                                                    <p class="font_17">This link will expire in 15 minutes.</p>
                                                    <p class="font_17">Website: ' . home_url() . '</p>
                                                </td>
                                            </tr>
                                        </table>
                            </table>';

            $from = get_bloginfo('admin_email');
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            
            if ($from != '') {
                $headers[] = 'From:' . $from;
            }

            $mail = wp_mail($email, "Your Magic Login Link for " . get_bloginfo('name'), $magic_link_message, $headers);
            return $mail;
        }

        /*
         * Handle all login form request
         * 
         * */
        public function otpl_login_action()
        {
            global $wpdb;

            // check security 
            if (wp_doing_ajax()) {
                check_ajax_referer('otpl_filed_once_val', 'otplsecurity');
            }

            $otplzplussecurity = isset($_POST['otplzplussecurity']) ? sanitize_text_field(wp_unslash($_POST['otplzplussecurity'])) : '';
            $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
            $sendmagiclink = isset($_POST['sendmagiclink']) ? sanitize_text_field(wp_unslash($_POST['sendmagiclink'])) : '';

            // check zplus security
            if (!isset($otplzplussecurity) || (isset($otplzplussecurity) && $otplzplussecurity != '')) {
                echo wp_json_encode(array('status' => 0, 'message' => 'Request has been cancelled.', 'response' => 'Request has been rejected due to security! Please contact administrator.'));
                wp_die();
            }

            // Generate and send magic link
            if ($sendmagiclink) {
                // required fields
                if (empty($email)) {
                    echo wp_json_encode(array('status' => 0, 'message' => 'Validation error', 'response' => 'Enter email'));
                    wp_die();
                }

                // check if user already registered
                $user_id = email_exists($email);

                if (!$user_id && false == email_exists($email)) {
                    echo wp_json_encode(array('status' => 0, 'message' => 'User does not exist.', 'response' => 'User does not exist'));
                    wp_die();
                } else {
                    // Check failed attempts and lockout (same logic as before)
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

                    if ($failed_attempts >= $max_attempts) {
                        $time_since_failed = time() - $last_failed_time;
                        $remaining_time = $lockout_period - $time_since_failed;

                        if ($remaining_time > 0) {
                            $remaining_hours = floor($remaining_time / 3600);
                            $remaining_minutes = floor(($remaining_time % 3600) / 60);
                            $remaining_seconds = $remaining_time % 60;

                            $message = sprintf(
                                'Too many failed attempts. Please try again after %d hour(s), %d minute(s), and %d second(s).',
                                $remaining_hours,
                                $remaining_minutes,
                                $remaining_seconds
                            );

                            echo wp_json_encode(array('status' => 0, 'message' => $message, 'response' => 'Account locked'));
                            wp_die();
                        } else {
                            update_user_meta($user_id, 'otpl_login_attempts', 0);
                        }
                    }

                    // Generate magic link token
                    $token = wp_generate_password(32, false);
                    $expiry = time() + (15 * 60); // 15 minutes from now
                    
                    // Store token with expiry
                    update_user_meta($user_id, "magic_login_token", $token);
                    update_user_meta($user_id, "magic_login_expiry", $expiry);

                    // Send magic link email
                    $magic_mail = $this->otpl_send_magic_link($email, $token, $user_id);

                    if (!$magic_mail) {
                        echo wp_json_encode(array('status' => 0, 'message' => 'Failed to send magic link. Please try again.', 'response' => 'Email failed'));
                    } else {
                        echo wp_json_encode(array('status' => 1, 'message' => 'Magic login link sent successfully!', 'response' => 'Success'));
                    }

                    wp_die();
                }
            }
        }

         /*
         * Handle the magic link
         * 
         * */
        public function otpl_handle_magic_login()
        {
            if (isset($_GET['action']) && $_GET['action'] === 'otpl_magic_login') {
                $token = sanitize_text_field($_GET['token']);
                $user_id = intval($_GET['user_id']);

                if (empty($token) || empty($user_id)) {
                    wp_die('Invalid magic link.');
                }

                $stored_token = get_user_meta($user_id, "magic_login_token", true);
                $expiry = get_user_meta($user_id, "magic_login_expiry", true);

                // Check if token is valid and not expired
                if ($stored_token !== $token) {
                    // Increment failed attempts for invalid token
                    $failed_attempts = (int) get_user_meta($user_id, 'otpl_login_attempts', true);
                    $failed_attempts = $failed_attempts + 1;
                    update_user_meta($user_id, 'otpl_login_attempts', $failed_attempts);
                    update_user_meta($user_id, 'otpl_last_failed_time', time());
                    
                    wp_die('Invalid or expired magic link.');
                }

                if (time() > $expiry) {
                    wp_die('This magic link has expired. Please request a new one.');
                }

                // Log in the user
                $user = get_userdata($user_id);
                wp_set_current_user($user_id, $user->user_login);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $user->user_login, $user);

                // Clean up the token
                delete_user_meta($user_id, "magic_login_token");
                delete_user_meta($user_id, "magic_login_expiry");
                
                // Reset failed attempts on successful login
                update_user_meta($user_id, 'otpl_login_attempts', 0);

                // Redirect to specified URL or home
                $redirect_url = get_option('otpl_redirect_url') ? get_option('otpl_redirect_url') : home_url();
                wp_redirect($redirect_url);
                exit;
            }
        }
    }
}

//init class
if (class_exists('OtpLoginFront')) {
    // instantiate the plugin class
    $OtpLoginFront = new OtpLoginFront();
}