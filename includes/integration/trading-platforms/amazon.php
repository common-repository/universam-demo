<?php 
require_once( USAM_FILE_PATH . '/includes/exchange/trading_platforms.class.php' );
/*
  Name: Amazon
 */
class USAM_Amazon_Exporter extends USAM_Trading_Platforms_Exporter
{			
	protected $file_type = 'tsv';
	protected function get_export_product( $post ) 
	{			
		$data = $this->get_file_structure();
		foreach($data as $key => $value) 
		{
			if ( $value['type_data'] == 'system_amazon' )
				$columns[$key] = $value['default'];
			else
			{
				if ( $value['type_data'] == 'attribute' )
				{
					$columns[$key] = $this->get_attribute($post->ID, $key);				
					if ( !is_numeric($value['default']) )					
						$columns[$key] = usam_sanitize_title_with_translit( $columns[$key] );
				}
				else			
					$columns[$key] = $this->get_system_data($key, $post);			
				$columns[$key] = $columns[$key] ? $columns[$key] : $value['default'];
			}
		}	
							
	/*	$images = usam_get_product_images_urls( $post->ID );
		for ($i = 1; $i <= 4; $i++)
		{
			$columns['other_image_url'.$i] = isset($images[1])?$images[1]:''; 
		}*/
		return $columns;		
	}
	
	public function get_system_data( $key, $post ) 
	{		
		switch ( $key ) 
		{					
			case 'item_sku':
				return $post->ID;
			break;
			case 'manufacturer':
				return usam_sanitize_title_with_translit($this->text_decode( usam_get_product_property( $post->ID, 'contractor') ));				
			break;			
			case 'item_name':
				return usam_sanitize_title_with_translit($this->text_decode( $post->post_title ));	
			break;
			case 'product_description':
				return preg_replace("/[^a-zA-ZА-Яа-я0-9.,:;?!\s]/u","", usam_sanitize_title_with_translit(usam_limit_words( $this->text_decode(get_the_excerpt( $post->ID )) )));
			break;			
			case 'purchasable_offer[marketplace_id=ATVPDKIKX0DER]#1.our_price#1.schedule#1.value_with_tax':
			case 'list_price':
				$currency = usam_get_currency_price_by_code( $this->rule['type_price'] );
				$price = usam_get_product_price( $post->ID, $this->rule['type_price'] );
				if ( $currency != 'USD' )
					$price = round(usam_convert_currency( $price, $currency, 'USD' ),2);
				return $price;
			break;
			case 'main_image_url':
				return usam_get_product_thumbnail_src($post->ID, 'single');
			break;			
			case 'fulfillment_availability#1.quantity':
				return usam_product_remaining_stock( $post->ID );	
			break;
		}
		return $array;
	}
	
	protected function get_export_file( $data ) 
	{		
		$structure = $this->get_file_structure();
		$columns = [];
		foreach($structure as $key => $value) 
		{
			$columns[$key] = $key;
		}
		array_unshift($data, $columns);
		$columns = [];
		foreach($structure as $key => $value) 
		{
			$columns[$key] = $value['name_amazon'];
		}
		array_unshift($data, $columns);		
		$columns = [];
		foreach($structure as $key => $value) 
		{
			$columns[$key] = isset($value['amazon_first_line'])?$value['amazon_first_line']:'';
		}
		array_unshift($data, $columns);
		return $data;
	}

	public function get_file_structure( )
	{
		$array = [
			'feed_product_type' => ['title' => __('Тип товара', 'usam'), 'name_amazon' => 'Product Type', 'default' => 'abisbook', 'type_data' => 'attribute', 'amazon_first_line' => 'TemplateType=fptcustom'],
			'item_sku' => ['name_amazon' => 'Seller SKU', 'default' => '', 'type_data' => 'system', 'amazon_first_line' => 'Version=2022.0920'],
			'external_product_id' => ['title' => __('Значение идентификатора товара', 'usam'), 'name_amazon' => 'Product ID', 'default' => '', 'type_data' => 'attribute', 'amazon_first_line' => 'TemplateSignature=QUJJU19CT09L'],			
			'external_product_id_type' => ['title' => __('Тип идентификатора товара', 'usam'), 'name_amazon' => 'Product ID Type', 'default' => 'ISBN', 'type_data' => 'attribute', 'amazon_first_line' => 'settings=attributeRow=3&contentLanguageTag=en_US&contributorId=amzn1.cr.o.A2MD7MEOJBKEB2&dataRow=4&feedType=610841&flavor=mixed-M%40-UMP-'],
			'item_name' => ['name_amazon' => 'Product Name', 'default' => '', 'type_data' => 'system', 'amazon_first_line' => 'TemplateSignature=QUJJU19CT09L'],
			'item_type' => ['title' => __('Категория', 'usam'), 'name_amazon' => 'Category (item-type)', 'default' => 'book', 'type_data' => 'attribute'],
			'binding' => ['title' => __('Тип переплета', 'usam'), 'name_amazon' => 'Binding', 'default' => 'Hardcover', 'type_data' => 'attribute'],
			'product_description' => ['name_amazon' => 'Product Description', 'default' => '', 'type_data' => 'system'],
			'manufacturer' => ['title' => __('Производитель', 'usam'), 'name_amazon' => 'Manufacturer', 'default' => '', 'type_data' => 'system'],
			'publication_date' => ['title' => __('Дата публикации', 'usam'), 'name_amazon' => 'Publication date', 'default' => '', 'type_data' => 'attribute'],
			'language_value1' => ['title' => __('Язык', 'usam'), 'name_amazon' => 'Language', 'default' => 'Russian', 'type_data' => 'attribute'],
			'language_value2' => ['title' => __('Язык', 'usam'), 'name_amazon' => 'Language', 'default' => '', 'type_data' => 'attribute'],
			'language_value3' => ['title' => __('Язык', 'usam'), 'name_amazon' => 'Language', 'default' => '', 'type_data' => 'attribute'],
			'language_value4' => ['title' => __('Язык', 'usam'), 'name_amazon' => 'Language', 'default' => '', 'type_data' => 'attribute'],
			'language_value5' => ['title' => __('Язык', 'usam'), 'name_amazon' => 'Language', 'default' => '', 'type_data' => 'attribute'],			
			'author1' => ['title' => __('Автор', 'usam'), 'name_amazon' => 'Author', 'default' => '', 'type_data' => 'attribute'],
			'author2' => ['title' => __('Автор', 'usam'), 'name_amazon' => 'Author', 'default' => '', 'type_data' => 'attribute'],
			'author3' => ['title' => __('Автор', 'usam'), 'name_amazon' => 'Author', 'default' => '', 'type_data' => 'attribute'],
			'author4' => ['title' => __('Автор', 'usam'), 'name_amazon' => 'Author', 'default' => '', 'type_data' => 'attribute'],
			'author5' => ['title' => __('Автор', 'usam'), 'name_amazon' => 'Author', 'default' => '', 'type_data' => 'attribute'],			
			'genre1' => ['title' => __('Жанр', 'usam'), 'name_amazon' => 'Genre', 'default' => 'Classical', 'type_data' => 'attribute'],
			'genre2' => ['title' => __('Жанр', 'usam'), 'name_amazon' => 'Genre', 'default' => '', 'type_data' => 'attribute'],
			'genre3' => ['title' => __('Жанр', 'usam'), 'name_amazon' => 'Genre', 'default' => '', 'type_data' => 'attribute'],
			'genre4' => ['title' => __('Жанр', 'usam'), 'name_amazon' => 'Genre', 'default' => '', 'type_data' => 'attribute'],
			'genre5' => ['title' => __('Жанр', 'usam'), 'name_amazon' => 'Genre', 'default' => '', 'type_data' => 'attribute'],			
			'maximum_reading_interest_age' => ['title' => __('Максимальный возраст', 'usam'), 'name_amazon' => 'Maximum Reading Age', 'default' => 95.00, 'type_data' => 'attribute'],
			'minimum_reading_interest_age_unit_of_measure' => ['title' => __('Единица измерения максимального возраста', 'usam'), 'name_amazon' => 'Minimum Reading Age Unit', 'default' => 'Years', 'type_data' => 'attribute'],
			'pages_unit_of_measure' => ['title' => __('Единица измерения страницы', 'usam'), 'name_amazon' => 'Pages Unit Of Measure', 'default' => 'Pages', 'type_data' => 'attribute'],
			'minimum_reading_interest_age' => ['title' => __('Минимальный возраст', 'usam'), 'name_amazon' => 'Minimum Reading Age', 'default' => 5.00, 'type_data' => 'attribute'],
			'pages' => ['title' => __('Количество страниц', 'usam'), 'name_amazon' => 'pages', 'default' => '', 'type_data' => 'attribute'],
			'maximum_reading_interest_age_unit_of_measure' => ['title' => __('Единица измерения минимального возраста', 'usam'), 'name_amazon' => 'Maximum Reading Age Unit', 'default' => 'Years', 'type_data' => 'attribute'],
			'country_of_origin' => ['title' => __('Страна', 'usam'), 'name_amazon' => 'Country/Region of Origin', 'default' => 'Russian', 'type_data' => 'attribute'],
			'cpsia_cautionary_statement1' => ['title' => 'Cpsia Warning', 'name_amazon' => 'Cpsia Warning', 'default' => 'NoWarningApplicable', 'type_data' => 'attribute'],
			'cpsia_cautionary_statement2' => ['title' => 'Cpsia Warning', 'name_amazon' => 'Cpsia Warning', 'default' => '', 'type_data' => 'attribute'],
			'cpsia_cautionary_statement3' => ['title' => 'Cpsia Warning', 'name_amazon' => 'Cpsia Warning', 'default' => '', 'type_data' => 'attribute'],
			'cpsia_cautionary_statement4' => ['title' => 'Cpsia Warning', 'name_amazon' => 'Cpsia Warning', 'default' => '', 'type_data' => 'attribute'],
			'list_price' => ['name_amazon' => 'List Price', 'default' => '', 'type_data' => 'system'],
			'main_image_url' => ['name_amazon' => 'Main Image URL', 'default' => '', 'type_data' => 'system'],	
			'other_image_url1' => ['title' => 'Other Image URL1', 'name_amazon' => 'Other Image URL1', 'default' => '', 'type_data' => 'system', 'amazon_first_line' => 'Images'],	
			'other_image_url2' => ['title' => 'Other Image URL2', 'name_amazon' => 'Other Image URL2', 'default' => '', 'type_data' => 'system'],
			'other_image_url3' => ['title' => 'Other Image URL3', 'name_amazon' => 'Other Image URL3', 'default' => '', 'type_data' => 'system'],
			'other_image_url4' => ['title' => 'Other Image URL4', 'name_amazon' => 'Other Image URL4', 'default' => '', 'type_data' => 'system'],
			'other_image_url5' => ['title' => 'Other Image URL5', 'name_amazon' => 'Other Image URL5', 'default' => '', 'type_data' => 'system'],
			'other_image_url6' => ['title' => 'Other Image URL6', 'name_amazon' => 'Other Image URL6', 'default' => '', 'type_data' => 'system'],
			'other_image_url7' => ['title' => 'Other Image URL7', 'name_amazon' => 'Other Image URL7', 'default' => '', 'type_data' => 'system'],
			'other_image_url8' => ['title' => 'Other Image URL8', 'name_amazon' => 'Other Image URL8', 'default' => '', 'type_data' => 'system'],			
			'swatch_image_url' => ['title' => 'Swatch Image URL', 'name_amazon' => 'Swatch Image URL', 'default' => '', 'type_data' => 'system'],			
			'update_delete' => ['name_amazon' => 'Update Delete', 'default' => 'Update', 'type_data' => 'system_amazon', 'amazon_first_line' => 'Basic'],
			'brand_name' => ['title' => 'Brand Name', 'name_amazon' => 'Brand Name', 'default' => '', 'type_data' => 'attribute'],			
			'edition' => ['title' => 'Edition', 'name_amazon' => 'Edition', 'default' => '', 'type_data' => 'attribute'],
			'part_number' => ['title' => 'Manufacturer Part Number', 'name_amazon' => 'Manufacturer Part Number', 'default' => '', 'type_data' => 'attribute'],
			'gtin_exemption_reason' => ['title' => 'Product Exemption Reason', 'name_amazon' => 'Product Exemption Reason', 'default' => '', 'type_data' => 'attribute'],
			'bisac_subject_heading_code' => ['title' => 'BISAC Subject Code', 'name_amazon' => 'BISAC Subject Code', 'default' => '', 'type_data' => 'attribute', 'amazon_first_line' => 'Discovery'],
			'bookdata_bic_subject_code' => ['title' => 'Bookdata BIC Subject Code', 'name_amazon' => 'Bookdata BIC Subject Code', 'default' => '', 'type_data' => 'attribute'],
			'bisac_subject_description_code' => ['title' => 'BISAC Code', 'name_amazon' => 'BISAC Code', 'default' => '', 'type_data' => 'attribute'],			
			'team_name' => ['title' => 'Team Name', 'name_amazon' => 'Team Name', 'default' => '', 'type_data' => 'attribute'],
			'target_audience_base1' => ['title' => 'Target Audience', 'name_amazon' => 'Target Audience', 'default' => '', 'type_data' => 'attribute'],
			'target_audience_base2' => ['title' => 'Target Audience', 'name_amazon' => 'Target Audience', 'default' => '', 'type_data' => 'attribute'],
			'target_audience_base3' => ['title' => 'Target Audience', 'name_amazon' => 'Target Audience', 'default' => '', 'type_data' => 'attribute'],
			'target_audience_base4' => ['title' => 'Target Audience', 'name_amazon' => 'Target Audience', 'default' => '', 'type_data' => 'attribute'],
			'target_audience_base5' => ['title' => 'Target Audience', 'name_amazon' => 'Target Audience', 'default' => '', 'type_data' => 'attribute'],
			'subject_keywords' => ['title' => 'Subject Keyword', 'name_amazon' => 'Subject Keyword', 'default' => '', 'type_data' => 'attribute'],
			'bullet_point1' => ['title' => 'Key Product Features', 'name_amazon' => 'Key Product Features', 'default' => '', 'type_data' => 'attribute'],
			'bullet_point2' => ['title' => 'Key Product Features', 'name_amazon' => 'Key Product Features', 'default' => '', 'type_data' => 'attribute'],
			'bullet_point3' => ['title' => 'Key Product Features', 'name_amazon' => 'Key Product Features', 'default' => '', 'type_data' => 'attribute'],
			'bullet_point4' => ['title' => 'Key Product Features', 'name_amazon' => 'Key Product Features', 'default' => '', 'type_data' => 'attribute'],
			'bullet_point5' => ['title' => 'Key Product Features', 'name_amazon' => 'Key Product Features', 'default' => '', 'type_data' => 'attribute'],
			'bullet_point6' => ['title' => 'Key Product Features', 'name_amazon' => 'Key Product Features', 'default' => '', 'type_data' => 'attribute'],
			'bullet_point7' => ['title' => 'Key Product Features', 'name_amazon' => 'Key Product Features', 'default' => '', 'type_data' => 'attribute'],
			'bullet_point8' => ['title' => 'Key Product Features', 'name_amazon' => 'Key Product Features', 'default' => '', 'type_data' => 'attribute'],
			'bullet_point9' => ['title' => 'Key Product Features', 'name_amazon' => 'Key Product Features', 'default' => '', 'type_data' => 'attribute'],
			'bullet_point10' => ['title' => 'Key Product Features', 'name_amazon' => 'Key Product Features', 'default' => '', 'type_data' => 'attribute'],			
			'original_language_title' => ['title' => 'original_language_title', 'name_amazon' => 'original_language_title', 'default' => '', 'type_data' => 'attribute'],
			'generic_keywords' => ['title' => 'Search Terms', 'name_amazon' => 'Search Terms', 'default' => '', 'type_data' => 'attribute'],
			'color_name' => ['title' => 'Color', 'name_amazon' => 'Color', 'default' => '', 'type_data' => 'attribute'],			
			'series_title' => ['title' => 'Series title', 'name_amazon' => 'Series title', 'default' => '', 'type_data' => 'attribute'],
			'league_name' => ['title' => 'League Name', 'name_amazon' => 'League Name', 'default' => '', 'type_data' => 'attribute'],
			'included_components' => ['title' => 'Included Components', 'name_amazon' => 'Included Components', 'default' => '', 'type_data' => 'attribute'],
			'size_name' => ['title' => 'Size', 'name_amazon' => 'Size', 'default' => '', 'type_data' => 'attribute'],
			'thema_classification_code' => ['title' => 'BISAC Subject Code', 'name_amazon' => 'BISAC Subject Code', 'default' => '', 'type_data' => 'attribute'],
			'format1' => ['title' => 'Format', 'name_amazon' => 'Format', 'default' => '', 'type_data' => 'attribute'],
			'format2' => ['title' => 'Format', 'name_amazon' => 'Format', 'default' => '', 'type_data' => 'attribute'],
			'format3' => ['title' => 'Format', 'name_amazon' => 'Format', 'default' => '', 'type_data' => 'attribute'],
			'format4' => ['title' => 'Format', 'name_amazon' => 'Format', 'default' => '', 'type_data' => 'attribute'],
			'format5' => ['title' => 'Format', 'name_amazon' => 'Format', 'default' => '', 'type_data' => 'attribute'],
			'unknown_subject' => ['title' => 'Subject', 'name_amazon' => 'Subject', 'default' => '', 'type_data' => 'attribute'],
			'importer' => ['title' => 'importer', 'name_amazon' => 'importer', 'default' => '', 'type_data' => 'attribute'],
			
			'school_type' => ['title' => 'School Type', 'name_amazon' => 'School Type', 'default' => '', 'type_data' => 'attribute', 'amazon_first_line' => 'Product Enrichment'],
			'chinese_thesaurus_classification' => ['title' => 'Subject', 'name_amazon' => 'Subject', 'default' => '', 'type_data' => 'attribute'],
			'bisac_subject_description' => ['title' => 'BISAC Subject Code', 'name_amazon' => 'BISAC Subject Code', 'default' => '', 'type_data' => 'attribute'],
			'foreword' => ['title' => 'Foreword', 'name_amazon' => 'Foreword', 'default' => '', 'type_data' => 'attribute'],
			'lexile_code' => ['title' => 'Lexile Code', 'name_amazon' => 'Lexile Code', 'default' => '', 'type_data' => 'attribute'],
			'legal_compliance_certification_certifying_authority_name' => ['title' => 'legal_compliance_certification_certifying_authority_name', 'name_amazon' => 'legal_compliance_certification_certifying_authority_name', 'default' => '', 'type_data' => 'attribute'],
			'series_number' => ['title' => 'Series Number', 'name_amazon' => 'Series Number', 'default' => '', 'type_data' => 'attribute'],
			'legal_compliance_certification_geographic_jurisdiction' => ['title' => 'legal_compliance_certification_geographic_jurisdiction', 'name_amazon' => 'legal_compliance_certification_geographic_jurisdiction', 'default' => '', 'type_data' => 'attribute'],
			'illustrator' => ['title' => 'Illustrator', 'name_amazon' => 'Illustrator', 'default' => '', 'type_data' => 'attribute'],						
			'volume_base' => ['title' => 'volume', 'name_amazon' => 'volume', 'default' => '', 'type_data' => 'attribute'],
			'copyright' => ['title' => 'Copyright', 'name_amazon' => 'Copyright', 'default' => '', 'type_data' => 'attribute'],
			'subject_code' => ['title' => 'BISAC Subject Code', 'name_amazon' => 'BISAC Subject Code', 'default' => '', 'type_data' => 'attribute'],
			'chinese_library_classification' => ['title' => 'Subject', 'name_amazon' => 'Subject', 'default' => '', 'type_data' => 'attribute'],
			'subtitle' => ['title' => 'Subtitle', 'name_amazon' => 'Subtitle', 'default' => '', 'type_data' => 'attribute'],
			'editor' => ['title' => 'Editor', 'name_amazon' => 'Editor', 'default' => '', 'type_data' => 'attribute'],
			'number_of_words' => ['title' => 'Number of Words', 'name_amazon' => 'Number of Words', 'default' => '', 'type_data' => 'attribute'],
			'language_published' => ['title' => 'Language of Text', 'name_amazon' => 'Language of Text', 'default' => '', 'type_data' => 'attribute'],
			'textbook_type' => ['title' => 'Textbook Type', 'name_amazon' => 'Textbook Type', 'default' => '', 'type_data' => 'attribute'],
			'reprint_date' => ['title' => 'Reprint Date', 'name_amazon' => 'Reprint Date', 'default' => '', 'type_data' => 'attribute'],
						
			'item_width_unit_of_measure' => ['title' => 'Item Width Unit Of Measure', 'name_amazon' => 'Item Width Unit Of Measure', 'default' => '', 'type_data' => 'attribute', 'amazon_first_line' => 'Dimensions'],
			'item_width' => ['title' => 'Width', 'name_amazon' => 'Width', 'default' => '', 'type_data' => 'attribute'],
			'item_height' => ['title' => 'Height', 'name_amazon' => 'Height', 'default' => '', 'type_data' => 'attribute'],
			'item_height_unit_of_measure' => ['title' => 'Item Height Unit Of Measure', 'name_amazon' => 'Item Height Unit Of Measure', 'default' => '', 'type_data' => 'attribute'],
			'item_length_unit_of_measure' => ['title' => 'Item Length Unit Of Measure', 'name_amazon' => 'Item Length Unit Of Measure', 'default' => '', 'type_data' => 'attribute'],
			'item_length' => ['title' => 'Length', 'name_amazon' => 'Length', 'default' => '', 'type_data' => 'attribute'],
			
			'package_length' => ['title' => 'Length', 'name_amazon' => 'Length', 'default' => '', 'type_data' => 'attribute', 'amazon_first_line' => 'Fulfillment'],
			'package_weight_unit_of_measure' => ['title' => 'Package Weight Unit Of Measure', 'name_amazon' => 'Package Weight Unit Of Measure', 'default' => '', 'type_data' => 'attribute'],
			'package_height' => ['title' => 'Height', 'name_amazon' => 'Height', 'default' => '', 'type_data' => 'attribute'],
			'package_length_unit_of_measure' => ['title' => 'Package Length Unit Of Measure', 'name_amazon' => 'Package Length Unit Of Measure', 'default' => '', 'type_data' => 'attribute'],
			'package_height_unit_of_measure' => ['title' => 'Package Height Unit Of Measure', 'name_amazon' => 'Package Height Unit Of Measure', 'default' => '', 'type_data' => 'attribute'],		
			'package_width' => ['title' => 'Width', 'name_amazon' => 'Width', 'default' => '', 'type_data' => 'attribute'],
			'package_width_unit_of_measure' => ['title' => 'Package Width Unit Of Measure', 'name_amazon' => 'Package Width Unit Of Measure', 'default' => '', 'type_data' => 'attribute'],
			'package_weight' => ['title' => 'Weight', 'name_amazon' => 'Weight', 'default' => '', 'type_data' => 'attribute'],
			
			'batteries_required' => ['title' => 'Is this product a battery or does it utilize batteries?', 'name_amazon' => 'Is this product a battery or does it utilize batteries?', 'default' => '', 'type_data' => 'attribute', 'amazon_first_line' => 'Compliance'],
			'are_batteries_included' => ['title' => 'Batteries are Included', 'name_amazon' => 'Batteries are Included', 'default' => '', 'type_data' => 'attribute'],
			'battery_cell_composition' => ['title' => 'Battery composition', 'name_amazon' => 'Battery composition', 'default' => '', 'type_data' => 'attribute'],
			'battery_type1' => ['title' => 'Battery type/size', 'name_amazon' => 'Battery type/size', 'default' => '', 'type_data' => 'attribute'],
			'battery_type2' => ['title' => 'Battery type/size', 'name_amazon' => 'Battery type/size', 'default' => '', 'type_data' => 'attribute'],
			'battery_type3' => ['title' => 'Battery type/size', 'name_amazon' => 'Battery type/size', 'default' => '', 'type_data' => 'attribute'],
			'number_of_batteries1' => ['title' => 'Number of batteries', 'name_amazon' => 'Number of batteries', 'default' => '', 'type_data' => 'attribute'],
			'number_of_batteries2' => ['title' => 'Number of batteries', 'name_amazon' => 'Number of batteries', 'default' => '', 'type_data' => 'attribute'],
			'number_of_batteries3' => ['title' => 'Number of batteries', 'name_amazon' => 'Number of batteries', 'default' => '', 'type_data' => 'attribute'],
			'battery_weight' => ['title' => 'Battery weight (grams)', 'name_amazon' => 'Battery weight (grams)', 'default' => '', 'type_data' => 'attribute'],
			'battery_weight_unit_of_measure' => ['title' => 'battery_weight_unit_of_measure', 'name_amazon' => 'battery_weight_unit_of_measure', 'default' => '', 'type_data' => 'attribute'],
			'number_of_lithium_metal_cells' => ['title' => 'Number of Lithium Metal Cells', 'name_amazon' => 'Number of Lithium Metal Cells', 'default' => '', 'type_data' => 'attribute'],
			'number_of_lithium_ion_cells' => ['title' => 'Number of Lithium-ion Cells', 'name_amazon' => 'Number of Lithium-ion Cells', 'default' => '', 'type_data' => 'attribute'],
			'lithium_battery_packaging' => ['title' => 'Lithium Battery Packaging', 'name_amazon' => 'Lithium Battery Packaging', 'default' => '', 'type_data' => 'attribute'],
			'lithium_battery_energy_content' => ['title' => 'Watt hours per battery', 'name_amazon' => 'Watt hours per battery', 'default' => '', 'type_data' => 'attribute'],
			'lithium_battery_energy_content_unit_of_measure' => ['title' => 'lithium_battery_energy_content_unit_of_measure', 'name_amazon' => 'lithium_battery_energy_content_unit_of_measure', 'default' => '', 'type_data' => 'attribute'],
			'lithium_battery_weight' => ['title' => 'Lithium content (grams)', 'name_amazon' => 'Lithium content (grams)', 'default' => '', 'type_data' => 'attribute'],
			'lithium_battery_weight_unit_of_measure' => ['title' => 'lithium_battery_weight_unit_of_measure', 'name_amazon' => 'lithium_battery_weight_unit_of_measure', 'default' => '', 'type_data' => 'attribute'],
			'supplier_declared_dg_hz_regulation1' => ['title' => 'Applicable Dangerous Goods Regulations', 'name_amazon' => 'Applicable Dangerous Goods Regulations', 'default' => '', 'type_data' => 'attribute'],
			'supplier_declared_dg_hz_regulation2' => ['title' => 'Applicable Dangerous Goods Regulations', 'name_amazon' => 'Applicable Dangerous Goods Regulations', 'default' => '', 'type_data' => 'attribute'],
			'supplier_declared_dg_hz_regulation3' => ['title' => 'Applicable Dangerous Goods Regulations', 'name_amazon' => 'Applicable Dangerous Goods Regulations', 'default' => '', 'type_data' => 'attribute'],
			'supplier_declared_dg_hz_regulation4' => ['title' => 'Applicable Dangerous Goods Regulations', 'name_amazon' => 'Applicable Dangerous Goods Regulations', 'default' => '', 'type_data' => 'attribute'],
			'supplier_declared_dg_hz_regulation5' => ['title' => 'Applicable Dangerous Goods Regulations', 'name_amazon' => 'Applicable Dangerous Goods Regulations', 'default' => '', 'type_data' => 'attribute'],
			'hazmat_united_nations_regulatory_id' => ['title' => 'UN number', 'name_amazon' => 'UN number', 'default' => '', 'type_data' => 'attribute'],
			'safety_data_sheet_url' => ['title' => 'Safety Data Sheet (SDS) URL', 'name_amazon' => 'Safety Data Sheet (SDS) URL', 'default' => '', 'type_data' => 'attribute'],
			'item_weight' => ['title' => 'Item Weight', 'name_amazon' => 'Item Weight', 'default' => '', 'type_data' => 'attribute'],
			'item_weight_unit_of_measure' => ['title' => 'item_weight_unit_of_measure', 'name_amazon' => 'item_weight_unit_of_measure', 'default' => '', 'type_data' => 'attribute'],
			'item_volume' => ['title' => 'Volume', 'name_amazon' => 'Volume', 'default' => '', 'type_data' => 'attribute'],
			'item_volume_unit_of_measure' => ['title' => 'item_volume_unit_of_measure', 'name_amazon' => 'item_volume_unit_of_measure', 'default' => '', 'type_data' => 'attribute'],
			'legal_compliance_certification_regulatory_organization_name' => ['title' => 'Regulatory Organization Name', 'name_amazon' => 'Regulatory Organization Name', 'default' => '', 'type_data' => 'attribute'],
			'legal_compliance_certification_status' => ['title' => 'Compliance Certification Status', 'name_amazon' => 'Compliance Certification Status', 'default' => '', 'type_data' => 'attribute'],
			'flash_point' => ['title' => 'Flash point (°C)?', 'name_amazon' => 'Flash point (°C)?', 'default' => '', 'type_data' => 'attribute'],
			'warranty_description' => ['title' => 'Manufacturer Warranty Description', 'name_amazon' => 'Manufacturer Warranty Description', 'default' => '', 'type_data' => 'attribute'],
			'legal_compliance_certification_date_of_issue' => ['title' => 'legal_compliance_certification_date_of_issue', 'name_amazon' => 'legal_compliance_certification_date_of_issue', 'default' => '', 'type_data' => 'attribute'],
			'legal_compliance_certification_metadata' => ['title' => 'legal_compliance_certification_metadata', 'name_amazon' => 'legal_compliance_certification_metadata', 'default' => '', 'type_data' => 'attribute'],
			'legal_compliance_certification_value' => ['title' => 'Legal Compliance Certification', 'name_amazon' => 'Legal Compliance Certification', 'default' => '', 'type_data' => 'attribute'],
			'ghs_classification_class1' => ['title' => 'Categorization/GHS pictograms (select all that apply)', 'name_amazon' => 'Categorization/GHS pictograms (select all that apply)', 'default' => '', 'type_data' => 'attribute'],
			'ghs_classification_class2' => ['title' => 'Categorization/GHS pictograms (select all that apply)', 'name_amazon' => 'Categorization/GHS pictograms (select all that apply)', 'default' => '', 'type_data' => 'attribute'],
			'ghs_classification_class3' => ['title' => 'Categorization/GHS pictograms (select all that apply)', 'name_amazon' => 'Categorization/GHS pictograms (select all that apply)', 'default' => '', 'type_data' => 'attribute'],
			'california_proposition_65_compliance_type' => ['title' => 'California Proposition 65 Warning Type', 'name_amazon' => 'California Proposition 65 Warning Type', 'default' => '', 'type_data' => 'attribute'],
			'california_proposition_65_chemical_names1' => ['title' => 'California Proposition 65 Chemical Names', 'name_amazon' => 'California Proposition 65 Chemical Names', 'default' => '', 'type_data' => 'attribute'],
			'california_proposition_65_chemical_names2' => ['title' => 'Additional Chemical Name1', 'name_amazon' => 'Additional Chemical Name1', 'default' => '', 'type_data' => 'attribute'],
			'california_proposition_65_chemical_names3' => ['title' => 'Additional Chemical Name2', 'name_amazon' => 'Additional Chemical Name2', 'default' => '', 'type_data' => 'attribute'],
			'california_proposition_65_chemical_names4' => ['title' => 'Additional Chemical Name3', 'name_amazon' => 'Additional Chemical Name3', 'default' => '', 'type_data' => 'attribute'],
			'california_proposition_65_chemical_names5' => ['title' => 'Additional Chemical Name4', 'name_amazon' => 'Additional Chemical Name4', 'default' => '', 'type_data' => 'attribute'],
			'pesticide_marking_type1' => ['title' => 'Pesticide Marking', 'name_amazon' => 'Pesticide Marking', 'default' => '', 'type_data' => 'attribute'],
			'pesticide_marking_type2' => ['title' => 'Pesticide Marking', 'name_amazon' => 'Pesticide Marking', 'default' => '', 'type_data' => 'attribute'],
			'pesticide_marking_type3' => ['title' => 'Pesticide Marking', 'name_amazon' => 'Pesticide Marking', 'default' => '', 'type_data' => 'attribute'],
			'pesticide_marking_registration_status1' => ['title' => 'Pesticide Registration Status', 'name_amazon' => 'Pesticide Registration Status', 'default' => '', 'type_data' => 'attribute'],
			'pesticide_marking_registration_status2' => ['title' => 'Pesticide Registration Status', 'name_amazon' => 'Pesticide Registration Status', 'default' => '', 'type_data' => 'attribute'],
			'pesticide_marking_registration_status3' => ['title' => 'Pesticide Registration Status', 'name_amazon' => 'Pesticide Registration Status', 'default' => '', 'type_data' => 'attribute'],
			'pesticide_marking_certification_number1' => ['title' => 'Pesticide Certification Number', 'name_amazon' => 'Pesticide Certification Number', 'default' => '', 'type_data' => 'attribute'],
			'pesticide_marking_certification_number2' => ['title' => 'Pesticide Certification Number', 'name_amazon' => 'Pesticide Certification Number', 'default' => '', 'type_data' => 'attribute'],
			'pesticide_marking_certification_number3' => ['title' => 'Pesticide Certification Number', 'name_amazon' => 'Pesticide Certification Number', 'default' => '', 'type_data' => 'attribute'],
						
			'merchant_release_date' => ['title' => 'Release Date', 'name_amazon' => 'Release Date', 'default' => '', 'type_data' => 'attribute', 'amazon_first_line' => 'Offer'],
			'number_of_items' => ['title' => 'Number of Items', 'name_amazon' => 'Number of Items', 'default' => '', 'type_data' => 'attribute'],
			'offering_can_be_giftwrapped' => ['title' => 'Is Gift Wrap Available', 'name_amazon' => 'Is Gift Wrap Available', 'default' => '', 'type_data' => 'attribute'],
			'max_order_quantity' => ['title' => 'Max Order Quantity', 'name_amazon' => 'Max Order Quantity', 'default' => '', 'type_data' => 'attribute'],
			'merchant_shipping_group_name' => ['title' => 'Merchant Shipping Group', 'name_amazon' => 'Merchant Shipping Group', 'default' => '', 'type_data' => 'attribute'],
			'product_tax_code' => ['title' => 'Product Tax Code', 'name_amazon' => 'Product Tax Code', 'default' => '', 'type_data' => 'attribute'],
			'offering_can_be_gift_messaged' => ['title' => 'Offering Can Be Gift Messaged', 'name_amazon' => 'Offering Can Be Gift Messaged', 'default' => '', 'type_data' => 'attribute'],
			'condition_type' => ['title' => __('Состояние товара', 'usam'), 'name_amazon' => 'Condition', 'default' => 'New', 'type_data' => 'attribute'],
			'condition_note' => ['title' => 'Condition Note', 'name_amazon' => 'Condition Note', 'default' => '', 'type_data' => 'attribute'],
			
			'fulfillment_availability#1.fulfillment_channel_code' => ['title' => __('Код канала выполнения', 'usam'), 'name_amazon' => 'Fulfillment Channel Code (US, CA, MX)', 'default' => 'DEFAULT', 'type_data' => 'attribute', 'amazon_first_line' => 'Offer (US, CA, MX)'],
			'fulfillment_availability#1.is_inventory_available' => ['title' => __('Доступность', 'usam'), 'name_amazon' => 'Inventory Available (US, CA, MX)', 'default' => 'Yes', 'type_data' => 'attribute'],			
			'fulfillment_availability#1.lead_time_to_ship_max_days' => ['title' => __('Время обработки', 'usam'), 'name_amazon' => 'Handling Time (US, CA, MX)', 'default' => 12, 'type_data' => 'attribute'],			
			'fulfillment_availability#1.quantity' => ['name_amazon' => 'Quantity (US, CA, MX)', 'default' => 30, 'type_data' => 'system'],				
			'fulfillment_availability#1.restock_date' => ['title' => 'Restock Date (US, CA, MX)', 'name_amazon' => 'Restock Date (US, CA, MX)', 'default' => '', 'type_data' => 'attribute'],		
			'purchasable_offer[marketplace_id=ATVPDKIKX0DER]#1.discounted_price#1.schedule#1.value_with_tax' => ['title' => 'Sale Price USD (US)', 'name_amazon' => 'Sale Price USD (US)', 'default' => '', 'type_data' => 'attribute'],			
			'purchasable_offer[marketplace_id=ATVPDKIKX0DER]#1.discounted_price#1.schedule#1.start_at' => ['title' => 'Sale Start Date (US)', 'name_amazon' => 'Sale Start Date (US)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=ATVPDKIKX0DER]#1.discounted_price#1.schedule#1.end_at' => ['title' => 'Sale End Date (US)', 'name_amazon' => 'Sale End Date (US)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=ATVPDKIKX0DER]#1.end_at.value' => ['title' => 'Stop Selling Date (US)', 'name_amazon' => 'Stop Selling Date (US)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=ATVPDKIKX0DER]#1.map_price#1.schedule#1.value_with_tax' => ['title' => 'Minimum Advertised Price (US)', 'name_amazon' => 'Minimum Advertised Price (US)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=ATVPDKIKX0DER]#1.our_price#1.schedule#1.value_with_tax' => ['name_amazon' => 'Your Price USD (US)', 'default' => '', 'type_data' => 'system'],				
			'purchasable_offer[marketplace_id=ATVPDKIKX0DER]#1.start_at.value' => ['title' => 'Offering Release Date (US)', 'name_amazon' => 'Offering Release Date (US)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A2EUQ1WTGCTBG2]#1.discounted_price#1.schedule#1.value_with_tax' => ['title' => 'Sale Price CAD (CA)', 'name_amazon' => 'Sale Price CAD (CA)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A2EUQ1WTGCTBG2]#1.discounted_price#1.schedule#1.start_at' => ['title' => 'Sale Start Date (CA)', 'name_amazon' => 'Sale Start Date (CA)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A2EUQ1WTGCTBG2]#1.discounted_price#1.schedule#1.end_at' => ['title' => 'Sale End Date (CA)', 'name_amazon' => 'Sale End Date (CA)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A2EUQ1WTGCTBG2]#1.end_at.value' => ['title' => 'Stop Selling Date (CA)', 'name_amazon' => 'Stop Selling Date (CA)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A2EUQ1WTGCTBG2]#1.map_price#1.schedule#1.value_with_tax' => ['title' => 'Minimum Advertised Price (CA)', 'name_amazon' => 'Minimum Advertised Price (CA)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A2EUQ1WTGCTBG2]#1.our_price#1.schedule#1.value_with_tax' => ['title' => 'Your Price CAD (CA)', 'name_amazon' => 'Your Price CAD (CA)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A2EUQ1WTGCTBG2]#1.start_at.value' => ['title' => 'Offering Release Date (CA)', 'name_amazon' => 'Offering Release Date (CA)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A1AM78C64UM0Y8]#1.discounted_price#1.schedule#1.value_with_tax' => ['title' => 'Sale Price MXN (MX)', 'name_amazon' => 'Sale Price MXN (MX)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A1AM78C64UM0Y8]#1.discounted_price#1.schedule#1.start_at' => ['title' => 'Sale Start Date (MX)', 'name_amazon' => 'Sale Start Date (MX)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A1AM78C64UM0Y8]#1.discounted_price#1.schedule#1.end_at' => ['title' => 'Sale End Date (MX)', 'name_amazon' => 'Sale End Date (MX)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A1AM78C64UM0Y8]#1.end_at.value' => ['title' => 'Stop Selling Date (MX)', 'name_amazon' => 'Stop Selling Date (MX)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A1AM78C64UM0Y8]#1.map_price#1.schedule#1.value_with_tax' => ['title' => 'Minimum Advertised Price (MX)', 'name_amazon' => 'Minimum Advertised Price (MX)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A1AM78C64UM0Y8]#1.our_price#1.schedule#1.value_with_tax' => ['title' => 'Your Price MXN (MX)', 'name_amazon' => 'Your Price MXN (MX)', 'default' => '', 'type_data' => 'attribute'],
			'purchasable_offer[marketplace_id=A1AM78C64UM0Y8]#1.start_at.value' => ['title' => 'Offering Release Date (MX)', 'name_amazon' => 'Offering Release Date (MX)', 'default' => '', 'type_data' => 'attribute'],
			'business_price' => ['title' => 'Business Price', 'name_amazon' => 'Business Price', 'default' => '', 'type_data' => 'attribute', 'amazon_first_line' => 'B2B'],
			'quantity_price_type' => ['title' => 'Quantity Price Type', 'name_amazon' => 'Quantity Price Type', 'default' => '', 'type_data' => 'attribute'],
			'quantity_lower_bound1' => ['title' => 'Quantity Lower Bound 1', 'name_amazon' => 'Quantity Lower Bound 1', 'default' => '', 'type_data' => 'attribute'],
			'quantity_price1' => ['title' => 'Quantity Price 1', 'name_amazon' => 'Quantity Price 1', 'default' => '', 'type_data' => 'attribute'],
			'quantity_lower_bound2' => ['title' => 'Quantity Lower Bound 2', 'name_amazon' => 'Quantity Lower Bound 2', 'default' => '', 'type_data' => 'attribute'],
			'quantity_price2' => ['title' => 'Quantity Price 2', 'name_amazon' => 'Quantity Price 2', 'default' => '', 'type_data' => 'attribute'],
			'quantity_lower_bound3' => ['title' => 'Quantity Lower Bound 3', 'name_amazon' => 'Quantity Lower Bound 3', 'default' => '', 'type_data' => 'attribute'],
			'quantity_price3' => ['title' => 'Quantity Price 3', 'name_amazon' => 'Quantity Price 3', 'default' => '', 'type_data' => 'attribute'],
			'quantity_lower_bound4' => ['title' => 'Quantity Lower Bound 4', 'name_amazon' => 'Quantity Lower Bound 4', 'default' => '', 'type_data' => 'attribute'],
			'quantity_price4' => ['title' => 'Quantity Price 4', 'name_amazon' => 'Quantity Price 4', 'default' => '', 'type_data' => 'attribute'],
			'quantity_lower_bound5' => ['title' => 'Quantity Lower Bound 5', 'name_amazon' => 'Quantity Lower Bound 5', 'default' => '', 'type_data' => 'attribute'],
			'quantity_price5' => ['title' => 'Quantity Price 5', 'name_amazon' => 'Quantity Price 5', 'default' => '', 'type_data' => 'attribute'],
			'progressive_discount_type' => ['title' => 'Progressive Discount Type', 'name_amazon' => 'Progressive Discount Type', 'default' => '', 'type_data' => 'attribute'],
			'progressive_discount_lower_bound1' => ['title' => 'Progressive Discount Lower Bound 1', 'name_amazon' => 'Progressive Discount Lower Bound 1', 'default' => '', 'type_data' => 'attribute'],
			'progressive_discount_value1' => ['title' => 'Progressive Discount Value 1', 'name_amazon' => 'Progressive Discount Value 1', 'default' => '', 'type_data' => 'attribute'],
			'progressive_discount_lower_bound2' => ['title' => 'Progressive Discount Lower Bound 2', 'name_amazon' => 'Progressive Discount Lower Bound 2', 'default' => '', 'type_data' => 'attribute'],
			'progressive_discount_value2' => ['title' => 'Progressive Discount Value 2', 'name_amazon' => 'Progressive Discount Value 2', 'default' => '', 'type_data' => 'attribute'],
			'progressive_discount_lower_bound3' => ['title' => 'Progressive Discount Lower Bound 3', 'name_amazon' => 'Progressive Discount Lower Bound 3', 'default' => '', 'type_data' => 'attribute'],
			'progressive_discount_value3' => ['title' => 'Progressive Discount Value 3', 'name_amazon' => 'Progressive Discount Value 3', 'default' => '', 'type_data' => 'attribute'],
			'national_stock_number' => ['title' => 'National Stock Number', 'name_amazon' => 'National Stock Number', 'default' => '', 'type_data' => 'attribute'],
			'unspsc_code' => ['title' => 'United Nations Standard Products and Services Code', 'name_amazon' => 'United Nations Standard Products and Services Code', 'default' => '', 'type_data' => 'attribute'],
			'pricing_action' => ['title' => 'Pricing Action', 'name_amazon' => 'Pricing Action', 'default' => '', 'type_data' => 'attribute']			
		];
		return $array;
	}
	
	public function get_form( ) 
	{		
		$product_attributes = get_terms(['hide_empty' => 0, 'orderby' => 'name', 'taxonomy' => 'usam-product_attributes']);	
		foreach( $product_attributes as $term )
		{
			if ( $term->parent != 0 )
			{				
				$this->product_attributes[$term->slug] = $term->name;
			}			
		}		
		$data = $this->get_file_structure();
		foreach( $data as $key => $value )
		{
			if ( $value['type_data'] == 'attribute' )
			{
				?>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php echo $value['title']; ?>:</div>
					<div class ="edit_form__item_option">
						<?php $this->display_attributes( $key ); ?>
					</div>
				</div>
				<?php
			}
		}		
	}
	
	protected function display_attributes( $property ) 
	{
		?>
		<select name='columns[<?php echo $property; ?>]'>
			<option value=''><?php _e('По умолчанию', 'usam'); ?></option>
			<?php				
			$d = isset($this->rule['columns'][$property]) ? $this->rule['columns'][$property] : '';
			foreach( $this->product_attributes as $key => $title ) 			
			{			
				?><option <?php selected($d, $key); ?> value='<?php echo $key; ?>'><?php echo $title; ?></option><?php
			}
			?>
		</select>	
		<?php
	}
	
	public function save_form( ) 
	{ 
		$new_rule['columns'] = isset($_POST['columns'])?array_map('sanitize_text_field', $_POST['columns']):[];
		return $new_rule;
	}
}