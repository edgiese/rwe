<?php if (FILEGEN != 1) die;
//////////////////////////////// support class for shopping cart
class util_prodcart {
	private $items;		// array of items in cart
	function __construct() {
		$this->items=array();
	}
	public function addToCart($prodid) {
		if (False === array_search($prodid,$this->items))
			$this->items[]=$prodid;
	}
	
	public function removeFromCart($prodid) {
		if (False !== ($i=array_search($prodid,$this->items)))
			unset($this->items[$i]);
	}
	
	public function isItemInCart($prodid) {
		return (False !== array_search($prodid,$this->items)) ? True : False;
	}
	
	public function getItems() {return $this->items;}

}

?>
