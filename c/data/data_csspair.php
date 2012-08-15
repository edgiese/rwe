<?php if (FILEGEN != 1) die;
// class to implement css pair as a data type
class data_tester implements data_customtype {
	// color applies to all.  color inherited, none others are		
	const color = 101;
	const background_color = 102;
	const border_color = 103; 
	
	// font
	const font_family = 201;
	const font_size = 202;
	const font_style = 203;
	const font_variant = 204;
	const font_weight = 205;

	// character-level formatting
	const text_decoration = 301;
	const text_transform = 302;
	const letter_spacing = 303;
	const word_spacing = 304;

	// paragraph-level formatting
	const text_align = 401;
	const text_indent = 402;
	const white_space = 403;
	const line_height = 404;
	const vertical_align = 405;

	// list formatting
	const list_style_type = 501;
	const list_style_position = 502;
	const list_style_image = 503;

	// table formatting
	const table_layout = 601;
	const border_collapse = 602;
	const border_spacing = 603;
	const empty_cells = 604;
	const caption_side = 605;

	// background, fill, & border
	//const background_image = 1;
	//const background_repeat = 2;
	//const background_attachment = 3;
	//const background_position = 4;
	//const border_style*
	//const border_width*

	// spacing around border
	const margin = 801;
	//const margin_left
	//const margin_right
	//const margin_top
	//const margin_bottom
	const padding = 802;	
	//const padding_left
	//const padding_right
	//const padding_top
	//const padding_bottom

	// layout	
	const clip
	const overflow
	const visibility
	const display
	const clear

	// size & position
	const position
	const float
	const height
	const width
	const top
	const bottom
	const left
	const right
	const z_index
	const max_height
	const max_width
	const min_height
	const min_width
	
	const cursor

	// unused:	
	// content
	// quotes
	// counter_increment
	// counter_reset
	//const outline_color
	//const outline_style
	//const outline_width
	//const outline
	// page_break_after
	// page_break_before
	// page_break_inside
	// orphans
	// widows
	
	
	 
	
	private $keyword;
	private $value;
	
	function __construct($keyword,$value) {
		$args=array(0,"Hello");
	}
	
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

?>
