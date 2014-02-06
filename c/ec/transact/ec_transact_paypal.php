<?php if (FILEGEN != 1) die;
class ec_transact_paypal {
//////////////////////////////////////////////////// paypal payment module
private $PROXY_HOST = '127.0.0.1';
private $PROXY_PORT = '808';
private $USE_PROXY;
private $SandboxFlag = True;
private $API_UserName;
private $API_Password;
private $API_Signature;
private $API_Endpoint;
private $PAYPAL_URL;
private $version;
private $sBNCode;
private $token;
private $paymentType;
private $currencyCodeType;
private $payerID;

function __construct() {
	global $qq;
	
	require_once("p/{$qq['project']}/paypal.php");
	
	if ($this->SandboxFlag == true) {
		$this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
		$this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
	} else {
		$this->API_Endpoint = "https://api-3t.paypal.com/nvp";
		$this->PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
	}
	$this->version="2.3";
}

// this is the transaction's chance to reflect state on a page refreshed in processes
public function initPage($js,$args) {
}

public function transactPayment($cust,$order) {
	// 'estimate' is now as good as it will get
	$paymentAmount=sprintf("%1.2f",$order->estimateTotal($cust)/100);
	
	$nvpstr='&TOKEN='.urlencode($this->token);
	$nvpstr .= '&PAYERID='.urlencode($this->payerID);
	$nvpstr .= '&PAYMENTACTION='.urlencode($this->paymentType);
	$nvpstr .= '&AMT='.urlencode($paymentAmount);
	$nvpstr .= '&CURRENCYCODE='.urlencode($this->currencyCodeType);
	$nvpstr .= '&IPADDRESS='.urlencode($_SERVER['SERVER_NAME']);

	$resArray=$this->hash_call("DoExpressCheckoutPayment",$nvpstr);
	if (is_string($resArray))
		return $resArray;
	
	// save transaction information
	/*
	$transactionId		= $resArray["TRANSACTIONID"]; // ' Unique transaction ID of the payment. Note:  If the PaymentAction of the request was Authorization or Order, this value is your AuthorizationID for use with the Authorization & Capture APIs. 
	$transactionType 	= $resArray["TRANSACTIONTYPE"]; //' The type of transaction Possible values: l  cart l  express-checkout 
	$paymentType		= $resArray["PAYMENTTYPE"];  //' Indicates whether the payment is instant or delayed. Possible values: l  none l  echeck l  instant 
	$orderTime 			= $resArray["ORDERTIME"];  //' Time/date stamp of payment
	$amt				= $resArray["AMT"];  //' The final amount charged, including any shipping and taxes from your Merchant Profile.
	$currencyCode		= $resArray["CURRENCYCODE"];  //' A three-character currency code for one of the currencies listed in PayPay-Supported Transactional Currencies. Default: USD. 
	$feeAmt				= $resArray["FEEAMT"];  //' PayPal fee amount charged for the transaction
	$settleAmt			= $resArray["SETTLEAMT"];  //' Amount deposited in your PayPal account after a currency conversion.
	$taxAmt				= $resArray["TAXAMT"];  //' Tax charged on the transaction.
	$exchangeRate		= $resArray["EXCHANGERATE"];  //' Exchange rate if a currency conversion occurred. Relevant only if your are billing in their non-primary currency. If the customer chooses to pay with a currency other than the non-primary currency, the conversion occurs in the customer’s account.
	
	//Completed: The payment has been completed, and the funds have been added successfully to your account balance.
	//Pending: The payment is pending. See the PendingReason element for more information. 
	$paymentStatus	= $resArray["PAYMENTSTATUS"]; 

	//'  none: No pending reason 
	//'  address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile. 
	//'  echeck: The payment is pending because it was made by an eCheck that has not yet cleared. 
	//'  intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview. 		
	//'  multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment. 
	//'  verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment. 
	//'  other: The payment is pending for a reason other than those listed above. For more information, contact PayPal customer service. 
	$pendingReason	= $resArray["PENDINGREASON"];  

	//'The reason for a reversal if TransactionType is reversal:
	//'  none: No reason code 
	//'  chargeback: A reversal has occurred on this transaction due to a chargeback by your customer. 
	//'  guarantee: A reversal has occurred on this transaction due to your customer triggering a money-back guarantee. 
	//'  buyer-complaint: A reversal has occurred on this transaction due to a complaint about the transaction from your customer. 
	//'  refund: A reversal has occurred on this transaction because you have given the customer a refund. 
	//'  other: A reversal has occurred on this transaction due to a reason not listed above. 
	$reasonCode		= $resArray["REASONCODE"];
	*/

	return array('Transaction ID'=>$resArray["TRANSACTIONID"],'Time Stamp'=>$resArray["ORDERTIME"],'Fee Charged'=>$resArray["FEEAMT"],'Final Deposit'=>$resArray["SETTLEAMT"]);		
}

///////////////////////// pay-pal specific functions (from their documentation)

// returns associative array
private function deformatNVP($nvpstr)
{
	$intial=0;
 	$nvpArray = array();

	while(strlen($nvpstr))
	{
		//postion of Key
		$keypos= strpos($nvpstr,'=');
		//position of value
		$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

		/*getting the Key and Value values and storing in a Associative Array*/
		$keyval=substr($nvpstr,$intial,$keypos);
		$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
		//decoding the respose
		$nvpArray[urldecode($keyval)] =urldecode( $valval);
		$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
     }
	return $nvpArray;
}



// workhorse call routine--returns an associative array
private function hash_call($methodName,$nvpStr)
{
	//setting the curl parameters.
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$this->API_Endpoint);
	curl_setopt($ch,CURLOPT_VERBOSE,1);

	// turning off the server and peer verification(TrustManager Concept).
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);

	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_POST,1);
	
	if ($this->USE_PROXY)
		curl_setopt ($ch,CURLOPT_PROXY,$this->PROXY_HOST. ":" . $this->PROXY_PORT); 

	//NVPRequest for submitting to server
	$nvpreq="METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($this->version) . "&PWD=" . urlencode($this->API_Password) . "&USER=" . urlencode($this->API_UserName) . "&SIGNATURE=" . urlencode($this->API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($this->sBNCode);

	//setting the nvpreq as POST FIELD to curl
	curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);
	infolog("dbg","nvpreq=$nvpreq");

	//getting response from server
	$response = curl_exec($ch);

	//converting NVPResponse to an Associative Array
	$resArray=$this->deformatNVP($response);
	$nvpReqArray=$this->deformatNVP($nvpreq);

	if (curl_errno($ch)) {
		// curl error occurred
		$retval="Call URL error number #".curl_errno($ch).": ".curl_error($ch);
	} else if (strtoupper($resArray["ACK"]) != "SUCCESS") {
		// paypal error
		$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
		$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
		$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
		$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
		$retval="Paypal API call failed.";
		$retval .= "\r\nDetailed Error Message: " . $ErrorLongMsg;
		$retval .= "\r\nShort Error Message: " . $ErrorShortMsg;
		$retval .= "\r\nError Code: " . $ErrorCode;
		$retval .= "\r\nError Severity Code: " . $ErrorSeverityCode;
	} else {
		$retval=$resArray;
	}
  	curl_close($ch);
	return $retval;
}

// returns True if success or String if Error Message.  if True, header has been output to do redirection
function setupExpressCheckout($paymentAmount, $returnURL, $cancelURL, $currencyCodeType, $paymentType, $cust) {
	$this->paymentType=$paymentType;
	$this->currencyCode=$currencyCodeType;
	
	$nvpstr="&Amt=". $paymentAmount;
	$nvpstr .= "&PAYMENTACTION=" . $paymentType;
	$nvpstr .= "&ReturnUrl=" . urlencode($returnURL);
	$nvpstr .= "&CANCELURL=" . urlencode($cancelURL);
	$nvpstr .= "&CURRENCYCODE=" . $currencyCodeType;

	if ($cust != Null) {
		$nvpstr = $nvpstr . "&ADDROVERRIDE=1";
		// use shipping address
		$idship=$cust->useShippingAddress();
		$saddrs=$cust->getShippingAddresses();
		if (!isset($saddrs[$idship]))
			throw new exception("undefined ship index $idship");
		list($name,$address,$phone,$giftrecipient)=$saddrs[$idship];
		$nvpstr = $nvpstr . "&SHIPTONAME=" . $name->output();
		$nvpstr = $nvpstr . "&SHIPTOSTREET=" . $address->getArgParam(data_addresscols::LINE1);
		$nvpstr = $nvpstr . "&SHIPTOSTREET2=" . $address->getArgParam(data_addresscols::LINE2);
		$nvpstr = $nvpstr . "&SHIPTOCITY=" . $address->getArgParam(data_addresscols::CITY);
		$nvpstr = $nvpstr . "&SHIPTOSTATE=" . $address->getArgParam(data_addresscols::STATE);
		$nvpstr = $nvpstr . "&SHIPTOCOUNTRYCODE=" . 'US';
		$nvpstr = $nvpstr . "&SHIPTOZIP=" . $address->getArgParam(data_addresscols::ZIP);
		$nvpstr = $nvpstr . "&PHONENUM=" . $phone->output();
	}
    $resArray=$this->hash_call("SetExpressCheckout", $nvpstr);
	if (is_string($resArray)) {
		$retval=$resArray;
	} else {
		$this->token = urldecode($resArray["TOKEN"]);
		echo ("window.location='".$this->PAYPAL_URL.$this->token."';");
		$retval=True;
	}	
    return $retval;
}

// returns True if success or String if Error Message.
function getOrderInfoFromPaypal($cust,$order) {
	$nvpstr="&TOKEN=".$this->token;
    $resArray=$this->hash_call("GetExpressCheckoutDetails",$nvpstr);
	if (is_string($resArray))
		return $resArray;

/******** values returned by paypal:
		$email 				= $resArray["EMAIL"]; // ' Email address of payer.
		$payerId 			= $resArray["PAYERID"]; // ' Unique PayPal customer account identification number.
		$payerStatus		= $resArray["PAYERSTATUS"]; // ' Status of payer. Character length and limitations: 10 single-byte alphabetic characters.
		$salutation			= $resArray["SALUTATION"]; // ' Payer's salutation.
		$firstName			= $resArray["FIRSTNAME"]; // ' Payer's first name.
		$middleName			= $resArray["MIDDLENAME"]; // ' Payer's middle name.
		$lastName			= $resArray["LASTNAME"]; // ' Payer's last name.
		$suffix				= $resArray["SUFFIX"]; // ' Payer's suffix.
		$cntryCode			= $resArray["COUNTRYCODE"]; // ' Payer's country of residence in the form of ISO standard 3166 two-character country codes.
		$business			= $resArray["BUSINESS"]; // ' Payer's business name.
		$shipToName			= $resArray["SHIPTONAME"]; // ' Person's name associated with this address.
		$shipToStreet		= $resArray["SHIPTOSTREET"]; // ' First street address.
		$shipToStreet2		= $resArray["SHIPTOSTREET2"]; // ' Second street address.
		$shipToCity			= $resArray["SHIPTOCITY"]; // ' Name of city.
		$shipToState		= $resArray["SHIPTOSTATE"]; // ' State or province
		$shipToCntryCode	= $resArray["SHIPTOCOUNTRYCODE"]; // ' Country code. 
		$shipToZip			= $resArray["SHIPTOZIP"]; // ' U.S. Zip code or other country-specific postal code.
		$addressStatus 		= $resArray["ADDRESSSTATUS"]; // ' Status of street address on file with PayPal   
		$invoiceNumber		= $resArray["INVNUM"]; // ' Your own invoice or tracking number, as set by you in the element of the same name in SetExpressCheckout request .
		$phonNumber			= $resArray["PHONENUM"]; // ' Payer's contact telephone number. Note:  PayPal returns a contact telephone number only if your Merchant account profile settings require that the buyer enter one. 
******************/
	
	$this->payerID=$resArray["PAYERID"];	
	$cust->setEmail($resArray["EMAIL"]);
	
	$newname=new data_name();
	$middle=$resArray['MIDDLENAME'] != '' ? ' '.$resArray['MIDDLENAME'].' ' : ' ';
	$name=$resArray['FIRSTNAME'].$middle.$resArray['LASTNAME'];
	if ($resArray['SALUTATION'] != '')
		$name=$resArray['SALUTATION'].' '.$name;
	if ($resArray['SUFFIX'] != '')
		$name .= $resArray['SUFFIX'];
	$newname->verifyAndSet($name);
	if ($resArray['COUNTRYCODE'] != 'US')
		return "Sorry, we cannot ship to addresses outside the U.S. Right now.";
	$addressarray=array(
		'line1'=>$resArray['SHIPTOSTREET'],
		'line2'=>$resArray['SHIPTOSTREET2'],
		'city'=>$resArray['SHIPTOCITY'],
		'state2'=>$resArray['SHIPTOSTATE'],
		'zip'=>$resArray['SHIPTOZIP'],
		'country'=>'USA'
	);	
	$newaddr=new data_addresscols(True);
	$newaddr->verifyAndSet($addressarray,True);
	$newphone=new data_phone();
	$newphone->verifyAndSet($resArray['PHONENUM']);			
	$cust->updateShippingAddress(0,$newname,$newaddr,$newphone);
	$cust->useShippingAddress(0);
	
	$cc=new data_cc();
	$cc->verifyAndSet(array('cctype'=>data_cc::TYPE_PAYPAL),True);
	$cust->useCreditCard($cust->addCreditCard($cc));
	return True;	
}

//////////////////////////////////////////////////////////// end of module
} ?>
