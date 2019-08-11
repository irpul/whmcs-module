<?php
/*
     * @author	: SimaNet
     * @URL		: http://simanet.co
*/
if(file_exists('../../../init.php')){require( '../../../init.php' );}else{require("../../../dbconnect.php");}
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = 'irpul';
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY['type']) die('Module Not Activated'); # Checks gateway module is active before accepting callback

header( 'Content-Type: text/html; charset=UTF-8' );
function redirect($url){
	if( !headers_sent($filename, $linenum) ){
		Header('Location: '. $url);
		exit;
	}else{
		echo "<br/>Headers already sent in $filename on line $linenum\n" ;
		exit;
	}
}
$amount 		= intval($_POST['amount']);
$invoiceid 		= $_POST['invoiceid']; 
$product 		= $_POST['product'];
$fullname 		= $_POST['fullname'];
$phone 			= $_POST['phone']; 
$mobile 		= $_POST['mobile']; 
$address 		= $_POST['address']; 
$email 			= $_POST['email'];
$callback_url 	= $CONFIG['SystemURL'] .'/modules/gateways/irpul/callback.php?invoiceid='. $invoiceid;

$parameters = array
(
	'webgate_id' 	=> $GATEWAY['webgate_id'],
	'amount' 		=> $amount,
	'callback_url' 	=> $callback_url, 
	'plugin' 		=> 'WHMCS',
	'order_id'		=> $invoiceid,
	'product'		=> $product,
	'payer_name'	=> $fullname,
	'phone' 		=> $phone,
	'mobile' 		=> $mobile,
	'email' 		=> $email,
	'address' 		=> $address,
	'description' 	=> '',
);
try {
	ini_set("soap.wsdl_cache_enabled", 0);
	$client = new SoapClient('https://irpul.ir/webservice.php?wsdl' , array('soap_version'=>'SOAP_1_1','cache_wsdl'=>WSDL_CACHE_NONE  ,'encoding'=>'UTF-8'));
	$result = $client->Payment($parameters);
	//print_r($result);exit;
}catch (Exception $e) { echo 'Error'. $e->getMessage();  }

if(isset($result['res_code']))
{
	if($result['res_code'] === 1){
		redirect($result['url']);
	}
	else{
		$address = $CONFIG['SystemURL'] . '/viewinvoice.php?id='.$invoiceid;
		$Show_Status =	'Error Code: '.$result['res_code'] . ' ' . $result['status'];
		echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$address."'</script>";
	}
}else{
	echo "result empty";
}

?>
