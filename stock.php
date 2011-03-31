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
		if($helper->isItemInMagentoDatabase($data['sku']))
		{
			try
			{
				$product = new MagentoProduct($data['sku']);
				$product->setStock($data['stock']);

				$helper->updateProductStock($product);
			}
			catch(MagentoProductException $e) {}
		}
	}

	$helper->makeCalls();
	$helper->closeSession();
}
catch (SoapException $e) {}
catch (SoapFault $e) {}
catch (MagentoApiHelperException $e) {}

?>