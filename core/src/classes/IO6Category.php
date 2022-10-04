<?php 

class IO6Category {
  public $id;
	public $code;
  public $parentId;  
	public $parentCode;  
  public $name;
  
	public $subCategories;
	
  function __construct($jCategory, $subCategories) {
		$this->id = $jCategory['id'];				  
		$this->code = $jCategory['code'];				
		$this->parentId = $jCategory['parentId'];		
			
		//TODO: EM20210318 => vedere se fare una get con le IO6API oppure aggiungerlo al json
		$this->parentCode = !empty($jCategory['parentId']) ? str_pad($jCategory['parentId'], 10, '0', STR_PAD_LEFT) : '';
		$this->name = $jCategory['name'];
		$this->subCategories = $subCategories;
	}

}
