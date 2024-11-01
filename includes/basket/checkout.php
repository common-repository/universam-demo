<?php

/**
 * Нужно ли регистрироваться перед покупкой
 */
function usam_show_user_login_form()
{
	if( !is_user_logged_in() && get_option('usam_registration_upon_purchase') == 'require' )
		return true;
	else
		return false;
}

function usam_has_consent_processing_personal_data()
{
	if('' == get_option('usam_consent_processing_personal_data'))
		return false;
	else
		return true;
}

// Получить сообщения по кодам
function usam_get_errors_checkout( $errors )
{	
	$return = usam_cart_errors_message();	
	if ( !empty($errors) )
	{
		foreach( $errors as $error )	
		{
			switch ( $error ) 
			{
				case "gateway":
					$return[] = __('Вы должны выбрать способ оплаты, в противном случае мы не можем обработать ваш заказ.', 'usam');
				break;		
				case "purchase_rules":
					$return[] = __('Не соответствует правилам покупки на сайте. Почитайте условия покупки или спросите у менеджера сайта.', 'usam');
				break;					
				case "shipping_method":
					$return[] = __('Вы должны выбрать способ доставки, в противном случае мы не можем обработать ваш заказ.', 'usam');
				break;		
				case "verify_nonce":
					$return[] = __('Сеанс устарел. Попробуйте еще раз.', 'usam');
				break;	
				case "login":
					$return[] = __('Вы должны зарегистрироваться или войти перед покупкой.', 'usam');
				break;
				case "validate_forms":
					$return[] = __('Заполните все поля формы.', 'usam');
				break;
				case "save_order":
					$return[] = __('Не возможно создать заказ. Попробуйте еще раз или свяжитесь с нами...', 'usam');
				break;				
				case "cart_empty":
					$return[] = __('Нет ни одного товара в корзине. Пожалуйста, добавьте товар в корзину и попробуйте еще раз, в противном случае мы не можем обработать ваш заказ.', 'usam');
				break;					
			}				
		} 
	}
	return $return;
}

// Получить типы плательщиков
function usam_get_group_payers( $args = [] ) 
{
	$option = get_option('usam_types_payers',array());
	$types_payers = maybe_unserialize($option);	
	$results = array();
	if ( !empty($types_payers) )
	{
		if ( isset($args['active']) && $args['active'] == 'all' )
			unset($args['active']);
		elseif ( !isset($args['active']) )
			$args['active'] = 1;
		else
			$args['active'] = !empty($args['active'])?1:0;

		if ( !empty($args) )
		{			
			foreach( $types_payers as $types_payer )	
			{
				$result = true;
				foreach( $args as $key => $value )	
				{
					if ( isset($types_payer[$key]) )
					{
						if ( $types_payer[$key] != $value )	
						{
							$result = false;
							break;
						}
					}
				}
				if ( $result )
					$results[] = $types_payer;
			}
		}
		else
			$results = $types_payers;
		
		$orderby = !isset($args['orderby']) ?'sort':$args['orderby'];	
		$order = empty($args['order']) ?'ASC':$args['order'];	
		
		if ( $orderby && isset($results[0][$orderby]) )	
		{			
			$comparison = new USAM_Comparison_Object( $orderby, $order );
			usort( $results, [$comparison, 'compare']);
		}
	}
	return $results;
}

// Получить имя плательщика
function usam_get_name_payer( $type_payer_id )
{		
	$type_payer = usam_get_payer( $type_payer_id );
	if ( !empty($type_payer) )
		$name = $type_payer['name'];
	else
		$name = '';		
	return $name;
}

// Тип плательщика компания
function usam_is_type_payer_company( $type_payer_id )
{	
	$type_payer = usam_get_payer( $type_payer_id );
	if ( !empty($type_payer) && $type_payer['type'] == 'company' )
		$type = true;
	else
		$type = false;		
	return $type;
}

function usam_get_payer( $type_payer_id )
{	
	$option = get_option('usam_types_payers',array());
	$types_payers = maybe_unserialize($option);	
	$type_payer = array();		
	foreach( $types_payers as $value )
	{						
		if ( $type_payer_id == $value['id'] )
		{
			$type_payer = $value;
			break;
		}		
	}
	return $type_payer;
}
?>