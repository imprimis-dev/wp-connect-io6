<?php
class IO6ConnectConfiguration
{
	public $apiToken;
	public $apiEndPoint;
	public $catalogId;

	public $priceListId;
	public $pageSize;

	public $selectedBrandField;
	public $selectedSkuField;
	public $selectedEanField;
	public $selectedPartNumberField;
	
	public $manageImages;
	public $manageFeatures;
	public $manageTitle;
	public $manageContent;
	public $manageExcerpt;
	public $manageCategories;
	public $managePrices;
	public $manageFeaturesHTML;
	public $manageTaxRule;
	public $concatFeaturesHTML;
  public $featuresHTMLTemplate;
	public $excludeNoImage;
	public $delayedDownloadsImages;
	public $imageLimit;
	public $excludeAvailLessThan;
	public $excludeAvailType;
	

	public $languageCode;
	public $tempFolder;

	function __construct($configuration) {		
		if (!array($configuration))
			throw new Exception('Configuration array is empty.');
		if (isset($configuration) && is_array($configuration) ) {
			$this->apiToken = array_key_exists('apitoken', $configuration) ? $configuration['apitoken'] : '';
			$this->apiEndPoint = array_key_exists('apiendpoint', $configuration) ? $configuration['apiendpoint'] : '';
			$this->catalogId = array_key_exists('catalog', $configuration) ? (int)$configuration['catalog'] : '';
			$this->languageCode = array_key_exists('languagecode', $configuration) ? $configuration['languagecode'] : '';
			$this->tempFolder = array_key_exists('tempfolder', $configuration) ? $configuration['tempfolder'] : '';
			
			$this->selectedBrandField = array_key_exists('select_brand_field', $configuration) ? $configuration['select_brand_field'] : 'io6_product_brand';
			$this->selectedSkuField = array_key_exists('select_sku_field', $configuration) ? $configuration['select_sku_field'] : 'io6_sku_productcode';
			$this->selectedEanField = array_key_exists('select_ean_field', $configuration) ? $configuration['select_ean_field'] : 'io6_eancode';
			$this->selectedPartNumberField = array_key_exists('select_partnumber_field', $configuration) ? $configuration['select_partnumber_field'] : 'io6_partnumber';

			$this->priceListId = array_key_exists('price_list', $configuration) ? (int)$configuration['price_list'] : 0;
			$this->pageSize = array_key_exists('page_size', $configuration) ? (int)$configuration['page_size'] : 25;
	
			$this->manageImages = array_key_exists('manage_images', $configuration) ? $configuration['manage_images'] == 1 : false;
			$this->manageFeatures = array_key_exists('manage_features', $configuration) ? $configuration['manage_features'] == 1 : false;
			$this->manageTitle = array_key_exists('manage_title', $configuration) ? $configuration['manage_title'] == 1 : false;
			$this->managePrices = array_key_exists('manage_prices', $configuration) ? $configuration['manage_prices'] == 1 : false;
			$this->manageContent = array_key_exists('manage_content', $configuration) ? $configuration['manage_content'] == 1 : false;
			$this->manageExcerpt = array_key_exists('manage_excerpt', $configuration) ? $configuration['manage_excerpt'] == 1 : false;
			$this->manageCategories = array_key_exists('manage_categories', $configuration) ? $configuration['manage_categories'] == 1 : false;
			$this->manageFeaturesHTML = array_key_exists('manage_features_html', $configuration) ? $configuration['manage_features_html'] == 1 : false;
			$this->manageTaxRule = array_key_exists('manage_tax_rule', $configuration) ? $configuration['manage_tax_rule'] == 1 : false;
			$this->concatFeaturesHTML = array_key_exists('concat_features_html', $configuration) ? $configuration['concat_features_html'] == 1 : false;
			$this->featuresHTMLTemplate = array_key_exists('features_html_template', $configuration) ? $configuration['features_html_template'] : 0;
			$this->excludeNoImage = array_key_exists('exclude_noimage', $configuration) ? $configuration['exclude_noimage'] == 1 : false;
			$this->delayedDownloadsImages = array_key_exists('delayed_downloads_images', $configuration) ? $configuration['delayed_downloads_images'] == 1 : false;
			$this->imageLimit = array_key_exists('image_limit', $configuration) ? (int)$configuration['image_limit'] : 0;

			$this->excludeAvailLessThan = array_key_exists('exclude_avail_lessthan', $configuration) ? (int)$configuration['exclude_avail_lessthan'] : 0;
			$this->excludeAvailType = array_key_exists('exclude_avail_type', $configuration) ? (int)$configuration['exclude_avail_type'] : 0;
		}
		else {

			$this->selectedBrandField = 'io6_product_brand';
			$this->selectedSkuField = 'io6_sku_partNumber';
			$this->selectedEanField = 'io6_eancode';
			$this->selectedPartNumberField = 'io6_partnumber';

			$this->priceListId = 0;
			$this->pageSize = 25;
	
			$this->manageImages = true;
			$this->manageFeatures = true;
			$this->manageTitle = true;
			$this->managePrices = true;
			$this->manageContent = true;
			$this->manageExcerpt = true;
			$this->manageCategories = true;
			$this->manageFeaturesHTML = true;
			$this->manageTaxRule = true;
			$this->concatFeaturesHTML = true;
      $this->featuresHTMLTemplate = 0;
			$this->excludeNoImage = true;
			$this->delayedDownloadsImages = false;
			$this->imageLimit = 0;

			$this->excludeAvailLessThan = 0;
			$this->excludeAvailType = 0;

		}
	}
}
