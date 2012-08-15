<?php if (FILEGEN != 1) die;
// transaction iterator class tester and base
class au_transiter8_base {
////////////////////////////////////////////////////////////////////////////////

// returns a form object
public function makeform($au) {
	$form=new llform($au,'form');
	$form->addFieldset('d','');
	$form->addControl('d',new ll_edit($form,'numtrans',4,4,'Number of Transactions'));
	$form->addControl('d',new ll_button($form,'iterate','Iterate'));
	return $form;
}

// returns data block, reading form
public function initIterator($au) {
	$form=$this->makeform($au);
	$vals=$form->getValueArray();
	return array('i'=>0, 'n'=>$vals['numtrans']);
}

// returns status text 'iterating', 'finished', or 'error'
public function getStatus($iter8data) {
	return ($iter8data['i'] < $iter8data['n']) ? 'iterating' : 'finished';
}

// returns text for the status line
public function getStatusLine($iter8data) {
	return ("processing #{$iter8data['i']} of {$iter8data['n']}");
}

// returns text for the next log line
public function getLogLine($iter8data) {
	$i=$iter8data['i']-1;
	return ("finished processing #{$i}");
}

// does the next iteration, updating data block and whatever else needs doing.  returns updated data block
public function iterate($iter8data) {
	++$iter8data['i'];
	return $iter8data;
}

public function outputForm($au,$state,$transaction) {
	$form=$this->makeform($au);
	echo $form->getDumpStyleFormOutput($state,$transactiondata);
}

/////////////////////////////////////////////////////////////////// end of class
} ?>
