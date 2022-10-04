<?php 


//require_once("icecat_supplier.class.php");
//require_once("icecat_product_image.class.php");
//require_once("icecat_product_multimedia_object.class.php");
//require_once("icecat_product_related_products.class.php");
//require_once("icecat_product_feature.class.php");

class IO6Catalog {
  public $id;
	public $name;
  
  function __construct($jCatalog) {
		$this->id = $jCatalog['id'];				  
		$this->name = $jCatalog['name'];		
	}
}
