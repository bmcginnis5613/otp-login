<div class="wrap">
    <h2>OTP Login Settings</h2>
    <form method="post" action="options.php" id="otpl-option-form">
        <?php settings_fields('otpl'); ?>
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