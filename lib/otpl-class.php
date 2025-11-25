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
            // Is enable settings from admin
            $isEnable = get_option('otpl_enable') ? get_option('otpl_enable') : 0;
            if (!$isEnable) {
                return;
            }
            //front-end hooks action
            add_action('wp_footer', array(&$this, 'otpl_popup_html'), 100);
            add_action('wp_ajax_nopriv_otplaction', array(&$this, 'otpl_login_action'));
            add_action('wp_enqueue_scripts', array(&$this, 'otpl_enqueue_scripts_hooks'));
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
			
				jQuery(document).on("click", "#otpl_lightbox .close span", function() { jQuery("#otpllightbox").html("");jQuery("#otpl_lightbox").hide().fadeOut(1000);});
			
			
			jQuery(document).on("submit", "#optl-form", function(event) {
				var formid = "#optl-form";
				event.preventDefault(); //prevent default action 
				var email = jQuery("#optl-form #email").val();
				var email_otp = jQuery("#optl-form #email_otp").val();
				var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
				if(!regex.test(email))
				{
				jQuery(".emailerror").text(" Invalid Email");
				return false;
				}else{
				//jQuery("#cl-login #email").removeAttr("disabled");
				jQuery(".emailerror").text("");
				}
					var post_url = "' . admin_url('admin-ajax.php') . '"; //get form action url
					var request_method = jQuery(formid).attr("method"); //get form GET/POST method
					var form_data = jQuery(formid).serialize()+ "&action=otplaction&validateotp=0"; //Encode form elements for submission
					jQuery.ajax({
						url : post_url,
						type: request_method,
						data : form_data,
						cache: false,             
						processData: false, 
					}).done(function(response){ 
						var data = JSON.parse(response);
									   var divclass = "error text-danger";
									   if(data.sendotp){
									   divclass = "success text-success";
									   jQuery("#sendotp").hide();jQuery("#submitotpsec").show();
									   jQuery(formid+" #submitotpsec #email_otp").val("");
									   jQuery("#submitotpsec #sbmitedemail").text(email);
									   jQuery(formid+" .otpestatus").addClass(divclass).show("slow").html("").html(data.message);
									   }else{
									   jQuery(formid+" .emailerror").addClass(divclass).show().html("").html(data.message);
									   console.log(data.response);
									   }
									   
					});
				});
				//validate otp
				jQuery(document).on("click", "#submitOtp", function(event){
				var formid = "#optl-form";
				event.preventDefault(); //prevent default action 
				var email = jQuery("#optl-form #email").val();
				var email_otp = jQuery("#optl-form #email_otp").val();
				var compare=/^[0-9]{1,6}$/g;
				if(email_otp=="" || !compare.test(email_otp))
				{
				jQuery(".otperror").html(" Invalid OTP");
				return false;
				}else{
				//jQuery("#optl-form #email_otp").removeAttr("disabled");
				jQuery(".otperror").html("");
				}
					var form_data = jQuery(formid).serialize()+ "&action=otplaction&validateotp=1";
					jQuery(formid+" #submitotp #email_otp").val("");
					jQuery.ajax({
						url : "' . admin_url('admin-ajax.php') . '",
						type: "POST",
						data : form_data,
						cache: false,             
						processData: false, 
					}).done(function(response){ //
						var data = JSON.parse(response);
						jQuery("#submitotpsec #email_otp").val("");
									   if(data.status)
									   {
									   divclass = "success text-success";
									   jQuery(".otpestatus").html(data.message);
									   var redirecturl = data.redirect;
									   if(typeof redirecturl !== "undefined")
									   {
										 document.location.href = redirecturl;
									   }
									   }else{
									   jQuery(".otpestatus").html(" Invalid OTP");
									   }
									   
					});
				});
				jQuery(document).on("click", ".loginback",function(){
				jQuery("#optl-form #email").val("");jQuery("#submitotpsec #email_otp").val("");
				jQuery(".emailerror").html("");
				jQuery(".otperror").html("");
				jQuery(".otpestatus").html("");
				jQuery("#sendotp").show();
				jQuery("#submitotpsec").hide();
				});
				
				jQuery(".otpl-popup a").click(function(e) {
					e.preventDefault();
					var content =jQuery("#otpl_contact").html();
							var otpl_lightbox_content = 
							"<div id=\"otpl_lightbox\">" +
								"<div id=\"otpl_content\">" +
								"<div class=\"close\"><span></span></div>"  + content  +
								"</div>" +	
							"</div>";
							//insert lightbox HTML into page
							jQuery("#otpllightbox").append(otpl_lightbox_content).hide().fadeIn(1000);
				});
			    
			});';

            wp_add_inline_script('jquery-core', $otplscript);

            // CSS 
            $otplcss = 'body.logged-in .otpl-popup { display: none; } form#optl-form {position: relative;}#otpl-body {background: #f9f9f9;padding: 3rem;}#submitotpsec, .otpinvisible{display:none;}#otpl_lightbox #otpl_content form label{color:#000;display:block;font-size:18px;}span.loginback {cursor: pointer;z-index: 99;top: 6px;position: absolute;left: 0px;padding: 2px 15px;color: #e96125;}#otpl_lightbox #otpl_content form .req{color:red;font-size:14px; display:inline-block;}#otpl_lightbox #otpl_content form input,#otpl_lightbox #otpl_content form textarea{border:1px solid #ccc;color:#666!important;display:inline-block!important;width:100%!important; min-height:40px;padding:0px 10px;}#otpl_lightbox #otpl_content form input[type=submit]{background: #00AEEF;color: #FFF !important;font-size: 100% !important;font-weight: 700 !important;width: 100% !important;padding: 10px 0px;margin-top: 10px;}#otpl_lightbox #otpl_content form #submitotpsec input[type=submit].generateOtp {cursor: pointer;  text-decoration: underline;background: none !important; border: 0px; color: #00AEEF !important; padding: 0px; outline: none; }#otpl_lightbox #otpl_content form input[type="submit"]:disabled {background: #ccc;cursor: initial;}#otpl_lightbox #otpl_content form input.cswbfs_submit_btn:hover{background:#000;cursor:pointer}#otpl_lightbox .close {cursor: pointer; position: absolute; top: 10px; right: 10px; left: 0px; z-index: 9;}@media (max-width:767px){#otpl-body {padding: 1rem;}#otpl_lightbox #otpl_content{width:90%}#otpl_lightbox #otpl_content p{font-size:12px!important}}@media (max-width:800px) and (min-width:501px){#otpl_lightbox #otpl_content{width:70%}#otpl_lightbox #otpl_content p{font-size:12px!important}}@media (max-width:2200px) and (min-width:801px){#otpl_lightbox #otpl_content{width:60%}#otpl_lightbox #otpl_content p{font-size:15px!important}}#otpl_lightbox{position:fixed;top:0;left:0;width:100%;height:100%;background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAA9JREFUeNpiYGBg2AwQYAAAuAC01qHx9QAAAABJRU5ErkJggg==);text-align:center;z-index:999999!important;clear:both}#otpl_lightbox #otpl_content{background: #FFF;color: #666;margin: 10% auto 0;position: relative;z-index: 999999;padding: 0px;font-size: 15px !important;height: 250px;overflow: initial;max-width: 450px;}#otpl_lightbox #otpl_content p{padding:1%;text-align:left;margin:0!important;line-height: 20px;}#otpl_lightbox #otpl_content .close span{background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABmJLR0QA/wD/AP+gvaeTAAAAjklEQVRIie2Vyw2AIBQER3uQaIlarhwsRy+Y4AfCPuTmnEx0dwg+FH4MzIAz5FzIZlmAHfCixIXMHjqSDMAaHtyAqaD8nhnVQE4ilysSc3mJpLo8J/ms/CSeEH+7tozzK/GqpZX3FdKuInuh6Ra9vVDLYSwuT92TJSWjaJYocy5LLIdIkjT/XEPjH87PgwNng1K28QMLlAAAAABJRU5ErkJggg==) right 0 no-repeat;display:block;float:right;height:36px;height:36px;width:100%}#otpl_lightbox #otpl_content .close span:hover,#otpl_lightbox .otplmsg:hover{cursor:pointer}#otpl_lightbox .heading {padding: 10px 5px;margin: 0 !important;}#otpl_lightbox .heading h3{font-size:1.5rem;} span.otplogin-shortcode.otpl-popup {border: 1px solid #ccc;padding: 8px 10px;border-radius: 10px;}';

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

            // OTP Form Fields
            $otpl_form_html .= '<div class="heading"><h3>' . esc_html__('One-Time Password Verification', 'otp-login') . '</h3></div>';
            $otpl_form_html .= '<div id="otpl-body">';
            $otpl_form_html .= '<div id="sendotp">';
            $otpl_form_html .= '<label for="email">' . esc_html__('Enter your email to receive a one-time password', 'otp-login') . '<span class="req">*</span><span class="emailerror req"></span></label>';
            $otpl_form_html .= '<input type="email" name="email" id="email" value="" class="otpl-req-fields" size="40"> ';
            $otpl_form_html .= '<input type="submit" class="otpl_submit_btn generateOtp" id="generateOtp" value="' . esc_attr__('Next', 'otp-login') . '">';
            $otpl_form_html .= '</div>';

            // Submit OTP Section
            $otpl_form_html .= '<div id="submitotpsec">';
            $otpl_form_html .= '<span class="loginback" type="button">< ' . esc_html__('Back', 'otp-login') . '</span>';
            $otpl_form_html .= '<span class="email-otp">';
            $otpl_form_html .= '<label for="email_otp">' . esc_html__('Please enter the 6 digit code we have sent to your email:', 'otp-login') . '<br><span id="sbmitedemail"></span><span class="req"><span class="otperror"></span></span></label>';
            $otpl_form_html .= '<input type="text" name="email_otp" id="email_otp" value="" maxlength="6">';
            $otpl_form_html .= '</span>';
            $otpl_form_html .= '<div class="otpl-submit-sec"><input type="submit" class="submitOtp" id="submitOtp" value="' . esc_attr__('Submit', 'otp-login') . '" /> <span class="otpestatus req d-inline-block"></span></div>';
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
                'form' => array(
                    'action' => true,
                    'method' => true,
                    'id' => true,
                    'class' => true,
                ),
                'input' => array(
                    'type' => true,
                    'name' => true,
                    'value' => true,
                    'id' => true,
                    'class' => true,
                    'checked' => true,
                    'placeholder' => true,
                ),
                'label' => array(
                    'for' => true,
                    'class' => true,
                ),
                'select' => array(
                    'name' => true,
                    'id' => true,
                    'class' => true,
                ),
                'option' => array(
                    'value' => true,
                    'selected' => true,
                ),
                'textarea' => array(
                    'name' => true,
                    'id' => true,
                    'class' => true,
                    'rows' => true,
                    'cols' => true,
                    'placeholder' => true,
                ),
                'div' => array(
                    'class' => true,
                    'id' => true,
                    'style' => true,
                ),
                'span' => array(
                    'class' => true,
                    'id' => true,
                ),
                'br' => array(),
                'p' => array(),
            );

            echo wp_kses($otpl_form_html, $allowed_html);
        }

        /*
         * Send OTP Email on User Email
         * 
         * */
        public function otpl_send_otp($email, $otp)
        {
            // send OTP over email
            $otp_message = '<table width="50%" cellpadding="0" cellspacing="0" align="center" bgcolor="f5f5f5">
								 <tr>
									<td>
										<table width="650" align="center">
											<tr>
												<td>
													<p class="font_18 pd_lft_25">We have received a one-time password request.</p>
													<p class="font_17">Your one-time password is <strong>' . $otp . '</strong></p>
													
														<p class="font_17">Website ' . home_url() . '</p>
												</td>
											</tr>
										</table>
							</table>';

            $from = get_bloginfo('admin_email');

            $headers[] = 'Content-Type: text/html; charset=UTF-8';

            //From
            if ($from != '')
                $headers[] = 'From:' . $from;

            $mail = wp_mail($email, "Your One-Time Password to login at " . get_bloginfo('name'), $otp_message, $headers);
            return $mail;
            die();
        }

        /*
         * Handle all login form request
         * 
         * */
        public function otpl_login_action()
        {
            global $wpdb;

            // check security 
            if (wp_doing_ajax())
                check_ajax_referer('otpl_filed_once_val', 'otplsecurity'); // First check the nonce, if it fails the function will break

            $otplzplussecurity = isset($_POST['otplzplussecurity']) ? sanitize_text_field(wp_unslash($_POST['otplzplussecurity'])) : '';
            $email_otp         = isset($_POST['email_otp']) ? sanitize_text_field(wp_unslash($_POST['email_otp'])) : '';
            $email             = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
            $validateotp       = isset($_POST['validateotp']) ? sanitize_text_field(wp_unslash($_POST['validateotp'])) : '';

            // check zplus security
            if (!isset($otplzplussecurity) || (isset($otplzplussecurity) && $otplzplussecurity != '')) {
                echo wp_json_encode(array('status' => 0, 'message' => 'Request has been cancelled.', 'response' => 'Request has been rejected due to security! Please contact administrator.'));
                wp_die();
            }

            // check is request for generate otp or submit otp
            if (empty($email_otp)) {
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

                    // Check failed attempts and lockout
                    $failed_attempts = (int) get_user_meta($user_id, 'otpl_login_attempts', true);
                    $last_failed_time = (int) get_user_meta($user_id, 'otpl_last_failed_time', true);

                    // Set max attempts and lockout period - Cast to integers to prevent type errors
                    $max_attempts = (int) get_option('otpl_login_attempt', 3);
                    $lockout_period = (int) get_option('otpl_login_locktime', 3600);

                    // Ensure we have valid values (fallback to defaults if options are empty/invalid)
                    if ($max_attempts <= 0) {
                        $max_attempts = 3; // Default max attempts
                    }
                    if ($lockout_period <= 0) {
                        $lockout_period = 3600; // Default 1 hour lockout
                    }

                    // Check if the user has exceeded the maximum attempts
                    if ($failed_attempts >= $max_attempts) {
                        // Calculate the remaining lockout time
                        $time_since_failed = time() - $last_failed_time;
                        $remaining_time = $lockout_period - $time_since_failed;

                        // If the lockout period hasn't passed, show the remaining wait time
                        if ($remaining_time > 0) {
                            // Convert remaining time to hours, minutes, and seconds
                            $remaining_hours = floor($remaining_time / 3600); // Hours
                            $remaining_minutes = floor(($remaining_time % 3600) / 60); // Minutes
                            $remaining_seconds = $remaining_time % 60; // Seconds

                            // Prepare the message with the remaining wait time
                            $message = sprintf(
                                'Too many failed attempts. Please try again after %d hour(s), %d minute(s), and %d second(s).',
                                $remaining_hours,
                                $remaining_minutes,
                                $remaining_seconds
                            );

                            echo wp_json_encode(array('status' => 0, 'message' => $message, 'response' => 'Account locked'));
                            wp_die();
                        } else {
                            // Reset failed attempts after lockout period has passed
                            update_user_meta($user_id, 'otpl_login_attempts', 0);
                        }
                    }

                    // Send OTP to email
                    $newotp = wp_rand(100000, 999999);
                    $otpmail = $this->otpl_send_otp($email, $newotp);
                    update_user_meta($user_id, "emilotp", $newotp);

                    if (!$otpmail) {
                        $json_arg['response'] = 'The one-time password was been generated, but the email failed. Please try again.';
                        $json_arg['message'] = '<input type="submit" class="generateOtp" value="Resend" name="resendotp" />';
                        $json_arg['status'] = 0;
                        $json_arg['sendotp'] = 1;
                    } else {
                        $json_arg['response'] = 'Success';
                        $json_arg['message'] = 'The one-time password has been sent to: ' . $email;
                        $json_arg['status'] = 1;
                        $json_arg['sendotp'] = 1;
                    }

                    echo wp_json_encode($json_arg);
                    wp_die();
                }
            } else {
                // Check OTP validity
                $user_id = email_exists($email);
                $db_otp = get_user_meta($user_id, "emilotp", true);

                if ($db_otp == $email_otp && $validateotp != 0) {
                    $user = get_user_by('email', $email);
                    wp_set_current_user($user_id, $user->user_login);
                    wp_set_auth_cookie($user_id);
                    do_action('wp_login', $user->user_login, $user);

                    if (is_user_logged_in()) {
                        $url = get_option('otpl_redirect_url') ? get_option('otpl_redirect_url') : home_url();
                        echo wp_json_encode(array('status' => 1, 'message' => 'You have successfully logged in', 'response' => 'OTP Matched', 'sendotp' => 0, 'redirect' => $url));
                        update_user_meta($user_id, "emilotp", ''); // Reset OTP
                        // Reset failed attempts on successful login
                        update_user_meta($user_id, 'otpl_login_attempts', 0);
                        wp_die();
                    }

                    echo wp_json_encode(array('status' => 1, 'message' => 'Not logged in', 'response' => 'OTP Matched but not logged in', 'sendotp' => 0, 'redirect' => $url));
                    wp_die();
                } else {
                    // Increment failed attempts
                    $failed_attempts = (int) get_user_meta($user_id, 'otpl_login_attempts', true);
                    $failed_attempts = $failed_attempts + 1;
                    update_user_meta($user_id, 'otpl_login_attempts', $failed_attempts);
                    update_user_meta($user_id, 'otpl_last_failed_time', time());

                    $failed_attempts = get_user_meta($user_id, 'otpl_login_attempts', true);

                    echo wp_json_encode(array('status' => 1, 'message' => 'One-time password does not match. <input type="submit" class="generateOtp" value="Resend OTP" name="resendotp" />', 'response' => 'OTP does not exist', 'sendotp' => 1));
                    wp_die();
                }
            }
        }
    }
}

//init class
if (class_exists('OtpLoginFront')) {
    // instantiate the plugin class
    $OtpLoginFront = new OtpLoginFront();
}
