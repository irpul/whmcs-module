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

		if( isset($_POST['trans_id']) && isset($_POST['order_id']) && isset($_POST['amount']) && isset($_POST['refcode']) && isset($_POST['status']) ){
			$trans_id 	= $_POST['trans_id'];
			$order_id 	= $_POST['order_id'];
			$amount 	= $_POST['amount'];//rial
			$refcode	= $_POST['refcode'];
			$status 	= $_POST['status'];
			
			if($order_id!='' && $amount!='' && $trans_id!='' && $status!='' ){//&& $refcode!='' در حالت پرداخت نشده خالی است

				$invoiceid 	= checkCbInvoiceID($invoiceid, $GATEWAY['name']); # Checks invoice ID is a valid invoice number or ends processing

				if($status == 'paid'){
					checkCbTransID($refcode); # Checks transaction number isn't already in the database and ends processing if it does
					/*if($GATEWAY['Currencies'] == 'rial'){//زمانی که واحد پول ایرپول تومان شود
						$amount  = $amount*10;
					}*/

					$params = array	(
						'method' 		=> 'verify',
						'trans_id' 		=> $trans_id,
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
							$irpul_amount  = $data['amount'];

							if($amount == $irpul_amount){
								//paid
								addInvoicePayment($invoiceid, $refcode, $amount, 0, $gatewaymodule);
								logTransaction($GATEWAY["name"]  ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'trans_id'=>$trans_id, 'refcode'=>$refcode, 'status'=>$status )  ,"موفق"); 
								Header('Location: '.$invoice_link);
							}
							else{
								$Show_Status = 'مبلغ تراکنش در ایرپول (' . number_format($irpul_amount) . ' تومان) تومان با مبلغ تراکنش در سیمانت (' . number_format($amount) . ' تومان) برابر نیست';
								echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
								logTransaction(  $GATEWAY["name"] ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'trans_id'=>$trans_id,'status'=>$status)  , "ناموفق") ; 
							}
						}
						else{
							$Show_Status =	'Error Code: '.$data['code'] . '\r\n ' . $data['status'];
							echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
							logTransaction(  $GATEWAY["name"] ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'trans_id'=>$trans_id,'status'=>$status)  , "ناموفق") ; 
						}
					}else{
						$Show_Status =	'پاسخی از سرویس دهنده دریافت نشد. لطفا دوباره تلاش نمائید';
						echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
						logTransaction(  $GATEWAY["name"] ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'trans_id'=>$trans_id,'status'=>$status)  , "ناموفق") ; 
					}
				}else{
					//$Show_Status =	'unpaid - Error Code: '.$data['code'] . '\r\n ' . $data['status'];
					//echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
					logTransaction(  $GATEWAY["name"] ,  array( 'invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$ir_output['amount'],'trans_id'=>$trans_id,'status'=>$status)  , "ناموفق") ; 
					Header('Location: '.$invoice_link);
				}
			}
		}
		else{
			$Show_Status =	"undefined callback parameters";
			echo "<script>alert('".$Show_Status."');</script><script>window.location ='".$invoice_link."'</script>";
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