<?php if (FILEGEN != 1) die;
class au_ecp_orderdetail extends au_base {
/////////////au definition for product based order processing (detail form) /////////
private $action;		// action being performed in ajax mode
private $data;			// data (not currently used)
			
function __construct($tag,$parent,$initstring,$state='',$data=0) {
	parent::__construct($tag,$parent,$initstring);
	$this->action=$state;
	$this->transaction=(int)$data;
}

// put the id of the product being viewed where it is available on the browser side
public function initialize($pgen) {
	global $qqs,$qqj;

	$idorder=False;
	if (($idorder=$pgen->getPageExtra()) == '') {
		$idorder=ec_prodorder::getUnshippedOrderId(False);
	}
	if ($idorder === False)
		return;

	// dynamic js--set chain mask
	$args=$qqj->seedArgs($this);
	$qqj->addjs('$','ecporder::setorderdetailid',array_merge($args,array('id'=>$idorder)));
}

// note:  this form is used only to simplify output.
private function makeform() {
	global $qqi;
	
	$form=new llform($this,'form');
	$form->addFieldset('d','');
	$form->addControl('d',new ll_listbox($form,'users','Users'));
	$form->addControl('d',new ll_button($form,'allusers','Select All','button'));
	$form->addControl('d',new ll_listbox($form,'functions','Functions'));
	$form->addControl('d',new ll_button($form,'allfunctions','Select All','button'));
	$rg=new ll_radiogroup($form,'showby');
		$rg->addOption('byusers','Rows of Users');
		$rg->addOption('byfunctions','Rows of Functions');
		$rg->setValue('byfunctions');
	$form->addControl('d',$rg);	
	$form->addControl('d',new ll_button($form,'update','Update Display','button'));
	return $form;
}

public function declareids($idstore,$state) {
	$form=$this->makeform();
	$form->declareids($idstore);
	$idstore->declareHTMLid($this);

	$idstore->registerAuthPage('process orders',False);
	$idstore->registerEncodedPage();
}

public function declarestyles($stylegen,$state) {
	$form=$this->makeform();
	$form->declarestyles($stylegen,$this->longname);
	$stylegen->registerStyledId($this->longname,'div:table,tr,td,th,p,a','auth_individual',$this->getParent()->htmlContainerId());
	
	//$stylegen->registerClass('auth_individual_form','p,table,td');
	//$stylegen->registerClass('auth_individual_data','p,table,td');
}

public function declarestaticjs($js) {
	global $qq;

	$args=$js->seedArgs($this);
	$js->addjs('$','ecporder::orderdetailsetup',$args);
}

// called when an ajax call occurs.  output is javascript to be executed upon return
public function processAjax($js,$originUri,$page) {
	global $qqs,$qqi;
	
	$action=$this->action;
	$transaction=$this->transaction;
	if (!$qqs->checkAuth('view_'.$page)) {
		echo "alert('Unauthorized Action');";
		return;
	}
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	$idorder=$_REQUEST['idorder'];
	$newvia=$_REQUEST['via'];
	$newtracking=$_REQUEST['tracking'];
	list($cust,$order,$timestamp,$email,$via,$tracking,$name,$zip)=ec_prodorder::infoFromId($idorder);
	$order->setExtra('cv','(erased)');
	$order->update($idorder);
	$order->setShippingRecord($idorder,$newvia,$newtracking,$cust);	
	$args=$js->seedArgs($this);
	echo $js->snippetToCode('ecporder::ordershipped',$args,$page);
}


private function getShippingAddress($cust,$idship,&$bGift) {
	$saddrs=$cust->getShippingAddresses();
	if (!isset($saddrs[$idship]))
		throw new exception("undefined ship index $idship");
	list($name,$address,$phone,$giftrecipient)=$saddrs[$idship];

	if ($name instanceof data_name) {
		$retval=$name->output();
	} else
		$retval='&nbsp;';	
	$retval=($name instanceof data_name) ? $name->output() : '&nbsp;';
	if ($idship != 0 && $giftrecipient) {
		$retval .= ' (gift)';
		$bGift=True;
	} else
		$bGift=False;	
	if ($address instanceof data_addresscols) {
		$addrstr=$address->output();
		$retval .= '<br />'.$address->output();
	}
	if ($phone instanceof data_phone) {
		$retval .= '<br />Phone: '.$phone->output();
	}
	return $retval;
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi,$qqu;

	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	$idorder=False;
	if (($idorder=$pgen->getPageExtra()) == '') {
		$idorder=ec_prodorder::getUnshippedOrderId(False);
	}
	if ($idorder === False) {
		echo '<p>No unfilled orders</p>';
		return;
	}
	list($cust,$order,$timestamp,$email,$via,$tracking,$name,$zip)=ec_prodorder::infoFromId($idorder);
	$shiptype=$cust->getPreferredShipping();
	$shipdesc=$order->getAllShippingOptions();
	$shipopts=$order->getShippingOptions($cust);
	
	echo "<div{$qqi->idcstr($this->longname)}>";

	$form=$this->makeform();


	echo "<h2>";
	if (False !== ($idnext=ec_prodorder::getUnshippedOrderId(True,$idorder)))
		echo link::anchorHTML("orderdetail/$idnext",'Previous unshipped order')."[<<< prev]</a>&nbsp;&nbsp;&nbsp;";
	echo "Order Number $idorder made on ".date('l jS \of F Y h:i:s A',$timestamp);
	if (False !== ($idprev=ec_prodorder::getUnshippedOrderId(False,$idorder)))
		echo '&nbsp;&nbsp;&nbsp;'.link::anchorHTML("orderdetail/$idprev",'Next unshipped order')."[next >>>]</a>";
	echo '</h2>';
	$instructions=nl2br(trim($order->getSpecialInstructions()));
	if (strlen($instructions)) {
		echo "<table><tr><th>Special Instructions</th></tr><tr><td>$instructions</td></tr></table><br />";
	}
	
	$lines=$order->getOrderArray(False);
	$subtotal=0;
	foreach ($lines as $litem) {
		$subtotal += $litem[4];
	}
	$showsubtotal=sprintf('%1.2f',$subtotal/100);

	// patch for peach basket special
	if ($subtotal > 3500) {
		$newshipopts=array();
		foreach ($shipopts as $shiptype=>$cost)
			$newshipopts[$shiptype]=0;
		$shipopts=$newshipopts;	
	}
		
	$tax=$order->calculateTax($cust);
	$showtax=sprintf('%1.2f',$tax/100);
	$showshipping=sprintf('%1.2f',$shipopts[$shiptype]/100);
	$showtotal=sprintf('%1.2f',($tax+$shipopts[$shiptype]+$subtotal)/100);
	
	echo "<table><tr><th>Email</th><th>Bill to</th><th>Payment</th><th style=\"text-align:right\">Totals</th></tr>";
	echo "<tr><td>{$cust->getEmail()}</td><td>{$this->getShippingAddress($cust,0,$bGift)}</td><td>{$cust->getActiveCreditCard()->output3(False)}<br />CV: <span id=\"zcv\">{$order->getExtra('cv')}</span></td>";
	echo "<td style=\"text-align:right\">Total Products: $showsubtotal<br />Tax: $showtax<br />Shipping: $showshipping<br />Total: $showtotal</td></tr>";
	echo "</table><br />";

	echo '<table><tr><th>Qty</th><th>Item</th><th>Unit Price</th><th>Disc.</th><th>Amount</th></tr>';
	foreach ($lines as $litem) {
		list($qty,$prodid,$baseprice,$discount,$subtotal)=$litem;
		$info=$pi->getProdInfo($prodid);
		$maininfo=$pm->getProductInfo($prodid);
		$title=$qqu->creolelite2html($info['title']);
		$upc=trim($maininfo['barcodes'][0]);
		echo "<tr><td>$qty</td><td>{$info['mfr']}: $title<br />UPC: <span id=\"za$prodid\">$upc</span> <input id=\"zb$prodid\" type=\"text\" size=\"20\" maxlength=\"20\" /> <span id=\"zc$prodid\">&nbsp;</span></td>";
		$baseprice=sprintf('%1.2f',$baseprice/100);
		$discount=sprintf('%1.2f',$discount/100);
		$subtotal=sprintf('%1.2f',$subtotal/100);
		echo "<td style=\"text-align:right\">$baseprice</td><td style=\"text-align:right\">$discount</td><td style=\"text-align:right\">$subtotal</td></tr>";
	}
	echo '</table><br />';

	echo '<table><tr><th>Shipping Address</th><th>Shipping Method</th><th>Shipping Status</th></tr>';
	$idaddr=$cust->useShippingAddress();
	echo "<tr><td>{$this->getShippingAddress($cust,$idaddr,$bGift)}";
	if ($bGift && False !== ($giftmsg=$order->getExtra('giftnote'))) {
		echo "<br /><br /><h3>Gift Message:</h3>$giftmsg";
	}
	if ($via == '(not shipped)')
		$via=ec_prodorder::getTrackingURL($shiptype);
	echo "</td><td>{$shipdesc[$shiptype]}</td><td>Shipped via:<br /><input type=\"text\" name=\"via\" value=\"$via\" size=\"60\" maxlength=\"60\" /><br />Tracking Number:<br /><input type=\"text\" name=\"tracking\" value=\"$tracking\" size=\"30\" maxlength=\"30\" /><br /><br /><input id='zsetship' name=\"ok\" type=\"button\" value=\"Set Shipping\" />";
		
	echo "</div>";
			

}
////////////////////////////////////////end of class definition/////////////////
} ?>
