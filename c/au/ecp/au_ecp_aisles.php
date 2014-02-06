<?php if (FILEGEN != 1) die;
///// Access Unit definition file for 'walk the aisles' dialog box
class au_ecp_aisles extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $instance;

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	$this->instance=$initdata;
}

public function declareids($idstore,$state) {
	// containing div:
	$idstore->declareHTMLid($this,$this->bAutoLock);
	$idstore->declareHTMLid($this,$this->bAutoLock,'cancel');
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div','dialog',$this->getParent()->htmlContainerId());
}

public function declarestaticjs($js) {
	global $qq,$qqp,$qqi;
	
	$init=$qqp->getPMod($this->instance);
	
	$args=$js->seedArgs($this);

	$aisle=array(
		'image'=>$qqi->resourceFileURL($init->getValue('base')),
		'cleft'=>$init->getValue('cleft'),
		'ctop'=>$init->getValue('ctop'),
		'cwidth'=>$init->getValue('cwidth'),
		'cheight'=>$init->getValue('cheight')
	);
	$js->addjs('$','ecp::aislesetup',array_merge($args,$aisle));
	
	$buttons=$init->getValue('buttons');
	$overlay=$qqi->resourceFileURL($init->getValue('overlay'));
	foreach ($buttons as $bd) {
		$buttondata=array(
			'overlay'=>$overlay,
			'key'=>$bd['key'],
			'left'=>$bd['left'],
			'top'=>$bd['top'],
			'width'=>$bd['width'],
			'height'=>$bd['height'],
			'image'=>$qqi->resourceFileURL($bd['image']), 
			'ileft'=>$bd['ileft'],
			'itop'=>$bd['itop'],
			'iwidth'=>$bd['iwidth'],
			'iheight'=>$bd['iheight']
		);
		$js->addjs('$','ecp::aislebuttonsetup',array_merge($args,$buttondata));
	}	
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qq,$qqs,$qqi;

	echo "<div{$qqi->idcstr($this->longname)}></div>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
