<div class="wrap">
    <h2>OTP Login Settings</h2>
    <form method="post" action="options.php" id="otpl-option-form">
        <?php settings_fields('otpl'); ?>
        <?php
        $password_reset_settings = get_option('custom_password_reset_settings', array());
        $password_reset_logo = is_array($password_reset_settings) && !empty($password_reset_settings['logo_url']) ? $password_reset_settings['logo_url'] : '';
        $password_reset_color = is_array($password_reset_settings) && !empty($password_reset_settings['button_color']) ? $password_reset_settings['button_color'] : '#0073aa';
        $otp_login_page_enable = get_option('otpl_login_page_enable', 1);
        $otp_logo_url = get_option('otpl_email_logo_url', '');
        $otp_logo_max_height = get_option('otpl_email_logo_max_height', 60);
        $otp_accent_color = get_option('otpl_email_accent_color', '');
        $otp_background_color = get_option('otpl_email_background_color', '#f9f9f9');
        $otp_text_color = get_option('otpl_email_text_color', '#555555');
        $otp_subject = get_option('otpl_email_subject', 'Your One-Time Password for {site_name}');
        ?>
        <div class="otpl-setting">
            <div class="first otpl-tab" id="div-otpl-general">
                <table class="form-table">
                    <tr>
                        <td style="vertical-align:top;">
                            <table>
                                <tr valign="top">
                                    <th width="10">
                                        <input type="checkbox" value="1" name="otpl_enable" id="otpl_enable" <?php checked(get_option('otpl_enable'), 1); ?> />
                                        <label for="otpl_enable">Enable OTP Login</label>
                                    </th>
                                </tr>
                                <tr valign="top">
                                    <th>
                                        <label for="otpl_redirect_url">Redirect URL</label>
                                        <input type="text" value="<?php echo esc_attr(get_option('otpl_redirect_url')); ?>" name="otpl_redirect_url" id="otpl_redirect_url" size="40" />
                                        <em>Define the redirect URL after user is logged in.</em>
                                    </th>
                                </tr>
                                <tr valign="top">
                                    <th>
                                        <label for="otpl_register_url">Register Page URL</label>
                                        <input type="text" value="<?php echo esc_url(get_option('otpl_register_url')); ?>" name="otpl_register_url" id="otpl_register_url" size="40" />
                                        <em>Define the register URL for non-registered users.</em>
                                    </th>
                                </tr>
								<tr valign="top">
                                    <th>
                                        <label for="otpl_login_attempt">Login Attempt</label>
                                        <input type="text" value="<?php echo esc_attr(get_option('otpl_login_attempt')); ?>" name="otpl_login_attempt" id="otpl_login_attempt" size="40" />
                                        <em>Define the number of login attempts.</em>
                                    </th>
                                </tr>
								<tr valign="top">
                                    <th>
                                        <label for="otpl_login_locktime">Lockout Period</label>
                                        <input type="text" value="<?php echo esc_attr(get_option('otpl_login_locktime')); ?>" name="otpl_login_locktime" id="otpl_login_locktime" size="40" />
                                        <em>Define the lockout period in seconds.</em>
                                    </th>
                                </tr>
                                <tr valign="top">
                                    <th>
                                        <input type="checkbox" value="1" name="otpl_login_page_enable" id="otpl_login_page_enable" <?php checked($otp_login_page_enable, 1); ?> />
                                        <label for="otpl_login_page_enable">Show OTP login on wp-login.php</label>
                                        <em> Adds a one-time password option to WordPress login pages, including BuddyBoss-styled login screens.</em>
                                    </th>
                                </tr>
                                <tr valign="top">
                                    <th>
                                        <h3>OTP Email Branding</h3>
                                        <p>Leave logo or accent color blank to inherit the Password Reset Email plugin settings when available.</p>
                                    </th>
                                </tr>
                                <tr valign="top">
                                    <th>
                                        <label for="otpl_email_logo_url">Logo URL</label>
                                        <input type="url" value="<?php echo esc_attr($otp_logo_url); ?>" name="otpl_email_logo_url" id="otpl_email_logo_url" class="regular-text" />
                                        <input type="button" id="otpl_upload_logo_button" class="button" value="Upload Logo" />
                                        <em>Optional. Current password reset fallback: <?php echo $password_reset_logo ? esc_html($password_reset_logo) : esc_html__('none', 'otp-login'); ?></em>
                                        <?php if ($otp_logo_url) : ?>
                                            <div id="otpl_logo_preview" style="margin-top: 10px;">
                                                <img src="<?php echo esc_url($otp_logo_url); ?>" style="max-height: 60px; border: 1px solid #ddd; padding: 5px; background: #fff;" alt="">
                                            </div>
                                        <?php endif; ?>
                                    </th>
                                </tr>
                                <tr valign="top">
                                    <th>
                                        <label for="otpl_email_logo_max_height">Logo Max Height (px)</label>
                                        <input type="number" value="<?php echo esc_attr($otp_logo_max_height); ?>" name="otpl_email_logo_max_height" id="otpl_email_logo_max_height" min="1" max="200" />
                                    </th>
                                </tr>
                                <tr valign="top">
                                    <th>
                                        <label for="otpl_email_accent_color">Accent Color</label>
                                        <input type="text" value="<?php echo esc_attr($otp_accent_color); ?>" name="otpl_email_accent_color" id="otpl_email_accent_color" class="otpl-color-field" data-default-color="<?php echo esc_attr($password_reset_color); ?>" />
                                        <em>Used for the OTP code block and login-page OTP button.</em>
                                    </th>
                                </tr>
                                <tr valign="top">
                                    <th>
                                        <label for="otpl_email_background_color">Email Background Color</label>
                                        <input type="text" value="<?php echo esc_attr($otp_background_color); ?>" name="otpl_email_background_color" id="otpl_email_background_color" class="otpl-color-field" data-default-color="#f9f9f9" />
                                    </th>
                                </tr>
                                <tr valign="top">
                                    <th>
                                        <label for="otpl_email_text_color">Email Text Color</label>
                                        <input type="text" value="<?php echo esc_attr($otp_text_color); ?>" name="otpl_email_text_color" id="otpl_email_text_color" class="otpl-color-field" data-default-color="#555555" />
                                    </th>
                                </tr>
                                <tr valign="top">
                                    <th>
                                        <label for="otpl_email_subject">OTP Email Subject</label>
                                        <input type="text" value="<?php echo esc_attr($otp_subject); ?>" name="otpl_email_subject" id="otpl_email_subject" class="regular-text" />
                                        <em>Available tokens: {site_name}, {site_domain}</em>
                                    </th>
                                </tr>
                                <tr>
                                    <td><?php @submit_button(); ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <hr>
                <h3>Login Popup Class Name:</h3>
                <p><strong>otpl-popup</strong> using this class you can add OTP login popup on your website</p>
                Example:
                <code>&lt;div class="otpl-popup"&gt;&lt;a href="javascript:"&gt;Login&lt;/a&gt;&lt;/div&gt;</code>

                <h3>Shortcode</h3>
                <p><strong>[otp_login title="Login with OTP"]</strong></p>
            </div>
        </div>
    </form>
</div>
