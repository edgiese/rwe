<?php if (FILEGEN != 1) die;
// interface definition for phone
class data_phone implements data_customtype {

	const TYPE_UNKNOWN = '?';
	const TYPE_HOME = 'H';
	const TYPE_WORK = 'W';
	const TYPE_CELL = 'C';

	private $phone='';
	private $type=self::TYPE_UNKNOWN;

	/////////////////// customtype implementation:
	
	// returns an argument, ostensibly to be sent to a query for storage.
	// number of indices must be determined form getFieldNames
	public function getArgParam($index) {
		if ($index > 1)
			throw new data_exception("data_phone stores only 2 parameters");
		return $this->args[$index];
	}
	
	// sets data for the custom type.  throws an exception if error occurred.
	// all data is set in vals indexed by number according to field names and types
	// returned below
	public function setDataArray($vals) {
		if (!is_array($vals) || sizeof($vals) != 2)
			throw new data_exception("Illegal array set into data_phone");
		$this->phone=$vals[0];
		$this->type=$vals[1];
	}
	
	public function getAllParams() {
		return array($this->phone);
	}
	
	public function getParamCount() {return 2;}
		
	// returns an array of strings indexed sequentially of required parameters for this data type
	public function getFieldNames() {
		return array ("phone","type");
	}
	
	// returns an array of type strings (see predefined types in crud) corresponding to FieldNames
	public function getFieldTypes() {
		return array(crud::TYPE_STRING,crud::TYPE_STRING);
	}
	
	public function output($delimeter='') {
		$retval=$this->phone;
		if ($delimeter != '' && $this->type != self::TYPE_UNKNOWN)
			$retval .= $delimeter . $this->type;
		return $retval;	
	}

	////////////////////////////////// api interface functions:
	
	
	public function set($phone,$type=self::TYPE_UNKNOWN) {
		global $qq;
		
		$phone=trim($phone);
		if (0 == preg_match('/^(\\([\\d]{3}\\)|[\\d]{3})[\\-\\/ ]??[\\d]{3}[\\- ]??[\\d]{4}$/',$phone)) {
			// it might be a phone number without an area code
			if (0 == preg_match('/^[\\d]{3}[\\- ]??[\\d]{4}$/',$phone)) 
				throw new data_exception("not a valid phone number $phone");
			if (!isset($qq['localareacode']))	
				throw new data_exception("area code must be specified");
			$phone=$qq['localareacode'].$phone;
		}	
		$this->type=$type;
		$phone=preg_replace('/[^\\d]+/','',$phone);
		$this->phone='('.substr($phone,0,3).') '.substr($phone,3,3).'-'.substr($phone,6,4);	
	}

	public function verifyAndSet($phone,$type=self::TYPE_UNKNOWN) {
		try {
			$this->set($phone,$type);
			$retval=True;
		} catch (data_exception $e) {
			$retval=array('*phone'=>$e->getMessage());
		}
		return $retval;
	}
	public function isComplete() {
		return ($this->phone != ''); 
	}
	
}
?>
