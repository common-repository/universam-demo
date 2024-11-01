<?php 
$html_table = '';	
$product_attributes = get_terms( array( 'hide_empty' => 0, 'orderby' => 'sort', 'taxonomy' => 'usam-product_attributes' ) );	
$html_table = '<div class="edit_form">';
foreach( $product_attributes as $term )
{							
	if ( $term->parent == 0 )
	{						
		$out = '';
		foreach( $product_attributes as $attr )
		{
			if ( $term->term_id == $attr->parent )
			{
				if ( $attr->slug == 'brand' )
					continue;
				$disabled = '';									
				$field_type = usam_get_term_metadata($attr->term_id, 'field_type');					
				$class = "show_change";										
				$fled_name = "data-slug='$attr->slug'";
				$fled_id = "id='product_attribute_{$attr->term_id}'";						
				$out .= "<div class='edit_form__item'>
							<div class='edit_form__item_name'><label for='product_attribute_{$attr->term_id}'>".$attr->name.":</label></div>
							<div class='edit_form__item_option'>";
							switch ( $field_type ) 
							{									
								case 'C' ://Один																									
									$out .= "<input type='hidden' value='0' $disabled $fled_name>";
									$out .= "<input type='checkbox' value='1' $disabled class='$class' $fled_name $fled_id>";
								break;
								case 'COLOR_SEVERAL' :
								case 'M' ://Несколько																					
									$attribute_values = usam_get_attribute_values( $attr->term_id );
									if ( !empty($attribute_values) )
									{											
										$out .= "<select class='$class' multiple='multiple' $fled_name $fled_id>";
										foreach ( $attribute_values as $option )
										{		
											$out .= "<option value='$option->id'>$option->value</option>";
										}	
										$out .= "</select>";	
									}
								break;
								case 'COLOR' :
								case 'BUTTONS' :
								case 'AUTOCOMPLETE' :								
								case 'N' ://Число
								case 'S' ://Текст											
									$attribute_values = usam_get_attribute_values( $attr->term_id );
									if ( !empty($attribute_values) )
									{
										$out .= "<select class='$class' $fled_name $fled_id>";														
										$out .= "<option value=''>".__("Не выбрано","usam")."</option>";
										foreach ( $attribute_values as $option )
										{					
											$out .= "<option value='$option->id'>$option->value</option>";
										}				
										$out .= "</select>";
									}
								break;																	
								case 'D' ://Дата											
									$class = 'js-date-picker';
									$out .= "<input type='text' class='$class' $fled_name $fled_id maxlength='10' pattern='(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}' />";
								break;
								case 'O' ://Число
									$out .= "<input $disabled class='$class' type='text' $fled_name $fled_id>";
								break;								
								case 'A' ://Агенты	
									$users = get_users( array('orderby' => 'nicename', 'role__in' => array('employee'), 'fields' => array( 'ID','display_name')) );
									if ( !empty($users) )
									{
										$out .= "<select class='$class' $fled_name $fled_id>";														
										foreach ( $users as $user )
										{					
											$out .= "<option value='$user->ID'>$user->display_name</option>";
										}				
										$out .= "</select>";
									}
								break;			
								case 'TIME' :	
									$out .=  "<input $disabled class='$class' type='text' $fled_name $fled_id>";
								break;								
								case 'YOUTUBE' :
								case 'T' ://Текст
								default:			
									$out .=  "<input $disabled class='$class' type='text' $fled_name $fled_id>";
								break;
							}									
						$out .= "</div></div>";
			}
		}
		if ( $out != '' )
			$html_table .= "<div class='edit_form__title'><strong>".$term->name."</strong></div>".$out;
	}
}		
$html_table .= "</div>";		
?>
<div id="bulk_actions_product_attribute" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Изменить свойства у товаров','usam'); ?></div>
	</div>	
	<div class='modal-body'>
		<div class='product_bulk_actions'>
			<div class='colum1'>
				<div class='title'><?php _e('Характеристики товара','usam') ?></div>
				<div class='product_attributes modal-scroll'><?php echo $html_table; ?></div>	
			</div>
			<div class='colum2'>
				<div class='title'><?php _e('Товары','usam'); ?></div>
				<div class='products modal-scroll'><?php _e('У товаров с учетом выбранных фильтров','usam'); ?></div>
			</div>
		</div>
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e('Сохранить', 'usam'); ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e('Закрыть', 'usam'); ?></button>
		</div>
	</div>
</div>