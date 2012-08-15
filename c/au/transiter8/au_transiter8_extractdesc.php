<?php if (FILEGEN != 1) die;
// transaction iterator to poll peach basket competitors for product information
class au_transiter8_extractdesc extends au_transiter8_base {
////////////////////////////////////////////////////////////////////////////////

// returns a form object
public function makeform($au) {
	$form=new llform($au,'form');
	$form->addFieldset('d','Poll Vendor');
	$form->addControl('d',new ll_button($form,'iterate','Poll Site Database'));
	return $form;
}

// returns data block, reading form
public function initIterator($au) {
	global $qqc;
	
	$data=array();
	
	$data['msg']=array();
	$data['upc']='';
	$data['status']='iterating';
	return $data;
}

// returns status text 'iterating', 'finished', or 'error'
public function getStatus($data) {
	return $data['status'];
}

// returns text for the status line
public function getStatusLine($data) {
	if ($data['status'] == 'finished')
		return "Done Processing.  Last Barcode Processed was {$data['upc']}";	
	return "processing UPC {$data['barcode']}";
}

// returns text for the next log line
public function getLogLine($data) {
	$retval='';
	foreach ($data['msg'] as $title=>$message) {
		$retval .= $title." : ".nl2br(htmlentities($message))."<br />";
	}
	return $retval;	
}

// does the next iteration, updating data block and whatever else needs doing.  returns updated data block
public function iterate($data) {
	global $qqc;
	
	do {
		$row=$qqc->getRows('mod/proj/pchbupc::nextsiterow',1);
		if ($row === False) {
			$data['status']='Finished';
			return $data;
		}
		$messages=self::readSiteData($row['site'],$row['query'],$row['barcode']);
		$qqc->act('mod/proj/pchbupc::markrowdone',$row['id'],$row['site']);
	} while ($messages === False);
	$data['barcode']=$row['barcode'];	
	
	foreach ($messages as $title=>$message) {
		switch ($title) {
			case 'mfr':
				$qqc->insert("mod/proj/pchbupc::newattrib",$row['id'],$title,'text',$messages['source'],$message);
			break;
			case 'img':
				$qqc->insert("mod/proj/pchbupc::newattrib",$row['id'],$title,'text',$messages['source'],$message);
			break;
			case 'title':
				$qqc->insert("mod/proj/pchbupc::newattrib",$row['id'],$title,'text',$messages['source'],$message);
			break;
			case 'desc':
				$qqc->insert("mod/proj/pchbupc::newattrib",$row['id'],$title,'creole',$messages['source'],$message);
				$messages['desc']='('.strlen($message).' chars)';
			break;
			default:
			break;
		}
	}
	$data['msg']=$messages;
	return $data;
}

public function outputForm($au,$state,$transaction) {
	$form=$this->makeform($au);
	echo $form->getDumpStyleFormOutput($state,$transaction);
}

static function readSiteData($site,$query,$upc) {
	$m=array();
	
	// no secret stuff here!
	$query=str_replace('https','http',$query);
	
	switch ($site) {
		case 'www.ramonafamilynaturals.com':
		case 'ramonafamilynaturals.com':
			$m['source']='ramona';
			$contents=file_get_contents($query);
			if (preg_match('/<div class="itemCaption"><h1>(.*?)<\\/h1>/',$contents,$matches)) {
				$m['title']=$matches[1];
			} else
				$m['titlefail']='No title found';
				
			if (preg_match("/window.open\\('([^']*?)'/",$contents,$matches)) {
				$m['img']=$matches[1];
			} else
				$m['imgfail']='No image found';

			if (preg_match('/<div class="productDesc">(.*?)<\\/div>/',$contents,$matches)) {
				$m['desc']=$matches[1];
			} else
				$m['descfail']='No description found';
				
			$m['contents']=$contents;
		break;
		
		case 'www.nutricity.com':
			$m['source']='nutricity';
			$contents=file_get_contents($query);
			if (preg_match('/<h1>(.*?)<\\/h1>/',$contents,$matches)) {
				$m['title']=$matches[1];
			} else
				$m['titlefail']='No title found';
				
			if (preg_match('/<a href="catalog\\/full\\/(.*?)" target="_blank">/',$contents,$matches)) {
				$m['img']='http://www.nutricity.com/n/pc/catalog/full/'.$matches[1];
			} else
				$m['imgfail']='No image found';

			if (preg_match('/<!-- Start long product description -->(.*?)<!-- End long product description -->/s',$contents,$matches)) {
				$m['desc']=trim($matches[1]);
				if ($m['desc'] == '') {
					unset($m['desc']);
					$m['descfail']='Page had no long description';
				}
			} else
				$m['descfail']='No description found';
				
			$m['contents']=$contents;
		break;
		
		case 'www.americancivilwar.com':
			$m['source']='civilwar';
			$contents=file_get_contents($query);
			if (preg_match('/<h1>(.*?)<\\/h1>/',$contents,$matches)) {
				$m['title']=$matches[1];
			} else
				$m['titlefail']='No title found';
				
			if (preg_match('/popurl="(.*?)"/',$contents,$matches)) {
				$m['img']=$matches[1];
			} else
				$m['imgfail']='No image found';

			if (preg_match('/Product Description<\\/span><br><span class="aom_tr">(.*?)<\\span>/s',$contents,$matches)) {
				$m['desc']=trim($matches[1]);
				if ($m['desc'] == '') {
					unset($m['desc']);
					$m['descfail']='Page had no long description';
				}
			} else
				$m['descfail']='No description found';
				
			$m['contents']=$contents;
		break;

		case 'www.shoprite.com':
		case 'www.netgrocer.com':
			$m['source']=substr($site,4,-4);
			$contents=file_get_contents($query);
			if (preg_match('/<h1 class="ProductDetail-Brand">(.*?)<\\/h1>/',$contents,$matches)) {
				$m['mfr']=$matches[1];
			} else
				$m['mfrfail']='No mfr found';
				
			if (preg_match('/<h2 class="ProductDetail-ProductName">(.*?)<\\/h2>/',$contents,$matches)) {
				$m['title']=$matches[1];
			} else
				$m['titlefail']='No title found';
				
			if (preg_match('/<div class="ProductImage"><img style="border:0px;" src="(.*?)"/',$contents,$matches)) {
				$m['img']=$matches[1];
			} else
				$m['imgfail']='No image found';

			$m['desc']='';
			if (preg_match('/<div class="ProductDetail-ProductName">(.*?)<\\/div>/',$contents,$matches)) {
				$m['desc'] .= $matches[1];
			}
			if (preg_match('/<div id="ctl06_ProductNutrition" class="ProductDetail-ProductNutritionHeader">(.*?)<\\/div>/',$contents,$matches)) {
				$m['desc'] .= $matches[1];
			}
			if (preg_match('/<div id="ctl07_ProductMoreInfo" class="ProductDetail-ProductMoreInfoHeader">(.*?)<\\/div>/',$contents,$matches)) {
				$m['desc'] .= $matches[1];
			}
			
			
			if (trim($m['desc']) == '') {
				unset($m['desc']);
				$m['descfail']='No description found';
			}	
				
				
			$m['contents']=$contents;
		break;

		case 'www.luckyvitamin.com':
			$m['source']=substr($site,4,-4);
			$contents=file_get_contents($query);
			if (preg_match('/<h1 class="itemPageH1">(.*?)<\\/h1>/',$contents,$matches)) {
				$m['title']=$matches[1];
			} else
				$m['titlefail']='No title found';
				
			if (preg_match("/javascript:displayItemMagnify\\('(.*?)\\?/",$contents,$matches)) {
				$m['img']=$matches[1];
			} else
				$m['imgfail']='No image found';

			$m['desc']='';
			if (preg_match('/<\\!--\\*\\*\\* ITEM DATA TAB - DESCRIPTION \\*\\*\\*-->(.*?)<\\!--\\*\\*\\* ITEM DATA TAB - MANUFACTURER INFO \\*\\*\\*-->/s',$contents,$matches)) {
				$m['desc'] .= $matches[1];
			}
			
			if (trim($m['desc']) == '') {
				unset($m['desc']);
				$m['descfail']='No description found';
			}	
				
				
			$m['contents']=$contents;
		break;
		
		case 'www.pedalpeople.com':
			$m['source']=substr($site,4,-4);
			$contents=file_get_contents($query);
			
			// do indirection to find distributor detail page
			if (preg_match('/"(http:\\/\\/www\\.assocbuyers\\.com[^\\?]*?\\?[^\\=]*?\\=\\d+)"/',$contents,$matches)) {
				$query=$matches[1];
				$contents=file_get_contents($query);
			}
			
			if (preg_match('/IDBrand=[0-9]+">\\s*(.*?)\\s*<\\/a>/',$contents,$matches)) {
				$m['mfr']=$matches[1];
			} else
				$m['mfrfail']='No mfr found';
				
			if (preg_match('/<h1>(.*?)<\\/h1>/',$contents,$matches)) {
				$m['title']=$matches[1];
			} else
				$m['titlefail']='No title found';
				
			if (preg_match("/<img id='mainimg' name='mainimg' src='(.*?)'/",$contents,$matches)) {
				$m['img']='http://www.assocbuyers.com/'.$matches[1];
			} else
				$m['imgfail']='No image found';

			$m['desc']='';
			if (preg_match('/<\\!-- Start long product description -->.*?<tr>.*?<tr>.*?<tr>.*?<td[^>]+>(.*?)<\\/td>/s',$contents,$matches)) {
				$m['desc'] .= $matches[1];
			}
			
			if (trim($m['desc']) == '') {
				unset($m['desc']);
				$m['descfail']='No description found';
			}	
				
			$m['contents']=$contents;
		break;

		case 'www.thenaturalsupermart.com':
			$m['source']='natsupermart';
			$contents=file_get_contents($query);
			if (preg_match('/<TITLE>(.*?):/',$contents,$matches)) {
				$m['title']=$matches[1];
			} else
				$m['titlefail']='No title found';
				
			if (preg_match('/ROWSPAN="5">\\s*<img src="(.*?)"/s',$contents,$matches)) {
				$m['img']=$matches[1];
			} else
				$m['imgfail']='No image found';

			$m['descfail']='No descriptions on this site';
				
			$m['contents']=$contents;
		break;

		case 'www.growinglifestyle.com':
		case 'www.growinghealthcare.com':
			$m['source']=substr($site,4,-4);
			$contents=file_get_contents($query);
			
			// find upc in this mess
			if (False === ($i=strpos($contents,$upc)))
				return False;
			$contents=substr($contents,$i);
			if (!preg_match('/href="(.*?)"/',$contents,$matches))
				return False;
			$query=$matches[1];	
			$contents=file_get_contents($query);
			
			// to to amazon.com detail
			if (!preg_match('/<iframe src="(.*?)"/',$contents,$matches))
				return False;
			$query=$matches[1];	
			$contents=file_get_contents($query);

			if (preg_match('/<div id="titleAndByLine">\\s*<h2>(.*?)<br>\\s*<span class="by">(From|By) (.*?)<\\/span>/si',$contents,$matches)) {
				$m['title']=$matches[1];
				$m['mfr']=$matches[3];
				if (0 === strpos($m['title'],$m['mfr'])) {
					// strip mfr off start of title
					$m['title']=trim(substr($m['title'],strlen($m['mfr'])));
				}
			} else {
				$m['titlefail']='No title found';
				$m['mfrfail']='No mfr found';
			}	

				
			if (preg_match('/id="imageViewerLink"><img src="(.*?)"/s',$contents,$matches)) {
				$m['img']=$matches[1];
			} else
				$m['imgfail']='No image found';

			$m['desc']='';
			if (preg_match('/<div id="productDescription">(.*?)<\\/div>/s',$contents,$matches)) {
				$m['desc'] .= trim($matches[1]);
			}
			if (trim($m['desc']) == '') {
				unset($m['desc']);
				$m['descfail']='No description found';
			}	
				
			$m['contents']=$contents;
		break;

		case 'www.totalhealthvitamins.net':
			$m['source']=substr($site,4,-4);
			$contents=file_get_contents($query);
			
			if (False === ($i=strpos($contents,'product_form')))
				return False;
			$haystack=substr($contents,$i);
			if (preg_match('/src="(.*?)".*?alt="(.*?)"/',$haystack,$matches)) {
				$m['title']=$matches[2];
				$m['img']=$matches[1];
			} else {
				$m['imgfail']='No title found';
				$m['titlefail']='No mfr found';
			}
				
			$m['desc']='';
			if (False !== ($i=strpos($contents,'your own review'))) {
				$haystack=substr($contents,$i);
				if (preg_match('/<td colspan="2" class="normaltext">(.*?)<\\/td>/s',$haystack,$matches)) {
					$m['desc'] .= trim($matches[1]);
				}
			}	
			if (trim($m['desc']) == '') {
				unset($m['desc']);
				$m['descfail']='No description found';
			}	
				
			$m['contents']=$contents;
		break;

		case 'www.herbalremedies.com':
			$m['source']=substr($site,4,-4);
			$contents=file_get_contents($query);
			if (preg_match('/<h1>(.*?)<\\/h1>/',$contents,$matches)) {
				$m['title']=$matches[1];
			} else
				$m['titlefail']='No title found';
				
			if (preg_match("/javascript:CaricaFoto\\('(.*?)'/",$contents,$matches)) {
				$m['img']=$matches[1];
			} else
				$m['imgfail']='No image found';

			$m['desc']='';
			if (preg_match('/<\\/form>(.*?)<B>Brand:<\\/B>/s',$contents,$matches)) {
				$m['desc'] .= $matches[1];
			}
			
			if (trim($m['desc']) == '') {
				unset($m['desc']);
				$m['descfail']='No description found';
			}	
				
				
			$m['contents']=$contents;
		break;
		
		default:
			return False;
		break;
	}
	unset($m['contents']);
	return $m;
}

/////////////////////////////////////////////////////////////////// end of class
} ?>
