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
                url: '<?php echo get_rest_url(null, "staking-pool/stake"); ?>',
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
                url: '<?php echo get_rest_url(null, "staking-pool/stake"); ?>',
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

add_action('wp_footer', 'jQuery_Handler');
add_action('wp_footer', 'Xaman_handler');