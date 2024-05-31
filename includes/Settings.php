<?php

if(!defined('ABSPATH'))
{
    die('Nice try!');
}
function xrpl_token_menu() {
    add_menu_page('Plugin Settings', 'XRPL staking', 'manage_options', 'XRPL-staking', 'Plugin_menu', '', 100);
}


function Plugin_menu() {
    if (!current_user_can('manage_options')) {
        return;
    }
    echo "<h2>" . __( 'Settings', 'menu-test' ) . "</h2>";
    echo "<p>This is a settings page for plugin.</p>";

    if (isset($_GET['settings-updated'])) {
        add_settings_error('save_messages', 'save_message', __('Settings Saved', 'settings'), 'updated');
    }

        ?>
        <div class="wrap">
            <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
            <form id = "Form" action="options.php" method="post">
                <?php
                settings_fields('settings');
                do_settings_sections('settings');
                submit_button('Save Settings');
                ?>
                <label for="XUMM_SECRET" style="font-size: 1.5em; margin-right: 14px;" >Xaman API Secret Key</label>
                <input type="text" size="40" id="XUMM_SECRET" name="XUMM_SECRET" value="<?php echo esc_attr(get_option('XUMM_SECRET')); ?>" /><br><br><br>
                <label for="XUMM_KEY" style="font-size: 1.5em; margin-right: 70px;" >Xaman API Key </label>
                <input type="text" size="40" id="XUMM_KEY" name="XUMM_KEY" value="<?php echo esc_attr(get_option('XUMM_KEY')); ?>" /><br><br><br>
                <label for="secret_key" style="font-size: 1.5em; margin-right: 110px;" >Secret Key </label>
                <input type="text" size="40" id="secret_key" name="secret_key" required minlength="29" maxlength="31" title="Secret key must be between 20 and 31 characters" value="<?php echo esc_attr(get_option('secret_key')); ?>" /><br><br><br>
                <label for="token_name" style="font-size: 1.5em; margin-right: 92px;">Token Name </label>
                <input type="text" size="10" id="token_name" name="token_name" value="<?php echo esc_attr(get_option('token_name')); ?>" /><br><br><br>
                <label for="issuer_key" style="font-size: 1.5em; margin-right: 115px;" >Issuer key </label>
                <input type="text" size="40" id="issuer_key" name="issuer_key" required minlength="34" maxlength="34" pattern=".{34}" title="Issuer key must be exactly 34 characters" value="<?php echo esc_attr(get_option('issuer_key')); ?>" /><br><br><br>
                <label for="enable_test" style="font-size: 1.5em; margin-right: 75px;">Enable Testing:</label>
                <input type="checkbox" id="enable_test" name="enable_test" value="1" <?php checked(1, get_option('enable_test'), true); ?> />
            </form>
        </div>
        <?php


}

function Settings_saved()
{
    register_setting('settings', 'XUMM_KEY');
    register_setting('settings', 'XUMM_SECRET');
    register_setting('settings', 'secret_key');
    register_setting('settings', 'token_name');
    register_setting('settings', 'issuer_key');
    register_setting('settings', 'enable_test');
}

add_action('admin_init', 'Settings_saved');
add_action('admin_menu', 'xrpl_token_menu');