<?php
require_once('IO6ConnectConfiguration.php');
require_once('IO6Category.php');
require_once('IO6Brand.php');
require_once('IO6PriceList.php');
require_once('IO6Product.php');
require_once('IO6Catalog.php');
require_once('IO6Supplier.php');

class IO6ConnectEngine {
  private  $configuration;

    //region Metodi
    function __construct(IO6ConnectConfiguration $configuration) {
			$this->configuration = $configuration;
    }

	public function CheckApiConnection() {
		try {
			$jResponse = $this->callIO6API('catalogs', 'GET'); //Eseguo una qualsiasi chiamata verso l'API

			return ($jResponse !== false);
				
		}
		catch(Exception $lEx) {
			throw $lEx; 
		}

		return false;
	}
	
	public function GetIO6Catalogs() {
		$catalogs = [];
		$jCatalogs = $this->callIO6API('catalogs', 'GET');

		 if(empty($jCatalogs))
		 	$jCatalogs = [];

		foreach($jCatalogs as $jCatalog) {			
			$catalogs[] = new IO6Catalog($jCatalog);
		}

		return $catalogs;
	}

		/**
		 * Return array of IO6Supplier for current Catalog from API Call
		 * @return array IO6Supplier
		 */
		public function GetIO6Suppliers() {
			$parameters = [];
			$parameters['onlyActive'] = 'true';	
			$parameters['personalCatalogId'] = $this->configuration->catalogId;	
			$jSuppliers = $this->callIO6API('suppliers', 'GET', $parameters);
			$suppliers = [];
			foreach($jSuppliers as $jSupplier) {			
				$suppliers[] = new IO6Supplier($jSupplier);
			}

			return $suppliers;
		}

		/**
		 * Return array of IO6Category from API Call
		 * @since 1.0.0
		 *
		 * @param int $parentCategoryId ParentCategory to retrieve.
		 * @return array IO6Category
		 */
		public function GetIO6Categories($parentCategoryId = 0) {
			$parameters = [];
			$parameters['parentId'] = $parentCategoryId;		
			$jCategories = $this->callIO6API(sprintf('catalogs/%s/categories/list', $this->configuration->catalogId), 'POST', $parameters);

			$categories = array();

			foreach($jCategories['items'] as $jCategory) {			
				$subCategories = [];					
				if($jCategory['hasChildCategories'] == true)
					$subCategories =	$this->GetIO6Categories($jCategory['id']);
				
				$categories[] = new IO6Category($jCategory, $subCategories);
			}

			return $categories;
		}

		public function GetIO6Brands() {
			$parameters = [];
			$jBrands = $this->callIO6API(sprintf('catalogs/%s/brands/list', $this->configuration->catalogId), 'POST', $parameters);

			$brands = array();

			foreach($jBrands['items'] as $jBrand) {							
				$brands[] = new IO6Brand($jBrand);
			}

			return $brands;
		}

		public function GetIO6Products($currentPage = 1) {
			$retValue = array();
			$parameters = [];
			$parameters['priceListId'] = $this->configuration->priceListId;
			$parameters['pageSize'] = $this->configuration->pageSize;
			$parameters['currentPage'] = $currentPage;
			$parameters['imagelimit'] = $this->configuration->imageLimit;
			$parameters['featuresSearch'] = $this->configuration->manageFeaturesHTML ? 2 : ($this->configuration->manageFeatures ? 1 : 0);
			$parameters['calculateFoundRows'] = true;
			$parameters['excludeAvailLessThan'] = $this->configuration->excludeAvailLessThan;
			$parameters['excludeAvailType'] = $this->configuration->excludeAvailType;
			$parameters['imagesSearch'] = $this->configuration->excludeNoImage ? 1 : 0;
			$parameters['isActive'] = 1;
			$parameters['isObsolete'] = 0;

			//$parameters['ean'] = '0606449133332';

			$results = $this->callIO6API(sprintf('catalogs/%s/products/search', $this->configuration->catalogId), 'POST', $parameters);

			$products = array();

			foreach($results['items'] as $jProduct) {							
				$products[] = new IO6Product($jProduct, $this->configuration);
			}

			$retValue['products'] = $products;
			$retValue['pages'] = $results['pages'];
			$retValue['elementsFounds'] = $results['elementsFounds'];
			return $retValue;
		}

		public function GetIO6PriceLists() {
			$parameters = [];
			$jPriceLists = $this->callIO6API(sprintf('catalogs/%s/pricelists', $this->configuration->catalogId), 'GET', $parameters);
			
			if(is_array($jPriceLists)) {
				foreach($jPriceLists as $jPriceList) {							
					$priceLists[] = new IO6PriceList($jPriceList);
				}
			}

			return $priceLists;
		}

		public function TestAPI($endPoint, $api_token) {
			$results = array();

			try {
				$retValue = $this->callIO6API('catalogs', 'GET', null, $endPoint, $api_token);
				$results['response']['catalogs']['passed'] = $retValue === false ? false : true;
				$results['response']['catalogs']['total'] = count($retValue);
			}
			catch(Exception $ex) {
				$results['response']['catalogs']['passed'] = false;
			}
			


			try {
				$parameters = [];
				//$parameters['priceListId'] = $this->configuration->priceListId;
				$parameters['pageSize'] = 5;
				$parameters['currentPage'] = 1;
				$parameters['imagelimit'] = 1;
				$parameters['featuresSearch'] = 0;
				$parameters['calculateFoundRows'] = true;			
				$parameters['imagesSearch'] = 0;
				$parameters['isActive'] = 1;
				$parameters['isObsolete'] = 0;

				$retValue = $this->callIO6API(sprintf('catalogs/%s/products/search', $this->configuration->catalogId), 'POST', $parameters, $endPoint, $api_token);

				$results['response']['products']['passed'] = $retValue === false ? false : true;;
				$results['response']['products']['total'] = $retValue['elementsFounds'];
			}
			catch(Exception $ex) {
				$results['response']['products']['passed'] = false;
			}
			
			return $results;
		}

	
		private function callIO6API($action, $method = 'POST', array $parameters = null, $endPoint = null, $api_token = null) {
			$api_token = $api_token == null ? $this->configuration->apiToken : $api_token;
			$endPoint = $endPoint == null ? rtrim($this->configuration->apiEndPoint, '/') : $endPoint;
		

			 $requestUrl = "$endPoint/$action";
			 if($method === 'GET' && isset($parameters) && !empty($parameters))
			 	$requestUrl .= (strpos($requestUrl, '?') === false  ? '?' :'&' ) . http_build_query($parameters);
				
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					"Authorization: Bearer $api_token",
					'Content-Type: application/json: application/json'
			));
			
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_URL, $requestUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
			if($method === 'POST') {
				curl_setopt($ch, CURLOPT_POST, true);
				if(isset($parameters) && !empty($parameters))					
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
				else
					curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
			}
			$response = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
			if(intval($httpcode) >= 400) {
				$output = false;
			}
			else if (curl_errno($ch)){
				 throw new Exception("Errore di comunicazione con le API di ImporterONE. " . curl_error($ch), curl_errno($ch));
			}
			else{
				$output =	json_decode($response, true);
			}
			curl_close($ch);
		
			return $output;
		}

  }
