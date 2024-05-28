<?php
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use Xrpl\XummSdkPhp\Payload\Payload;
use Xrpl\XummSdkPhp\XummSdk;

class Xaman_handler {

    private $xummSdk;
    
    public function __construct() {
        error_log("1");
        add_action('stake_payment', array($this,'sending_tokens'), 10, 3);  
        $this->xummSdk = new XummSdk(get_option('XUMM_KEY'), get_option('XUMM_SECRET'));
    }

    public function xaman_payment_req($stakeAmount) {
        $secretKey = get_option('secret_key'); 
        $recipientAddress = Wallet::fromSeed($secretKey);

        $payload = new Payload([
            'TransactionType' => 'Payment',
            'Destination' => $recipientAddress->getClassicAddress(),
            'Amount' => [
                'currency' => get_option('token_name'),
                'value' => $stakeAmount,
                'issuer' => get_option('issuer_key')
            ] 
        ]);

        try {
            $response = $this->xummSdk->createPayload($payload);
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

    public function schedule_stake_payout($duration, $amount, $staker, $stake) {     
        if(get_option("enable_test"))
        {
            wp_schedule_single_event(time() + $duration, 'stake_payment', [$amount, $staker, $stake]);
        }
        else 
        {
            wp_schedule_single_event(time() + $duration * SECONDS_IN_MONTH, 'stake_payment', [$amount, $staker, $stake]);
        }
    }



    public function sending_tokens( $amount, $staker, $stake)
    {
        error_log("uspio");
        $secretKey = get_option('secret_key'); 
        $recipientAddress = Wallet::fromSeed($secretKey);
        $issuer_address = get_option('issuer_key');
        $amountToSend = $this->calculate_amount($amount,$stake);
        $transaction = $this->prepare_transaction($recipientAddress->getClassicAddress(), $staker, $amountToSend, $issuer_address);

        if($amountToSend > 0)
        {
            $this->sign_transaction($transaction, $secretKey);
        }
    }



    public function calculate_amount($ammount,$stake) {
        $ammountToSend = $ammount * $stake/100 + $ammount;
        return $ammountToSend;
    }

    public function prepare_transaction($fromAddress, $toAddress, $amount,$issuer_address) {

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

    public function sign_transaction($transaction, $secretKey) {
                
        $client = new JsonRpcClient(TESTNET_URL);
        $standbyWallet = Wallet::fromSeed($secretKey);
        $tx = json_decode($transaction,true);
        $autofilledTx = $client->autofill($tx);
        $signedTx = $standbyWallet->sign($autofilledTx);
        $txResponse = $client->submitAndWait($signedTx['tx_blob']);
        $result = $txResponse->getResult();
            if ($result['meta']['TransactionResult'] === 'tecUNFUNDED_PAYMENT') {
            error_log("Error: The sending account is unfunded! TxHash: {$result['hash']}" . PHP_EOL);
        } else {
            error_log("Token payment done! TxHash: {$result['hash']}" . PHP_EOL);
        }

    }   
}
