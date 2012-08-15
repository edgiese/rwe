<?php if (FILEGEN != 1) die; 
//// This file contains all the definitions for the low level form and the subclasses it uses.

////// llform class is the container and main interface for all the others ///////
class llform {  /// class for form wrapper for other controls
////////////////////////////////////////////////////////////////////////////////
protected $usage;	// usage for this form
protected $tag;		// short, commonsense name to uniquely identify this form (unique to au)
protected $longname;// html id for this form
protected $c;		// array of control objects contained in form (indexed by control tag)
protected $fieldset;	// array of $fieldsetname=>array($fieldsetlongname,$fieldsettitle,array of control tags) 
protected $title;	// title of the form--used for display (msg id)
protected $extra;	// array of "extra" values stored in hidden inputs
protected $bMultiPart;	// if true, this is a multi-part form
protected $extraclass;	// extra classes
protected $bPost=True;	// post or get for method
protected $aushort;

function __construct($au,$tag="",$title="",$usage="form") {
	global $qqu;

	$this->aushort=$au->getShortName();
	$this->longname=$au->getLongName();
	if ($tag != "")
		$this->longname .= "_".$tag;
	$this->usage=$usage;	
	$this->tag=$tag;
	$this->title=$title;
	$this->c=array();
	$this->fieldset=array();
	$this->bMultiPart=False;
	$this->extraclass="";
	$this->extra=array();
}

//////////////////////////////////////////////// convenience functions for aus:
static function redirectstring($newstate,$newdata='') {
	global $qqi;
	if ($newdata == '')
		$newdata=$_REQUEST['t'];
	return $qqi->hrefPrep("form/{$_REQUEST['r']}/$newstate/$newdata");
}

// returns URL to invoke a form
static function buildFormURL($au,$requesturi,$newstate,$transaction,$values,$page='') {
	global $qq,$qqi;

	$requesturi=urlencode($requesturi);
	$qargs="?q={$requesturi}&r={$au->getShortName()}&s=$newstate&t=$transaction";
	if (!$qq['production'])
		$qargs .= "&u={$qq['project']}";
	foreach($values as $name=>$value) {
		$qargs .= '&'.$name.'='.urlencode($value);
	}
	return $qqi->hrefPrep('form.php',False,$page,idstore::ENCODE_BYSOURCE).$qargs;
}

// returns text suitable to invoke a form without printing the form.  Useful for single-command links
static function getLinkText($au,$newstate,$transaction,$values,$linktext,$classtext='') {
	$classtext=trim($classtext);
	if ($classtext != '')
		$classtext .= ' ';
	$url=self::buildFormURL($au,urlencode($qq['request']),$newstate,$transaction,$values);
	return "<a {$classtext}href=\"$url\">$linktext</a>";
}

///////////////////////////////////////////////// data access:
public function useGetMethod($switch=True) {$this->bPost=!$switch;} 
public function longname() {return $this->longname;}

public function declareids($idstore,$bLock=False) {
	// the form
	$idstore->declareHTMLlong($this->longname);
	// the field sets
	foreach ($this->fieldset as $tag=>$fs) {
		$idstore->declareHTMLlong($fs[0]);
		if ($bLock)
			$idstore->lockHTMLid($fs[0]);
	}	
	// controls	
	foreach ($this->c as $tag=>$ctrl)
		$ctrl->declareHTML($idstore,$bLock);
	// the extra data (hidden).  this is so js can manipulate them
	foreach ($this->extra as $name=>$value) {
		$idstore->declareHTMLlong("{$this->longname}_$name");
		if ($bLock)
			$idstore->lockHTMLid("{$this->longname}_$name");
	}	
					
}

public function declarestyles($stylegen,$parentlong) {
	// the form -- html parent is passed in
	$stylegen->registerStyledId($this->longname,"form:table,tr,th,td",$this->usage,$parentlong);
	// the field sets -- html parent is this form
	foreach ($this->fieldset as $tag=>$fs) {
		$stylegen->registerStyledId($fs[0],"fieldset",$this->usage,$this->longname);
	}	
	// controls	
	foreach ($this->c as $tag=>$ctrl)
		$ctrl->declareStyles($stylegen,$this->longname);
}

public function setExtraClass($stylegen,$extraclass) {
	$stylegen->registerClass($extraclass,"form");
	$this->extraclass=$extraclass;
}

// gets a $_POST variable or, as appropriate, a saved one
private function request($name,$default=Null,$values) {
	if (isset($values[$name]))
		$value=$values[$name];
	else
		$value=$default;
	return $value;		
}

public function tag() {return $this->tag;}
public function controlNames() {return array_keys($this->c);}

// add a fieldset to the form ("" for label means no legend)
// note -- this feature only makes sense for "defs" and "par" output formats
public function addFieldset($tag,$label="") {
	if (isset($this->fieldset[$tag]))
		throw new exception("Duplicate fieldset {$tag} entered on form {$this->tag}");
	$long=$this->longname."_fs_".$tag;
	// longname, label, array of tags, extraclass	
	$this->fieldset[$tag]=array($long,$label,array(),"");
}
public function getFieldsetLong($fieldset) {
	if (!isset($this->fieldset[$fieldset]))
		throw new exception("Trying to get a long name of a Fieldset {$fieldset} in form {$this->tag} that does not exist");
	return $this->fieldset[$fieldset][0];
} 
public function getFieldsetTitle($fieldset) {
	if (!isset($this->fieldset[$fieldset]))
		throw new exception("Trying to get a title of a Fieldset {$fieldset} in form {$this->tag} that does not exist");
	return $this->fieldset[$fieldset][1];
} 

public function setExtraClassFieldset($stylegen,$fieldset,$extraclass) {
	$stylegen->registerClass($extraclass,"fieldset");
	if (!isset($this->fieldset[$fieldset]))
		throw new exception("Trying to set a fieldset extra class {$fieldset} in form {$this->tag} that does not exist");
	$this->fieldset[$fieldset][3]=$extraclass;
}


// add a control to the form
public function addControl ($fieldset,$control) {
	if (!is_subclass_of($control,'ll_field'))
		throw new exception("control passed into addcontrol is not valid");
	$tag=$control->tag();
	if (isset($this->c[$tag]))
		throw new exception("Duplicate tag {$tag} entered on form {$this->tag}");
	$this->c[$tag]=$control;
	if (!isset($this->fieldset[$fieldset]))
		throw new exception("Trying to add {$tag} to undefined fieldset {$fieldset} in form {$this->tag}");
	$this->fieldset[$fieldset][2][]=$tag;
	if ($control->isMultiPart()) {
		$this->bMultiPart=True;
	}	
}

public function getControl($tag) {
	if (!isset($this->c[$tag]))
		throw new exception("Attempting to get unknown field {$tag} from form {$this->longname}");
	return $this->c[$tag];
}

public function setValue($name,$value) {
	if (!isset($this->c[$name])) {
		throw new exception("Attempting to set value of unknown field {$name} in form {$this->longname}");
		return;
	}
	if (!$this->c[$name]->hasValue()) {
		throw new exception("Attempting to set value of value-less field {$name} in form {$this->longname}");
		return;
	}
	$this->c[$name]->setValue($value);
}

public function setExtraValue($name,$value) {$this->extra[$name]=$value;}

public function hasValue($name) {
	if (isset($this->c[$name]) && $this->c[$name]->hasValue())
		return True;
	if (isset($this->extra[$name]))
		return True;
	return False;		
}

public function getValue($name) {
	if (isset($this->c[$name]) && $this->c[$name]->hasValue())
		return $this->c[$name]->getValue();
	if (isset($this->extra[$name]))
		return $this->extra[$name];
	$names="";	
	foreach($this->c as $n=>$c)
		$names .= "$n ";	
	throw new exception("undefined value or unknown field {$name} requested from form {$this->longname}.  Names available: $names");
}

// returns true if control is a button and was pressed.  otherwise false
public function wasPressed($name) {
	global $qqi;
	
	if (!isset($this->c[$name]))
		throw new exception("Attempting to check if unknown '{$name}' from form {$this->longname} was pressed");
	$button=$this->c[$name];
	if (!($button instanceof ll_button))
		throw new exception("cannot check if $name is pressed; it isn't a button");
	return ($this->request($name,"",$_REQUEST) == $button->getTitle());
}

public function setValueArray($vals) {
	foreach ($vals as $tag=>$value) {
		$this->c[$tag]->setValue($value);
	}
}

public function getValueArray() {
	$this->setFieldsFromRequests();
	$retval=$this->extra;
	foreach ($this->c as $tag=>$c) {
		if ($c->hasValue())
			$retval[$tag]=$c->getValue();
	}
	return $retval;
}

public function setErrorArray($errors) {
	foreach ($errors as $tag=>$error) {
		$this->c[$tag]->setError($value);
	}
}

public function getErrorArray() {
	foreach ($this->c as $tag=>$c) {
		if (is_string($c->error()) && $c->error() != '')
			$retval[$tag]=$c->error();
	}
	return $retval;
}

public function setFieldsFromRequests($datavals=Null) {
	// put values that correspond to controls into control values.  otherwise,
	// set them as extra data.
	if ($datavals === Null)
		$datavals=$_REQUEST;
	$retval="";
	foreach ($this->c as $varname=>$ctrl) {
		if ($ctrl->hasValue()) {
			$value=$this->request($varname,Null,$datavals);
			if ($value == Null) {
				if ($ctrl instanceof ll_checkbox)
					$value=False;		// unchecked checkboxes return no values
				else if ($ctrl instanceof ll_listbox)
					$value=array();		// empty (totally unchecked) multiple sel listbox
				else if ($ctrl instanceof ll_radioitem) { // radio items are true or false depending on the group value they're a member of
					$value=($this->request($ctrl->getGroup(),Null,$datavals) == $ctrl->tag()) ? True : False;
				} else
					continue;			// no value set for this control, leave as default	
			} else if (get_magic_quotes_gpc() && is_string($value))
				$value=stripslashes($value);
			$ctrl->setValue($value);
		} else if ($ctrl instanceof ll_button) {
			if ($this->request($varname,"",$datavals) != "")
				$retval=$ctrl->tag();
		}
	}
	// whatever is left is "extra"
	foreach ($this->extra as $name=>$value) {
		if (Null != ($value=$this->request($name,Null,$datavals))) {
			if (get_magic_quotes_gpc())
				$value=stripslashes($value);
			$this->setExtraValue($name,$value);
		}
	}
	return $retval;
}


public function getOutputFormStart() {
	global $qq,$qqi;
	$enctype=$this->bMultiPart ? " enctype=\"multipart/form-data\"" : "";
	$method=$this->bPost ? "post" : "get";
	// even though the action is technically a link, it is outside of the project tree and thus not a link, but part of the base system.  so we use srcbase:
	$action=$qqi->hrefPrep('form.php',False,'',idstore::ENCODE_BYSOURCE);
	$retval="<form{$qqi->idcstr($this->longname,$this->extraclass)} method=\"$method\" action=\"$action\"$enctype>";
	return $retval;	
}

public function getOutputFieldsetStart($tag) {
	global $qqi;
	if (!isset($this->fieldset[$tag]))
		throw new exception("Trying to output undefined fieldset {$tag} in form {$this->tag}");
	$fieldset=$this->fieldset[$tag];
	$output='';
	if ($fieldset[1] != '')
		$output .= "<fieldset{$qqi->idcstr($fieldset[0],$fieldset[3])}><legend>{$fieldset[1]}</legend>";
	return $output;	
}

public function getOutputFormEnd($state='',$transactiondata=0) {
	global $qq,$qqi;
	 
	// put out extra values as hidden controls
	$output="";
	foreach ($this->extra as $name=>$value) {
		$id=$qqi->htmlShortFromLong("{$this->longname}_$name");
		$output .= "<input type=\"hidden\" id=\"$id\" name=\"{$name}\" value=\"{$value}\"/ >";
	}
	// standard hidden values for all forms:
	$output .="<input type=\"hidden\" name=\"q\" value=\"{$qq['request']}\" />";
	$output .="<input type=\"hidden\" name=\"r\" value=\"{$this->aushort}\" />";
	$output .="<input type=\"hidden\" name=\"s\" value=\"{$state}\" />";
	$output .="<input type=\"hidden\" name=\"t\" value=\"{$transactiondata}\" />";
	if (!$qq['production'])
		$output .="<input type=\"hidden\" name=\"u\" value=\"{$qq['project']}\" />";
	$output .= "</form>";
	return $output;
}

public function getOutputFieldsetEnd($tag) {
	if (!isset($this->fieldset[$tag]))
		throw new exception("Trying to output undefined fieldset {$tag} in form {$this->tag}");
	return ($this->fieldset[$tag][1] != '') ? '</fieldset>' : '' ;
}

public function getFieldsetFields($tag) {
	if (!isset($this->fieldset[$tag]))
		throw new exception("Trying to output undefined fieldset {$tag} in form {$this->tag}");
	return $this->fieldset[$tag][2];	
}

public function getFieldOutput($tag,$bLabel,$bField,$separator='<br />') {
	if (!isset($this->c[$tag]))
		throw new exception("Attempting to output unknown field '{$tag}' from form {$this->longname}");
	$retval="";
	$field=$this->c[$tag];
	if ($bLabel) {
		$retval=$field->outputlabel();
		if ($retval != '')
			$retval .= $separator;	
	}	
	if ($bField) {
		if ('' != ($output=$field->outputError()))
			$retval .= $output.$separator;
		$retval .= $field->output();
	}	
	return $retval;	
}

public function getDumpstyleFieldsetOutput($fieldset,$separator="<br />",$ctrlsep="<br />") {
	$controls=$this->getFieldsetFields($fieldset);
	$output=$this->getOutputFieldsetStart($fieldset);
	$lastwasbutton=False;
	$currentsep='';
	foreach ($controls as $cname) {
		$isbutton=(get_class($this->getControl($cname)) == 'll_button');
		// attempt to do the tiniest amount of beautification--but adjacent buttons on a row together
		if ($isbutton && $lastwasbutton)
			$currentsep='&nbsp;&nbsp;&nbsp;';
		$output .= $currentsep.$this->getFieldOutput($cname,True,True,$ctrlsep);
		$currentsep=$separator;
		$lastwasbutton=$isbutton;
	}
	$output .= $this->getOutputFieldsetEnd($fieldset);
	
	return $output;
}

public function getTablestyleFieldsetOutput($fieldset) {
	$controls=$this->getFieldsetFields($fieldset);
	$output=$this->getOutputFieldsetStart($fieldset);
	$currentsep='';
	$buttons='';
	$output .= '<table>';
	foreach ($controls as $cname) {
		$field=$this->c[$cname];
		$isbutton=(get_class($field) == 'll_button');
		if ($isbutton) {
			$buttons .= $currentsep.$field->output();
			$currentsep='&nbsp;&nbsp;&nbsp;';
		} else {
			$labeltext=$field->outputlabel();
			if ($labeltext == '')
				$labeltext='&nbsp';
			$errortext=$field->outputError();
			if ($errortext != '')
				$errortext .= '<br>';
			$text=$field->output();
			$output .= "<tr><th>$labeltext</th><td>$errortext$text</td></tr>";
		}	
	}
	if ($buttons != '')
		$output .= "<tr colspan=\"2\">$buttons</tr>";
	$output .= '</table>';
	$output .= $this->getOutputFieldsetEnd($fieldset);
	
	return $output;
}

public function getFieldsets() {
	$retval=array();
	foreach ($this->fieldset as $fieldsetname=>$fs)
		$retval[]=$fieldsetname;
	return $retval;	
}

public function getDumpStyleFormOutput($state='',$transactiondata=0,$separator="<br />") {
	$output=$this->getOutputFormStart();
	$fieldsetnames=$this->getFieldsets();
	foreach ($fieldsetnames as $fsname) {
		$output .= $this->getDumpStyleFieldsetOutput($fsname,$separator);
	}
	$output .= $this->getOutputFormEnd($state,$transactiondata);
	return $output;
}

public function getTableStyleFormOutput($state='',$transactiondata=0) {
	$output=$this->getOutputFormStart();
	$fieldsetnames=$this->getFieldsets();
	foreach ($fieldsetnames as $fsname) {
		$output .= $this->getTableStyleFieldsetOutput($fsname);
	}
	$output .= $this->getOutputFormEnd($state,$transactiondata);
	return $output;
}

public function getAnnotatedOutput($errors=array(),$errorclass='',$state='',$transactiondata=0) {
	$output=$this->getOutputFormStart();
	$fieldsetnames=$this->getFieldsets();
	foreach ($fieldsetnames as $fsname) {
		$controls=$this->getFieldsetFields($fsname);
		$output .= $this->getOutputFieldsetStart($fsname);
		$lastwasbutton=False;
		$currentsep='';
		foreach ($controls as $cname) {
			$isbutton=(get_class($this->getControl($cname)) == 'll_button');
			// attempt to do the tiniest amount of beautification--but adjacent buttons on a row together
			if ($isbutton && $lastwasbutton)
				$currentsep='&nbsp;&nbsp;&nbsp;';
			if ('' !== ($label=$this->c[$cname]->outputlabel())) {
				$output .= $currentsep.$label;
				$currentsep='<br />';
			}	
			if (isset($errors['*'.$cname])) {
				$output .= "$currentsep<span$errorclass>{$errors['*'.$cname]}</span>";
				$currentsep='<br />';
			}	
			$output .= $currentsep.$this->c[$cname]->output();
			$currentsep='<br />';
			$lastwasbutton=$isbutton;
		}
		$output .= $this->getOutputFieldsetEnd($fsname);
	}
	$output .= $this->getOutputFormEnd($state,$transactiondata);
	return $output;
}


public function getFormattedOutput($format,$bFullForm=True,$state='',$transactiondata=0) {
	$output=$bFullForm ? $this->getOutputFormStart() : '';
	while ($format != '') {
		$i=strpos($format,'<<');
		if ($i !== False) {
			$output .= substr($format,0,$i);
			$format=substr($format,$i+2);
			$i=strpos($format,'>>');
			if ($i === False)
				throw new exception("error in form format-- unmatched double angle bracket");
			$tag=substr($format,0,$i);
			$format=substr($format,$i+2);
			if ($tag[0] == '!') {
				$tag=substr($tag,1);
				$output .= $this->getControl($tag)->outputLabel();
			} else if ($tag[0] == '?') {
				$tag=substr($tag,1);
				$output .= $this->getControl($tag)->outputError();
			} else {
				$output .= $this->getControl($tag)->output();
			}
		} else {
			$output .= $format;
			$format='';
		}
	}
	if ($bFullForm)
		$output .= $this->getOutputFormEnd($state,$transactiondata);
	return $output;
}


} /////// END OF FORM CLASS DEFINITION /////////////////////////////////////////


/////////// field class is the base for all the other controls /////////////////
class ll_field {
//////////////////////////////////////////////////////////////////////////////// 
protected $tag;			// common-sense name for the field -- NOT the full unique name, just the "tag" name
protected $longname="";	// id for this control
protected $label;		// label for the field
protected $error;		// error message for this field
protected $extraclass="";	// extra class for formatting
protected $extraclasslabel="";
protected $extraclasserror="";
protected $html;		// html for formatting
protected $usage;		// usage for formatting
protected $message;
function __construct($form,$tag,$label,$html,$usage) {
	$this->longname="{$form->longname()}_{$tag}";
	$this->html=$html;
	$this->usage=$usage;
	$this->tag=$tag;
	$this->label=$label;
	$this->error='';
}

public function declareHTML($idstore,$bLock) {
	$idstore->declareHTMLlong($this->longname);
	if ($bLock)
		$idstore->lockHTMLid($this->longname);
	if (isset($this->label) && $this->label != "") {
		$idstore->declareHTMLlong($this->longname."_label");
		if ($bLock)
			$idstore->lockHTMLid($this->longname."_label");
	}	
	$idstore->declareHTMLlong($this->longname."_error");
	if ($bLock)
		$idstore->lockHTMLid($this->longname."_error");
}

public function declareStyles($stylegen,$parent) {
	$stylegen->registerStyledId($this->longname,$this->html,$this->usage,$parent);
	if (isset($this->label) && $this->label != "")
		$stylegen->registerStyledId($this->longname."_label","label",$this->usage,$parent);
	$stylegen->registerStyledId($this->longname."_error","span",$this->usage,$parent);
}	

public function tag() {return $this->tag;}
public function longname() {return $this->longname;}
public function hasValue() {return False;}
public function label() {return $this->label;}
public function error() {return $this->error;}
public function setError($string) {$this->error=$string;}
public function isMultiPart() {return False;}
public function setExtraClass($stylegen,$extraclass) {
	$stylegen->registerClass($extraclass,$this->html);
	$this->extraclass=$extraclass;
}
public function setExtraClassLabel($stylegen,$extraclass) {
	$stylegen->registerClass($extraclass,"label");
	$this->extraclasslabel=$extraclass;
}
public function setExtraClassError($stylegen,$extraclass) {
	$stylegen->registerClass($extraclass,"span");
	$this->extraclasslabel=$extraclass;
}

// returns strings--does not echo
public function outputLabel() {
	global $qqi;
	$output="";
	if (isset($this->label) && $this->label != "") {
		$qqi->lockHTMLid($this->longname);
		$output="<label{$qqi->idcstr($this->longname."_label",$this->extraclasslabel)} for=\"{$qqi->htmlShortFromLong($this->longname)}\">$this->label</label>";		
	}
	return $output;
}
// returns strings--does not echo
public function outputError() {
	global $qqi;
	$output="";
	if (isset($this->error) && $this->error != "") {
		$qqi->lockHTMLid($this->longname);
		$output="<span{$qqi->idcstr($this->longname."_error",$this->extraclasserror)}>$this->error</span>";		
	}
	return $output;
}
public function output() {return "";}


} /////////// END OF FIELD DEFINITION ////////////////////////////////////////


/////// BUTTON /////////////////////////////////////////////////////////////////
class ll_button extends ll_field {
protected $buttontype;
protected $title;
function __construct($form,$tag,$title="",$type="submit",$usage="button") {
	if ($title == "")
		$title=$tag;	// default is to have the same name
	$this->title=$title;	
	if (!in_array($type, array("submit","button","reset")))
		throw new Exception("Illegal type for button: $type",891);	
	$this->buttonType=$type;	
	parent::__construct($form,$tag,$title,"input",$usage);
	unset ($this->label);
	unset ($this->error);
}

public function getTitle() {return $this->title;}

public function output() {
	global $qqi;
	return "<input{$qqi->idcstr($this->longname,$this->extraclass)} type=\"{$this->buttonType}\" name=\"{$this->tag}\" value=\"{$this->title}\" />"; 
}

} //////// END OF BUTTON DEFINITION //////////////////////////////////////////// 


////////// CHECKBOX CONTROL ////////////////////////////////////////////////////
class ll_checkbox extends ll_field {
////////////////////////////////////////////////////////////////////////////////
private $value;		// Boolean--True or False

function __construct($form,$tag,$label,$usage="checkbox") {
	parent::__construct($form,$tag,$label,"input",$usage);
	$this->value=False;	// master default prevents complaints
}

public function hasValue() {return True;}
public function setValue($value) {$this->value=$value;}
public function getValue() {return $this->value;}
// no output for labels--they are built into control
public function outputlabel() {return "";}
public function output() {
	global $qqi;
	// label output for a checkbox is so standardized that it is just part of the control
	// we don't need to have a persistent id, because the label structure is integrated with the control output
	$checked=$this->value ? " checked" : "";
	return "<label{$qqi->idcstr($this->longname.'_label',$this->extraclass)}><input{$qqi->idcstr($this->longname,$this->extraclass)} type=\"checkbox\" name=\"{$this->tag}\" value=\"1\"$checked />{$this->label}</label>";
}

} ///// END OF CHECKBOX ////////////////////////////////////////////////////////


//////////// DROPDOWN CONTROL //////////////////////////////////////////////////
class ll_dropdown extends ll_field {
////////////////////////////////////////////////////////////////////////////////
public $value;		// one of the $values array
protected $display;	// array of display text indexed by values
public $bDispVal=True;

function __construct($form,$tag,$label="",$usage="dropdown") {
	parent::__construct($form,$tag,$label,"select",$usage);
	$this->display=array();
}
public function hasValue() {return True;}
public function setValue($value) {$this->value=$value;}
public function getValue() {return $this->value;}

public function getDisplayValue($value) {return (isset($this->display[$value])) ? $this->display[$value]: "";}

public function addOption($value,$display) {
	$this->display[(string)$value]=$display;
}

public function setOptionArray($values) {
	foreach ($values as $key=>$value) {
		$this->display[$key]=$value;
	}
}

public function setOptionArrayDual($values,$displays) {
	for ($i=0; $i<sizeof($values); ++$i) {
		$this->display[$values[$i]]=$displays[$i];
	}
}

public function setOptionDisplayVal($values) {
	$this->display=$values;
	$this->bDispVal=False;
}

public function output() {
	global $qqi;
	
	$retval="<select{$qqi->idcstr($this->longname,$this->extraclass)} name=\"{$this->tag}\">";
	foreach($this->display as $value=>$display) {
		$selected=($this->value===$value || (!$this->bDispVal && $this->value === $display)) ? " selected=\"selected\"" : "";
		$valstring= $this->bDispVal ? " value=\"$value\"" : '';
		$retval .= "<option$valstring$selected>$display</option>";
	}
	$retval .= "</select>";
	return $retval;
}

} ////////// END OF DROPDOWN ///////////////////////////////////////////////////


//////////// EDIT CONTROL //////////////////////////////////////////////////////
class ll_edit extends ll_field {
////////////////////////////////////////////////////////////////////////////////
public $value;		// Text
private $bPassword;
private $width;
private $maxChars;

function __construct($form,$tag,$width=10,$maxChars=10,$label="",$bPassword=False,$usage="edit") {
	parent::__construct($form,$tag,$label,"input",$usage);
	$this->width=$width;
	$this->maxChars=$maxChars;
	$this->bPassword=$bPassword;
	$this->value="";
}

public function hasValue() {return True;}
public function setValue($value) {$this->value=$value;}
public function getValue() {return $this->value;}
public function output() {
	global $qqi;
	
	$type=($this->bPassword) ? "password" : "text";
	return "<input type=\"$type\"{$qqi->idcstr($this->longname,$this->extraclass)} name=\"{$this->tag}\" value=\"{$this->value}\" size=\"{$this->width}\" maxlength=\"{$this->maxChars}\" />";
}

} ////////// END OF EDIT ///////////////////////////////////////////////////////


//////////// FILE CONTROL //////////////////////////////////////////////////////
class ll_file extends ll_field {
////////////////////////////////////////////////////////////////////////////////
public $value;		// Text
private $maxSize;

function __construct($form,$tag,$maxsize,$label="",$usage="file") {
	parent::__construct($form,$tag,$label,"input",$usage);
	$this->maxsize=$maxsize;
}

public function isMultiPart() {return True;}
public function hasValue() {return True;}

// no "setValue" function.  This one is read-only

public function getValue() {
	global $qqi;
	// the return value is True if there's a valid file and false otherwise
	return (isset($_FILES[$this->tag]) && $_FILES[$this->tag]['size'] > 0 && $_FILES[$this->tag]['error'] == 0);
}

// gets the temporary name of the uploaded file
public function getTempName($exceptionThreshold=1) {
	if (!isset($_FILES[$this->tag])) {
		if ($exceptionThreshold == 0)
			throw new exception("uploaded file not available");
		return False;	
	}	
	$retval=$_FILES[$this->tag]['tmp_name'];
	return (is_string($retval) && strlen($retval) > 0) ? $retval : False;	
}

// gets the original (client) name of the uploaded file
public function getUploadedName($exceptionThreshold=0) {
	if (!isset($_FILES[$this->tag])) {
		if ($exceptionThreshold == 0)
			throw new exception("uploaded file not available");
		return False;	
	}	
	return $_FILES[$this->tag]['name'];	
}

// moves temporary file to a new place.  if newname not specified, uses user's file name.  error control
// can either be by exception or by return value, depending on specified threshhold.
public function moveFile($newdir,$newname="",$exceptionThreshold=0) {
	
	if (!isset($_FILES[$this->tag])) {
		if ($exceptionThreshold > 1)
			return 2;
		else
			throw new Exception('Expected uploaded file info not available.');	
	}
		
	$file=($_FILES[$this->tag]);
	if ($newname == "")
		$newname=$file['name'];
	$fe=new exception_fileupload($_FILES[$this->tag]);
	$errLevel=$fe->errorLevel();
	if ($errLevel > $exceptionThreshold)
		throw $fe;
	if ($errLevel == 0)	{
		move_uploaded_file($file['tmp_name'], $newdir.$newname);
	}
	return $errLevel;
}

public function readFile($bLines=False,$exceptionThreshold=0) {
	if (!isset($_FILES[$this->tag])) {
		if ($exceptionThreshold > 1)
			return 2;
		else
			throw new Exception('Expected uploaded file info not available.');	
	}
	$file=($_FILES[$this->tag]);
	$fe=new exception_fileupload($_FILES[$this->tag]);
	$errLevel=$fe->errorLevel();
	if ($errLevel > $exceptionThreshold)
		throw $fe;
	$retval=$errLevel;	
	if ($errLevel == 0)	{
		if ($bLines)
			$retval=file($file['tmp_name']);
		else
			$retval=file_get_contents($file['tmp_name']);	
	}
	return $retval;
}

public function output () {
	global $qqi;
	
	return "<input type=\"hidden\" name=\"max_file_size\" value=\"{$this->maxSize}\" /><input type=\"file\"{$qqi->idcstr($this->longname,$this->extraclass)} name=\"{$this->tag}\" />";
}

} ////////// END OF FILE ///////////////////////////////////////////////////////


//////////// LISTBOX CONTROL ///////////////////////////////////////////////////
class ll_listbox extends ll_field {
////////////////////////////////////////////////////////////////////////////////
public $values;		// array of selected items (indices)
protected $display;	// array of display text indexed by values
protected $nrows;

function __construct($form,$tag,$label,$nrows=5,$usage="listbox") {
	parent::__construct($form,$tag,$label,"select",$usage);
	$this->values=array();
	$this->display=array();
	$this->nrows=$nrows;
}

public function hasValue() {return True;}

// this function is designed to respond to php-style representation--a list of values set, by index
public function setValue($values) {
	foreach ($this->values as $ix=>$val)
		$this->values[$ix]=False;
	foreach ($values as $ix) {
		$this->values[$ix]=True;
	}	
}

public function getValue() {return $this->values;}

public function addOption($value,$display,$bSelected=False) {
	$this->display[$value]=$display;
	$this->values[$value]=$bSelected;
}

// takes an array of value=>display.  all will be selected the same.
public function addOptions($options,$bSelected=False) {
	foreach ($options as $value=>$display) {
		$this->display[$value]=$display;
		$this->values[$value]=$bSelected;
	}	
}

public function setOptionSel($value,$bSelected) {
	$this->values[$values]=$bSelected;
}

public function setOptionArray($values,$valuecol,$displaycol) {
	foreach ($values as $e) {
		$this->display[$e[$valuecol]]=$e[$displaycol];
	}
}

public function setOptionArrayDual($values,$displays) {
	for ($i=0; $i<sizeof($values); ++$i) {
		$this->display[$values[$i]]=$displays[$i];
	}
}

public function output() {
	global $qqi;
	$size=$this->nrows != 0 ? " size=\"{$this->nrows}\"" : '';
	$retval="<select{$qqi->idcstr($this->longname,$this->extraclass)} name=\"{$this->tag}[]\" multiple$size>";
	foreach($this->display as $value=>$display) {
		$selected=(isset($this->values[$value]) && $this->values[$value]) ? " selected=\"selected\"" : "";
		$retval .= "<option value=\"{$value}\"$selected>$display</option>";
	}
	$retval .= "</select>";
	return $retval;
}

} ////////// END OF LISTBOX ////////////////////////////////////////////////////


//////////// RADIOGROUP CONTROL ////////////////////////////////////////////////
class ll_radiogroup extends ll_field {
////////////////////////////////////////////////////////////////////////////////
public $value;		// one of the $values array
protected $display;	// array of display text indexed by values
protected $radioseparator;	// separator between the radio controls

function __construct($form,$tag,$usage="radio",$separator="<br />") {
	parent::__construct($form,$tag,"","input",$usage);
	$this->separator=$separator;
	$this->value=Null;
}

public function hasValue() {return True;}

public function setValue($value) {$this->value=$value;}

public function getValue() {return $this->value;}

public function addOption($value,$display) {
	$this->display[$value]=$display;
}

public function setOptionArray($values,$valuecol,$displaycol) {
	foreach ($values as $e) {
		$this->display[$e[$valuecol]]=$e[$displaycol];
	}
}

public function setOptionArrayDual($values,$displays) {
	for ($i=0; $i<sizeof($values); ++$i) {
		$this->display[$values[$i]]=$displays[$i];
	}
}

// this one "control" is actually treated as a group of fields surrounded by a div.
// it is the div that has the id and the formatting
public function output () {
	global $qqi;
	$retval="";
	$separator="";	
	foreach($this->display as $value=>$display) {
		$selected=($this->value==$value) ? ' checked="checked"' : '';
		$retval .= "$separator<label{$qqi->cstr($this->extraclass)}><input{$qqi->cstr($this->extraclass)} type=\"radio\" name=\"{$this->tag}\" value=\"{$value}\"$selected />$display</label>";
		$separator=$this->separator;
	}
	return $retval;
}

} ////////// END OF RADIOGROUP /////////////////////////////////////////////////


//////////// RADIOITEM CONTROL /////////////////////////////////////////////////
class ll_radioitem extends ll_field {
////////////////////////////////////////////////////////////////////////////////
// this one is implemented
// for those radio buttons which must be formatted separately.  each radio item will have a value or True or False
// there is no functional protection against all individual radio buttons in a group being set on output--it is up to the higher level code to ensure this.
public $value;
protected $group;	// group name

function __construct($form,$tag,$group,$label="",$usage="radio") {
	parent::__construct($form,$tag,$label,"input",$usage);
	$this->group=$group;
	$this->label=$label;
	$this->value=False;
}
public function hasValue() {return True;}
public function setValue($value) {$this->value=$value;}
public function getValue() {return $this->value;}
public function getGroup() {return $this->group;}
// no label output for radios--they are built into control
public function outputlabel() {return "";}
public function output() {
	global $qqi;
	
	// label output for a checkbox is so standardized that it is just part of the control
	// we don't need to have a persistent id, because the label structure is integrated with the control output
	$checked=$this->value ? ' checked="checked"' : '';

	return "<label><input{$qqi->idcstr($this->longname,$this->extraclass)} type=\"radio\" name=\"{$this->group}\" value=\"{$this->tag}\"$checked />{$this->label}</label>";
}

} ////////// END OF RADIOITEM //////////////////////////////////////////////////


//////////// STATIC CONTROL //////////////////////////////////////////////////////
class ll_static extends ll_field {
////////////////////////////////////////////////////////////////////////////////
public $value;		// Text (display only) -- this is 'write only'.  useless for traditional forms, but OK for js forms
public $html;		// html keyword for output (default is p)

function __construct($form,$tag,$label="",$usage="static",$html='p') {
	parent::__construct($form,$tag,$label,$html,$usage);
	$this->value="";
	$this->html=$html;
}

public function hasValue() {return True;}
public function setValue($value) {$this->value=$value;}
public function getValue() {return $this->value;}
public function output() {
	global $qqi;
	
	return "<{$this->html}{$qqi->idcstr($this->longname,$this->extraclass)}>{$this->value}</{$this->html}>";
}

} ////////// END OF STATIC /////////////////////////////////////////////////////


//////////// TEXTAREA CONTROL //////////////////////////////////////////////////
class ll_textarea extends ll_field {
////////////////////////////////////////////////////////////////////////////////
public $value;		// Text
private $width;
private $height;

function __construct($form,$tag,$width=60,$height=5,$label="",$usage="edit") {
	parent::__construct($form,$tag,$label,"textarea",$usage);
	$this->label=$label;
	$this->width=$width;
	$this->height=$height;
	$this->value="";
}

public function hasValue() {return True;}

public function setValue($value) {$this->value=$value;}

public function getValue() {return $this->value;}

public function isMultiPart() {return True;}

public function output() {
	global $qqi;
	return "<textarea{$qqi->idcstr($this->longname,$this->extraclass)} name=\"{$this->tag}\", rows=\"{$this->height}\", cols=\"{$this->width}\">{$this->value}</textarea>";
}	

} ////////// END OF TEXTAREA ///////////////////////////////////////////////////

// end of file:
?>
