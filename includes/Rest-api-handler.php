<?php
class Rest_api {
    public function __construct() {
        add_action('rest_api_init',array($this,'register_rest_api'));
        error_log("uspio");
    }
    public function register_rest_api()
    {
        register_rest_route(
            'staking-pool',
            'stake',
            array(
                'methods' => 'GET, POST',
                'callback' => array($this,'handle_rest_api_reqs'),
                'permission_callback' => '__return_true'

            )
        );

    }

    public function handle_rest_api_reqs($request)
    {
        global $stakePlugin;
        $headers = $request->get_headers();
        $action = $headers['action'];
        switch ($action[0]) {
            case 'payment_request':

                try {
                $stakeAmmount = (int)$headers['stakeammount'][0];
                error_log($stakeAmmount);
                $stakePlugin->xaman_payment_req($stakeAmmount);

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
                    error_log("stakeAmmount: ".$stakeAmmount);
                    error_log("stake: ". $stakeAmmount);
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
                
                $stakePlugin->schedule_stk_payout($duration,$stakeAmmount,$staker,$stakepercentage);
                return wp_send_json_success($body);
        
        
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                    error_log($error_message);
                    echo json_encode($error_message);
                }
                break;
            default:
            error_log("Not valid request!");
        }

    }
}

new Rest_api();