<?php if (FILEGEN != 1) die;
class au_auth_individual extends au_base {
/////////////au definition for individual authorization administration /////////
private $action;		// action being performed in ajax mode
private $data;			// data (not currently used)
			
function __construct($tag,$parent,$initstring,$state="",$data=0) {
	parent::__construct($tag,$parent,$initstring);
	$this->action=$state;
	$this->transaction=(int)$data;
}


// note:  this form is used only to simplify output.
private function makeform() {
	global $qqi;
	
	$form=new llform($this);
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
	$idstore->registerAuthPage('change authorizations',False);
	$idstore->declareHTMLid($this,True,'data');
}

public function declarestyles($stylegen,$state) {
	$form=$this->makeform();
	$form->declarestyles($stylegen,$this->longname);
	// div containing table:
	$stylegen->registerStyledId("{$this->longname}_data",'div:table,tr,td,th,p','auth_individual',$this->getParent()->htmlContainerId());
	
	$stylegen->registerClass('auth_individual_form','p,table,td');
	$stylegen->registerClass('auth_individual_data','p,table,td');
}

public function declarestaticjs($js) {
	global $qq;
	
	$form=$this->makeform();
	$args=$js->seedArgs($this);
	// select all users button clieck event
	$js->addeventjs($form->getControl('allusers')->longname(),'click','auth_individual::selectall',array_merge($args,array('listbox'=>'users')));
	// select all functions button click event 
	$js->addeventjs($form->getControl('allfunctions')->longname(),'click','auth_individual::selectall',array_merge($args,array('listbox'=>'functions')));
	$argshort=array('users','functions','data');
	// update display click event 
	$js->addeventjs($form->getControl('update')->longname(),'click','auth_individual::updatedisplay',array_merge($args,array('users'=>'users','functions'=>'functions','data'=>'data')));
	// snippets called in ajax routine:
	$args['***']='';	// turn off parameter checking 
	$js->scanjsdependencies('auth_individual::setdata',$args);
	$js->scanjsdependencies('auth_individual::checkboxupdatesuccess',$args);
	$js->scanjsdependencies('auth_individual::editupdatesuccess',$args);
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
	
	
	if ($action == 'settable') {
		$authobj=$qqs->getAuthObject();
		$users=explode('|',$_REQUEST['users']);
		$sortby=$_REQUEST['sortby'];
		$functions=explode('|',$_REQUEST['functions']);
		$fnames=$qqi->getAuthNames();
		$o='<table>';
		if ($sortby == 'byusers') {
			// header row contains function names
			$o .= '<tr><th>Name</th>';
			foreach ($functions as $findex) {
				$o .= "<th>{$fnames[$findex]}</th>";			
			}
			$o .= '</tr>';
			foreach ($users as $userid) {
				$auth=$authobj->read($userid);
				if ($userid == 0)
					$username='(anonymous user)';
				else if ($userid == -2)	
					$username='(newly registered user)';
				else {
					$nameobj=$qqs->getUserProfile($userid,'name');
					$username=$nameobj->output();
				}
				$o .= "<tr><td>$username</td>";
				foreach ($functions as $findex) {
					list($min,$max)=$qqi->getAuthLimits($findex);
					if ($min == 0 && $max == 1) {
						$checked=$auth[$findex] ? ' checked="checked"' : '';
						$o .= "<td><input type=\"checkbox\" value=\"1\"$checked onclick=\"fg.auth_individual_check(this)\" id=\"{$findex}_$userid\" /> <span id=\"{$findex}_{$userid}_span\"></span></td>";
					} else {
						// edit control
						$o .= "<td><input type=\"text\" style=\"border:1px black solid\" value=\"{$auth[$findex]}\" onchange=\"fg.auth_individual_edit(this)\" size=\"8\" maxlength=\"8\" id=\"{$findex}_$userid\" /> <span id=\"{$findex}_{$userid}_span\"></span></td>";
					}	
				}
				$o .= '</tr>';						
			}
		} else {
			// header row contains user names
			$o .= '<tr><th>Function</th>';
			foreach ($users as $userid) {
				if ($userid == 0)
					$username='(anonymous user)';
				else if ($userid == -2)	
					$username='(newly registered user)';
				else {
					$nameobj=$qqs->getUserProfile($userid,'name');
					$username=$nameobj->output();
				}
				$o .= "<th>$username</th>";
			}
			$o .= '</tr>';
			foreach ($functions as $findex) {
				$o .= "<tr><td>{$fnames[$findex]}</td>";			
				foreach ($users as $userid) {
					$auth=$authobj->read($userid);
					list($min,$max)=$qqi->getAuthLimits($findex);
					if ($min == 0 && $max == 1) {
						$checked=$auth[$findex] ? ' checked="checked"' : '';
						$o .= "<td><input type=\"checkbox\" value=\"1\"$checked onclick=\"fg.auth_individual_check(this)\" id=\"{$findex}_$userid\" /> <span id=\"{$findex}_{$userid}_span\"></span></td>";
					} else {
						// edit control
						$o .= "<td><input type=\"text\" style=\"border:1px black solid\" value=\"{$auth[$findex]}\" onchange=\"fg.auth_individual_edit(this)\" size=\"8\" maxlength=\"8\" id=\"{$findex}_$userid\" /> <span id=\"{$findex}_{$userid}_span\"></span></td>";
					}	
				}
				$o .= '</tr>';						
			}
		}	
		$o .= '</table>';		
		$args=$js->seedArgs($this);
		echo $js->snippetToCode('auth_individual::setdata',array_merge($args,array('divtag'=>'data','setdata'=>$o)),$page);
	} else if ($action == 'setcheck') {
		$checkid=$_REQUEST['itemid'];
		if (False === ($i=strrpos($checkid,'_')))
			throw new exception("illegal checkbox id:  $checkid");
		$userid=substr($checkid,$i+1);
		$findex=substr($checkid,0,$i);
		$newvalue=($_REQUEST['itemchecked'] == 'true');
		// update the authorization profile
		$authobj=$qqs->getAuthObject();
		$authobj->update($userid,$findex,$newvalue);
		// send back a result to indicate that the value has been accepted:
		$args=$js->seedArgs($this);
		echo $js->snippetToCode('auth_individual::checkboxupdatesuccess',array_merge($args,array('checkid'=>$checkid,'spanid'=>"{$checkid}_span")),$page);
	} else if ($action == 'setedit') {
		$editid=$_REQUEST['itemid'];
		if (False === ($i=strrpos($editid,'_')))
			throw new exception("illegal edit id:  $checkid");
		$userid=substr($editid,$i+1);
		$findex=substr($editid,0,$i);
		$newvalue=$_REQUEST['itemvalue'];
		// update the authorization profile
		$authobj=$qqs->getAuthObject();
		$authobj->update($userid,$findex,$newvalue);
		// send back a result to indicate that the value has been accepted:
		$args=$js->seedArgs($this);
		echo $js->snippetToCode('auth_individual::editupdatesuccess',array_merge($args,array('editid'=>$editid,'spanid'=>"{$editid}_span")),$page);
	} else
		throw new exception("unknown action: $action");	
}


public function processVars($originUri) {
	// this is an ajax only form.  no processing needed
	return $originUri;	 
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	$form=$this->makeform();
	
	// populate the form with user names
	$ids=$qqs->lookupProfile(array());		// gets all ids
	$ids[]=0;
	$ids[]=-2;
	$lb=$form->getControl('users');
	foreach ($ids as $id) {
		if ($id == 0) {
			$name='(anonymous user)';
		} else if ($id == -2) {
			$name='(newly registered user)';
		} else {	
			$profname=$qqs->getUserProfile($id,'name');
			$name=$profname->output();
		}	
		$lb->addOption($id,$name);
	}
	
	// populate with authorization table names
	$functions=$qqi->getAuthNames();
	$lb=$form->getControl('functions');
	foreach ($functions as $name=>$desc) {
		$lb->addOption($name,$desc);
	}
	
	$format="<table{$qqi->cstr('auth_individual_form')}><tr>
			<td{$qqi->cstr('auth_individual_form')}><<!users>><br /><<users>><br /><<allusers>></td>
			<td{$qqi->cstr('auth_individual_form')}><<!functions>><br /><<functions>><br /><<allfunctions>></td>
			<td{$qqi->cstr('auth_individual_form')}><<showby>></td>
			<td{$qqi->cstr('auth_individual_form')}><<update>></td></tr></table>";
	echo $form->getFormattedOutput($format);
	echo "<br />";
	echo "<div{$qqi->idcstr("{$this->longname}_data")}>Click on 'update display' to view authorization elements</div>";
	echo "<br />";
}
////////////////////////////////////////end of class definition/////////////////
} ?>
