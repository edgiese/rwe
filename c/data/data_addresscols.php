<?php if (FILEGEN != 1) die;
// interface definition for all custom data types
class data_addresscols implements data_customtype {
	const LINE1=0;
	const LINE2=1;
	const CITY=2;
	const STATE=3;
	const ZIP=4;
	const COUNTRY=5;
	const MAXINDEX=5;
	
	private $params;
	
	function __construct($bUSAonly) {
		if (!$bUSAonly)
			throw new exception('international addresses not implemented yet');
		else
			$this->params[self::COUNTRY]='USA';	
		$this->bUSAonly=$bUSAonly;
		$this->params=array('','','','','','USA');
	}

	/////////////////// customtype implementation:
	
	// returns an argument, ostensibly to be sent to a query for storage.
	// number of indices must be determined form getFieldNames
	public function getArgParam($index) {
		if ($index < 0 || $index > self::MAXINDEX)
			throw new exception("data_addresscols index out of range: $index");
		return $this->params[$index];
	}
	
	// sets data for the custom type.  throws an exception if error occurred.
	// all data is set in vals indexed by number according to field names and types
	// returned below
	public function setDataArray($vals) {
		if (!is_array($vals) || sizeof($vals) <5 || sizeof($vals) > 6)
			throw new exception("Illegal array set into data_address");
		if (sizeof($vals) == 5)
			$vals[self::COUNTRY]='USA';	
		$this->params=$vals;	
		if ($this->params[self::COUNTRY] != 'USA') {
			throw new exception('international addresses not implemented yet');
		}	
	}
	public function getAllParams() {return $this->params;}
	public function getParamCount() {return sizeof($this->params);}
		
	// returns an array of strings indexed sequentially of required parameters for this data type
	public function getFieldNames() {
		return array ("line1","line2","city","state2","zip","country");
	}
	// returns an array of type strings (see predefined types in crud) corresponding to FieldNames
	public function getFieldTypes() {
		return array(crud::TYPE_STRING,crud::TYPE_STRING,crud::TYPE_STRING,crud::TYPE_STRING,crud::TYPE_STRING,crud::TYPE_STRING);
	}
	
	public function output($delimeter='<br />') {
		$o='';
		if ($this->params[self::COUNTRY] == 'USA') {
			if ($this->params[self::LINE1] != '')
				$o .= $this->params[self::LINE1].$delimeter;
			if ($this->params[self::LINE2] != '')
				$o .= $this->params[self::LINE2].$delimeter;
			$o .= $this->params[self::CITY].', '.$this->params[self::STATE].'  '.$this->params[self::ZIP];	
		} else {
			throw new exception('international addresses not implemented yet');
		}
		return $o;	
	}

	////////////////////////////////// api interface functions:
	
	// returns True or with an array of field=>message
	public function verifyAndSet($a,$bRequired) {
		if ($a['country'] != 'USA')
			throw new exception("internation addresses not supported yet");
		$this->params[self::COUNTRY]='USA';		
		$this->params[self::LINE1] = isset($a['line1']) ? $a['line1'] : ''; 	
		$this->params[self::LINE2] = isset($a['line2']) ? $a['line2'] : ''; 	
		$this->params[self::CITY] = isset($a['city']) ? $a['city'] : ''; 	
		$this->params[self::STATE] = isset($a['state2']) ? $a['state2'] : ''; 	
		$this->params[self::ZIP] = isset($a['zip']) ? $a['zip'] : ''; 	
			
		if ($this->params[self::LINE1] == '' && $this->params[self::LINE2] == '') {
			if ($bRequired || $this->params[self::CITY] != '' || $this->params[self::ZIP] != '')
				return (array('*line1'=>'You must provide an address'));
			else {
				$this->params[self::CITY]=$this->params[self::STATE]=$this->params[self::ZIP];
				return True;		// OK -- just no address
			}		
		}
		$retval=array();
		if ($this->params[self::CITY] == '')
			$retval['*city']='You must provide a city';
		if ($this->params[self::ZIP] == '')
			$retval['*zip']='You must provide a ZIP code';
		else {
			$nmatches=preg_match('/^[\\d]{5}(-[\\d]{4})??$/',$this->params[self::ZIP]);
			if (0 == $nmatches)
				$retval['*zip']="Invalid ZIP code";
		}	
		return (sizeof($retval) != 0) ? $retval : True;
	}
	public function isBlank() {
		return ($this->params[self::LINE1] == '' || $this->params[self::CITY] == '' || $this->params[self::STATE] == '' || $this->params[self::ZIP] == '');
	}
	public function isComplete() {
		return ($this->params[self::LINE1] != '' || $this->params[self::CITY] != '' || $this->params[self::STATE] != '' || $this->params[self::ZIP] != ''); 
	}
	
}
?>
