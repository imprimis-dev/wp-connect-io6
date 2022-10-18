<?php 

class IO6Catalog {
  public $id;
	public $name;
  
  function __construct($jCatalog) {
		$this->id = $jCatalog['id'];				  
		$this->name = $jCatalog['name'];		
	}
}
