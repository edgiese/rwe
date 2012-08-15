<?php if (FILEGEN != 1) die;
// transaction iterator class tester and base
class au_transiter8_pchblookupupc extends au_transiter8_base {
////////////////////////////////////////////////////////////////////////////////

// returns a form object
public function makeform($au) {
	$form=new llform($au,'form');
	$form->addFieldset('d','UPC Lookup');
	$form->addControl('d',new ll_edit($form,'lastupc',25,25,'Starting UPC or blank for first'));
	$form->addControl('d',new ll_edit($form,'count',4,4,'Count'));
	$form->addControl('d',new ll_button($form,'iterate','Iterate'));
	return $form;
}

// returns data block, reading form
public function initIterator($au) {
	global $qqc;
	
	$data=array();
	
	$form=$this->makeform($au);
	$vals=$form->getValueArray();
	$row=$qqc->getRows('mod/proj/pchbupc::nextupc',1,$vals['lastupc']);
	$data['lastupc']=$vals['lastupc'];
	if ($row === False)
		$data['status']='finished';
	else {
		$data['status']='iterating';
		$data['upc']=$row['barcode'];
	}
	$data['i']=0;
	$data['maxcount']=$vals['count'];		
	return $data;
}

// returns status text 'iterating', 'finished', or 'error'
public function getStatus($data) {
	return $data['status'];
}

// returns text for the status line
public function getStatusLine($data) {
	if (!isset($data['upc']))
		return "no Matching Barcodes Found after {$data['lastupc']}";
	if ($data['status'] == 'finished')
		return "Done Processing.  Last Barcode Processed was {$data['upc']}";	
	return ("processing #{$data['i']} UPC {$data['upc']}");
}

// returns text for the next log line
public function getLogLine($data) {
	$retval="processed {$data['lastupc']}:<br />";
	if (sizeof($data['matches'] == 0))
		$retval .= $data['warning'];
	foreach ($data['matches'] as $match) {
		$retval .= $match."<br />";
	}	
	return $retval;	
}

// does the next iteration, updating data block and whatever else needs doing.  returns updated data block
public function iterate($data) {
	global $qqc;
	
	$data['lastupc']=$data['upc'];
	$row=$qqc->getRows('mod/proj/pchbupc::nextupc',1,$data['lastupc']);
	++$data['i'];
	
	if ($row !== False) {
		$google=file_get_contents("http://www.google.com/search?hl=en&q={$row['barcode']}&btnG=Search");
		preg_match_all('/<h3 class=r><a href="([^"]+)"/',$google,$matches);
		$data['matches']=$matches[1];
		if (sizeof($matches[1]) == 0) {
			if (isset($data['warning']))
				$data['status']='search engine fail';
			$data['warning']=$google;
		} else
			unset($data['warning']);	
		foreach ($matches[1] as $match) {
			$host=@parse_url($match,PHP_URL_HOST);
			if ($host !== False)
				$qqc->insert('mod/proj/pchbupc::newsite',$row['id'],$row['barcode'],$host,$match);
		}	
	}
	
	if ($row === False || $data['i'] >= $data['maxcount'])
		$data['status']='finished';
	else {
		$data['status']='iterating';
		$data['upc']=$row['barcode'];
	}		
	return $data;
}

public function outputForm($au,$state,$transaction) {
	$form=$this->makeform($au);
	echo $form->getDumpStyleFormOutput($state,$transaction);
}

/////////////////////////////////////////////////////////////////// end of class
} ?>
