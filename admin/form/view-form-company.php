<?php		
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_company extends USAM_View_Form
{	
	protected $view = true;	
	protected $ribbon = true;	
	protected function get_data_tab()
	{ 	
		$this->change = current_user_can('edit_company');
		
		$this->data = usam_get_company( $this->id );	
		if ( empty($this->data) )
			return false;
		$user_id = get_current_user_id();		
		if ( !$this->data['open'] && $this->data['manager_id'] != $user_id )
		{
			$this->change = false;			
			$this->view = false;				
		}		
		else
		{			
			$this->tabs = [
				['slug' => 'dossier', 'title' => __('Досье','usam')],	
				['slug' => 'orders', 'title'  => __('Заказы','usam')], 
				['slug' => 'documents', 'title' => __('Документы','usam')], 						
				['slug' => 'company_employees', 'title' => __('Сотрудники','usam')], 		
				['slug' => 'company_divisions', 'title' => __('Подразделения','usam')], 		
				['slug' => 'report', 'title' => __('Отчет','usam')],						
			];			
			if ( $this->data['parent_id'] )
				unset($this->tabs['company_divisions']);	
			if ( !current_user_can('view_orders') )
				unset($this->tabs['orders']);			
			if ( !empty($this->data) )
			{
				$this->header_title = __('Описание', 'usam');
				$this->header_content = usam_get_company_metadata($this->id, 'description');
			}					
		}
		$this->js_args['parent_company'] = usam_get_company( $this->data['parent_id'] );	
		usam_vue_module('list-table');
	}	
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function form_class( ) 
	{ 
		return 'company_view_form';
	}
	
	protected function ability_to_delete( )
	{
		return current_user_can('delete_company');
	}	
	
	protected function toolbar_buttons( ) 
	{
		if ( $this->id != null && $this->view )
		{		
			$this->newsletter_templates( 'company' );
			if ( $this->change ) { 
				?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button"><?php _e('Изменить','usam'); ?></a></div><?php		
			}
			$this->delete_button();
		}
	}
	
	protected function main_content_cell_1( ) 
	{			
		$timestamp = strtotime( $this->data['date_insert'] );
		?>	
		<div class="customer_preview">
			<div class ="customer_preview__left">	
				<div class ="customer_preview__foto image_container">
					<img src='<?php echo usam_get_company_logo( $this->id, [130, 130]) ?>'/>
				</div>
				<span class="item_status_valid item_status status_online" title="<?php esc_html_e( 'Время в базе', 'usam'); ?>"><?php echo human_time_diff( $timestamp, time() ); ?></span>
			</div>
			<div class ="customer_preview__data">					
				<h2 class ="customer_title" id="customer_name"><span v-if="data.parent_id"><?php _e('Подразделение', 'usam'); ?> </span><?php echo $this->data['name']; ?></h2>				
				<div class ="customer_main_company" v-if="data.parent_id"><?php printf( __('Основная компания: %s','usam'), '<a href="'.usam_get_company_url( $this->data['parent_id'] ).'">'.(isset($company['name'])?$company['name']:'').'</a>'); ?></div>
				<div v-if="!data.open" class="object_close"><div class="status_blocked item_status"><?php _e("Закрытая компания","usam"); ?></div></div>
				<?php
				if ( $this->data['type'] == 'partner' ) 
				{				
					?><div class ="customer_partner"><?php printf( __('Партнер №%s','usam'), $this->data['id']); ?></div><?php 
				} 
				$address = usam_get_company_address( $this->id, 'contact', '%country%, %city%' );
				if ( $address ) 
				{ 
					?><div class ="customer_address"><?php echo $address; ?></div><?php 
				}
				usam_display_status( $this->data['status'], 'company' );					
				?>		
			</div>					
		</div>		
		<div class="view_data">	
		<?php $this->display_groups( admin_url("admin.php?page=crm&tab=companies") );	?>		
		</div>	
		<?php			
	}	
	
	protected function main_content_cell_2( ) 
	{ 	
		?>		
		<div class="view_data">				
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Ответственный', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<?php echo $this->data['manager_id']?usam_get_manager_name( $this->data['manager_id'] ):__('Нет','usam'); ?>
				</div>
			</div>
			<?php if ( !empty($this->data['type']) ) { ?>				
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Тип компании', 'usam'); ?>:</label></div>
					<div class ="view_data__option">
						<?php echo usam_get_name_type_company( $this->data['type'] ); ?>
					</div>
				</div>
			<?php } ?>
			<?php if ( !empty($this->data['industry']) ) { ?>				
					<div class ="view_data__row">
						<div class ="view_data__name"><?php esc_html_e( 'Сфера деятельности', 'usam'); ?>:</label></div>
						<div class ="view_data__option">
							<?php echo esc_html(usam_get_name_industry_company( $this->data['industry'] )); ?>
						</div>
					</div>
			<?php } ?>		
			<?php 
			$employees = usam_get_company_metadata($this->id, 'employees');
			if ( $employees ) 
			{ 
				$employees_lists = ['-50' => __('менее 50','usam'), '250' => __('50-250','usam'), '500' => __('250-500','usam'), '+500' => __('более 500','usam')];
				?>		
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Кол-во сотрудников', 'usam'); ?>:</label></div>
					<div class ="view_data__option">
						<?php echo $employees_lists[$employees]; ?>
					</div>
				</div>
			<?php } 
			$revenue = usam_get_company_metadata( $this->id, 'revenue');
			if ( $revenue ) { ?>		
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Годовой оборот', 'usam'); ?>:</label></div>
					<div class ="view_data__option">
						<?php echo $revenue; ?>
					</div>
				</div>
			<?php } 
			$type_price = usam_get_company_metadata( $this->id, 'type_price');
			if ( $type_price ) {
			?>		
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Индивидуальная цена', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_get_name_price_by_code( $type_price ); ?></div>
			</div>	
			<?php } ?>								
		</div>	
		<?php
	}
	
	function display_tab_dossier()
	{		
		$this->display_properties(); 
		usam_vue_module('form-table');
		?>
		<div class ="form_table company_connections">
			<h3><?php esc_html_e( 'Связанные компании', 'usam'); ?></h3><?php	
			$columns = [
				'n'         => __('№', 'usam'),
				'title'     => __('Название', 'usam'),
				'delete'    => '',
			];
			?>
<form-table :lists='connections' :edit='edit_form' @change="connections=$event" @add="addConnection"  @save="saveConnections" :table_name="'company'" :columns='<?php echo json_encode( $columns ); ?>'>
	<template v-slot:tbodyrow="slotProps">	
		<td class="column-title">
			<div class="user_block">
				<a :href="slotProps.item.url" v-if="slotProps.item.logo" class="usam_foto image_container">
					<img :src="slotProps.item.logo"></a>
				<div>
					<a :href="slotProps.item.url" v-html="slotProps.item.name"></a>
				</div>
			</div>
		</td>
	</template>
	<template v-slot:tautocomplete="slotProps">
		<autocomplete @change="slotProps.selectElement" :request="'companies'" :clearselected='1' :none="'<?php _e('Нет данных','usam'); ?>'"></autocomplete>
	</template>
</form-table>
		
		</div>
		<div class ="form_table company_personal_accounts">
			<h3><?php _e( 'Личные кабинеты компании', 'usam'); ?></h3><?php
			$columns = [
				'n'         => __('№', 'usam'),
				'title'     => __('Имя', 'usam'),
				'login'     => __('Логин', 'usam'),
				'role'      => __('Группа доступа', 'usam'),
				'contact'   => __('Контакт', 'usam'),
				'delete'    => '',
			];			
			?>
		<form-table :lists='users' :edit='edit_form' @change="users=$event" @add="addUser" @save="saveUsers" :table_name="'company_personal_accounts'" :columns='<?php echo json_encode( $columns ); ?>'>
			<template v-slot:tbodyrow="slotProps">			
				<td class="column-title">
					<a :href="slotProps.item.url" v-html="slotProps.item.display_name"></a>
				</td>
				<td class="column-login">{{slotProps.item.user_login}}</td>
				<td class="column-role"><div v-for="role in slotProps.item.roles" v-html="role.name"></div></td>		
				<td class="column-contact"><a v-if="slotProps.item.contact" :href="slotProps.item.contact.url" v-html="slotProps.item.contact.appeal"></a></td>			
			</template>
			<template v-slot:tautocomplete="slotProps">
				<autocomplete @change="slotProps.selectElement" :request="'users'" :clearselected='1' :none="'<?php _e('Нет данных','usam'); ?>'"></autocomplete>
			</template>
		</form-table>
		</div>		
		<div id="usam_company_accounts" class ="form_table usam_company_accounts">
			<h3><?php esc_html_e( 'Банковские счета', 'usam'); ?></h3>
			<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/form-document.php' ); ?>
		</div>
		<?php
		if ( current_user_can('view_files') )
			usam_add_box( 'usam_files', __('Файлы','usam'), [$this, 'display_attachments']);
		$this->display_map('company',  usam_get_company_address( $this->id ) );	
	}
	
	function display_tab_company_divisions()
	{ 
		 $columns = [   
			['id' => 'name', 'name' => __('Компания', 'usam'), 'sort' => false],
			['id' => 'affairs', 'name' => __('Дела', 'usam'), 'sort' => false],
		//	['id' => 'communication', 'name' => __('Связаться', 'usam'), 'sort' => false],
			['id' => 'sum', 'name' => __('Всего куплено', 'usam'), 'sort' => false],			
			['id' => 'manager', 'name' => __('Менеджер', 'usam'), 'sort' => false],		
        ];
		?>
		<div class="view_form_tab_buttons">
			<a class="button" :href="'<?php echo admin_url('admin.php?page=crm&tab=companies&form=edit&form_name=company'); ?>&parent_id='+data.id"><?php _e( 'Добавить подразделение', 'usam'); ?></a>
		</div>
		<list-table :query="'companies'" :args="argsDivisions" :columns='<?php echo json_encode( $columns ); ?>'>			
			<template v-slot:tbody="slotProps">
				<tr v-for="(item, k) in slotProps.items">
					<td class="column_name">
						<div class='user_block'>	
							<a class='image_container usam_foto' :href="'<?php echo add_query_arg(['form' => 'view', 'form_name' => 'company']); ?>&id='+item.id"><img :src='item.logo'></a>
							<div class='user_data'>
								<a class='user_name' :href="'<?php echo add_query_arg(['form' => 'view', 'form_name' => 'company']); ?>&id='+item.id" v-html="item.name"></a>	
								<div class='user_description' v-html="item.post"></div>
								<div class='item_status' :style="item.status_color?'background:'+item.status_color+';':''+item.status_text_color?'color:'+item.status_text_color+';':''" v-html="item.status_name"></div>							
							</div>
						</div>	
					</td>					
					<td class="column_affairs">
						<?php include( USAM_FILE_PATH . '/admin/templates/template-parts/affairs-list.php' ); ?>
					</td>					
					<td class="column_sum">
						{{to_currency(item.total_purchased)}}		
						<div class="description_column"><?php _e('Заказов', 'usam'); ?> - {{formatNumber(item.number_orders, 0)}}</div>
					</td>
					<td class="column_title">
						<a class='user_block' v-if="Object.keys(item.manager).length" :href="'<?php echo add_query_arg(['form' => 'view', 'form_name' => 'contact']); ?>&id='+item.manager.id">	
							<div class='image_container usam_foto'><img :src='item.manager.foto'></div>
							<div class='user_name' v-html="item.manager.appeal"></div>							
						</a>	
					</td>
				</tr>
			</template>
		</list-table>
		<?php			
	}	
	
	function display_tab_company_employees()
	{ 	
		 $columns = [   
			['id' => 'contact', 'name' => __('Сотрудник', 'usam'), 'sort' => false],
			['id' => 'affairs', 'name' => __('Дела', 'usam'), 'sort' => false],
			['id' => 'communication', 'name' => __('Связаться', 'usam'), 'sort' => false],
			['id' => 'manager', 'name' => __('Менеджер', 'usam'), 'sort' => false],		
        ];
		?>
		<div class="view_form_tab_buttons">
			<a class="button" :href="'<?php echo admin_url('admin.php?page=personnel&tab=employees&form=edit&form_name=employee'); ?>&company_id='+data.id"><?php _e( 'Добавить сотрудника', 'usam'); ?></a>
		</div>
		<list-table :query="'contacts'" :args="argsEmployees" :columns='<?php echo json_encode( $columns ); ?>'>			
			<template v-slot:tbody="slotProps">
				<tr v-for="(item, k) in slotProps.items">
					<td class="column_name">
						<div class='user_block'>	
							<a class='image_container usam_foto' :href="'<?php echo add_query_arg(['form' => 'view', 'form_name' => 'contact']); ?>&id='+item.id"><img :src='item.foto'></a>
							<div class='user_data'>
								<a class='user_name' :href="'<?php echo add_query_arg(['form' => 'view', 'form_name' => 'contact']); ?>&id='+item.id" v-html="item.appeal"></a>	
								<div class='user_description' v-html="item.post"></div>
								<div class='item_status' :style="item.status_color?'background:'+item.status_color+';':''+item.status_text_color?'color:'+item.status_text_color+';':''" v-html="item.status_name"></div>							
							</div>
						</div>	
					</td>					
					<td class="column_affairs">
						<?php include( USAM_FILE_PATH . '/admin/templates/template-parts/affairs-list.php' ); ?>
					</td>
					<td class="column_communication">
						<div class = 'emails' v-for="email in item.emails">
							<div>{{email}}</div>
						</div>
						<div class = 'phones' v-for="phone in item.phones">
							<div>{{phone}}</div>
						</div>
					</td>
					<td class="column_title">
						<a class='user_block' v-if="Object.keys(item.manager).length" :href="'<?php echo add_query_arg(['form' => 'view', 'form_name' => 'contact']); ?>&id='+item.manager.id">	
							<div class='image_container usam_foto'><img :src='item.manager.foto'></div>
							<div class='user_name' v-html="item.manager.appeal"></div>							
						</a>	
					</td>
				</tr>
			</template>
		</list-table>
		<?php		
	}
		
	function display_tab_counterparty_verification()
	{
		$file = USAM_FILE_PATH . "/admin/reports-view/counterparty_verification_reports_view.class.php";
		if ( file_exists($file) )
		{
			require_once( $file );
			$class = "USAM_Counterparty_Verification_Reports_View";
			$reports_view = new $class( );
			$reports_view->display_reports();		
		}
	}		
}
?>