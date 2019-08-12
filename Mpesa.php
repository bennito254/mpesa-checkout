<?php
/***********************************************
 * Safaricom MPESA Checkout API
 * @Author: Ben Muriithi
 * @Email: benmuriithi92@gmail.com
 * @Licence: MIT Licence
 * 
 * ********************************************/
define("URL", "https://www.safaricom.co.ke/mpesa_online/lnmo_checkout_server.php?wsdl"); //Put the api endpint.
class Mpesa {
	public function __construct($config){
		if(is_array($config)){
			foreach($config as $key => $value){
				$this->$key = $value;
			}
		}else{
			//Show initialization error
		}
	}
	
	public function checkout($phone, $amount, $reference_id){
		// Some validation.
		if( $this->merchant_id == '' || !isset($this->merchant_id) || $this->sag_key == '' || !isset($this->sag_key) || !isset($this->callback_url) || !isset($this->callback_method) || $this->callback_url == '' || $this->callback_method == '' ){
			return false;
		}
		$TIMESTAMP=new DateTime();
		$datetime=$TIMESTAMP->format('YmdHis');
		
		$identifier = $this->generateRandomString();
		$passwd = $this->generateHash($this->merchant_id, $this->sag_key, $datetime);
		
		$post_string='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="tns:ns">
		<soapenv:Header>
		  <tns:CheckOutHeader>
			<MERCHANT_ID>'.$this->merchant_id.'</MERCHANT_ID>
			<PASSWORD>'.$passwd.'</PASSWORD>
			<TIMESTAMP>'.$datetime.'</TIMESTAMP>
		  </tns:CheckOutHeader>
		</soapenv:Header>
		<soapenv:Body>
		  <tns:processCheckOutRequest>
			<MERCHANT_TRANSACTION_ID>'.$identifier.'</MERCHANT_TRANSACTION_ID>
			<REFERENCE_ID>'.$reference_id.'</REFERENCE_ID>
			<AMOUNT>'.$amount.'</AMOUNT>
			<MSISDN>'.$phone.'</MSISDN>
			<!--Optional parameters-->
			<ENC_PARAMS>BennitoOnTh3Go</ENC_PARAMS>
			<CALL_BACK_URL>'.$this->callback_url.'</CALL_BACK_URL>
			<CALL_BACK_METHOD>'.$this->callback_method.'</CALL_BACK_METHOD>
			<TIMESTAMP>'.$datetime.'</TIMESTAMP>
		  </tns:processCheckOutRequest>
		</soapenv:Body>
		</soapenv:Envelope>';
		/*
		Headers
		 */
		$headers = array(  
		"Content-type: text/xml",
		"Content-length: ".strlen($post_string),
		"Content-transfer-encoding: text",
		"SOAPAction: \"processCheckOutRequest\"",
		);

		if($check = $this->submitRequest(URL,$post_string,$headers)){
			$confirm = $this->confirmTransaction($check,$datetime,$passwd,$this->merchant_id);
			$returns = array(
				'checkout' => json_encode($check),
				'confirm' => json_encode($confirm)
			);
			return $returns;
		}else{
			return false;
		}
	}
	
	public function confirmTransaction($checkoutResponse,$datetime,$password,$paybill){		
		$xml = simplexml_load_string($checkoutResponse);
		$ns = $xml->getNamespaces(true);
		$soap = $xml->children($ns['SOAP-ENV']);
		$sbody = $soap->Body;
		$mpesa_response = $sbody->children($ns['ns1']);
		$rstatus = $mpesa_response->processCheckOutResponse;
		$status = $rstatus->children();		
		$s_returncode = $status->RETURN_CODE;
		$s_description = $status->DESCRIPTION;
		$s_transactionid = $status->TRX_ID;
		$s_enryptionparams = $status->ENC_PARAMS;
		$s_customer_message = $status->CUST_MSG;
		if($s_returncode==42){

			return json_encode("Authentication Failed",401);
		}
		$confirmTransactionResponse='
			<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="tns:ns">
		   <soapenv:Header>
		      <tns:CheckOutHeader>
		         <MERCHANT_ID>'.$paybill.'</MERCHANT_ID>
			<PASSWORD>'.$password.'</PASSWORD>
			<TIMESTAMP>'.$datetime.'</TIMESTAMP>
		      </tns:CheckOutHeader>
		   </soapenv:Header>
		   <soapenv:Body>
		      <tns:transactionConfirmRequest>
		         <!--Optional:-->
		         <TRX_ID>'.$s_transactionid.'</TRX_ID>
		         <!--Optional:-->
		         
		      </tns:transactionConfirmRequest>
		   </soapenv:Body>
		</soapenv:Envelope>';

		$headers = array(  
		"Content-type: text/xml",
		"Content-length: ".strlen($confirmTransactionResponse),
		"Content-transfer-encoding: text",
		"SOAPAction: \"transactionConfirmRequest\"",
		);

		return $this->submitRequest(URL,$confirmTransactionResponse,$headers);
	}
	//Helper Functions
	private function submitRequest($url,$post_string,$headers){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100);
		curl_setopt($ch, CURLOPT_POST,TRUE); 
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,  $post_string); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$data = curl_exec($ch);
		if($data === FALSE)
		{
			$err = 'Curl error: ' . curl_error($ch);
			curl_close($ch);
			return false;
		}
		else
		{
			curl_close($ch);
			$body = $data;
			return $body;
		}
		
	}
	private function generateRandomString($length = 10) {
		return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
	}
	
	private function generateHash($paybill, $sag_key, $datetime){
		$password=base64_encode(hash("sha256", $paybill.''.$sag_key.''.$datetime));
	
		return $password;
	}
}
