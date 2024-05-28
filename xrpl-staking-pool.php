<?php
/**
 * Plugin Name: XRPL Staking Pool
 * Plugin URI:  https://github.com/rihno123/wp-xrpl-staking-pool
 * Description: Introducing a plugin designed to empower your custom token with staking capabilities on the XRPL, compatible with Elementor.
 * Version:     1.0
 * Author:      Lein
 */
require_once __DIR__ . '/vendor/autoload.php';



if(!defined('ABSPATH'))
{
    die('Nice try!');
}

if (!function_exists('add_action')) {
    echo 'This is a plugin for WordPress and cannot be called directly.';
    exit;
}
define('MAINNET_URL', 'https://xrplcluster.com/');
define('TESTNET_URL', 'https://testnet.xrpl-labs.com/');
define('SECONDS_IN_MONTH', 30 * 24 * 60 * 60);
    class StakePlugin {
        public function __construct() {
            $this->initHooks();
        }

        private function initHooks() {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_custom_scripts'));
            require_once plugin_dir_path(__FILE__) . 'includes/jQuery-handler.php';
            require_once plugin_dir_path(__FILE__) . 'includes/Settings.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-xaman-handler.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-rest-api.php';
        }

        public function enqueue_custom_scripts() {
            wp_enqueue_script('jquery');
        }
    }

function stake()
{
    new StakePlugin();
}

stake();



