<?php if (FILEGEN != 1) die;
class au_visitorrequestdata extends au_base {
///////////////////////// class definition for visitor request data display ////
private $state;
private $data;
private $requesttype;
private $bSecure;
			
function __construct($tag,$parent,$initstring,$state="",$data=0) {
	parent::__construct($tag,$parent,$initstring);
	$initdata=parent::getInit($initstring,'*secure="0"|type=""');
	
	$this->state=$state;
	$this->transaction=(int)$data;
	$this->requesttype=isset($initdata['type']) ? $initdata['type'] : '';
	$this->bSecure=(int)$initdata['secure'];
}

// returns form and form formatting string
private function makeform() {
	global $qqi;
	
	$form=new llform($this);
	$form->addFieldset('d','');
	$form->addControl('d',new ll_button($form,'delete','Delete Selected','button'));
	$form->addControl('d',new ll_button($form,'deleteall','Delete All','button'));
	return $form;
}

public function declareids($idstore,$state) {
	$form=$this->makeform();
	$form->declareids($idstore);
	if ($this->requesttype != '') {
		$idstore->registerAuthPage("type: {$this->requesttype}",False);
	} else	
		$idstore->registerAuthPage('general request data',False);
	if ($this->bSecure)
		$idstore->registerEncodedPage();
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'form:input,table,td,tr,th,p','visitorrequest',$this->getParent()->htmlContainerId());
	$form=$this->makeform();
	$form->declarestyles($stylegen,$this->longname);
	$stylegen->registerClass('visitorrequest','h1,p,table,td');
	$stylegen->registerClass('visitorrequesterror','p,td');
}


public function processVars($originUri) {
	global $qq,$qqs,$qqi;

	// get data from form:
	$form=$this->makeForm();	
	$vals=$form->getValueArray();
	$mod=new mod_visitorrequest();
	
	// check to see which action user chose
//	if ($form->wasPressed('delete')) {
		$ids=$_REQUEST[ids];
//	} else {
//		$rows=$mod->readActions();
//		$ids=array();
//		foreach ($rows as $row)
//			$ids[$row['id']]=1;
//	}
	foreach ($ids as $id=>$true)
		$mod->deleteAction((string)$id);
	return $qqi->hrefPrep($originUri);	 
}

private function doRows($form,$rows,$mytype) {
	if ($mytype != '') {
		if (sizeof($rows) == 0)
			$mytype .= ' (no requests of this type)';
		echo "<tr><th colspan=\"7\">$mytype</td></tr>";
	}	
	foreach ($rows as $row) {
		extract($row); //sets:  id,type,timestamp,data
		// put out the checkbox
		echo "<tr><td><input type=\"checkbox\" name=\"ids[$id]\" value=\"1\" /></td>";
		// type (if applicable) and date		
		$datestring=date('m/d/y h:m:s',$timestamp);
		echo "<td>";
		if ($mytype == '')
			echo "$type<br />";
		echo "$datestring</td>";
		// put out 'fixed' data in their own columns
		$cell=isset($data['name']) ? $data['name']->output() : '&nbsp';
		echo "<td>$cell</td>";
		$cell=isset($data['email']) ? $data['email']->output() : '&nbsp';
		echo "<td>$cell</td>";
		$cell=isset($data['mail']) ? $data['mail']->output() : '&nbsp';
		echo "<td>$cell</td>";
		$cell=isset($data['phone0']) ? $data['phone0']->output(' ') : '&nbsp';
		if (isset($data['phone1']))
			$cell .= '<br />'.$data['phone1']->output(' ');
		if (isset($data['phone2']))
			$cell .= '<br />'.$data['phone2']->output(' ');
		echo "<td>$cell</td>";
		
		// variable data is all done in one cell
		echo '<td>';
		$fixed=array('name','email','mail','phone0','phone1','phone2');
		$separator='';
		foreach ($data as $name=>$item) {
			if (False !== array_search($name,$fixed))
				continue;  // already output
			if ($item instanceof data_cc)
				echo $separator.$item->output('<br />');
			else	
				echo "$separator$name: $item";
			$separator='<br />';	
		}
		if ($separator == '')
			echo '&nbsp';
		echo '</td></tr>';	
	}
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	
	$mod=new mod_visitorrequest();
	
	$form=$this->makeForm();
	echo $form->getOutputFormStart();

	// either do full-scale dump or dump by requested sections	
	if (($type=$this->requesttype) != '') {
		// do a series of sections for each type
		$alltypes=explode(',',$type);
		echo "<table><tr><th>&nbsp</th><th>Time</th><th>Name</th><th>Email</th><th>Address</th><th>Phone</th><th>Additional Data</th>";
		foreach ($alltypes as $mytype) {
			if ($mod->countActions($mytype) > 0) {
				$rows=$mod->readActions($mytype);
			} else {
				$rows=array();
			}
			$this->doRows($form,$rows,$mytype);
		}
		echo "</table><br />";
	} else {
		// full dump of all types:
		if ($mod->countActions() > 0) {
			$rows=$mod->readActions();
			echo "<table><tr><th>&nbsp</th><th>Action/Time</th><th>Name</th><th>Email</th><th>Address</th><th>Phone</th><th>Additional Data</th>";
			$this->doRows($form,$rows,'');
			echo "</table><br />";
			
		} else {
			echo '<p><br /><br /><strong>&nbsp;&nbsp;&nbsp;&nbsp;There are currently no user requests on file.</strong></p>';
		}
	}
	
	$button1out=$form->getFieldOutput('delete',False,True);
	$button1out=str_replace('type=',"onclick= \"if (true === confirm('Sure you want to delete?  Action cannot be undone'))submit();\" type=",$button1out);
	//need to have a way to distinguish these two buttons, because submit() takes no arguments.  maybe a hidden variable, change its value.	
//	$button2out=$form->getFieldOutput('deleteall',False,True);	
//	$button2out=str_replace('type=',"onclick= \"if (true === confirm('Sure you want to delete?  Action cannot be undone'))submit();\" type=",$button2out);	
	echo "$button1out"; // echo "&nbsp;&nbsp;&nbsp;$button2out";
	echo $form->getOutputFormEnd('',0);
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
