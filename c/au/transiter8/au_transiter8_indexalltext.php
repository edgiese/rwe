<?php if (FILEGEN != 1) die;
// transaction iterator class tester and base
class au_transiter8_indexalltext extends au_transiter8_base {
////////////////////////////////////////////////////////////////////////////////

// returns a form object
public function makeform($au) {
	$form=new llform($au,'form');
	$form->addFieldset('d','');
	$form->addControl('d',new ll_button($form,'iterate','Re-Index'));
	return $form;
}

// returns data block, reading form
public function initIterator($au) {
	global $qqc;
	// this is for hygiene, and since there is a but in PHP's DBO object, skip it:
	$qqc->act("search::deleteAllSrchIndex");
	$qqc->act("search::deleteAllKeywords");
	
	return array('ids'=>$qqc->getCols("text::getAllTextIds",-1),'words'=>0);
}

// returns status text 'iterating', 'finished', or 'error'
public function getStatus($iter8data) {
	return (sizeof($iter8data['ids']) > 0) ? 'iterating' : 'finished';
}

// returns text for the status line
public function getStatusLine($iter8data) {
	$id=current($iter8data['ids']);
	$size=sizeof($iter8data['ids']);
	return ("ids left to process: $size (on id: $id)");
}

// returns text for the next log line
public function getLogLine($iter8data) {
	$id=current($iter8data['ids']);
	return ("finished processing text through id {$id} (word count: {$iter8data['words']})");
}

// does the next iteration, updating data block and whatever else needs doing.  returns updated data block
public function iterate($iter8data) {
	$starttime=time();
	while (time()-$starttime < 3) {
		$iter8data['id']=array_shift($iter8data['ids']);
		$id=$iter8data['id'];
		if (is_numeric($id) && $id != 0)
			$iter8data['words'] += utilrare1::searchIndexText($iter8data['id']);
		if (sizeof($iter8data['ids']) == 0)
			break;
	}	
	return $iter8data;
}

public function outputForm($au,$state,$transaction) {
	$form=$this->makeform($au);
	echo $form->getDumpStyleFormOutput($state,$transactiondata);
}

/////////////////////////////////////////////////////////////////// end of class
} ?>
