<?php if (FILEGEN != 1) die;
//////////////////////////////// support class for shopping cart
class util_prodcart {
	private $items;		// array of items in cart
	function __construct() {
		$this->items=array();
	}
	public function addToCart($prodid) {
		if (False === array_search($prodid,$this->items))
			$this->prodid[]=$prodid;
	}
	
	public function removeFromCart($prodid) {
		if (False !== ($i=array_search($prodid,$this->items)))
			unset($this->prodid[$i]);
	}
	
	public function isItemInCart($prodid) {
		return (False !== array_search($prodid,$this->items)) ? True : False;
	}

}

?>
