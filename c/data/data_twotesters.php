<?php if (FILEGEN != 1) die;
// interface definition for all custom data types
class data_twotesters implements data_customtype {
	private $args;
	
	function __construct() {
		$args=array();
	}

	// returns an argument, ostensibly to be sent to a query for storage.
	// number of indices must be determined form getFieldNames
	public function getArgParam($index) {
		if ($index != 0 && $index != 1 && $index != 3)
			throw new exception("Illegal index ($index) sent to data_twotesters");
		return $this->args[$index];
	}
	
	// sets data for the custom type.  throws an exception if error occurred.
	// all data is set in vals indexed by number according to field names and types
	// returned below
	public function setDataArray($vals) {
		if (!is_array($vals) || sizeof($vals) != 3)
			throw new exception("Illegal array set into data_twotesters");
		$this->args[0]=$vals[0];
		$this->args[1]=$vals[1];
		$this->args[2]=$vals[2];
	}
	
	public function getAllParams() {
		return $this->args;
	}
	
	public function getParamCount() {return 3;}
	
	// returns an array of strings indexed sequentially of required parameters for this data type
	public function getFieldNames() {
		return array ("index","tester 1","tester 2");
	}
	
	// returns an array of type strings (see predefined types in crud) corresponding to FieldNames
	public function getFieldTypes() {
		return array(crud::TYPE_INT,"tester","tester");
	}
}
?>
