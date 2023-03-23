<?php
/*
     * @author	: SimaNet
     * @URL		: https://sng.co.ir
*/

if( isset($_POST['invoiceid']) && isset($_POST['amount']) && isset($_POST['product']) && isset($_POST['fullname']) && isset($_POST['phone']) && isset($_POST['mobile']) && isset($_POST['address']) && isset($_POST['email']) ){
	$invoiceid 		= $_POST['invoiceid'];
	$amount 		= intval($_POST['amount']);
	$product 		= $_POST['product'];
	$fullname 		= $_POST['fullname'];
	$phone 			= $_POST['phone']; 
	$mobile 		= $_POST['mobile']; 
	$address 		= $_POST['address']; 
	$email 			= $_POST['email'];
	
	if(file_exists('../../../init.php')){require( '../../../init.php' );}else{require("../../../dbconnect.php");}
	include("../../../includes/functions.php");
	include("../../../includes/gatewayfunctions.php");
	include("../../../includes/invoicefunctions.php");
	require_once("func.php");
	
	// Checks invoice ID is a valid invoice number or ends processing
	$invoiceid 	= checkCbInvoiceID($invoiceid, $GATEWAY['name']);
	
	$gatewaymodule 	= 'irpul';
	$GATEWAY 		= getGatewayVariables($gatewaymodule);
	if (!$GATEWAY['type']) die('Module Not Activated'); # Checks gateway module is active before accepting callback
	
	header( 'Content-Type: text/html; charset=UTF-8' );

	$callback_url 	= $CONFIG['SystemURL'] .'/modules/gateways/irpul/callback.php?invoiceid='. $invoiceid;

	//if irpul Currencies toman
	/*if( $GATEWAY['Currencies'] == 'rial'){
		$amount = round($amount/10);
	}*/

	if( $GATEWAY['Currencies'] == 'toman'){
		$amount = round($amount*10);
	}
	
	$order_id 	    = $invoiceid.rand(1000, 9999);

	$params = array(
		'method' 		=> 'payment',
		'amount' 		=> $amount,//rial
		'callback_url' 	=> $callback_url, 
		'plugin' 		=> 'WHMCS',
		'order_id'		=> $order_id,
		'product'		=> $product,
		'payer_name'	=> $fullname,
		'phone' 		=> $phone,
		'mobile' 		=> $mobile,
		'email' 		=> $email,
		'address' 		=> $address,
		'description' 	=> '',
		'test_mode' 	=> false,
	);

	$result 	= post_data('https://irpul.ir/ws.php', $params, $GATEWAY['token']);
	//print_r($result);

	$invoice_link = $CONFIG['SystemURL'] . '/viewinvoice.php?id='.$invoiceid;

	if( isset($result['http_code']) ){
		$data =  json_decode($result['data'],true);

		if( isset($data['code']) && $data['code'] === 1){
			redirect($data['url']);
		}
		else{
			$Show_Status =	'Error Code: '.$data['code'] . '\r\n ' . $data['status'];
			echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
		}
	}else{
		$Show_Status =	'پاسخی از سرویس دهنده دریافت نشد. لطفا دوباره تلاش نمائید';
		echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
	}
}



?>