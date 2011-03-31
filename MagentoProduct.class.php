<?php

class MagentoProduct
{
	const PRODUCT_STATUS_ENABLED	= 1;
	const PRODUCT_STATUS_DISABLED	= 2;

	const TAX_CLASS_NONE			= 0;
	const TAX_CLASS_TAXABLE_GOODS	= 2;
	const TAX_CLASS_SHIPPING		= 4;

	private $sku;
	private $ean;
	private $name;
	private $class;
	private $stock;
	private $short_description;
	private $long_description;
	private $price;
	private $weight;

	public function MagentoProduct($sku = null)
	{
		$this->setSku($sku);
		$this->ean = null;
		$this->name = null;
		$this->class = null;
		$this->stock = 0;
		$this->short_description = null;
		$this->long_description = null;
		$this->price = null;
		$this->weight = null;
	}

	public function setSku($sku)
	{
		if($sku == null)
		{
			throw new MagentoProductException('Invalid product SKU.');
		}

		$this->sku = (string)$sku;
	}

	public function getSku()
	{
		if($this->sku == null)
		{
			throw new MagentoProductException('Product SKU has not been set.');
		}

		return $this->sku;
	}

	public function setEan($ean = '')
	{
		if(!isset($ean))
		{
			throw new MagentoProductException('Invalid product EAN.');
		}

		$this->ean = (string)$ean;
	}

	public function getEan()
	{
		if($this->ean == null)
		{
			return '';
		}

		return $this->ean;
	}

	public function setName($name)
	{
		if($name == null || empty($name) || strlen($name) == 0)
		{
			throw new MagentoProductException('Invalid product name.');
		}

		$this->name = (string)$name;
	}

	public function getName()
	{
		if($this->name == null)
		{
			throw new MagentoProductException('Product name must be set.');
		}

		return $this->name;
	}

	public function setClass($class)
	{
		if($class == null || empty($class) || strlen($class) == 0)
		{
			throw new MagentoProductException('Invalid product class.');
		}

		$this->class = (string)$class;
	}

	public function getClass()
	{
		if($this->class == null)
		{
			throw new MagentoProductException('Product class must be set.');
		}

		return $this->class;
	}

	public function setStock($stock)
	{
		if(!is_numeric($stock))
		{
			throw new MagentoProductException('Stock value must be an integer.');
		}

		$this->stock = intval($stock);
	}

	public function getStock()
	{
		return $this->stock;
	}

	public function setShortDescription($short_description)
	{
		$this->short_description = (string)$short_description;
	}

	public function getShortDescription()
	{
		if($this->short_description == null)
		{
			throw new MagentoProductException('Short description must be set (it\'s too short!).');
		}

		return $this->short_description;
	}

	public function setLongDescription($long_description)
	{
		$this->long_description = (string)$long_description;
	}

	public function getLongDescription()
	{
		if($this->long_description == null || empty($this->long_description) || strlen($this->long_description)==0)
		{
			throw new MagentoProductException('Long description must be set.');
		}

		return $this->long_description;
	}

	public function setPrice($price)
	{
		$price = floatval($price);

		if(empty($price) || $price == '' || $price == 0)
		{
			throw new MagentoProductException('Invalid price value.');
		}

		$this->price = $price;
	}

	public function getPrice()
	{
		if($this->price == null)
		{
			throw new MagentoProductException('Price must be set.');
		}
		else if ($this->price == 0)
		{
			throw new MagentoProductException('Product price cannot be zero.');
		}

		return $this->price;
	}

	public function setWeight($weight)
	{
		$weight = floatval($weight);

		if(empty($weight) || $weight == '' || $weight == 0)
		{
			throw new MagentoProductException('Invalid weight value.');
		}

		$this->weight = $weight;
	}

	public function getWeight()
	{
		if($this->weight == null)
		{
			throw new MagentoProductException('Weight must be set.');
		}

		return $this->weight;
	}

	public function getProductData($include_generic = true)
	{
		$generic = array(
			// websites - Array of website ids to which you want to assign a new product
			'websites'          => array(1), // array(1,2,3,...)
			'status'			=> self::PRODUCT_STATUS_ENABLED,
			'tax_class_id'		=> self::TAX_CLASS_TAXABLE_GOODS
		);

		// Product data keys need to match up with magento keys
		$product_data = array();

		// use try-catch blocks as missing keys causes entire product insertion to fail
		try {
			$product_data['ean'] = $this->getEan();
		} catch (MagentoProductException $e) {}
		try {
			$product_data['name'] = $this->getName();
		} catch (MagentoProductException $e) {}
		try {
			$product_data['weight'] = $this->getWeight();
		} catch (MagentoProductException $e) {}
		try {
			$product_data['short_description'] = $this->getShortDescription();
		} catch (MagentoProductException $e) {}
		try {
			$product_data['description'] = $this->getLongDescription();
		} catch (MagentoProductException $e) {}
		try {
			$product_data['price'] = $this->getPrice();
		} catch (MagentoProductException $e) {}
		try {
			$product_data['stock_data'] = array('qty' => $this->getStock(), 'is_in_stock' => ($this->getStock()>0), 'use_config_manage_stock' => 1);
		} catch (MagentoProductException $e) {}

/*if($this->getClass()=='snom')
{
	$product_data['status'] = self::PRODUCT_STATUS_DISABLED;
	//$product_data['manufacturer']=2;//ucwords(strtolower($this->getClass()));
}*/
		return (($include_generic === true) ? array_merge($generic, $product_data) : $product_data);
	}

	public function generateProductName()
	{
		$name = str_replace(array('-','_'), array(' ',' '), $this->getSku());

		switch(strtolower($this->getClass()))
		{
			case 'snom':
				$name = preg_replace('/^PVSnom/', 'Snom', $name);
				$name = preg_replace('/Snom([0-9][0-9][0-9])/', 'Snom $1', $name);
			break;

			case 'avm':
				$name = preg_replace('/^avm\s?/i', '', $name);
				$name = strtoupper($this->getClass())." {$name}";
			break;

			case 'cisco':
			case 'yealink':
				$name = ucwords(strtolower($this->getClass()))." {$name}";
			break;

			case 'gstream':
				$name = "Grandstream {$name}";
			break;

			case 'siemens':
				$name = "Gigaset {$name}";
			break;

			default:
			break;
		}

		return $name;
	}
}

class MagentoProductException extends Exception {}

?>