<?php

@require('config.php');
@require('MagentoProduct.class.php');
@require('MagentoApiHelper.class.php');

try
{
	$helper = new MagentoApiHelper($config['api']['url']);

	$helper->loadProvuProductData($config['prosys']['username'], $config['prosys']['password']);
	$products = $helper->getProvuProductData();


	$helper->openSession($config['api']['username'], $config['api']['password']);

	$magento_products = $helper->getMagentoProductData();

	foreach($products as $data)
	{
		try
		{
			$product = new MagentoProduct($data['sku']);
			$product->setEan($data['ean']);
			$product->setClass($data['class']);
			$product->setName($product->generateProductName()); // used as there is no direct 'name' field
			$product->setPrice($data['price']);
			$product->setWeight($data['weight']);
			$product->setStock($data['stock']);
			$product->setShortDescription($data['short_description']);
			$product->setLongDescription($data['long_description']);

			if($helper->isItemInMagentoDatabase($product->getSku()))
			{
				$helper->updateExistingProduct($product);
			}
			else
			{
				$helper->addNewProduct($product);
			}
		}
		catch(MagentoProductException $e) {}
	}

	$helper->makeCalls();
	$helper->closeSession();
}
catch (SoapException $e) {}
catch (SoapFault $e) {}
catch (MagentoApiHelperException $e) {}

?>