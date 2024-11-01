<?php	
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
require_once( USAM_FILE_PATH . '/includes/customer/customer_account.class.php' );
class USAM_Form_customer_account extends USAM_View_Form
{	
	protected function get_title_tab()
	{ 	
		return sprintf( __('Клиентский счет &#8220;%d&#8221;', 'usam'), $this->id );
	}
	
	protected function get_data_tab()
	{ 	
		$this->data = usam_get_customer_account( $this->id );	
		$this->tabs = array( 		
			array( 'slug' => 'report', 'title' => __('Отчет','usam') ),		
			array( 'slug' => 'transactions', 'title' => __('Транзакции','usam') ), 
			array( 'slug' => 'change', 'title' => __('Изменения','usam') ),			
		);		
	}		
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
		
	protected function main_content_cell_1( ) 
	{				
		$timestamp = strtotime( $this->data['date_insert'] );	
		$date = human_time_diff( $timestamp, time() );	
		?>							
		<div class="view_data">					
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Сумма', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<span v-if="data.sum>0" class="item_status item_status_valid">{{formatted_number( data.sum )}}</span>
					<span v-else class="item_status item_status_attention">{{formatted_number( data.sum )}}</span>
				</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Создан', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_local_date( $this->data['date_insert'] ); ?></div>
			</div>			
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php esc_html_e( 'Статус', 'usam'); ?>:</label></div>
				<div class ="view_data__option">
					<span class="<?php echo $this->data['status'] == 'active'?'item_status_valid':'status_blocked'; ?> item_status"><?php echo usam_get_customer_account_status_name( $this->data['status'] ); ?></span>
				</div>
			</div>							
		</div>	
		<?php	
	}	
	
	protected function main_content_cell_2( ) 
	{ 		
		?>		
		<div class="view_data">				
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php _e('Клиент', 'usam'); ?>:</label></div>
				<div class ="view_data__option">
					<a href='<?php echo usam_get_contact_url( $this->data['user_id'], 'user_id' ); ?>'><?php echo usam_get_customer_name( $this->data['user_id'] ); ?></a>
				</div>
			</div>			
		</div>	
		<?php
	}		
	
	protected function main_content_cell_3( ) 
	{ 			
		if ( current_user_can( 'view_account_transactions' ) &&  $this->data['status'] == 'active' )
		{	
			?>		
			<h3><?php _e('Добавить сумму', 'usam'); ?></h3>
			<div class="view_data">				
				<div class ="view_data__row">
					<div class ="view_data__name"><?php _e('Сумма', 'usam'); ?>:</div>
					<div class ="view_data__option">
						<input type='text' v-model="sum">
					</div>
				</div>		
				<div class ="view_data__row">
					<div class ="view_data__name"><?php _e('Основание', 'usam'); ?>:</div>
					<div class ="view_data__option">
						<textarea v-model="description"></textarea>
					</div>
				</div>	
				<div class ="view_data__row">
					<div class ="view_data__name"></div>
					<div class ="view_data__option">
						<button class="button" @click="addTransactions"><?php _e('Добавить транзакцию', 'usam'); ?></button>
					</div>
				</div>			
			</div>
			<?php	
		}
	}

	function display_tab_transactions()
	{
		 $columns = [ 
			['id' => 'id', 'name' => __('Номер', 'usam')],
			['id' => 'date', 'name' => __('Дата', 'usam')],	
			['id' => 'sum', 'name' => __('Сумма', 'usam')],
			['id' => 'description', 'name' => __('Основание', 'usam')],
			['id' => 'customer', 'name' => __('Автор операции', 'usam')],				
        ];			
		usam_vue_module('list-table');		
		?>
		<list-table ref="transactions" :query="'account/transactions'" :args="argsTransactions" :columns='<?php echo json_encode( $columns ); ?>' @change="transactions=$event">
			<template v-slot:tbody="slotProps">
				<tr v-for="(item, k) in slotProps.items">
					<td class="column-id">
						{{item.id}}
						<div class="row-actions">
							<a @click="confirm='transaction'+k" v-if="confirm!=='transaction'+k"><?php _e('Удалить', 'usam'); ?></a>							
						</div>
						<div class="row_confirm" v-if="confirm=='transaction'+k">							
							<a class="button" @click="confirm=''"><?php _e('Отменить', 'usam'); ?></a>
							<a class="button-primary" @click="transactionDel(k)"><?php _e('Удалить', 'usam'); ?></a>
						</div>
					</td>
					<td class="column-date">
						{{localDate(item.date_insert,'<?php echo get_option('date_format', 'Y/m/j'); ?>')}}
					</td>					
					<td class="column-sum">		
						<span v-if="item.type_transaction==2" class='item_status status_black' :title="'<?php _e("Активируются через") ?>'+' '+item.days_before_activation">+{{formatted_number( item.sum )}}</span>
						<span v-if="item.type_transaction==1" class='item_status item_status_attention'>-{{formatted_number( item.sum )}}</span>		
						<span v-else class='item_status item_status_valid'>+{{formatted_number( item.sum )}}</span>	
					</td>					
					<td class="column-description">
						<p v-html="item.description"></p>
						<p v-if="item.order_id>0"><?php _e('Заказ', 'usam'); ?> <a :href="'<?php echo admin_url("admin.php?page=orders&tab=orders&form_name=order&form=view"); ?>&id='+item.order_id" >№{{item.order_id}}</a></p>
					</td>
					<td class="column-customer">
						<a class='user_block'>	
							<div class='image_container usam_foto'><img :src='item.user.foto'></div>
							<div class='user_name' v-html="item.user.appeal"></div>							
						</a>
					</td>
				</tr>
			</template>
		</list-table>
		<?php
	}
}
?>