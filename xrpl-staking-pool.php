<?php
/**
 * Plugin Name: XRPL Staking Pool
 * Plugin URI:  https://github.com/rihno123/wp-xrpl-staking-pool
 * Description: Introducing a plugin designed to empower your custom token with staking capabilities on the XRPL, compatible with Elementor.
 * Version:     1.0
 * Author:      Lein
 */
require_once __DIR__ . '/vendor/autoload.php';
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use Xrpl\XummSdkPhp\Payload\Payload;
use Xrpl\XummSdkPhp\XummSdk;


if(!defined('ABSPATH'))
{
    die('Nice try!');
}

if (!function_exists('add_action')) {
    echo 'This is a plugin for WordPress and cannot be called directly.';
    exit;
}
const MAINNET_URL=  'https://xrplcluster.com/';
const TESTNET_URL = 'https://testnet.xrpl-labs.com/';
define('SECONDS_IN_MONTH', 30 * 24 * 60 * 60);


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

function xaman_payment_req($stakeAmmount) {
    $apiKey = get_option('XUMM_KEY');
    $apiSecret = get_option('XUMM_SECRET');
    $xummSdk = new XummSdk($apiKey, $apiSecret);
    $secretKey = get_option('secret_key'); 
    $recipientAddress = Wallet::fromSeed($secretKey);

    $payload = new Payload([
        'TransactionType' => 'Payment',
        'Destination' => $recipientAddress->getClassicAddress(),
        'Amount' => [
            'currency' => get_option('token_name'),
            'value' => $stakeAmmount,
            'issuer' => get_option('issuer_key')
        ] 
    ]);

        try {
            $response = $xummSdk->createPayload($payload);
            if (isset($response->uuid)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'uuid' => $response->uuid, 
                    'websocket' => $response->refs->websocketStatus,
                    'redirect_url' => $response->next->always
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create payload']);
                return null;
            }
        } catch (Exception $e) {
            error_log('Failed to create sign-in payload: ' . $e->getMessage());
            return null;
        }
}

function schedule_stk_payout($duration,$amount, $staker, $stake) {
    
    if(get_option("enable_test"))
    {
        wp_schedule_single_event(time() + $duration, 'stake_payment', [$amount, $staker, $stake]);
    }
    else 
    {
        wp_schedule_single_event(time() + $duration * SECONDS_IN_MONTH, 'stake_payment', [$amount, $staker, $stake]);
    }
}



function sending_tokens( $amount, $staker, $stake)
{
    $secretKey = get_option('secret_key'); 
    $recipientAddress = Wallet::fromSeed($secretKey);
    $issuer_address = get_option('issuer_key');
    $amountToSend = calculate_amount($amount,$stake);
    $transaction = prepare_transaction($recipientAddress->getClassicAddress(), $staker, $amountToSend, $issuer_address);

    if($amountToSend > 0)
    {
    sign_transaction($transaction, $secretKey);
    }

    wp_die(); 
}



function calculate_amount($ammount,$stake) {
    $ammountToSend = $ammount * $stake/100 + $ammount;
    return $ammountToSend;
}

function prepare_transaction($fromAddress, $toAddress, $amount,$issuer_address) {

    $transaction = [
        'TransactionType' => 'Payment',
        'Account' => $fromAddress,
        'Destination' => $toAddress,
        'Amount' => [ 
            'currency' => get_option('token_name'),
            'value' => $amount,
            'issuer' => $issuer_address
        ], 
    ];
    return json_encode($transaction);
}

function sign_transaction($transaction, $secretKey) {
    
    $client = new JsonRpcClient(MAINNET_URL);
    $standbyWallet = Wallet::fromSeed($secretKey);
    $tx = json_decode($transaction,true);
    $autofilledTx = $client->autofill($tx);
    $signedTx = $standbyWallet->sign($autofilledTx);
    $txResponse = $client->submitAndWait($signedTx['tx_blob']);
    $result = $txResponse->getResult();
    if ($result['meta']['TransactionResult'] === 'tecUNFUNDED_PAYMENT') {
        debug_prt("Error: The sending account is unfunded! TxHash: {$result['hash']}" . PHP_EOL);
    } else {
        debug_prt("Token payment done! TxHash: {$result['hash']}" . PHP_EOL);
    }

    }   

function debug_prt($value)
{
    ob_start();
    var_dump($value);
    $contents = ob_get_contents();
    ob_end_clean();
    error_log($contents);

}

function jQuery_Handler()
{
            ?>
            <script>   
            
    jQuery(document).ready(function ($) {
        var nonce = '<?php echo wp_create_nonce("wp_rest"); ?>'

        jQuery('#Form').on("submit", function (event) {
            event.preventDefault(); 

            var formElement = event.target; 
            var formData = new FormData(formElement); 
            var stakeAmmount = formData.get('form_fields[field1]');
            var stakePercentagedata = formData.get('form_fields[period]');
            var matches = stakePercentagedata.match(/(\d+)%/);
            var stakePercentage = 0;

            if (matches) {
                stakePercentage = matches[1];
                console.log("Percentage:", stakePercentage + '%');
            } else {
                console.log("No percentage found in the string.");
                return;
            }

            matches = stakePercentagedata.match(/(\d+)\s*months?/i);
            var duration = 0;
            if (matches) {
                duration = parseInt(matches[1], 10); 
            } else {
                return;
            }

            var form = { "key": "value" };
            form = JSON.stringify(form);
            jQuery.ajax({
                method: 'POST',
                url: '<?php echo get_rest_url(null, "staking-pool/airdrop"); ?>',
                crossDomain: true, 
                headers: {
                    'X-WP-Nonce': nonce, 
                    "accept": "application/json", 
                    'action': 'payment_request',
                    "Access-Control-Allow-Origin": "*",
                    'stakeammount': stakeAmmount,
                    'stakepercentage': stakePercentage,
                    'duration': duration
                },
                data: form,
                dataType: "json"

            }).done(function (res) { 
                var popup = window.open(res.redirect_url, 'Popup', 'width=600,height=600');
                if (!popup) {
                    alert('Popup blocked by browser');
                }
                Websocket_handler(duration,stakePercentage,stakeAmmount,res.websocket, res.uuid);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            });

      });
    });
    
    </script>

<?php
}
function Xaman_handler()
{
    ?>
    <script>   
    function Websocket_handler(duration,stakePercentage,stakeAmmount,websocket, uuid){
        socket = new WebSocket(websocket);

        socket.onmessage = function (event) {
            let data = JSON.parse(event.data);

            if (data.expires_in_seconds < 0) {
                    socket.close();
            } else if (data.signed) {
                    socket.close();
                    Checking_transaction(duration,stakePercentage,stakeAmmount,uuid);
            } else if (data.signed != undefined && !data.signed) {
                    socket.close();
            } 
            else {
                    console.log(`[message] Data received from server: ${event.data}`);
                }
            };
    }

    function Checking_transaction(duration,stakePercentage,stakeAmmount,uuid)
    {
        var nonce = '<?php echo wp_create_nonce("wp_rest"); ?>'
        var form = { "key": "value" };
        form = JSON.stringify(form);

        jQuery.ajax({
                method: 'POST',
                url: '<?php echo get_rest_url(null, "staking-pool/airdrop"); ?>',
                crossDomain: true, 
                headers: {
                    'X-WP-Nonce': nonce, 
                    "accept": "application/json", 
                    "uuid": uuid,
                    'action': 'checking_transaction',
                    "Access-Control-Allow-Origin": "*", 
                    "stakeammount": stakeAmmount,
                    'stakepercentage': stakePercentage,
                    'duration': duration
                },
                data: form,
                dataType: "json"

            }).done(function (res) { 
                if (!res.data.response.account) 
                {
                    console.log("No account information received");
                } 

            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.log("Checking transaction failed");
            });
    }
    </script>
<?php
}
function register_rest_api()
{
    register_rest_route(
        'staking-pool',
        array(
            'methods' => 'GET, POST',
            'callback' => 'handle_rest_api_reqs',
            'permission_callback' => '__return_true'

        )
    );

}

function handle_rest_api_reqs($request)
{
    $headers = $request->get_headers();
    $action = $headers['action'];

    switch ($action[0]) {
        case 'payment_request':

            try {
            $stakeAmmount = (int)$headers['stakeammount'][0];
            debug_prt($stakeAmmount);
            xaman_payment_req($stakeAmmount);

            } catch (Exception $e) {
                $error_message = $e->getMessage();
                error_log($error_message);
                echo json_encode($error_message);
            }
            break;

        case 'checking_transaction':

            try {
                $stakeAmmount = (int)$headers['stakeammount'][0];
                $stakepercentage = (int)$headers['stakepercentage'][0];
                $duration = (int)$headers['duration'][0];
                debug_prt("stakeAmmount: ".$stakeAmmount);
                debug_prt("stake: ". $stakeAmmount);
                $uuid = $headers['uuid'][0];
                $client = new \GuzzleHttp\Client();
                $url = "https://xumm.app/api/v1/platform/payload/" . $uuid;
                $response = $client->request('GET', $url, [
                    'headers' => [
                        'X-API-Key' => get_option("XUMM_KEY"),
                        'X-API-Secret' => get_option("XUMM_SECRET"),
                        'accept' => 'application/json',
                    ],
                ]);
            $body = json_decode($response->getBody(), true);
            $staker = $body['response']['account'];
            
            schedule_stk_payout($duration,$stakeAmmount,$staker,$stakepercentage);
            return wp_send_json_success($body);
    
    
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                error_log($error_message);
                echo json_encode($error_message);
            }
            break;
        default:
        error_log("Not valid requst!");
    }

}

function enqueue_custom_scripts() {
    wp_enqueue_script('jquery');
}

add_action('stake_payment', 'sending_tokens', 10, 3);
add_action('wp_footer', 'Xaman_handler');
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');
add_action('admin_init', 'Settings_saved');
add_action('admin_menu', 'xrpl_token_menu');
add_action('wp_footer', 'jQuery_Handler');
add_action('rest_api_init', 'register_rest_api');