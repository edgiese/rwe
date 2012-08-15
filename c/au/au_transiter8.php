<?php if (FILEGEN != 1) die;
///// Access Unit definition file for transaction iterator
class au_transiter8 extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $state;
private $data;
private $iterator;								// name of iterator object

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	$initdata=parent::getInit($initdata,'iterator');
	
	$this->iterator='au_transiter8_'.$initdata['iterator'];

	$this->state=$state;
	$this->transaction=(int)$data;
}

const STATE_NONE = '';
const STATE_PROCESSING = 'processing';


public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$idstore->declareHTMLid($this,True,'status');
	$idstore->declareHTMLid($this,True,'log');
	$idstore->declareHTMLid($this,True,'abort');
	$iterator=new $this->iterator;
	$iterator->makeform($this)->declareids($idstore);
		
	$idstore->registerAuthBool('iterate_'.$this->longname,"Iterate function {$this->iterator}",False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,"div:p,a","iterator",$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId($this->longname.'_status',"p","iterator",$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId($this->longname.'_log',"p","iterator",$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId($this->longname.'_abort',"input","iterator",$this->getParent()->htmlContainerId());
	
	$iterator=new $this->iterator;
	$iterator->makeform($this)->declarestyles($stylegen,$this->longname);
}

public function processVars($originUri) {
	global $qq,$qqs;
	
	// any transaction-based forms need to be stated
	if (!$qqs->isStated())
		return message::redirectString(message::MISSINGCOOKIE);

	// iterator transactions cannot be re-entered.	
	if ($this->transaction != 0)
		return message::redirectString(message::TRANSACTIONFINISHED);

	$transaction=$qqs->beginTransaction($originUri);
	$iterator=new $this->iterator;
	
	$iter8data=$iterator->initIterator($this);
	$qqs->setTransactionData($transaction,"iter8data",$iter8data);
	return llform::redirectString(self::STATE_PROCESSING,$transaction);
}

public function declarestaticjs($js) {
	global $qq;
	$args=$js->seedArgs($this);
	$js->addjs('$','transiter8::iter8setup',$args);
	$args['***']=0;	// turn off parameter existence checking for scan
	$js->scanjsdependencies('transiter8::iter8start',$args);
}

public function initialize($pgen) {
	global $qqs,$qqj;
	
	if ($this->state == self::STATE_PROCESSING) {
		$transaction=$this->transaction;
		if ($transaction == 0)
			throw new exception("uninitialized transaction {$transaction}");
		$iter8data=$qqs->getTransactionData($transaction,"iter8data");	
		$iterator=new $this->iterator;
		// add dynamic js to run when page is done loading
		$args=$qqj->seedArgs($this);
		$args['transaction']=$this->transaction;
		$args['initialstatus']=$iterator->getStatusLine($iter8data);
		$args['log']=$iterator->getLogLine($iter8data);
		$qqj->addjs('$','transiter8::iter8start',$args);
	}	
}

// called when an ajax call occurs.  output is javascript to be executed upon return
public function processAjax($js,$originUri,$page) {
	global $qqs,$qqi;
	
	$transaction=$this->transaction;
	if ($transaction == 0)
		throw new exception("uninitialized transaction {$transaction}");
	if (!$qqs->checkAuth('iterate_'.$this->longname)) {
		echo "alert('Unauthorized Action');";
		return;
	}
	$iter8data=$qqs->getTransactionData($transaction,"iter8data");	
	$iterator=new $this->iterator;
	
	$iter8data=$iterator->iterate($iter8data);
	$statusline=$iterator->getStatusLine($iter8data);
	$logline=$iterator->getLogLine($iter8data);
	$status=$iterator->getStatus($iter8data);
	$qqs->setTransactionData($transaction,"iter8data",$iter8data);	
	echo "fg.iter8.setStatusDisplay('$statusline');";	
	echo "fg.iter8.addLogLine('$logline');";
	echo "fg.iter8.status='$status';";	
}


// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	
	$transaction=$this->transaction;
	echo "<div{$qqi->idcstr($this->longname)}>";	
	$iterator=new $this->iterator;
	
	if ($this->state == self::STATE_PROCESSING) {
		if ($transaction == 0)
			throw new exception("transaction must be initialized to do processing");
		// output elements that will be updated by ajax:
		echo "<input{$qqi->idcstr($this->longname.'_abort')} type=\"button\" value=\"abort\" onclick=\"fg.iter8.localcancel=true;\" />";
		echo "<p{$qqi->idcstr($this->longname.'_status')}></p>";
		echo "<p{$qqi->idcstr($this->longname.'_log')}></p>";
		
	} else {
		if ($transaction != 0)
			throw new exception("logic error -- initialized transaction in base state");
		$iterator->outputForm($this,self::STATE_PROCESSING,$transaction);
	}
	echo '</div>';
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
