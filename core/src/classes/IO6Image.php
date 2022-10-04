<?php


class IO6Image
{
	public $orderIndex;
	public $imageUri;
	public $lastUpdate;


	function __construct($jImage)
	{
		$this->orderIndex = $jImage['orderIndex'];				  
		$this->imageUri = !empty($jImage['imageUri']) ? $jImage['imageUri'] : '';
		$this->lastUpdate = $jImage['lastUpdate'];
	}
	
}
