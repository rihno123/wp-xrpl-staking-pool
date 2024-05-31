<?php

if(!defined('ABSPATH'))
{
    die('Nice try!');
}
    class Rest_api {

        private $xaman_handler;
        public function __construct() {
            $this->xaman_handler = new Xaman_handler();
            add_action('rest_api_init',array($this,'register_rest_api'));
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
            $xaman_handler = $this->xaman_handler;
            
            $rawData = file_get_contents("php://input");
            $data = json_decode($rawData, true);
            $action = $data['action'] ?? 'Not provided';


            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(["error" => "Invalid JSON"]);
                exit;
            }

            switch ($action) {
                case 'payment_request':

                    try {
                    $stakeAmount = (int)$data['stakeAmount'] ?? 'Not provided';
                    error_log(json_encode($data));
                    $xaman_handler->xaman_payment_req($stakeAmount);

                    } catch (Exception $e) {
                        $error_message = $e->getMessage();
                        error_log($error_message);
                        echo json_encode($error_message);
                    }
                    break;

                case 'checking_transaction':

                    try {
                        $stakeAmount = (int)$data['stakeAmount'] ?? 'Not provided';
                        $stakepercentage = (int)$data['stakePercentage'] ?? 'Not provided';
                        $durationInMonths = (int)$data['durationInMonths'] ?? 'Not provided';
                        $uuid = $data['uuid'];

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
                    $xaman_handler->schedule_stake_payout($durationInMonths,$stakeAmount,$staker,$stakepercentage);
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