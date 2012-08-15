<?php if (FILEGEN != 1) die;
class au_ecp_orderlist extends au_base {
/////////////au definition for product based order list  /////////
private $action;		// action being performed in ajax mode
private $data;			// data (not currently used)
			
function __construct($tag,$parent,$initstring,$state='',$data=0) {
	parent::__construct($tag,$parent,$initstring);
	$this->action=$state;
	$this->transaction=(int)$data;
}


// note:  this form is used only to simplify output.
private function makeform() {
	global $qqi;
	
	$form=new llform($this,'form');
	return $form;
}

public function declareids($idstore,$state) {
	$form=$this->makeform();
	$form->declareids($idstore);
	$idstore->declareHTMLid($this);

	$idstore->registerAuthPage('view orders',False);
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
/*
	$args=$js->seedArgs($this);
	$js->addjs('$','ecporder::orderdetailsetup',$args);
*/	
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
	list($name,$address,$phone,$giftrecipient)=$saddrs[$idship];
	
}


// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi,$qqu;

	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	$orderarray=ec_prodorder::getAllPlacedOrders();
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	echo "<table><tr><th>Number</th><th>Placed on</th><th>Name</th><th>ZIP code</th><th>Email</th><th>Shipped via</th><th>Tracking #</th></tr>";
	foreach ($orderarray as $orderinfo) {
		list($id,$timestamp,$email,$via,$tracking,$name,$zip)=$orderinfo;
		$time=date('m/d/Y',$timestamp);
		$idlink=link::anchorHTML("orderdetail/$id",'Order Detail').$id."</a>";
		echo "<tr><td>$idlink</td><td>$time</td><td>$name</td><td>$zip</td><td>$email</td><td>$via</td><td>$tracking</td></tr>";
	}
	echo "</div>";
			

}
////////////////////////////////////////end of class definition/////////////////
} ?>
