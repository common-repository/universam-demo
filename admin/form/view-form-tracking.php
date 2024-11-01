<?php		
//http://rupost.info/api
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_tracking extends USAM_View_Form
{		
	private $shipped_document;		
	protected function get_title_tab()
	{ 	
		return sprintf(__('Отслеживание посылки № %s', 'usam'), $this->shipped_document['track_id']);
	}
	
	protected function title_tab_none( ) 
	{ 
		esc_attr_e('Почтовое отправление не найдено.','usam');
	}
	
	protected function toolbar_buttons( ) {	}
	
	protected function get_url_go_back( ) 
	{ 
		return add_query_arg(['form' => 'edit', 'form_name' => 'shipped', 'id' => $this->shipped_document['id'], 'page' => 'storage', 'tab' => 'warehouse_documents'], admin_url('admin.php') );
	}
	
	protected function get_data_tab()
	{ 	
		$this->shipped_document = usam_get_shipped_document( $this->id );
		$shipped_instance = usam_get_shipping_class( $this->shipped_document['method'] );	
		$this->data = $shipped_instance->get_delivery_history( $this->shipped_document['track_id'] );	
		$error = $shipped_instance->get_errors( );		
		if ( !empty($error) ) 
		{ 
			usam_set_user_screen_error( $error );
		} 			
		$this->tabs = [ 
			['slug' => 'history', 'title' => __('История','usam')], 		
		];
	}
	
	protected function main_content_cell_1( ) 
	{ 						
		?>		
		<h4><?php _e('Текущий статус', 'usam'); ?></h4>
		<div class="view_data">		
			<?php 
			if ( !empty($this->data['status_description']) )	
			{
				?>
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Статус','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $this->data['status_description']; ?></div>
				</div>
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Выдан','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $this->data['issued']?__('Да','usam'):__('Нет','usam'); ?></div>
				</div>	
				<?php 
			}
			if ( !empty($this->data['operations']) ) 
			{
				end($this->data['operations']);
				$data = current($this->data['operations']);
				?>				
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Местонахождение','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $data['name']; ?></div>
				</div>		
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Последние изменения статуса','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $data['date']; ?></div>
				</div>				
			<?php
			} 
			?>	
		</div>	
		<?php	
	}		
	
	protected function main_content_cell_2( ) 
	{ 		
		?>	
		<h4><?php _e('Данные для доставки', 'usam'); ?></h4>
		<div class="view_data">				
			<?php if ( !empty($this->data['destination_address']) ) { ?>		
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Адрес назначения','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $this->data['destination_address']['country'].' '.$this->data['destination_address']['address'].' '.$this->data['destination_address']['index']; ?></div>
				</div>
			<?php 
			} 
			else
			{
				$properties = usam_get_properties(['type' => 'order']);	
				foreach( $properties as $property )		
				{  
					if ( stripos($property->code,'shipping') !== false )
					{
						$value = usam_get_order_metadata( $this->shipped_document['order_id'], $property->code );
						if ( $value )
						{
							?>
							<div class ="view_data__row">
								<div class ="view_data__name"><label><?php echo $property->name; ?>:</label></div>
								<div class ="view_data__option"><?php echo usam_get_formatted_property( $value, $property ); ?></div>
							</div>
							<?php	
						}
					}
				}
			}			
			?>	
		</div>	
		<?php	
	}
	
	protected function main_content_cell_3( ) 
	{				
		?>		
		<h4><?php _e('Данные отправления', 'usam'); ?></h4>
		<div class="view_data">
			<?php if ( !empty($this->data['sender_name']) ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Отправитель','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $this->data['sender_name']; ?></div>
				</div>
			<?php } ?>		
			<?php if ( !empty($this->data['recipient']) ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Получатель','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $this->data['recipient']; ?></div>
				</div>
			<?php } ?>							
			<?php if ( !empty($this->data['weight']) ) { ?>				
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Вес','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $this->data['weight']; ?></div>
				</div>		
			<?php } ?>	
			<?php if ( !empty($this->data['departure_type']) ) { ?>	
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Тип отправления','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $this->data['departure_type']; ?></div>
				</div>
			<?php } ?>	
			<?php if ( !empty($this->data['payment']) ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Стоимость отправления','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $this->data['payment']; ?></div>
				</div>
			<?php } ?>				
		</div>	
		<?php	
	}
	
	function display_tab_history()
	{
		?>	
		<table class = "wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td><?php esc_html_e( 'Дата', 'usam'); ?></td>
					<td><?php esc_html_e( 'Место', 'usam'); ?></td>
					<td><?php esc_html_e( 'Описание', 'usam'); ?></td>
				</tr>
			</thead>
			<tbody>
			<?php
			if ( !empty($this->data['operations']) )
				foreach ( $this->data['operations'] as $value )
				{
					?>
					<tr>
						<td><?php echo $value['date']; ?></td>
						<td><?php echo $value['name']; ?></td>
						<td><?php echo $value['description']; ?></td>						
					</tr>
					<?php				
				}
			?>				
			</tbody>
		</table>
		<?php			
	}
}