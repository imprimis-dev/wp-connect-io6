<?php 


class IO6PriceList {
  public $id;
  public $name;
  
	
  function __construct($jPriceList) {
		$this->id = $jPriceList['id'];				  		
		$this->name = $jPriceList['name'];
	}

}
