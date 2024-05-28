<?php

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
            var stakeAmount = formData.get('form_fields[amount]');
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

            var form = {
                "action": "payment_request",
                "stakeAmount": stakeAmount
            };

            form = JSON.stringify(form);
            
            jQuery.ajax({
                method: 'POST',
                url: '<?php echo get_rest_url(null, "staking-pool/stake"); ?>',
                crossDomain: true, 
                headers: {
                    'X-WP-Nonce': nonce, 
                    "accept": "application/json", 
                    "Access-Control-Allow-Origin": "*"
                },
                data: form,
                dataType: "json"

            }).done(function (res) { 
                var popup = window.open(res.redirect_url, 'Popup', 'width=600,height=600');
                if (!popup) {
                    alert('Popup blocked by browser');
                }
                Websocket_handler(duration,stakePercentage,stakeAmount,res.websocket, res.uuid);

            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            });

      });
    });
    
    </script>

<?php
}
function Transaction_handler()
{
    ?>
    <script>   
    function Websocket_handler(duration,stakePercentage,stakeAmount,websocket, uuid){

        socket = new WebSocket(websocket);

        socket.onmessage = function (event) {
            let data = JSON.parse(event.data);

            if (data.expires_in_seconds < 0) {
                    socket.close();
            } else if (data.signed) {
                    socket.close();
                    Checking_transaction(duration,stakePercentage,stakeAmount,uuid);
            } else if (data.signed != undefined && !data.signed) {
                    socket.close();
            } 
            else {
                    console.log(`[message] Data received from server: ${event.data}`);
                }
            };
    }

    function Checking_transaction(duration,stakePercentage,stakeAmount,uuid)
    {
        var nonce = '<?php echo wp_create_nonce("wp_rest"); ?>'
        var form = {
                    'action': 'checking_transaction',
                    "uuid": uuid,
                    "stakeAmount": stakeAmount,
                    'stakePercentage': stakePercentage,
                    'durationInMonths': duration
            };
        form = JSON.stringify(form);

        jQuery.ajax({
                method: 'POST',
                url: '<?php echo get_rest_url(null, "staking-pool/stake"); ?>',
                crossDomain: true, 
                headers: {
                    'X-WP-Nonce': nonce, 
                    "accept": "application/json", 
                    "Access-Control-Allow-Origin": "*"
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

add_action('wp_footer', 'jQuery_Handler');
add_action('wp_footer', 'Transaction_handler');