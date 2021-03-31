<?php
if( isset($_GET['invoiceid']) && $_GET['invoiceid']!='' ){
	if(file_exists('../../../init.php')){require( '../../../init.php' );}else{require("../../../dbconnect.php");}
	include("../../../includes/functions.php");
	include("../../../includes/gatewayfunctions.php");
	include("../../../includes/invoicefunctions.php");
	
	require_once("func.php");

	$whmcs_url	= $CONFIG['SystemURL'];
	$invoiceid 	= $_GET['invoiceid'];
	$invoice_link = "{$whmcs_url}/viewinvoice.php?id={$invoiceid}";
	
	if( isset($_GET['irpul_token']) && $_GET['irpul_token']!='' ){
		
		$gatewaymodule 	= 'irpul';
		$GATEWAY 		= getGatewayVariables($gatewaymodule);
		if (!$GATEWAY['type']) die('Module Not Activated'); # Checks gateway module is active before accepting callback

		$irpul_token 	= $_GET['irpul_token'];
		$decrypted 		= url_decrypt( $irpul_token );
		
		if($decrypted['status']){
			parse_str($decrypted['data'], $ir_output);
			$tran_id 	= $ir_output['tran_id'];
			$order_id 	= $ir_output['order_id'];
			$amount 	= $ir_output['amount'];//rial
			$refcode	= $ir_output['refcode'];
			$status 	= $ir_output['status'];
			
			//print_r($ir_output);
			
			//error_log( 'ir_output '. print_r($ir_output,true) );
			
			if($order_id!='' && $amount!='' && $tran_id!='' && $status!='' ){//&& $refcode!='' در حالت پرداخت نشده خالی است

				$invoiceid 	= checkCbInvoiceID($invoiceid, $GATEWAY['name']); # Checks invoice ID is a valid invoice number or ends processing

				if($status == 'paid'){
					checkCbTransID($refcode); # Checks transaction number isn't already in the database and ends processing if it does
					/*if($GATEWAY['Currencies'] == 'rial'){//زمانی که واحد پول ایرپول تومان شود
						$amount  = $amount*10;
					}*/

					$params = array	(
						'method' 		=> 'verify',
						'trans_id' 		=> $tran_id,
						'amount'	 	=> $amount//rial
					);
					$result 	= post_data('https://irpul.ir/ws.php', $params, $GATEWAY['token']);
					//error_log( 'result '. print_r($result,true) );
					
					if($GATEWAY['Currencies'] == 'toman'){
						$amount  = $amount/10;
					}
				
					if( isset($result['http_code']) ){
						$data =  json_decode($result['data'],true);

						if( isset($data['code']) && $data['code'] === 1){
							addInvoicePayment($invoiceid, $refcode, $amount, 0, $gatewaymodule);
							logTransaction($GATEWAY["name"]  ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'tran_id'=>$tran_id, 'refcode'=>$refcode, 'status'=>$status )  ,"موفق"); 
							Header('Location: '.$invoice_link);
						}
						else{
							$Show_Status =	'Error Code: '.$data['code'] . '\r\n ' . $data['status'];
							echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
							logTransaction(  $GATEWAY["name"] ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'tran_id'=>$tran_id,'status'=>$status)  , "ناموفق") ; 
						}
					}else{
						$Show_Status =	'پاسخی از سرویس دهنده دریافت نشد. لطفا دوباره تلاش نمائید';
						echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
						logTransaction(  $GATEWAY["name"] ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'tran_id'=>$tran_id,'status'=>$status)  , "ناموفق") ; 
					}
				}else{
					//$Show_Status =	'unpaid - Error Code: '.$data['code'] . '\r\n ' . $data['status'];
					//echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
					logTransaction(  $GATEWAY["name"] ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'tran_id'=>$tran_id,'status'=>$status)  , "ناموفق") ; 
					Header('Location: '.$invoice_link);
				}
			}
		}
	}
	else{
		$Show_Status =	"undefined irpul token";
		echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
	}
}
else{
	echo "undefined invoice id";
}
//Header('Location: '.$invoice_link);
?>