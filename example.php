<?php
// You can create a configuration like this
$config = array(
	'merchant_id' => '334455' //Your paybill number, associated with the SAG key below
	'sag_key' => 'andfnnsdgfkgdf75fdsfmjgdgv', //MPESA API SAG Key associated with the Merchant ID above. Contact Mpesa representative if you dont have one
	'callback_url' => 'http://example.com/ipn', //THe URL MPESA will send information to
	'callback_method' => 'POST' //POST or GET. HTTP Method that Mpesa should use to call your URL
);

//~ Or like this
//~ $config['merchant_id'] = '334455';
//~ $config['sag_key'] = 'kjhgyewyvtjkewyh5846ghjksdtt';
//~ $config['callback_url'] => 'http://example.com/ipn';
//~ $config['callback_method'] = 'POST';
include('Mpesa.php');

$mpesa = new Mpesa($config);

// $mpesa->checkout returns false on failure, or an array or json objects received from the API responses
// during Requesting and confirmation.

$response = $mpesa->checkout('0716483805', '50', 'Donation');
?>
