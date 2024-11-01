<?php
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

require_once( USAM_FILE_PATH.'/includes/crm/comment.class.php' );

global $wpdb;
$user_id = get_current_user_id(); 
set_time_limit(3800);

$anonymous_function = function($a) { return true; };	
add_filter( 'http_request_host_is_external', $anonymous_function, 10, 3 );

$image_url = "http://wp-universam.ru/wp-content/uploads/sl/downloadables/fotos/universam/";

$import = false;
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugins = get_plugins();			
foreach ( $plugins  as $pluginname => $plugin ) 
{			
	if ( stripos($pluginname, 'woocommerce') !== false && is_plugin_active( $pluginname ) ) 
	{
		if ( usam_upload_plugin('woo-to-universam-product-exporter') && class_exists('MyClass') )
		{
			$e = new Woo_to_Universam_Product_Exporter();
			$e->start();
			
		//	$import = true;
		}
	//	if ( is_plugin_active( $pluginname ) ) 
		//deactivate_plugins( $pluginname );
	}
}	
if ( !$import )
{
	$new_brands = array(
		array( 'title' => 'Absolut Joy', 'img' => '', 'args' => array( 'description' => 'История компании Nuova R2S берет начало в пятидесятых годах прошлого века, когда основатели фабрики решили организовать производство небьющейся посуды и товаров для детей.', 'parent' => 0, )),
		array( 'title' => 'Ana Lublin',  'img' => '','args' => array( 'description' => 'Бренд обуви Ana Lublin – это удобные модели для стильных и уверенных в себе представительниц женского пола. Ana Lublin отлично подходит, как обувь, в которой можно ходить на работу, в театр, ресторан, музей, на прогулку, вечеринку и просто носить в повседневной жизни. Среди моделей вы найдете: туфли, ботинки, кроссовки и многое другое. В линейках бренда присутствует обувь как из натуральной кожи и нубука, так и из качественного экологического кожзаменителя, который не вызывает аллергических реакций и сохраняет свой первозданный внешний вид долгое время. Это отличная обувь для того, чтобы произвести хорошее впечатления, показать тонкое чувство стиля и гармонизировать свой образ..', 'parent' => 0 )),
		array( 'title' => 'Bosccolo',  'img' => '','args' => array( 'description' => 'Bosccolo – это польский бренд, под маркой которого изготавливается женская обувь, выполненная в лучших традициях романтического, классического и делового стилей. Изделия марки отличаются пастельным цветовым решением, безупречным качеством и минимальным декоративным оформлением, которое прослеживается в фигурных строчках, перфорации или металлической фурнитуре.Концепция дизайнеров польской компании, работающих над расширением актуального ассортимента, заключается в консервативных фасонах, соответствии модным трендам сезона и неизменно удобной колодке. Коллекции бренда Bosccolo обновляются каждые три месяца.Основу модельного ряда составляют:', 'parent' => 0 )),
		array( 'title' => 'Burton', 'img' => '', 'args' => array( 'description' => 'Марка Burton в настоящее время представлена одной из лучших компаний по выпуску оборудования для катания на сноуборде, а также спортивной одежды для зимних видов спорта. Burton выпускает более 100 наименований товаров: это доски, обувь, спортивная форма, очки, крепления, шлемы для девушек, парней и детей.', 'parent' => 0)),
		array( 'title' => 'Lacoste', 'img' => 'lacoste.jpg', 'args' => array( 'description' =>'', 'parent' => 0)),
		array( 'title' => 'Prada', 'img' => 'prada.jpg', 'args' => array( 'description' =>'', 'parent' => 0)),	
		array( 'title' => 'Gardeur', 'img' => 'gardeur.png', 'args' => array( 'description' =>'', 'parent' => 0)),
	);
	$brands = array();
	foreach ( $new_brands as $brand ) 
	{
		$term = wp_insert_term( $brand['title'], 'usam-brands', $brand['args'] );
		if ( !is_wp_error($term) ) 
		{
			if ( !empty($brand['img']) ) 
			{
				$url     = $image_url.'brands/'.$brand['img'];
				$desc    = "";
				
				$file_array = array();
				$tmp = download_url( $url );
					
				preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches );
				$file_array['name'] = basename( $matches[0] );
				$file_array['tmp_name'] = $tmp;	
				$thumbnail_id = media_handle_sideload( $file_array, 0, $desc );	
			
				if ( is_numeric($thumbnail_id) )
					update_term_meta( $term['term_id'], 'thumbnail', $thumbnail_id );
			}		
			$brands[] = $term['term_id'];
		}
		else
		{
			
			$term = get_term_by( 'name', $brand['title'], 'usam-brands' );			
			$brands[] = $term->term_id;
		}
	}
	$new_category_sale = array(
		array( 'title' => 'Скидка 20% на "Bosccolo"', 'img' => '5tiubs.jpg', 'args' => array( 'description' => 'Скидка 20% на весь ассортимент торговых марок "Bosccolo"', 'parent' => 0)),
		array( 'title' => 'Скидка новым покупателям!', 'img' => '2.jpg', 'args' => array( 'description' => 'Каждому новому покупателю, при оформлении заказа с доставкой курьером, мы дарим дополнительную СКИДКУ 5%!', 'parent' => 0)),
	);
	$category_sale = array();
	foreach ( $new_category_sale as $category ) 
	{
		$term = wp_insert_term( $category['title'], 'usam-category_sale', $category['args'] );	
		if ( !is_wp_error($term) ) 		
		{		
			if ( !empty($category['img']) ) 
			{
				$url     = $image_url.$category['img'];
				$desc    = "";
				
				$file_array = array();
				$tmp = download_url( $url );
					
				preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches );
				$file_array['name'] = basename( $matches[0] );
				$file_array['tmp_name'] = $tmp;	
				$thumbnail_id = media_handle_sideload( $file_array, 0, $desc );	
			
				if ( is_numeric($thumbnail_id) )
					update_term_meta( $term['term_id'], 'thumbnail', $thumbnail_id );
			}
			$category_sale[] = $term['term_id'];
			usam_update_term_metadata($term['term_id'], 'status_stock', 1 );
		}
		else
		{
			$term = get_term_by( 'name', $category['title'], 'usam-category_sale' );
			$category_sale[] = $term->term_id;
		}
	}	
	$new_categories = array(
		array( 'title' => 'Нижнее билье', 'img' => '890809.jpg','args' => array( 'description' => 'Женское нижнее белье – это скрытая, но очень важная деталь любого образа. В правильно подобранном комплекте Вы будете чувствовать себя уверенно и комфортно. Нижнее белье способно преобразить любую девушку, аккуратно скрыв несовершенства фигуры и сделав акцент на достоинствах.', 'parent' => 0 )),
		array( 'title' => 'Платья', 'img' => '468413_468413_1.jpg', 'args' => array( 'description' => 'Платья — это атрибут каждой женщины. Летом, желая подчеркнуть красивый загар, мы открываем плечи и выбираем нежные, воздушные ткани. ', 'parent' => 0)),
		array( 'title' => 'Обувь', 'img' => '1002375.jpg', 'args' => array( 'description' => '', 'parent' => 0 )),
		array( 'title' => 'Сумки', 'img' => '54175-0.jpg', 'args' => array( 'description' => '', 'parent' => 0 )),
		array( 'title' => 'Брюки', 'img' => 'bruki.jpg', 'args' => array( 'description' => '', 'parent' => 0 )),
	);
	$categories = array();
	foreach ( $new_categories as $category ) 
	{
		$term = wp_insert_term( $category['title'], 'usam-category', $category['args'] );
		if ( !is_wp_error($term) ) 		
		{			
			if ( !empty($category['img']) ) 
			{
				$url     = $image_url.'category/'.$category['img'];
				$desc    = "";
				
				$file_array = array();
				$tmp = download_url( $url );
					
				preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches );
				$file_array['name'] = basename( $matches[0] );
				$file_array['tmp_name'] = $tmp;	
				$thumbnail_id = media_handle_sideload( $file_array, 0, $desc );	
				if ( is_numeric($thumbnail_id) )
					update_term_meta( $term['term_id'], 'thumbnail', $thumbnail_id );
			}
			$categories[] = $term['term_id'];
		}
		else
		{
			$term = get_term_by( 'name', $category['title'], 'usam-category' );
			$categories[] = $term->term_id;
		}
	}	

	$products_data = array(
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Комплект нижнего билья Bunny',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[0]), 'usam-brands' => array($brands[0]), 'usam-category_sale' => array($category_sale[0]) ),
			'thumbnail' => $image_url.'products/6586.jpg',
			'product_stock' => array( 'storage_1' => 23 ),		
			'prices' => array( 'price_tp_1' => 5350 ),		
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),			
			'productmeta' => array(														
				'code' => '',											
				'sku' => '6586',
				'barcode' => '',		
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1	
			),			
		),	
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Халат Bunny',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[0]), 'usam-brands' => array($brands[0]), 'usam-category_sale' => array($category_sale[0]) ),
			'thumbnail' => $image_url.'products/80809-0.jpg',
			'product_stock' => array( 'storage_1' => 23 ),
			'prices' => array( 'price_tp_1' => 834 ),
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
			'productmeta' => array(														
				'code' => '',											
				'sku' => '80809-0',
				'barcode' => '',
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1
			),			
		),
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Платье',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[1]), 'usam-brands' => array($brands[0]) ),
			'thumbnail' => $image_url.'products/67567.jpg',
			'product_stock' => array( 'storage_1' => 23 ),
			'prices' => array( 'price_tp_1' => 3789 ),
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
			'productmeta' => array(														
				'code' => '',											
				'sku' => '67567',
				'barcode' => '',	
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1
			),			
		),
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Брюки',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[4]), 'usam-brands' => array($brands[1]) ),
			'thumbnail' => $image_url.'products/bruki-444.jpg',
			'product_stock' => array( 'storage_1' => 23 ),
			'prices' => array( 'price_tp_1' => 975 ),
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3),
			'productmeta' => array(														
				'code' => '',											
				'sku' => '444',
				'barcode' => '',	
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1
			),			
		),	
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Платье',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[1]), 'usam-brands' => array($brands[1]) ),
			'thumbnail' => $image_url.'products/879789.jpg',
			'product_stock' => array( 'storage_1' => 23 ),
			'prices' => array( 'price_tp_1' => 4567 ),
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
			'productmeta' => array(														
				'code' => '',											
				'sku' => '879789',
				'barcode' => '',	
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1							
			),			
		),		
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Платье',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[1]), 'usam-brands' => array($brands[1]) ),
			'thumbnail' => $image_url.'products/6786789.jpg',
			'product_stock' => array( 'storage_1' => 23 ),
			'prices' => array( 'price_tp_1' => 3578 ),
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
			'productmeta' => array(														
				'code' => '',											
				'sku' => '6786789',
				'barcode' => '',	
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1							
			),			
		),	
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Платье',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[1]), 'usam-brands' => array($brands[1]) ),
			'thumbnail' => $image_url.'products/7898707.jpg',
			'product_stock' => array( 'storage_1' => 23 ),
			'prices' => array( 'price_tp_1' => 4569 ),
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
			'productmeta' => array(														
				'code' => '',											
				'sku' => '7898707',
				'barcode' => '',				
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1
			),			
		),	
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Платье',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[1]), 'usam-brands' => array($brands[1]) ),
			'thumbnail' => $image_url.'products/78097807.jpg',
			'product_stock' => array( 'storage_1' => 23 ),
			'prices' => array( 'price_tp_1' => 6805 ),
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
			'sticky' => 1,
			'productmeta' => array(	
				'code' => '',											
				'sku' => '78097807',
				'barcode' => '',	
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1									
			),			
		),		
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Платье',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[1]), 'usam-brands' => array($brands[1]) ),
			'thumbnail' => $image_url.'products/78978908.jpg',
			'product_stock' => array( 'storage_1' => 23 ),
			'prices' => array( 'price_tp_1' => 467 ),
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3),
			'sticky' => 1,
			'productmeta' => array(			
				'code' => '',											
				'sku' => '78978908',
				'barcode' => '',	
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1	
			),			
		),	
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Платье Redg',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[1]), 'usam-brands' => array($brands[1]) ),
			'thumbnail' => $image_url.'products/547568.jpg',
			'product_stock' => array( 'storage_1' => 23 ),
			'prices' => array( 'price_tp_1' => 890 ),
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
			'productmeta' => array(														
				'code' => '',											
				'sku' => '547568',
				'barcode' => '',		
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1	
			),			
		),	
		array(		
			'post_content'   => '',
			'post_excerpt'   => '',
			'post_title'     => 'Платье',
			'post_status'    => 'publish',	
			'post_type'      => "usam-product",
			'tax_input' => array('usam-category' => array($categories[1]), 'usam-brands' => array($brands[1]) ),
			'thumbnail' => $image_url.'products/79780.jpg',
			'product_stock' => array( 'storage_1' => 23 ),
			'prices' => array( 'price_tp_1' => 700 ),
			'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3),
			'sticky' => 1,
			'productmeta' => array(														
				'code' => '',		
				'sku' => '79780',
				'barcode' => '',	
				'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1	
			),			
		),		
		array(		
				'post_content'   => '',
				'post_excerpt'   => '',
				'post_title'     => 'Туфли Alessio Nesca',
				'post_status'    => 'publish',	
				'post_type'      => "usam-product",
				'tax_input' => array('usam-category' => array($categories[2]), 'usam-brands' => array($brands[1]) ),
				'thumbnail' => $image_url.'products/780780987.jpg',
				'product_stock' => array( 'storage_1' => 23 ),
				'prices' => array( 'price_tp_1' => 1350 ),
				'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
				'productmeta' => array(														
					'code' => '',											
					'sku' => '780780987',
					'barcode' => '',		
					'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1
				),			
			),		
		array(		
				'post_content'   => '',
				'post_excerpt'   => '',
				'post_title'     => 'Крассовки ASICS Tiger Gel-Lyte',
				'post_status'    => 'publish',	
				'post_type'      => "usam-product",
				'tax_input' => array('usam-category' => array($categories[2]), 'usam-brands' => array($brands[2]) ),
				'thumbnail' => $image_url.'products/78785876.jpg',
				'product_stock' => array( 'storage_1' => 23 ),
				'prices' => array( 'price_tp_1' => 3350 ),
				'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
				'sticky' => 1,
				'productmeta' => array(				
					'code' => '',											
					'sku' => '78785876',
					'barcode' => '',
					'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1
				),			
			),	
		array(		
				'post_content'   => '',
				'post_excerpt'   => '',
				'post_title'     => 'Мужские кроссовки Puma RS-0 SOUND',
				'post_status'    => 'publish',	
				'post_type'      => "usam-product",
				'tax_input' => array('usam-category' => array($categories[2]), 'usam-brands' => array($brands[0]) ),		
				'thumbnail' => $image_url.'products/King_Street_Sneak.jpg',	
				'product_stock' => array( 'storage_1' => 23 ),
				'prices' => array( 'price_tp_1' => 2350 ),
				'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
				'sticky' => 1,
				'productmeta' => array(		
					'code' => '',											
					'sku' => 'King_Street_Sneak',
					'barcode' => '',
					'weight' => 0, 'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1
				),			
			),
				array(		
				'post_content'   => '',
				'post_excerpt'   => '',
				'post_title'     => 'Сумка DKNY',
				'post_status'    => 'publish',	
				'post_type'      => "usam-product",
				'tax_input' => array('usam-category' => array($categories[3]), 'usam-brands' => array($brands[2]) ),		
				'thumbnail' => $image_url.'products/sumki-dkny-16.jpg',	
				'product_stock' => array( 'storage_1' => 23 ),
				'prices' => array( 'price_tp_1' => 2350 ),
				'postmeta' => array('views' => 60, 'rating' => 5, 'rating_count' => 3 ),
				'sticky' => 1,
				'productmeta' => array(			
					'code' => '',											
					'sku' => 'sumki-dkny-16',
					'barcode' => '',
					'weight' => 0,		
					'height' => 0,'width'=> 0,'length'=> 0, 'unit_measure' => 'thing', 'unit' => 1
				),			
			),
		);		
	$product_ids = array();
	foreach ( $products_data as $key => $product ) 
	{ 	
		$_product = new USAM_Product( $product );		
		$product_id = $_product->insert_product();	
		$_product->insert_media();
		$product_ids[] = $product_id;
	}
	/* Создание заказов */
	/* Заказ №1*/
	$data = array( 'totalprice' => 0, 'type_price' => 'tp_1','paid' => 0,'status' => 'received', 'type_payer' => 1,'user_ID' => $user_id, 'source' => 'order');	

	$order_products = array( 
	array( 'product_id' => $product_ids[0], 'name' => 'Туфли Alessio Nesca', 'order_id' => 1,'old_price' => 0,'price' => 608, 'quantity' => 1 ) , 
	array( 'product_id' => $product_ids[1], 'name' => 'Мужские кроссовки Puma RS-0 SOUND', 'order_id' => 1,'old_price' => 0,'price' => 308, 'quantity' => 10 ) );	
	
	$order_id = usam_insert_order( $data, $order_products );
	
	$new_document = array( 'method' => 2, 'storage' => 1, 'storage_pickup' => 1, 'order_id' => $order_id );
	$order_products = usam_get_products_order( $order_id );	
	$shipped_document_products = array();
	foreach ( $order_products as $product ) 
	{						
		$shipped_document_products[] = array( 'product_id' => $product->product_id, 'quantity' => $product->quantity, 'reserve' => $product->quantity );
	}		
	usam_insert_shipped_document( $new_document, $shipped_document_products, ['document_id' => $order_id, 'document_type' => 'order'] );

	$customer_data = array( 'billingfirstname' => 'Иван', 'billinglastname' => 'Кузнецов', 'billingemail' => 'customer@wp-universam.ru', 'shippinglocation'=> 1061, 'shippingaddress' => 'г. Калининград ул. Куйбышева д.4', 'shippingnotesclient' => 'Сообщите когда будет доставка' );
	usam_add_order_customerdata( $order_id, $customer_data );
	usam_insert_comment(['message' => 'Неудалось связаться с клиентов', 'object_id' => $order_id, 'object_type' => 'order'], [['object_id' => $order_id, 'object_type' => 'order']]);	

	/* Заказ №2*/
	$data = array( 'totalprice' => 0, 'type_price' => 'tp_1','paid' => 2,'status' => 'closed', 'type_payer' => 1,'user_ID' => $user_id,'source' => 'order');	
		
	$order_products = array( 
	array( 'product_id' => $product_ids[0], 'name' => 'Комплект нижнего билья Bunny', 'order_id' => 1,'old_price' => 0,'price' => 608, 'quantity' => 1 ) , 
	array( 'product_id' => $product_ids[1], 'name' => 'Халат Bunny', 'order_id' => 1,'old_price' => 0,'price' => 308, 'quantity' => 10 ) );	
	
	$order_id = usam_insert_order( $data, $order_products );
		
	$new_document = array( 'method' => 2, 'storage' => 1, 'storage_pickup' => 1, 'order_id' => $order_id );
	$order_products = usam_get_products_order( $order_id );	
	$shipped_document_products = array();
	foreach ( $order_products as $product ) 
	{						
		$shipped_document_products[] = array( 'product_id' => $product->product_id, 'quantity' => $product->quantity, 'reserve' => $product->quantity );
	}		
	usam_insert_shipped_document( $new_document, $shipped_document_products, ['document_id' => $order_id, 'document_type' => 'order'] );

	$customer_data = array( 'billingfirstname' => 'Иван', 'billinglastname' => 'Кузнецов', 'billingemail' => 'customer@wp-universam.ru', 'shippinglocation'=> 1061, 'shippingaddress' => 'г. Калининград ул. Куйбышева д.4', 'shippingnotesclient' => '' );
	usam_add_order_customerdata( $order_id, $customer_data );
	usam_insert_comment(['message' => 'Подготовка доставки', 'object_id' => $order_id, 'object_type' => 'order'], [['object_id' => $order_id, 'object_type' => 'order']]);

	/* Заказ №3*/
	$data = array( 'type_price' => 'tp_1','paid' => 2,'status' => 'job_dispatched', 'type_payer' => 1,'user_ID' =>$user_id,'source' => 'order');	
	
	$order_products = array( 
	array( 'product_id' => $product_ids[3], 'name' => 'Платье Redg', 'order_id' => 1,'old_price' => 0,'price' => 608, 'quantity' => 3 ) , 
	array( 'product_id' => $product_ids[4], 'name' => 'Платье Braun', 'order_id' => 1,'old_price' => 0,'price' => 308, 'quantity' => 2 ) );	
	$order_id = usam_insert_order( $data, $order_products );	
	
	$new_document = array( 'method' => 2, 'storage' => 1, 'storage_pickup' => 1, 'order_id' => $order_id );
	$order_products = usam_get_products_order( $order_id );	
	$shipped_document_products = array();
	foreach ( $order_products as $product ) 
	{						
		$shipped_document_products[] = array( 'product_id' => $product->product_id, 'quantity' => $product->quantity, 'reserve' => $product->quantity );
	}		
	usam_insert_shipped_document( $new_document, $shipped_document_products, ['document_id' => $order_id, 'document_type' => 'order'] );

	$customer_data = array( 'billingfirstname' => 'Екатерина', 'billinglastname' => 'Смирнова', 'billingemail' => 'customer@wp-universam.ru', 'shippinglocation'=> 217, 'shippingaddress' => 'ул. Куйбышева д.4', 'shippingnotesclient' => '' );
	usam_add_order_customerdata( $order_id, $customer_data );
	usam_insert_comment(['message' => 'Ожидание товара', 'object_id' => $order_id, 'object_type' => 'order'], [['object_id' => $order_id, 'object_type' => 'order']]);

}

/* Слайдер */	
$images = array( 'slide-1.png', 'slide-2.png', 'slide-3.png');
$imgs = array();
foreach ( $images as $key => $image ) 
{
	$url     = $image_url.$image;
	$desc    = "";
	
	$file_array = array();
	$tmp = download_url( $url );
		
	preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches );
	$file_array['name'] = basename( $matches[0] );
	$file_array['tmp_name'] = $tmp;	
	$image_id = media_handle_sideload( $file_array, 0, $desc );
	if ( $image_id )
	{
		$src = wp_get_attachment_image_src( $image_id, 'full' );	
		if ( !empty($src[0]) )
			$imgs[] = ['object_id' => $image_id, 'object_url' => $src[0]];	
	}
}

$slider = ['name' => 'Слайдер акций и новинок', 'active' => 1, 'type' => 'I', 'settings' => ['devices' => ['computer' => 1, 'notebook' => 0, 'tablet' => 0, 'mobile' => 0], 'layouttype' => 'layout', 'size' => ['computer' => ['width' => '100%', 'height' => '500px'], 'notebook' => ['width' => '100%', 'height' => '400px'], 'tablet' => ['width' => '100%', 'height' => '300px'], 'mobile' => ['width' => '100%', 'height' => '200px']], 'show' => 'home_head', 'condition' => ['roles' => [], 'sales_area' => []], 'autospeed' => 6000, 'autoplay' => true, 'button' => ['position' => 'bottom center', 'orientation' => 'row', 'css' => ['width' => '10px', 'height' => '10px', 'border-radius' => '5px', 'margin' => '0 5px 10px 0', 'background-color' => '#ffffff', 'border-color' => '#ffffff', 'border-width' => '1px', 'border-style' => 'double'], 'show' => 1]]];


$slides = [
	['type' => 'image', 'object_id' => $imgs[0]['object_id'], 'object_url' => $imgs[0]['object_url'], 'settings' => ['background-color' => '#ddf1ef', 'css' => ['background-size' => 'contain', 'background-position' => 'center center', 'background-repeat' => 'no-repeat', 'border-radius' => ''], 'actions' => ['type' => '', 'value' => ''], 'filter' => '', 'filter_opacity' => 1, 'effect' => '', 'layers' => []]],
	['type' => 'image',  'object_id' => $imgs[1]['object_id'], 'object_url' => $imgs[1]['object_url'], 'settings' => ['background-color' => '#f1f1f1', 'css' => ['background-size' => 'contain', 'background-position' => 'center center', 'background-repeat' => 'no-repeat', 'border-radius' => ''], 'actions' => ['type' => '', 'value' => ''], 'filter' => '', 'filter_opacity' => 1, 'effect' => '', 'layers' => []]],
	['type' => 'image', 'object_id' => $imgs[2]['object_id'], 'object_url' => $imgs[2]['object_url'], 'settings' => ['background-color' => '#f7ecec', 'css' => ['background-size' => 'contain', 'background-position' => 'center center', 'background-repeat' => 'no-repeat', 'border-radius' => ''], 'actions' => ['type' => '', 'value' => ''], 'filter' => '', 'filter_opacity' => 1, 'effect' => '', 'layers' => []]],
];
$slider = new USAM_Slider( $slider );				
$slider->save();
$slider->save_slides( $slides );


$review = array(
	['user_id' => $user_id, 'rating' => 4, 'mail' => 'customer@wp-universam.ru', 'name' => 'Миронов Сергей', 'title' => 'Очень удобный и красивый сайт.', 'review_text' => 'Поздравляем! У вас очень хороший сайт. Будем рады покупать у вас.'],
	['user_id' => $user_id, 'rating' => 5, 'mail' => 'customer@wp-universam.ru', 'name' => 'Никулин Михаил', 'title' => 'Товар купленный на вашем сайте, оказался хорошего качества! Спасибо.', 'review_text' => 'Спосибо!'],
	['user_id' => $user_id, 'rating' => 5, 'mail' => 'customer@wp-universam.ru', 'name' => 'Никулин Михаил', 'title' => 'Поздравляем! У вас очень хороший сайт. Будем рады покупать у вас.'],
);
foreach ( $review as $data ) 
{		
	$data['status'] = 2;
	usam_insert_review( $data );		
}
//Валютные курсы
//========================================================================================
require_once( USAM_FILE_PATH . '/includes/directory/currency_rate.class.php' );			
usam_insert_currency_rate(['basic_currency' => 'RUB', 'currency' => 'USD', 'rate' => 62, 'autoupdate' => 1]);
usam_insert_currency_rate(['basic_currency' => 'RUB', 'currency' => 'EUR', 'rate' => 80, 'autoupdate' => 1]);
//Задания
//========================================================================================

$tasks = array( 
	array( 		
		'calendar'    => 1, 
		'status'      => 1, 
		'title'       => 'Помощник установки платформы', 
		'description' => 'Подключить группу или анкеты Вконтакте. Появится возможность ручного или автоматического постинга, автоматическое поздравление с днем рождения и многое другое.', 		
		'user_id'     => $user_id,	
		'color'       => '', 			
		'type'        => 'task', 	
		'start'       => date("Y-m-d H:i:s", mktime(11, 0, 0, date("m"), 30, date("Y"))), 
		'end'         => date("Y-m-d H:i:s", mktime(18, 0, 0, date("m"), date("d")+100, date("Y"))), 
	),	
	array( 		
		'calendar'    => 1, 
		'status'      => 1, 
		'title'       => 'Подключить сервисы Яндекс или Google', 
		'description' => 'Появится возможность передача данных в Яндекс метрика, подключить Яндекс XML(необходим для определения позиции в яндексе) и многое другое', 		
		'user_id'     => $user_id,	
		'color'       => '', 			
		'type'        => 'task', 	
		'start'       => date("Y-m-d H:i:s", mktime(11, 0, 0, date("m"), 30, date("Y"))), 
		'end'         => date("Y-m-d H:i:s", mktime(18, 0, 0, date("m"), date("d")+100, date("Y"))), 
	),	
	array( 		
		'calendar'    => 1, 
		'status'      => 1, 
		'title'       => 'Настройте смс шлюз', 
		'description' => 'Будет возможность отправлять клиентам смс оповещения.', 		
		'user_id'     => $user_id,	
		'color'       => '', 			
		'type'        => 'task', 	
		'start'       => date("Y-m-d H:i:s", mktime(11, 0, 0, date("m"), 30, date("Y"))), 
		'end'         => date("Y-m-d H:i:s", mktime(18, 0, 0, date("m"), date("d")+100, date("Y"))), 
	),	
	array( 		
		'calendar'    => 1, 
		'status'      => 1, 
		'title'       => 'Настройте бланки', 
		'description' => 'Бланки это печатные формы счетов и актов', 		
		'user_id'     => $user_id,	
		'color'       => '', 			
		'type'        => 'task', 	
		'start'       => date("Y-m-d H:i:s", mktime(11, 0, 0, date("m"), 30, date("Y"))), 
		'end'         => date("Y-m-d H:i:s", mktime(18, 0, 0, date("m"), date("d")+100, date("Y"))), 
	),	
	array( 		
		'calendar'    => 1, 
		'status'      => 1, 
		'title'       => 'Создайте правила перекрестных продаж', 
		'description' => 'Настройте автоматический выбор товаров перекрестных продаж', 		
		'user_id'     => $user_id,	
		'color'       => '', 			
		'type'        => 'task', 	
		'start'       => date("Y-m-d H:i:s", mktime(11, 0, 0, date("m"), 30, date("Y"))), 
		'end'         => date("Y-m-d H:i:s", mktime(18, 0, 0, date("m"), date("d")+100, date("Y"))), 
	),	
	array( 		
		'calendar'    => 1, 
		'status'      => 1, 
		'title'       => 'Настройте сайты ваших поставщиков', 
		'description' => 'Настройте сайты ваших поставщиков товара, если вы занимаетесь перепродажей. Цены и доступность товаров будет автоматически проверяться', 		
		'user_id'     => $user_id,	
		'color'       => '', 			
		'type'        => 'task', 	
		'start'       => date("Y-m-d H:i:s", mktime(11, 0, 0, date("m"), 30, date("Y"))), 
		'end'         => date("Y-m-d H:i:s", mktime(18, 0, 0, date("m"), date("d")+100, date("Y"))), 
	),
	array( 
		'importance'  => 0, 
		'calendar'    => 1, 
		'status'      => 1, 
		'title'       => 'Позвонить в компанию СБЕРБАНК', 
		'description' => 'Позвонить в компанию СБЕРБАНК и заказать подключение платежного шлюза к моему сайту', 		
		'user_id'     => $user_id,	
		'color'       => '', 			
		'type'        => 'call', 	
		'start'       => date("Y-m-d H:i:s", mktime(date("H"), 0, 0, date("m"), date("d"), date("Y"))), 
		'end'         => date("Y-m-d H:i:s", mktime(date("H"), 30, 0, date("m"), date("d")+100, date("Y"))), 
	),	
	array( 
		'importance'  => 1, 
		'calendar'    => 1, 
		'status'      => 1, 
		'title'       => 'Акция Весенняя распродажа', 
		'description' => 'Не забыть сделать акцию', 		
		'user_id'     => $user_id,	
		'color'       => '', 			
		'type'        => 'task', 	
		'start'       => date("Y-m-d H:i:s", mktime(date("H"), 0, 0, date("m"), date("d"), date("Y"))), 
		'end'         => date("Y-m-d H:i:s", mktime(date("H"), 30, 0, date("m"), date("d")+100, date("Y"))), 
	),		
	array( 
		'importance'  => 1, 
		'calendar'    => 1, 
		'status'      => 1, 
		'title'       => 'Встретится с руководителем компании Газпром', 
		'description' => 'Возможно оптовая поставка. Выясню на встрече.', 		
		'user_id'     => $user_id,	
		'color'       => '', 			
		'type'        => 'meeting', 	
		'start'       => date("Y-m-d H:i:s", mktime(11, 0, 0, date("m"), date("d")+2, date("Y"))), 
		'end'         => date("Y-m-d H:i:s", mktime(11, 30, 0, date("m"), date("d")+200, date("Y"))), 
	),	
	array( 
		'importance'  => 1, 
		'calendar'    => 1, 
		'status'      => 1, 
		'title'       => 'Клиет хочет заказать товар', 
		'description' => 'Клиет хочет заказать товар. Узнать у поставщика наличие.', 		
		'user_id'     => $user_id,	
		'color'       => '', 			
		'type'        => 'meeting', 	
		'start'       => date("Y-m-d H:i:s", mktime(11, 0, 0, date("m"), date("d")+2, date("Y"))), 
		'end'         => date("Y-m-d H:i:s", mktime(11, 30, 0, date("m"), date("d")+200, date("Y"))), 
	),
);
foreach ( $tasks as $task ) 
{
	$document['date_insert'] = date( "Y-m-d H:i:s");
	$insert = $wpdb->insert( USAM_TABLE_EVENTS, $task );
}	
	
//Коммерческие предложения
//========================================================================================

$documents = array( 
	array( 'name' => 'предложение для СБЕРБАНК', 'manager_id' => $user_id, 'number' => 1, 'bank_account_id' => 1, 'customer_id' => 1, 'customer_type' => 'company', 'type_price' => '', 'status' => 'draft', 'closedate' => date( "Y-m-d H:i:s"), 'type' => 'suggestion' ),		
	array( 'name' => 'Счет для СБЕРБАНК', 'manager_id' => $user_id, 'number' => 1, 'bank_account_id' => 1, 'customer_id' => 1, 'customer_type' => 'company', 'type_price' => '', 'status' => 'draft', 'closedate' => date( "Y-m-d H:i:s"), 'type' => 'invoice' ),
	array( 'name' => 'Договор с СБЕРБАНК', 'manager_id' => $user_id, 'number' => 1, 'bank_account_id' => 1, 'customer_id' => 1, 'customer_type' => 'company', 'type_price' => '', 'status' => 'draft', 'closedate' => date( "Y-m-d H:i:s"), 'type' => 'contract' ),
);
foreach ( $documents as $document ) 
{
	$document['date_insert'] = date( "Y-m-d H:i:s");
	$insert = $wpdb->insert( USAM_TABLE_DOCUMENTS, $document );
}	
	
//Компании
//========================================================================================

$companies = array( 
	['name' => '', 'manager_id' => $user_id,  'open' => 1, 'type' => '', 'industry' => 'customer', 'name' => 'Сбербанк', 'date_insert' => date( "Y-m-d H:i:s")]
);
foreach ( $companies as $company ) 
	$insert = $wpdb->insert( USAM_TABLE_COMPANY, $company );	
	
//Контакты
//========================================================================================

$users = get_users( );
foreach ( $users as $user ) 
{	
	if ( empty($user->last_name) )
		$lastname = $user->display_name;
	else
		$lastname = $user->last_name;
	$contact = array( 'contact_source' => '', 'manager_id' => $user_id, 'user_id' => $user->ID, 'open' => 1, 'date_insert' => date( "Y-m-d H:i:s") );
	$insert = $wpdb->insert( USAM_TABLE_CONTACTS, $contact );	
	
	usam_update_contact_metadata($wpdb->insert_id, 'email', $user->user_email );
	usam_update_contact_metadata($wpdb->insert_id, 'lastname', $lastname );
	usam_update_contact_metadata($wpdb->insert_id, 'firstname', $user->first_name );
}

//SEO
//========================================================================================
$keywords = array( 'интернет-магазин' );
require_once( USAM_FILE_PATH .'/includes/seo/keyword.class.php' );
foreach ( $keywords as $keyword ) 
{		
	usam_insert_keyword( array( 'keyword' => $keyword, 'check' => 1) );
}
$static = array( 
	array( 'date_insert' => date( "Y-m-d", strtotime('-120 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 98 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-90 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 76 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-65 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 54 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-40 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 45 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-35 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 56 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-22 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 34 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-21 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 87 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-11 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 23 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-10 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 4 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-9 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 59 ),
	array( 'date_insert' => date( "Y-m-d" ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'y', 'site_id' => 0, 'number' => 50 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-30 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'g', 'site_id' => 0, 'number' => 56 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-3 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'g', 'site_id' => 0, 'number' => 56 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-2 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'g', 'site_id' => 0, 'number' => 80 ),
	array( 'date_insert' => date( "Y-m-d", strtotime('-1 days') ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'g', 'site_id' => 0, 'number' => 99 ),
	array( 'date_insert' => date( "Y-m-d" ), 'keyword_id' => 1, 'location_id' => 1061, 'url' => get_bloginfo('url'), 'search_engine' => 'g', 'site_id' => 0, 'number' => 43 ),	
);
$count = (int)$wpdb->get_results("SELECT COUNT(*) FROM `".USAM_TABLE_STATISTICS_KEYWORDS."` LIMIT 1");	
if ( $count == 0 )
{
	foreach ( $static as $stat ) 
	{		
		$insert = $wpdb->insert( USAM_TABLE_STATISTICS_KEYWORDS, $stat );	
	}
}

$messages = [
	['date_insert' => date( "Y-m-d H:i:s"), 'from_email' => 'customer@wp-universam.ru', 'from_name' => 'Платформа для бизнеса Универсам', 'to_email' => get_bloginfo('admin_email'), 'to_name' => 'Интернет магазин '.get_bloginfo('name'), 'title' => 'Разработчики платформы Универсам приветствуют Вас!', 'body' => 'Здравствуйте!<br> Спасибо что Вы выбрали платформу для бизнеса Универсам! <br>она поможет Вам продавать больше!', 'folder' => 'inbox', 'mailbox_id' => 1, 'type' => 'inbox_letter'],
];
foreach ( $messages as $message ) 
{		
	$insert = $wpdb->insert( USAM_TABLE_EMAIL, $message );	
}
usam_insert_chat_message( array( 'message' => 'Вы можете получать сообщения из skype!', 'channel' => 'skype' ) );
usam_insert_chat_message( array( 'message' => 'Вы можете получать и отправлять сообщения из telegram!', 'channel' => 'telegram' ) );
usam_insert_chat_message( array( 'message' => 'Вы можете получать и отправлять сообщения из viber!', 'channel' => 'viber' ) );
usam_insert_chat_message( array( 'message' => 'Вы можете получать и отправлять сообщения из vk!', 'channel' => 'vk' ) );
usam_insert_chat_message( array( 'message' => 'Разработчики платформы Универсам приветствуют Вас!', 'channel' => 'facebook' ) );
usam_insert_chat_message( array( 'message' => 'Вы можете получать и отправлять сообщения из chat!', 'channel' => 'chat' ) );

usam_insert_folder( array( 'name' => 'Счета покупателей', 'parent_id' => 0, 'user_id' => $user_id ) );
usam_insert_folder( array( 'name' => 'Мои документы', 'parent_id' => 0, 'user_id' => $user_id ) );

$contact_id = usam_get_contact_id();
usam_add_data(["name" => "Уведомление о новом заказе", "active" => 1, "email" => "email", "phone" => "mobilephone", "messenger" => "telegram_user_id", "events" => ["order" => ["email" => 1, "sms" => 0, "messenger" => 0], "conditions" => ["prices" => []]], "contacts" => [$contact_id]], 'usam_notifications' );	