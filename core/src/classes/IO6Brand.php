<?php 

class IO6Brand {
	public $id;
	public $code;
	public $name;
	public $logo;
  
	
	function __construct($jBrand) {
		$this->id = $jBrand['id'];
		$this->code = $jBrand['code'];
		$this->name = $jBrand['names'][0];
		$this->logo = $jBrand['logo'];
	}

}
