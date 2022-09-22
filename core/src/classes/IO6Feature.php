<?php 


class IO6Feature {
  public $code;
	public $name;
  public $description;
	public $searchable;
	public $dataType;
	public $groupId;
	public $groupName;
	public $value;
	public $unitMeasure;
  
	
  function __construct($jFeature) {
		$this->code = $jFeature['code'];
		$this->name = $jFeature['name'];
		$this->description = $jFeature['description'];
		$this->searchable = $jFeature['searchable'] == true;
		$this->dataType = $jFeature['dataType'];
		$this->groupId = $jFeature['groupId'];
		$this->groupName = $jFeature['groupName'];
		$this->value = $jFeature['value'];
		$this->unitMeasure = $jFeature['unitMeasure'];
	}

}
