<?php
function url_decrypt($string){
	$counter = 0;
	$data = str_replace(array('-','_','.'),array('+','/','='),$string);
	$mod4 = strlen($data) % 4;
	if ($mod4) {
	$data .= substr('====', $mod4);
	}
	$decrypted = base64_decode($data);
	
	$check = array('tran_id','order_id','amount','refcode','status');
	foreach($check as $str){
		str_replace($str,'',$decrypted,$count);
		if($count > 0){
			$counter++;
		}
	}
	if($counter === 5){
		return array('data'=>$decrypted , 'status'=>true);
	}else{
		return array('data'=>'' , 'status'=>false);
	}
}

if( isset($_GET['invoiceid']) && $_GET['invoiceid']!='' && isset($_GET['irpul_token']) && $_GET['irpul_token']!='')
{
	if(file_exists('../../../init.php')){require( '../../../init.php' );}else{require("../../../dbconnect.php");}
	include("../../../includes/functions.php");
	include("../../../includes/gatewayfunctions.php");
	include("../../../includes/invoicefunctions.php");

	$gatewaymodule 	= 'irpul';
	$GATEWAY 		= getGatewayVariables($gatewaymodule);
	if (!$GATEWAY['type']) die('Module Not Activated'); # Checks gateway module is active before accepting callback
	$whmcs_url	= $CONFIG['SystemURL'];

	$invoiceid 		= $_GET['invoiceid'];
	$irpul_token 	= $_GET['irpul_token'];
	$decrypted 		= url_decrypt( $irpul_token );
	if($decrypted['status']){
		parse_str($decrypted['data'], $ir_output);
		$tran_id 	= $ir_output['tran_id'];
		$order_id 	= $ir_output['order_id'];
		$amount 	= $ir_output['amount'];
		$refcode	= $ir_output['refcode'];
		$status 	= $ir_output['status'];
		
		if($order_id!='' && $amount!='' && $tran_id!='' && $status!='' && $refcode!=''){
			$invoiceid 	= checkCbInvoiceID($invoiceid, $GATEWAY['name']); # Checks invoice ID is a valid invoice number or ends processing

			if($status == 'paid'){
				checkCbTransID($refcode); # Checks transaction number isn't already in the database and ends processing if it does
				$parameters = array	(
					'webgate_id'	=> $GATEWAY['webgate_id'],
					'tran_id' 		=> $tran_id,
					'amount'	 	=> $amount
				);
				try {
					$client = new SoapClient('https://irpul.ir/webservice.php?wsdl' , array('soap_version'=>'SOAP_1_1','cache_wsdl'=>WSDL_CACHE_NONE ,'encoding'=>'UTF-8'));
					$result = $client->PaymentVerification($parameters);
				}catch (Exception $e) { echo 'Error'. $e->getMessage();  }
			}

			if($GATEWAY['Currencies'] == 'toman'){
				$amount  = $amount/10;
			}
			
			if ($result == 1){
				addInvoicePayment($invoiceid, $refcode, $amount, 0, $gatewaymodule);
				logTransaction($GATEWAY["name"]  ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'tran_id'=>$tran_id, 'refcode'=>$refcode, 'status'=>$status )  ,"موفق"); 
			}
			else{
				logTransaction(  $GATEWAY["name"] ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'tran_id'=>$tran_id,'status'=>$status)  , "ناموفق") ; 
			}
		}
		$action = $whmcs_url."/viewinvoice.php?id="."$invoiceid" ;
		Header('Location: '.$action);
	}
}
else{
	echo "invoice id is blank";
}
?>
