<?php
function irpul_config(){
	$sql_custom = @mysql_query( "SELECT id,fieldname FROM tblcustomfields WHERE type='client' AND fieldtype='text' ");
	$custom =array();
	while( $row_custom = @mysql_fetch_assoc( $sql_custom ) ){
		$field_id 			= $row_custom['id'];
		$fieldname 			= $row_custom['fieldname'];
		$custom[$field_id] 	= $fieldname;
	}
	
    $configarray = array(
		"FriendlyName" 	=> array("Type" => "System"		, "Value"=>"درگاه ایرپول"),
		//"webgate_id" 	=> array("Type"	=> "text"		, "FriendlyName" => "شناسه درگاه"	, "Size" => "50", ),
		"token" 		=> array("Type"	=> "text"		, "FriendlyName" => "توکن درگاه"	, "Size" => "50", ),
		"Currencies" 	=> array("Type" => "dropdown"	, "FriendlyName" => "واحد پول سیستم", "Options" => "rial,toman", "Description" => "لطفا واحد پول سیستم خود را انتخاب کنید.",),
		"mobile_num" 	=> array("Type" => "dropdown"	, "FriendlyName" => "تلفن همراه"	, "Options" => $custom, "Description" => "فیلد مخصوص شماره موبایل را انتخاب کنید",),
    );
	return $configarray;
}
function irpul_link($params) {
    $currencies = $params['Currencies'];
    $invoiceid 	= $params['invoiceid'];
    $amount 	= $params['amount']; # Format: ##.##
    $email 		= $params['clientdetails']['email'];
	
	$phone		= $params['clientdetails']['phonenumber'];
	$fullname	= $params['clientdetails']['fullname'];
	
	$state		= $params['clientdetails']['state'];
	$city		= $params['clientdetails']['city'];
	$address1	= $params['clientdetails']['address1'];
	$address 	= $state . ' ' . $city  .' '. $address1 ;
      
	$amount = $params['amount']-'.00';
	
	$mobile='';
	foreach($params['clientdetails']['customfields'] as $name ){
		$customfields_id = $name['id'];
		if($customfields_id == $params['mobile_num'] ){
			$mobile = $name['value'];
		}
	}
	
	$command = "getinvoice";
	$adminuser = "simanet";
	$values["invoiceid"] = $invoiceid;
	$results = localAPI($command,$values,$adminuser);
	
	$product_item 	= $results['items']['item'];
	$product='';
	$i = 0;
	$len = count($product_item);
	foreach( $product_item as $pro){
		$amount1 = str_replace('.00','',$pro['amount']);
		$product .= $pro['description'] . ' مبلغ: ' .  $pro['amount'] . ' ' .$params['currency'];
		if ($i!=$len-1) {//not be last loop
			$product .= '|';
		}
		$i++;
	}

	$code = '<form method="post" action="modules/gateways/irpul/pay.php">
	<input type="hidden" name="invoiceid" value="'. $invoiceid .'" />
	<input type="hidden" name="product" value="'. $product .'" />
	<input type="hidden" name="amount" value="'. $amount .'" />
	<input type="hidden" name="email" value="'. $email .'" />
	<input type="hidden" name="phone" value="'. $phone .'" />
	<input type="hidden" name="fullname" value="'. $fullname .'" />
	<input type="hidden" name="mobile" value="'. $mobile .'" />
	<input type="hidden" name="address" value="'. $address .'" />
	<input type="submit" name="pay" value=" پرداخت " class="btn btn-primary " /></form>
	';
	
	return $code;
}
?>