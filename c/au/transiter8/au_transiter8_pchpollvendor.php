<?php if (FILEGEN != 1) die;
// transaction iterator to poll peach basket competitors for product information
class au_transiter8_pchpollvendor {
////////////////////////////////////////////////////////////////////////////////

private $vendors;
function __construct() {
	$this->vendors=array(
		/* 0 */
		array(
			'name'=>'www.vitacost.com',
			'searchurl'=>'http://www.vitacost.com/Search.aspx?ss=1&Ntk=products&x=12&y=12&Ntt=<<upc>>',
			'searchfail'=>'/No matches were found for/i',
			'linkurl'=>False,
			'extractions'=>array(
				'title'=>array('type'=>'html','search'=>'/pNameL">(.*?)<\\/div>/is'),
				'desc'=>array('type'=>'html','search'=>'/<span>Description<\\/span><\\/div>(.*?)<div class="pdSct">.*?<div class="subHdr1 cf">.*?<span>(Free Of|Disclaimer)/is'),
				'img'=>array('type'=>'imglink','search'=>'/pdMainL">.*?<img src="(.*?)"/is')
			)
		),
		/* 1 */
		array(
			'name'=>'www.naturalgrocers.com',
			'searchurl'=>'http://www.naturalgrocers.com/advanced_search_result.php?keywords=<<upc>>&Submit=search',
			'searchfail'=>'/Try modifying your search terms for better results/i',
			'linkurl'=>'/<td class="productListing-data.*?href="(.*?)"/is',
			'extractions'=>array(
				'title'=>array('type'=>'html','search'=>'/<td>.*?<h1>(.*?)<\\/h1>/is'),
				'desc'=>array('type'=>'html','search'=>'/colspan="3">(.*?)<\\/td>/is'),
				'img'=>array('type'=>'imglink','search'=>"/javascript:popupWindow\\(\\\\'(.*?)\\\\'/is")
			)
		),
		/* 2 */
		array(
			'name'=>'www.herbsmd.com',
			'searchurl'=>'http://www.herbsmd.com/upc/<<upc>>.htm',
			'searchfail'=>'/<title><\\/title>/i',
			'linkurl'=>'/<a href="([^"]*?)" class="viewtext" rel="nofollow" >View Product Detail/is',
			'linkprefix'=>'http://www.herbsmd.com',
			'extractions'=>array(
				'title'=>array('type'=>'html','search'=>'/<title>(.*?) - /is'),
				'desc'=>array('type'=>'html','matchix'=>0,'search'=>'/<table width="100%" border="0" cellspacing="3" cellpadding="0" class="verd2" style="border-top: solid 1px #D8D8D8;border-left: solid 1px #D8D8D8;border-right: solid 1px #D8D8D8;border-bottom: solid 1px #D8D8D8;">.*?<h4>Disclaimer<\\/h4>.*?<\\/table>.*?<\\/table>/is'),
				'img'=>array('type'=>'imglink','search'=>'/src="(\\/img\\/pimg.*?)"/is')
			)
		),
		/* 3 */
		array(
			'name'=>'www.houseofnutrition.com',
			'searchurl'=>'http://search.store.yahoo.net/cgi-bin/nsearch?catalog=hono&query=<<upc>>&vwcatalog=hono',
			'searchfail'=>'/Sorry, no matches were found./i',
			'linkurl'=>'/Search for:  .*?<A HREF="(.*?)"/is',
			'extractions'=>array(
				'title'=>array('type'=>'html','search'=>'/<div id="item-header-top"><h1>(.*?)<\\/h1>/is'),
				'img'=>array('type'=>'imglink','search'=>'/<div id="scItemImage">.*?<img src="(.*?)"/is'),
				'desc'=>array('type'=>'html','search'=>'/<!--sc-caption-start-->(.*?)<!--sc-caption-end-->/is')
			)
		) // last one has no comma
	);
}

// returns a form object
public function makeform($au) {
	$form=new llform($au,'form');
	$form->addFieldset('d','Poll Vendor');
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
	$data['ixvendor']=0;
	$data['id']=$row['id'];
	$data['upc']=$row['barcode'];
	if ($row === False)
		$data['status']='finished';
	else {
		$data['status']='iterating';
		$data['upc']=$row['barcode'];
	}
	$data['i']=0;
	$data['ixvendor']=0;
	$data['maxcount']=(int)$vals['count'];	
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
	if (!isset($data['source']))
		return "getting started...";
	$retval="processed {$data['lastupc']} for vendor {$data['source']} types: {$data['typesprocessed']}";
	if ($data['message'] != '')
		$retval .= " message: {$data['message']}";
	return $retval;	
}

// does the next iteration, updating data block and whatever else needs doing.  returns updated data block
public function iterate($data) {
	global $qqc;

	if ($data['ixvendor'] >= sizeof($this->vendors)) {
		++$data['i'];
		if ($data['i'] >= $data['maxcount']) {
			$data['status']='finished';
			return $data;
		}
		$data['lastupc']=$data['upc'];
		$row=$qqc->getRows('mod/proj/pchbupc::nextupc',1,$data['lastupc']);
		if ($row === False) {
			$data['status']='finished';
			return $data;
		}
		$data['ixvendor']=0;
		$data['id']=$row['id'];
		$data['upc']=$row['barcode'];
	}
	$ix=$data['ixvendor'];
	$data['source']=$this->vendors[$ix]['name'];
	$searchurl=str_replace('<<upc>>',$data['upc'],$this->vendors[$ix]['searchurl']);
		
	$data['message']=$data['typesprocessed']='';
	$searchresult=file_get_contents($searchurl);
	if (preg_match($this->vendors[$ix]['searchfail'],$searchresult,$matches)) {
		// nothing found
		$data['message']="Site search returned no results.";
	} else {
		// assume only one match found.  use the first one after the search bar.
		//infolog_dump("monitor","search results",$searchresult);	
		if (False === $this->vendors[$ix]['linkurl'] || preg_match($this->vendors[$ix]['linkurl'],$searchresult,$matches)) {
			$info=array();
			if (False === $this->vendors[$ix]['linkurl'])
				$linkresult=$searchresult;
			else {
				$filelink=$matches[1];
				if (isset($this->vendors[$ix]['linkprefix']))
					$filelink=$this->vendors[$ix]['linkprefix'].$filelink;
				infolog("monitor","page={$filelink}");	
				$linkresult=file_get_contents($filelink);
			}	
			//infolog_dump("monitor","product page",$linkresult);	
			foreach ($this->vendors[$ix]['extractions'] as $type=>$extinfo) {
				if (preg_match($extinfo['search'],$linkresult,$matches)) {
					$matchix=(isset($extinfo['matchix'])) ? $extinfo['matchix'] : 1;
					$text=$matches[$matchix]; 
					$qqc->insert("mod/proj/pchbupc::newattrib",$data['id'],$extinfo['type'],$type,$data['source'],$text);
					$data['typesprocessed'] .= $type." ";
				}
			}		
		}
	}
	$data['ixvendor']++;	
	return $data;
}

public function outputForm($au,$state,$transaction) {
	$form=$this->makeform($au);
	echo $form->getDumpStyleFormOutput($state,$transaction);
}

/////////////////////////////////////////////////////////////////// end of class
} ?>
