<?php

class MagentoApiHelperException extends Exception {}

class MagentoApiHelper
{
	private $client;
	private $session;

	private $magento_products;
	private $provu_products;

	private $attribute_sets;

	private $calls;

	public function MagentoApiHelper($uri = null)
	{
		$this->calls = array();
		$this->client = new SoapClient($uri);
		$this->attribute_sets = null;
	}

	public function loadProvuProductData($user = '', $pass = '')
	{
		$this->provu_products = array();

		$xml = simplexml_load_file("https://{$user}:{$pass}@secure.provu.co.uk/prosys/price_list.php?XML=yes&LongDesc=yes");
		$size = count($xml->line);
		for($i = 0; $i < $size; $i++)
		{
			$line = $xml->line[$i];

			$this->provu_products[] = array(
						'sku'					=> $line->item,
						'ean'					=> $line->ean,
						'class'					=> $line->class,
						'price'					=> $line->price_each,
						'short_description'		=> $line->description,
						'long_description'		=> $line->description_long,
						'stock'					=> (int)$line->free_stock,
						'weight'				=> ((empty($line->weight)) ? 1.00 : $line->weight)
					);
		}
	}

	public function getProvuProductData()
	{
		if($this->provu_products == null || !is_array($this->provu_products) || count($this->provu_products) == 0)
		{
			throw new MagentoApiHelperException('Requires product data to be loaded first. Must provide valid Prosys username and password.');
		}

		return $this->provu_products;
	}

	public function loadMagentoProductData()
	{
		$this->magento_products = $this->singleCall('product.list', array());
	}

	public function getMagentoProductData()
	{
		if($this->magento_products == null)
		{
			$this->loadMagentoProductData();
		}

		return $this->magento_products;
	}

	public function openSession($username = '', $password = '')
	{
		$this->session = $this->client->login($username, $password);
	}

	public function closeSession()
	{
		if($this->client != null)
		{
			$this->client->endSession($this->session);
		}
	}

	public function addCall($call)
	{
		$this->calls[] = $call;
	}

	public function getCalls()
	{
		return $this->calls;
	}

	public function makeCalls()
	{
		return $this->client->multiCall($this->session, $this->getCalls());
	}

	public function clearCalls()
	{
		unset($this->calls);
		$this->calls = array();
	}

	public function singleCall($call, $data = null)
	{
		return $this->client->call($this->session, $call, $data);
	}










/** Product Library **/
	public function addNewProduct(MagentoProduct $product)
	{
		if($this->attribute_sets == null)
		{
			$this->attribute_sets = $this->client->call($this->session, 'product_attribute_set.list');
		}
		$set = current($this->attribute_sets);

		$this->addCall(
				array(
					'product.create',
					array('simple', (int)$set['set_id'], $product->getSku(), $product->getProductData() )
				)
			);
	}

	public function updateExistingProduct(MagentoProduct $product)
	{
		$this->addCall(
				array(
					'product.update',
					array($product->getSku(), $product->getProductData(false) ) // discontinue product with 'discontinued'=>1
				)
			);
	}

	public function updateProductStock(MagentoProduct $product)
	{
		$this->addCall(
				array(
					'product_stock.update',
					array($product->getSku(), array('qty' => $product->getStock(), 'is_in_stock' => ($product->getStock()>0) ) )
				)
			);
	}

	public function updateProductPrice(MagentoProduct $product)
	{
		$this->addCall(
				array(
					'product.update',
					array($product->getSku(), array('price' => $product->getPrice() ) )
				)
			);
	}

	public function createProductImage(MagentoProduct $product, $imageData, $imageTypes = array('image', 'small_image', 'thumbnail'))
	{
		$newImage = array(
			'file' => array(
				'content' => $imageData,
				'mime'    => 'image/jpeg'
				),
			'label'    => $product->getName(),
			'position' => 0,
			'types'    => $imageTypes,
			'exclude'  => 1 // whether or not to be excluded from showing in 'More views' of product
		);

		$this->addCall(
			array(
				'product_media.create',
				array($product->getSku(), $newImage )
			)
		);
	}

	/*
	 * $imageFileName	string
	 * $imageData		array() // label, position, exclude, types
	 */
	public function updateProductImage(MagentoProduct $product, $imageFileName, $imageData)
	{
		$this->addCall(
			array(
				'product_media.update',
				array($product->getSku(), $imageFileName, $imageData )
			)
		);
	}

	public function isItemInMagentoDatabase($sku)
	{
		if($this->magento_products == null || !is_array($this->magento_products) || count($this->magento_products) == 0)
		{
			throw new MagentoApiHelperException('Product list has not been loaded.');
		}

		foreach($this->magento_products as $product)
		{
			if($product['sku'] == $sku) return true;
		}

		return false;
	}





/** Helper methods **/
	private function cleanseOrderNumber($orderId)
	{
		if(!intval($orderId))
		{
			throw new MagentoApiHelperException('Order ID invalid.');
		}
		else if ($orderId < 100000000)
		{
			$orderId += 100000000;
		}
		return $orderId;
	}









/** Sales Library **/
	public function holdOrder($orderId)
	{
		$orderId = $this->cleanseOrderNumber($orderId);

		$this->addCall(
				array(
					'sales_order_invoice.info',
					$orderId
				)
			);
	}


	public function shipOrder($orderId, $orderItems = array(), $comment = '')
	{
		$orderId = $this->cleanseOrderNumber($orderId);

		$this->addCall(
				array(
					'sales_order_shipment.create',
					array(
						$orderId,
						$orderItems,
						$comment,
						true,		// Send email?
						($comment!='')		// Include comment in email?
					)
				)
			);
	}

	public function orderDetails($orderId)
	{
		$orderId = $this->cleanseOrderNumber($orderId);

		$this->addCall(
				array(
					'sales_order.info',
					$orderId
				)
			);
	}

}

?>
