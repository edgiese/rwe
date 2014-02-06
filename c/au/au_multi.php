<?php if (FILEGEN != 1) die;
///// Access Unit definition file for multi-divs (layered/alternating)
class au_multi extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $options;  // pmod


function __construct($tag,$parent,$initdata) {
	parent::__construct($tag,$parent,$initdata);
	if (isset($initdata) && $initdata != '')
		$this->options=$initdata;
	else
		$this->options=$tag;	
}

public function declareids($idstore,$state) {
	global $qqp;
	
	$options=$qqp->getPMod($this->options);
	$triggers=$options->getTriggerNames();
	foreach ($triggers as $triggername=>$recipe)
		$idstore->declareHTMLid($this,True,'trigger_'.$triggername);
}

public function declarestyles($stylegen,$state) {
	global $qqp;
	
	$options=$qqp->getPMod($this->options);
	$triggers=$options->getTriggerNames();
	foreach ($triggers as $triggername=>$recipe)
		$stylegen->registerStyledId($this->longname.'_trigger_'.$triggername,'div','zone',$this->getParent()->htmlContainerId());

}

public function declarestaticjs($js) {
	global $qqp,$qqi;

	$options=$qqp->getPMod($this->options);
	$args=$js->seedArgs($this);

	// add triggers
	$triggers=$options->getTriggerNames();
	foreach ($triggers as $triggername=>$recipe) {
		$triggername=$qqi->htmlShortFromLong($this->longname.'_trigger_'.$triggername);
		$js->addjs('$','multi::addtrigger',array_merge($args,array('recipe'=>(string)$recipe,'triggername'=>$triggername)));
	}
	
	// position triggers.  note that this must be done before hiding overlays, since javascript positioning does not work once overlays are hidden
	$mimics=$options->getTriggerPositionSel();
	foreach ($mimics as $triggername=>$mimicsel) {
		$triggersel='#'.$qqi->htmlShortFromLong($this->longname.'_trigger_'.$triggername);
		$js->addjs('$','multi::movetrigger',array_merge($args,array('triggersel'=>$triggersel,'mimicsel'=>$mimicsel)));
	}

	$ovlydef='';
	$overlays=$options->getOverlays();
	foreach ($overlays as $ix=>$ovly)
		$ovlydef .= "fg.multi_{$this->shortname}.ovlys[{$ix}]='$ovly';";
	$recipedef='';	
	$recipes=$options->getRecipes();
	foreach ($recipes as $ix=>$recipe) {
		$relems=$delim='';
		foreach ($recipe as $ri) {
			$relems .= "{$delim}new Array({$ri[0]},{$ri[1]},{$ri[2]})";
			$delim=',';
		}	
		$recipedef .= "fg.multi_{$this->shortname}.recipes[{$ix}]=new Array($relems);";
	}	
	$js->addjs('$','multi::setup',array_merge($args,array('ovlydef'=>$ovlydef,'recipedef'=>$recipedef)));

}

public function output($pgen,$brotherid) {
	global $qqi, $qqs, $qqp;
	
	$options=$qqp->getPMod($this->options);
	$triggers=$options->getTriggerNames();
	if ($qqs->answerBrowserQuestion(state::QUESTION_TRANSPARENTBYOPACITY)) {
		$transparent=' style="background-color:#fff;filter:alpha(opacity=0)"';
	} else
		$transparent='';
	foreach ($triggers as $triggername=>$recipe) {
		echo "<div{$qqi->idcstr($this->longname.'_trigger_'.$triggername)}$transparent></div>";
	}
		
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
