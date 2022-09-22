<?php
require_once('IO6Image.php');
require_once('IO6Feature.php');

class IO6Product
{

	public $id;
	public $code;

	public $creationDate;
	public $isActive;

	public $ean;
	public $partNumber;

	public $brandCode;
	public $supplierId;
	public $categoryCode;

	public $title;
	public $shortDescription;
	public $fullDescription;
	public $avail;
	public $arrivalAvail;
	public $arrivalDate;
	public $officialPrice;
	public $dealerPrice;
	public $sellingPrice;
	public $sellingCustomPriceUntil;
	public $sellingCustomPrice;
	public $promoPrice;
	public $promoDateStart;
	public $promoDateEnd;
	public $weight;
	public $statusCode;

	public $siaeAmount;
	public $raeeAmount;

	public $volume;
	public $width;
	public $height;
	public $length;

	public $images;
	public $features;
	public $featuresHtml;

	public $minLimitQty;


	//region Metodi
	function __construct($jProduct, $io6_configuration)
	{
		$this->id = $jProduct['id'];
		$this->code = $jProduct['code'];

		$this->creationDate = $jProduct['creationDate'];
		$this->isActive = $jProduct['isActive'];
		$this->statusCode = $jProduct['statusCode'];

		$this->ean = !empty($jProduct['eans']) ? $jProduct['eans'][0] : '';
		$this->partNumber = !empty($jProduct['partNumbers']) ? $jProduct['partNumbers'][0] : '';
		$this->brandCode = $jProduct['brandCode'];
		$this->supplierId = $jProduct['supplierId'];
		$this->categoryCode = $jProduct['categoryCode'];
		$this->title = $jProduct['title'];
		$this->shortDescription = $jProduct['shortDescription'];
		$this->fullDescription = $jProduct['fullDescription'];

		$this->avail = $jProduct['avail'];
		$this->arrivalAvail = $jProduct['arrivalAvail'];
		$this->arrivalDate = $jProduct['arrivalDate'];

		$this->officialPrice = $jProduct['officialPrice'];
		$this->dealerPrice = $jProduct['dealerPrice'];
		$this->sellingPrice = $jProduct['sellingPrice'];					//prezzo di vendita
		$this->sellingCustomPrice = $jProduct['sellingCustomPrice'];		//prezzo di vendita in promo (se no promo = sellingprice)
		$this->sellingCustomPriceUntil = $jProduct['sellingCustomPriceUntil'];				//data del prezzo custom impostato dall'utente (inutilizzato al momento)


		$this->promoPrice = $jProduct['promoPrice'];		//prezzo di acquisto in promo
		$this->promoDateStart = $jProduct['promoDate']['minValue'];		//promo stabilita dal fornitore
		$this->promoDateEnd = $jProduct['promoDate']['maxValue'];			//promo stabilita dal fornitore

		$this->siaeAmount = $jProduct['siaeAmount'];
		$this->raeeAmount = $jProduct['raeeAmount'];

		$this->weight = $jProduct['weight'];
		$this->volume = $jProduct['volume'];
		$this->width = $jProduct['dimX'];
		$this->height = $jProduct['dimY'];
		$this->length = $jProduct['dimZ'];

		$this->minLimitQty = $jProduct['minLimitQty'];

		$this->images = [];
		foreach ($jProduct['images'] as $jImage) {
			$this->images[] = new IO6Image($jImage);
		}

		$this->features = [];
		foreach ($jProduct['features'] as $jFeature) {
			$this->features[] = new IO6Feature($jFeature);
		}



		if (count($this->features)) {
			//TODO: EM20210825 => definire con quale ordine visualizzare i gruppi. IceCat li specifica ma ImporterONE no.
			// usort($this->features, function($a, $b) {
			// 	return strcmp($a->groupName, $b->groupName);
			// 	return $a->groupId - $b->groupId;
			// });
			$currentGroupName = '';

			switch((int)$io6_configuration->featuresHTMLTemplate) {
			  case 1:

          //BOOTSTRAP 4.X TEMPLATE
          $this->featuresHtml .= '<div class="card-columns"><div class="card features-group"><div class="card-body">';			
          foreach ($this->features as $feature) {
            if(!empty($feature->groupName) && $feature->groupName != $currentGroupName) {
              if($currentGroupName != '')
                $this->featuresHtml .= '</div></div><div class="card features-group"><div class="card-body">';
              $this->featuresHtml .= sprintf("<h3>%s</h3>", $feature->groupName);
              $currentGroupName = $feature->groupName;
            }
            $this->featuresHtml .= '<div class="d-flex justify-content-between feature">';
            $this->featuresHtml .= sprintf('<label>%s:</label>', $feature->name);
            $this->featuresHtml .= sprintf('<span class="value">%s%s</span>', $feature->value, $feature->unitMeasure);
            $this->featuresHtml .= "</div>";
          }
          if(!empty($currentGroupName))
            $this->featuresHtml .= "</div></div>";
          $this->featuresHtml .= "</div>";
          break;
			  case 2:

          //BOOTSTRAP 5.X TEMPLATE
          $this->featuresHtml .= '<div class="row row-cols-1 row-cols-md-2 g-4" data-masonry={"percentPosition": true}><div class="col"><div class="card"><div class="card-body">';			
          foreach ($this->features as $feature) {
            if(!empty($feature->groupName) && $feature->groupName != $currentGroupName) {
              if($currentGroupName != '')
                $this->featuresHtml .= '</div></div></div><div class="col"><div class="card"><div class="card-body">';
              $this->featuresHtml .= sprintf('<h3 class="card-title">%s</h3>', $feature->groupName);
              $currentGroupName = $feature->groupName;
            }
            $this->featuresHtml .= '<div class="card-text">';
            $this->featuresHtml .= sprintf('<label>%s:</label>', $feature->name);
            $this->featuresHtml .= sprintf('<span class="value">%s%s</span>', $feature->value, $feature->unitMeasure);
            $this->featuresHtml .= "</div>";
          }
          if(!empty($currentGroupName))
            $this->featuresHtml .= "</div></div></div>";
          $this->featuresHtml .= "</div>";
          break;
        default:

          //DEFAULT TEMPLATE
          $this->featuresHtml .= '<div class="features-html">';
          foreach ($this->features as $feature) {
            if (!empty($feature->groupName) && $feature->groupName != $currentGroupName) {
              if ($currentGroupName != '')
              $this->featuresHtml .= "</ul>";
              $this->featuresHtml .= sprintf("<h3>%s</h3>", $feature->groupName);
              $this->featuresHtml .= "<ul>";
              $currentGroupName = $feature->groupName;
            }
            $this->featuresHtml .= "<li>";
            $this->featuresHtml .= sprintf('<label>%s:</label>', $feature->name);
            $this->featuresHtml .= sprintf('<span class="value">%s%s</span>', $feature->value, $feature->unitMeasure);
            $this->featuresHtml .= "</li>";
          }
          if (!empty($currentGroupName))
          $this->featuresHtml .= "</ul>";
          $this->featuresHtml .= "</div>";
          break;
      }
    }
	}
}
