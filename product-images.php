<?php

/*header('Content-Type: text/plain');
ignore_user_abort(true);
set_time_limit(-1);*/

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
			$product->setClass($data['class']);
			$product->setName($product->generateProductName()); // used as there is no direct 'name' field

			if($helper->isItemInMagentoDatabase($product->getSku()))
			{
				$file = sprintf($config['local_media'], $product->getSku());

				if(file_exists($file) && is_readable($file))
				{
					$media_info = $helper->singleCall('product_media.list', $product->getSku());

					if(count($media_info) == 0)
					{
						$helper->createProductImage($product, base64_encode(file_get_contents($file)));
					}
					// Does not appear to be possible to update the image file data
					/*else
					{
						$helper->updateProductImage($product,
							$media_info[0]['file'],
							array('file' =>
									array(
										'content' => base64_encode(file_get_contents($file)),
										'mime'    => 'image/jpeg'
										),
									'types' => array('image', 'small_image', 'thumbnail')
								)
						);
					}*/
				}
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