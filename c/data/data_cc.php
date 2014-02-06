<?php if (FILEGEN != 1) die;
// data class for credit cards
class data_cc implements data_customtype {
	const TYPE_UNKNOWN = '?';
	const TYPE_MASTERCARD = 'M';
	const TYPE_VISA = 'V';
	const TYPE_AMEX = 'A';
	const TYPE_DISCOVER = 'D';
	const TYPE_DINERS = 'I';
	const TYPE_PAYPAL = 'P';
	
	private $type=self::TYPE_UNKNOWN;
	private $number='';
	private $exp=0;		// months since january 2000
	private $cv2='';
	private $name='';		// name on the card
	
	static function getAllTypes() {
		return array(self::TYPE_UNKNOWN=>'Unknown',self::TYPE_MASTERCARD=>'Mastercard',self::TYPE_VISA=>'Visa',self::TYPE_AMEX=>'American Express',self::TYPE_DISCOVER=>'Discover',self::TYPE_DINERS=>'Diners Club',self::TYPE_PAYPAL=>'PayPal');
	}

	/////////////////// customtype implementation:
	
	// returns an argument, ostensibly to be sent to a query for storage.
	// number of indices must be determined form getFieldNames
	public function getArgParam($index) {
		if ($index > 5)
			throw new data_exception("data_cc stores only 5 parameters");
		$data=array($this->type,$this->number,$this->exp,$this->cv2,$this->name);	
		return $data[$index];
	}
	
	// sets data for the custom type.  throws an exception if error occurred.
	// all data is set in vals indexed by number according to field names and types
	// returned below
	public function setDataArray($vals) {
		if (!is_array($vals) || sizeof($vals) != 5)
			throw new data_exception("Illegal array set into data_cc");
		list($this->type,$this->number,$this->exp,$this->cv2,$this->name)=$vals;	
	}
	
	public function getAllParams() {
		return array($this->type,$this->number,$this->exp,$this->cv2,$this->name);	
	}
	
	public function getParamCount() {return 5;}
		
	// returns an array of strings indexed sequentially of required parameters for this data type
	public function getFieldNames() {
		return array('type','number','exp','cv2','name');
	}
	
	// returns an array of type strings (see predefined types in crud) corresponding to FieldNames
	public function getFieldTypes() {
		return array(crud::TYPE_STRING,crud::TYPE_STRING,crud::TYPE_NUMBER,crud::TYPE_STRING,crud::TYPE_STRING);
	}

	// returns a string
	public function output($separator=' ') {
		if ($this->type == self::TYPE_PAYPAL)
			return ('(Using Paypal)');
		$cv2=($this->cv2 != '') ? "$separator(CV2:{$this->cv2})" : '';
		$exp=sprintf('%02d/%02d',(int)($this->exp % 12)+1,(int)($this->exp/12));
		$types=self::getAllTypes();
		$type=$types[$this->type];
		return "Card type {$type}$separator{$this->number}$cv2{$separator}exp. $exp{$separator}Name: {$this->name}";
	}
	// returns a string with a one-line description of credit card
	public function output1($bMask=True) {
		if ($this->type == self::TYPE_PAYPAL)
			return ('PayPal');
		$types=self::getAllTypes();
		$output=$types[$this->type];
		$number=$this->number;
		if ($bMask) {
			// turn all but the last group of numbers into asterisks
			$i=strrpos($number,' ');
			$nums=substr($number,$i);
			$asterisks=preg_replace('/[0-9]/','*',substr($number,0,$i));
			$number=$asterisks.$nums;
		}
		return $output.' '.$number;
	}
	// returns a string with a 3-line description of credit card
	public function output3($bMask=True,$separator='<br />') {
		if ($this->type == self::TYPE_PAYPAL)
			return ('(Using Paypal)');
		$output=$this->output1($bMask).$separator;
		$exp=sprintf('%02d/%02d',(int)($this->exp % 12)+1,(int)($this->exp/12));
		$output .= "Expires $exp$separator";
		$output .= "Name on Card: {$this->name}";
		return $output;		
	}

	////////////////////////////////// api interface functions:
	
	// returns True or with an array of field=>message
	public function verifyAndSet($a,$bRequired) {
		global $qq;

		$this->type = isset($a['cctype']) ? trim($a['cctype']) : ''; 	
		$this->number = isset($a['ccnumber']) ? trim($a['ccnumber']) : ''; 	
		$this->cv2 = isset($a['cccv2']) ? trim($a['cccv2']) : ''; 	
		$exp = isset($a['ccexp']) ? trim($a['ccexp']) : ''; 	
		$this->name = isset($a['ccname']) ? trim($a['ccname']) : ''; 	

		if ($this->type == self::TYPE_UNKNOWN) {
			if ($bRequired) {
				return array('*cctype'=>'You must specify a card type');
			} else {
				$this->number=$this->cv2=$this->name='';
				$this->exp=0;
				return True;		// OK -- just no card type
			}		
		}
		if ($this->type == self::TYPE_PAYPAL) {
			$this->number=$this->cv2=$this->name='';
			$this->exp=0;
			return True;		// OK -- no checking needed
		}
		switch ($this->type) {
			case self::TYPE_MASTERCARD:
				$regex=array('/^5[12345]\\d{14}$/');
				$cv2reg='/^\\d{3}$/';
				$digitgroups=array(4,4,4,4);
				$bChecksum=True;
			break;

			case self::TYPE_VISA:
				$regex=array('/^4\\d{12}(\\d\\d\\d){0,1}$/');
				$cv2reg='/^\\d{3}$/';
				$digitgroups=array(4,4,4,4);
				$bChecksum=True;
			break;

			case self::TYPE_AMEX:
				$regex=array('/^3[47]\\d{13}$/');
				$cv2reg='/^\\d{4}$/';
				$digitgroups=array(4,6,5);
				$bChecksum=True;
			break;
			
			case self::TYPE_DISCOVER:
				$regex=array('/^6011\\d{12}$/');
				$cv2reg='/^\\d{3}$/';
				$digitgroups=array(4,4,4,4);
				$bChecksum=True;
			break;
			
			case self::TYPE_DINERS:
				$regex=array('/^30[012345]\\d{11}$/','/^3[68]\\d{12}$/');
				$cv2reg='/^\\d{3}$/';
				$digitgroups=array(4,4,4,4);
				$bChecksum=True;
			break;
			
			case self::TYPE_UNKNOWN:
				if ($bRequired)
					return (array('*cctype'=>'You must specify a card type'));
				else {
					$this->number=$this->cv2=$this->name='';
					$this->exp=0;
					return True;		// OK -- just no card type
				}
			break;
			default:
				throw new exception("impossible credit card type: {$this->type}");
			break;			
		} // switch on type
		
		// security check to make sure that credit card is valid for this site.
		if (!isset($qq['cctypes']) || False === strpos($qq['cctypes'],$this->type))
			throw new exception("invalid credit card type for this site: {$this->type}");
		
		// remove all non-digits from the card sequence
		$ccnum=preg_replace('/[^\\d]+/','',$this->number);

		$retval=array();
		
		// check to make certain that the card number is valid
		$bInvalid=True;	// guilty until proven innocent
		$checksum=0;
		foreach ($regex as $r) {
			if (preg_match($r,$ccnum)) {
				$bInvalid=False;
				break;
			}
		}
		if (!$bInvalid && $bChecksum) {
			// made it through first check.  now do the checksum.
			$rev = strrev($ccnum);
		
			for ($i = 0; $i < strlen($rev); $i++) {
				$cur = intval($rev[$i]);
				if ($i & 1) {
					$cur *= 2;
				}
				$checksum += $cur % 10;
				if ($cur > 9) {
					$checksum += 1;
				}
			}
			if (($checksum % 10) != 0)
				$bInvalid=True;
		}
		
		if ($bInvalid) {
			$retval['*ccnumber']='There is a typo in the card number ';
		} else {
			// format number into groups
			$this->number='';
			foreach ($digitgroups as $dg) {
				$this->number .= substr($ccnum,0,$dg).' ';
				$ccnum=substr($ccnum,$dg);
			}
			// strip off final space:
			$this->number=substr($this->number,0,-1);
		}
		
		if (isset($qq['cccv2']) && $qq['cccv2'] && !preg_match($cv2reg,$this->cv2)) {
			$retval['*cccv2']='Check the cv2 code';
		}
		
		// check the expiration date
		if (preg_match('/^(\\d{1,2})[ \\-\\/]??(\\d{2})$/',$exp,&$matches)) {
			$month=(int)$matches[1];
			$year=(int)$matches[2];
			if ($month > 12) {
				$retval['*ccexp']='Check the expiration -- format mm/yy';
			} else {
				$current=getdate();
				if ($current['year']-2000 > $year || ($current['year']-2000 == $year && $current['mon'] > $month))
					$retval['*ccexp']='Expiration date has passed';
				$this->exp=$month-1+12*$year;
			}		
		} else
			$retval['*ccexp']='Check expiration - format mm/yy';
		
		if ($this->name == '') {
			$retval['*ccname']='Provide the name as it appears on the card';
		}
		return (sizeof($retval) > 0) ? $retval : True;
	}

	static function getTypes() {
		global $qq;
		
		$alltypes=self::getAllTypes();
		$retval=array();
		$allowed=isset($qq['cctypes']) ? $qq['cctypes'] : '';
		foreach ($alltypes as $type=>$fullname) {
			if ($type == self::TYPE_UNKNOWN)
				$retval[$type]='(Select Card Type)';
			else if (False !== strpos($allowed,$type))
				$retval[$type]=$fullname;
		}
		return $retval;
	}
	
	static function useCv2() {
		global $qq;
		return isset($qq['cccv2']) && $qq['cccv2'];		
	}

	// True if name matches, false if not
	public function equal($cc) {
		if ($cc instanceof data_cc) {
			return ($this->type == $cc->type && $this->number == $cc->number); 
		} else
			throw new exception("argument for equals function not a data_cc");
	}
	
	public function isComplete() {
		if ($this->type == self::TYPE_PAYPAL)
			return True;
		return ($this->type != self::TYPE_UNKNOWN && $this->number != '' && $this->exp != 0 && $this->name != ''); 
	}
	public function isExpired() {
		$current=getdate();
		$curval=($current['year']-2000)*12+$current['mon']-1;
		return ($this->exp < $curval);
	}


// end of class
}
?>
