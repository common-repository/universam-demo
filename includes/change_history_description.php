<?php

function usam_change_history_event_type( $item ) 
{
	$text = ''; 	
	switch ( $item->operation ) 
	{						
		case 'delete':					
			switch ( $item->field ) 
			{				
				case 'product':					
					$text = __('Удален товар','usam');	
				break;	
				case 'action':					
					$text = __('Удален пункт из списка действий','usam');	
				break;					
				case 'participant':					
					$text = __('Удаление исполнителя','usam');
				break;
				case 'responsible':					
					$text = __('Удаление ответственного','usam');
				break;
				case 'observer':					
					$text = __('Удаление наблюдателя','usam');
				break;
				default:				
					$text = __('Удалено','usam');
				break;					
			}			
		break;
		case 'edit':								
			switch ( $item->field ) 
			{				
				case 'product_old_price':
				case 'product_price':
				case 'product_quantity':					
					$text = __('Изменение товара','usam');	
				break;
				case 'status':					
					$text = __('Изменение статуса','usam');	
				break;				
				case 'sum':	
				case 'totalprice':					
					$text = __('Изменение суммы','usam');	
				break;	
				case 'number':					
					$text = __('Изменение номера','usam');	
				break;	
				case 'number_products':					
					$text = __('Изменение количества товара в документе','usam');	
				break;
				case 'date_insert':					
					$text = __('Изменение даты создания','usam');	
				break;				
				case 'type_price':					
					$text = __('Изменение типа цены','usam');	
				break;
				case 'type_price':					
					$text = __('Изменение фирмы','usam');	
				break;				
				case 'closedate':					
					$text = __('Изменение даты оплаты документа','usam');	
				break;				
				case 'manager_id':					
					$text = __('Изменение менеджера','usam');	
				break;			
				case 'customer_id':					
					$text = __('Изменение контрагента','usam');	
				break;			
				case 'external_document_date':					
					$text = __('Изменение даты внешнего документа','usam');	
				break;
				case 'external_document':					
					$text = __('Изменение номера внешнего документа','usam');	
				break;	
				case 'title':					
					$text = __('Обновлено название','usam');						
				break;
				case 'text':					
					$text = __('Обновлено описание','usam');									
				break;
				case 'name':					
					$details_documents = usam_get_details_documents();	
					if ( isset($details_documents[$item->object_type]) )
						$text = __('Изменение названия документа','usam');						
				break;				
				case 'note':				
					$text = __('Изменение комментария','usam');	
				break;				
				default: 			
					switch ( $item->object_type ) 
					{					
						case 'order':	
							$text = __('Изменение реквизитов заказа','usam');
						break;
						case 'lead':
							$text = __('Изменение реквизитов лида','usam');
						break;					
					}
				break;
			}
		break;
		case 'add':						
			switch ( $item->field ) 
			{						
				case 'participant':					
					$text = __('Добавление исполнителя','usam');
				break;
				case 'responsible':					
					$text = __('Добавление ответственного','usam');
				break;
				case 'observer':					
					$text = __('Добавление наблюдателя','usam');
				break;
				case 'action':					
					$text = __('Добавление в список действия','usam');
				break;
				case 'product':					
					$text = __('Добавление товара','usam');
				break;								
				default:					
					switch ( $item->object_type ) 
					{		
						case 'order':					
							$text = __('Создание заказа','usam');	
						break;
					}
				break;
			}
		break;		
		case 'view':			
			switch ( $item->object_type ) 
			{						
				case 'product':											
					$text = sprintf( __('Просмотр товара %s','usam'), get_the_title( $item->object_id ) );	
				break;		
				case 'document':					
					$text = sprintf( __('Просмотр документа %s','usam'), $item->value );
				break;					
				case 'order':					
					$text = sprintf( __('Просмотр заказа %s','usam'), $item->object_id );
				break;	
				case 'lead':					
					$text = sprintf( __('Просмотр лида %s','usam'), $item->object_id );
				break;						
				case 'email':					
					$text = sprintf( __('Просмотр письма от %s','usam'), $item->value );
				break;			
				case 'sms':					
					$text = sprintf( __('Просмотр СМС сообщения %s','usam'), $item->value );	
				break;		
				case 'review':					
					$text = sprintf( __('Просмотр отзыва %s','usam'), $item->value );	
				break;						
			}
		break;				
	}
	return $text;
}

function usam_change_history_description( $item ) 
{  
	static $order_properties = null;
	$text = '';
	switch ( $item->operation ) 
	{	
		case 'delete':
			switch ( $item->object_type ) 
			{							
				case 'product':					
					switch ( $item->field ) 
					{						
						case 'product':									
							$text = sprintf( __('Удален товар %s','usam'), "<a href='".get_edit_post_link($item->sub_object_id)."'>$item->value</a>" );
						break;			
						default:
							$text = sprintf( __('Удалено &#8220;%s&#8221;.','usam'), $item->old_value );	
						break;				
					}	
				break;
				case 'order':						
					switch ( $item->field ) 
					{						
						case 'product':			
							$text = sprintf( __('Удален товар %s','usam'), "<a href='".get_edit_post_link($item->sub_object_id)."'>$item->value</a>" );
						break;					
						case 'customer_data':	
						default:
							$text = sprintf( __('Удалено &#8220;%s&#8221;.','usam'), $item->old_value );	
						break;				
					}	
				break;		
				default:
					switch ( $item->field ) 
					{							
						case 'participant':			
						case 'responsible':			
						case 'observer':							
							$contact = usam_get_contact( $item->value, 'user_id' );
							if( $contact )
								$text = "<a href='".usam_get_contact_url( $contact['id'] )."'>{$contact['name']}</a>";
						break;
						default:
							$text = $item->value;
						break;
					}
				break;						
			}				
		break;
		case 'edit': 
			switch ( $item->field ) 
			{
				case 'status':					
					$text = sprintf('%s <span class="dashicons dashicons-arrow-right-alt"></span> %s', usam_get_object_status_name( $item->old_value, $item->object_type ), usam_get_object_status_name( $item->value, $item->object_type ) );	
				break;
				case 'external_document_date':	
				case 'closedate':
				case 'date_paid':				
				case 'date_insert':					
					$text = sprintf( '&#8220;%s&#8221; <span class="dashicons dashicons-arrow-right-alt"></span> &#8220;%s&#8221;.', usam_local_date($item->old_value), usam_local_date($item->value) );
				break;																												
				case 'bank_account_id':					
					$text = sprintf( '&#8220;%s&#8221; <span class="dashicons dashicons-arrow-right-alt"></span> &#8220;%s&#8221;.', usam_get_display_company_by_acc_number($item->old_value), usam_get_display_company_by_acc_number($item->value) );
				break;		
				case 'totalprice':					
					$text = sprintf('%s <span class="dashicons dashicons-arrow-right-alt"></span> %s', $item->old_value, $item->value );	
				break;
				case 'product_quantity':					
					$title = get_the_title( $item->sub_object_id );
					$text = sprintf( __('Изменено количества у товара &#8220;%s&#8220; %s <span class="dashicons dashicons-arrow-right-alt"></span> %s','usam'), $title, $item->old_value, $item->value );	
				break;
				case 'product_price':					
					$title = get_the_title( $item->sub_object_id );
					$text = sprintf( __('Изменена цена у товара &#8220;%s&#8220; %s <span class="dashicons dashicons-arrow-right-alt"></span> %s','usam'), $title, $item->old_value, $item->value );	
				break;
				case 'product_old_price':					
					$title = get_the_title( $item->sub_object_id );
					$text = sprintf( __('Изменена скидка у товара &#8220;%s&#8220; %s <span class="dashicons dashicons-arrow-right-alt"></span> %s','usam'), $title, $item->old_value, $item->value );	
				break;
				case 'manager_id':	
					if ( $item->old_value )
						$text = sprintf( 'Изменение менеджера &#8220;%s&#8221; <span class="dashicons dashicons-arrow-right-alt"></span> &#8220;%s&#8221;', usam_get_manager_name( $item->old_value ), usam_get_manager_name( $item->value ) );
					else
						$text = sprintf( 'Указание менеджера &#8220;%s&#8221;.', usam_get_manager_name( $item->value ) );
				break;
				case 'user_id':
				case 'user_ID':					
					$old_user = get_user_by('id', $item->old_value );							
					$user = get_user_by('id', $item->value );
					$text = sprintf( '&#8220;%s&#8221; <span class="dashicons dashicons-arrow-right-alt"></span> &#8220;%s&#8221;', !empty($old_user->data) ? $old_user->data->user_login : $item->old_value, !empty($user->data) ? $user->data->user_login : $item->value );
				break;
				case 'type_price':	
					$text = sprintf( '&#8220;%s&#8221; <span class="dashicons dashicons-arrow-right-alt"></span> &#8220;%s&#8221;', usam_get_name_price_by_code( $item->old_value ), usam_get_name_price_by_code( $item->value ) );
				break;
				case 'customer':		
					$value = explode('-',$item->value);					
					if( $value[0] == 'contact' ) 
						$customer = usam_get_contact( $value[1] );
					else
						$customer = usam_get_company( $value[1] );
					$old_value = explode('-',$item->old_value);					
					if( $old_value[0] == 'contact' ) 
						$old_customer = usam_get_contact( $old_value[1] );
					else
						$old_customer = usam_get_company( $old_value[1] );
					$text = sprintf( '&#8220;%s&#8221; <span class="dashicons dashicons-arrow-right-alt"></span> &#8220;%s&#8221;', $old_customer['name'], $customer['name'] );
				break;				
				default:		
					if ( is_numeric($item->old_value) )
						$text = sprintf('%s <span class="dashicons dashicons-arrow-right-alt"></span> %s', number_format($item->old_value, 0, '.', ' '), number_format($item->value, 0, '.', ' ') );
					elseif( is_email($item->value) && !current_user_can('view_communication_data') && current_user_can('store_section') )
						$text = sprintf( '%s <span class="dashicons dashicons-arrow-right-alt"></span> %s', usam_get_hiding_data( $item->old_value, 'email' ), usam_get_hiding_data( $item->value, 'email' ) );
					else
						$text = sprintf( '%s <span class="dashicons dashicons-arrow-right-alt"></span> %s', $item->old_value, $item->value );
				break;					
			}
		break;
		case 'add':
			switch ( $item->field ) 
			{							
				case 'participant':			
				case 'responsible':			
				case 'observer':							
					$contact = usam_get_contact( $item->value, 'user_id' );
					if( $contact )
						$text = "<a href='".usam_get_contact_url( $contact['id'] )."'>{$contact['name']}</a>";
				break;
				default:
					$text = $item->value;
				break;
			}
		break;			
	}
	return $text;
}
	
?>