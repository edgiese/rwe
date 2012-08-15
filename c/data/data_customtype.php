<?php if (FILEGEN != 1) die;
// interface definition for all custom data types
interface data_customtype {
	// returns an argument, ostensibly to be sent to a query for storage.
	// number of indices must be determined form getFieldNames
	public function getArgParam($index);
	
	// sets data for the custom type.  throws an exception if error occurred.
	// all data is set in vals indexed by number according to field names and types
	// returned below
	public function setDataArray($vals);
	
	// returns all data parameters in an array
	public function getAllParams();
	
	public function getParamCount();
	
	// returns an array of strings indexed sequentially of required parameters for this data type
	public function getFieldNames();
	
	// returns an array of type strings (see predefined types in crud) corresponding to FieldNames
	public function getFieldTypes();

	// returns a formatted string for output
	public function output($separator='<br />');
}
?>
