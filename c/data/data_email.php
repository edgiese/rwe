<?php if (FILEGEN != 1) die;
// interface definition for email
class data_email implements data_customtype {
	private $email;

	public function __construct($initvalue='') {
		$this->email=$initvalue;
	}

	/////////////////// customtype implementation:
		
	// returns an argument, ostensibly to be sent to a query for storage.
	// number of indices must be determined form getFieldNames
	public function getArgParam($index) {
		if ($index != 0)
			throw new data_exception("data_email stores only one parameter");
		return $this->args[$index];
	}
	
	// sets data for the custom type.  throws an exception if error occurred.
	// all data is set in vals indexed by number according to field names and types
	// returned below
	public function setDataArray($vals) {
		if (!is_array($vals) || sizeof($vals) != 1)
			throw new data_exception("Illegal array set into data_email");
		$this->email=$vals[0];
	}
	
	public function getAllParams() {
		return array($this->email);
	}
	
	public function getParamCount() {return 1;}
		
	// returns an array of strings indexed sequentially of required parameters for this data type
	public function getFieldNames() {
		return array ("1");
	}
	
	// returns an array of type strings (see predefined types in crud) corresponding to FieldNames
	public function getFieldTypes() {
		return array(crud::TYPE_STRING);
	}
	
	public function output($delimeter='') {
		return $this->email;
	}

	////////////////////////////////// api interface functions:
	
	
	public function set($email) {
		if (False === filter_var($email, FILTER_VALIDATE_EMAIL))
			throw new data_exception("$email is not a valid email address");
		$this->email=$email;	
	}

	
}
?>
