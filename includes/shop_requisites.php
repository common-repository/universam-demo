<?php

function usam_shop_logo( ) 
{
	$shop_company = get_option( 'usam_shop_company' );
	$bank_account = usam_get_bank_account( $shop_company );
	if ( !empty($bank_account['company_id']) )
		$thumbnail = usam_get_company_logo( $bank_account['company_id'] );
	else
		$thumbnail = usam_get_no_image_uploaded_file([60,60]);
	return $thumbnail;
}

function usam_shop_requisites()
{
	$shop_company = get_option( 'usam_shop_company' );
	return usam_get_company_by_acc_number( $shop_company );
}

function usam_shop_requisites_shortcode( $key = '' )
{
	static $requisites = null;
	
	$requisites = get_option('usam_shop_requisites_shortcode');
	if ( !$requisites )
	{
		$requisites = usam_shop_requisites();	
		$requisites['contactaddress'] = !empty($requisites['contactaddress'])?$requisites['contactaddress']:'';
		$requisites['full_legaladdress'] = !empty($requisites['full_legaladdress'])?$requisites['full_legaladdress']:'';
		$requisites['full_company_name'] = !empty($requisites['full_company_name'])?$requisites['full_company_name']:'';		
		$requisites['contactpostcode'] = !empty($requisites['contactpostcode'])?$requisites['contactpostcode']:'';
		$requisites['legalpostcode'] = !empty($requisites['legalpostcode'])?$requisites['legalpostcode']:'';
		$requisites['legaladdress'] = !empty($requisites['legaladdress'])?$requisites['legaladdress']:'';;
		$requisites['phone'] = !empty($requisites['phone'])?$requisites['phone']:'';
		$requisites['shop_name'] = get_option( 'blogname' );			
		$requisites['shop_mail'] = usam_get_shop_mail();
		$requisites['shop_phone'] = usam_get_shop_phone();		
		$requisites['latitude'] = (float)usam_get_company_metadata( $requisites['id'], 'latitude' );
		$requisites['longitude'] = (float)usam_get_company_metadata( $requisites['id'], 'longitude' );
		$thumbnail_id = usam_get_company_metadata( $requisites['id'], 'logo' );
		if ( $thumbnail_id )
		{
			$image = wp_get_attachment_image_src( $thumbnail_id, 'full' );
			if ( $image )
			{
				$thumbnail = $image[0];
				$requisites['logo_url'] = $image[0];	
				$requisites['logo_width'] = $image[1];	
				$requisites['logo_height'] = $image[2];	
				$requisites['logo_caption'] = wp_get_attachment_caption( $thumbnail_id );
			}
			else
				$thumbnail = usam_get_no_image_uploaded_file( 'full' );	
		}
		if ( isset($requisites['shop_logo']) )
			$requisites['shop_logo'] = '<img class="shop_logo" src="'.$thumbnail.'" alt ="logo">';
		else
			$requisites['shop_logo'] = '';	
		update_option('usam_shop_requisites_shortcode', $requisites);
	}
	if ( $key )
		return !empty($requisites[$key])?$requisites[$key]:'';
	else
		return $requisites;
}

function usam_get_shop_mail( $encode = true )
{		
	$email = get_option("usam_return_email");
	if ( $email )
	{
		if ( $encode )
			return usam_encode_email( $email );
		else
			return $email;
	}
	return '';
}

function usam_get_shop_phone( $format = true )
{		
	$shop_phone = get_option( 'usam_shop_phone' );
	if ( $format && $shop_phone )
	{
		foreach( usam_get_phones() as $phone )
		{	
			if ( $shop_phone == $phone['phone'] )
				return usam_phone_format($phone['phone'], $phone['format']);
		}	
	}	
	return $shop_phone;
}

function usam_get_shop_social( )
{		
	$requisites = usam_shop_requisites_shortcode();	
	$socials = ['vk', 'facebook', 'telegram', 'telegram', 'viber', 'skype', 'icq', 'msn', 'jabber', 'twitter', 'ok'];
	$results = [];
	foreach ($requisites as $key => $value) 
	{
		if ( $value && in_array($key, $socials) )
			$results[$key] = $value;		
	}
	return $results;
}
?>