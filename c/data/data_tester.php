<?php if (FILEGEN != 1) die;
// interface definition for all custom data types
class data_tester implements data_customtype {
	private $args;
	
	function __construct() {
		$args=array(0,"Hello");
	}

	// returns an argument, ostensibly to be sent to a query for storage.
	// number of indices must be determined form getFieldNames
	public function getArgParam($index) {
		if ($index != 0 && $index != 1)
			throw new exception("Illegal index ($index) sent to data_tester");
		return $this->args[$index];
	}
	
	// sets data for the custom type.  throws an exception if error occurred.
	// all data is set in vals indexed by number according to field names and types
	// returned below
	public function setDataArray($vals) {
		if (!is_array($vals) || sizeof($vals) != 2)
			throw new exception("Illegal array set into data_tester");
		$this->args[0]=$vals[0];
		$this->args[1]=$vals[1];
	}
	
	public function getAllParams() {
		return $this->args;
	}
	
	public function getParamCount() {return 2;}
		
	// returns an array of strings indexed sequentially of required parameters for this data type
	public function getFieldNames() {
		return array ("count","message");
	}
	
	// returns an array of type strings (see predefined types in crud) corresponding to FieldNames
	public function getFieldTypes() {
		return array(crud::TYPE_INT,crud::TYPE_STRING);
	}
}
?>
