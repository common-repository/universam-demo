<?php	
require_once( USAM_FILE_PATH . '/includes/customer/customer_account.class.php' );
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-customer.php' );
class USAM_Form_contact extends USAM_Form_customer
{	
	private $view = true;		
	protected function get_data_tab()
	{ 	
		$this->change = current_user_can('edit_contact');
		
		$this->data = usam_get_contact( $this->id );		
		if( !$this->data )
			return false;
		$metas = usam_get_contact_metadata( $this->id );
		if ( $metas )
			foreach($metas as $metadata )
				$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);		
		$user_id = get_current_user_id();		
		if ( !$this->data['open'] && $this->data['manager_id'] != $user_id && $this->data['manager_id'] )
		{
			$this->change = false;			
			$this->view = false;				
		}		
		else
		{
			$this->tabs = [
				['slug' => 'dossier', 'title' => __('Досье','usam')],	
				['slug' => 'orders', 'title' => __('Заказы','usam')], 
				['slug' => 'documents', 'title' => __('Документы','usam')], 
				['slug' => 'report', 'title' => __('Отчет','usam')],			
			];			
			$this->header_title = __('Описание', 'usam');
			$this->header_content = usam_get_contact_metadata($this->id, 'about');
		}			
	}	
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function ability_to_delete( )
	{
		return current_user_can('delete_contact');
	}
	
	protected function toolbar_buttons( ) 
	{
		if ( $this->id != null && $this->view )
		{
			$this->newsletter_templates( 'contact' );
			?>
			<?php if ( $this->change ) { ?>	
				<div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button"><?php _e('Изменить','usam'); ?></a></div>
				<?php	
			}
			$this->delete_button();			
		}
	}
			
	protected function main_content_cell_1( ) 
	{
		?>
		<div class="customer_preview">	
			<div class ="customer_preview__left">				
				<?php $this->display_contact_foto(); ?>	
				<?php $this->display_contact_online(); ?>
			</div>
			<div class ="customer_preview__data">					
				<h2 class ="customer_title" id="customer_name"><span v-html="data.full_name"></span>
				<?php					
				$birthday = usam_get_contact_metadata($this->id, 'birthday');
				if ( $birthday ) 
				{ 
					?><span class ="customer_years"><?php echo human_time_diff( strtotime($birthday), current_time('timestamp') ); ?></span><?php 
				}
				?>				
				</h2>
				<div v-if="!data.open" class="object_close"><div class="status_blocked item_status"><?php _e("Закрытый контакт","usam"); ?></div></div>
				<?php
				usam_display_status( $this->data['status'], 'contact' );
				$address = usam_get_full_contact_address( $this->id );	
				if ( $address ) 
				{ 
					?><div class ="customer_address"><?php echo $address; ?></div><?php 
				}				
				?>
				<div class ="social_networks" v-if="Object.keys(socialNetworks).length">					
					<a :href="property.value" v-for="(property, k) in socialNetworks" target='_blank' rel="noopener">
						<img class='svg_icon' :class="'svg_icon_'+property.code" :src="'<?php echo USAM_SVG_ICON_URL; ?>#'+property.code+'-usage'" width = '20' height='20'>
					</a>			
				</div>				
			</div>									
		</div>	
		<div class="view_data">					
			<?php
			$this->display_groups( admin_url("admin.php?table=contacts&tab=contacts&page=crm") ); 				
			$bonus_card = usam_get_bonus_card( $this->data['user_id'], 'user_id' );
			if ( !empty($bonus_card['code']) ) { ?>		
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Бонусная карта', 'usam'); ?>:</div>
					<div class ="view_data__option">
						<a href='<?php echo admin_url("admin.php?page=crm&tab=contacts&table=bonus_cards&form=view&form_name=bonus_card&id=".$bonus_card['code']); ?>'>
							<span class="<?php echo $bonus_card['sum']>=0?'item_status_valid':'item_status_attention'; ?> item_status"><?php echo usam_currency_display($bonus_card['sum'], ['decimal_point' => false]); ?></span>
						</a>
					</div>
				</div>	
			<?php } ?>
			<?php 
			$account = usam_get_customer_account( $this->data['user_id'], 'user_id' );		
			if ( !empty($account['id']) ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e('Персональный счет', 'usam'); ?>:</div>
					<div class ="view_data__option">
						<a href='<?php echo admin_url("admin.php?page=crm&tab=contacts&table=customer_accounts&form=view&form_name=customer_account&id=".$account['id']); ?>'>
							<span class="<?php echo $account['sum']>=0?'item_status_valid':'item_status_attention'; ?> item_status"><?php echo usam_currency_display( $account['sum'] ); ?></span>
						</a>
					</div>
				</div>	
			<?php }
			$favorite_shop = usam_get_contact_metadata($this->data['id'], 'favorite_shop');	
			if ( !empty($favorite_shop) ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e('Любимый магазин', 'usam'); ?>:</div>
					<div class ="view_data__option">
						<?php 				
						$storage = usam_get_storage( $favorite_shop );
						if ( $storage )
							echo $storage['title'];	
						else
							printf( __('Объект с номером %s удален', 'usam'), $favorite_shop);
						?>
					</div>
				</div>	
			<?php } ?>
		</div>	
		<?php	
	}	
	
	protected function main_content_cell_2( ) 
	{ 		
		?>		
		<div class="view_data">						
			<?php if ( $this->data['user_id'] ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Логин', 'usam'); ?>:</div>
					<div class ="view_data__option">
						<?php
						$user = get_userdata( $this->data['user_id'] );
						if ( !empty($user) )
						{
							?>
							<a href="<?php echo add_query_arg(['user_id' => $this->data['user_id']], admin_url('user-edit.php') ); ?>"><?php echo $user->user_login; ?></a>
							<?php
							if ( get_option('usam_user_profile_activation') && get_user_option( 'usam_user_profile_activation' ) )
							{
								?><span class ="item_status_valid item_status"><?php esc_html_e( 'Активирован', 'usam'); ?></span><?php
							}
						}
						?>
					</div>
				</div>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Группа доступа', 'usam'); ?>:</div>
					<div class ="view_data__option">
						<?php 
						foreach( $user->roles as $role )
						{
							echo translate_user_role( wp_roles()->roles[$role]['name'] )."<br>";
						}
						?>
					</div>
				</div>	
			<?php } ?>	
			<?php			
			$type_price = usam_get_contact_metadata( $this->id, 'type_price');	
			if ( $type_price ) {
			?>	
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Индивидуальная цена', 'usam'); ?>:</div>
					<div class ="view_data__option"><?php echo usam_get_name_price_by_code( $type_price ); ?></div>
				</div>	
			<?php } ?>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Ответственный', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<?php echo $this->data['manager_id']?usam_get_manager_name( $this->data['manager_id'] ):__('Нет','usam'); ?>
				</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Время в базе', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<?php echo human_time_diff( strtotime( $this->data['date_insert'] ), time() ); ?>
				</div>
			</div>
			<?php if ( $this->data['contact_source'] !='' ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Источник', 'usam'); ?>:</div>
					<div class ="view_data__option">
						<?php echo usam_get_name_contact_source( $this->data['contact_source'] ); ?>
					</div>
				</div>	
			<?php }	 
			$contact_location_id = usam_get_contact_metadata( usam_get_contact_id(), 'location' );
			$location_id = usam_get_contact_metadata( $this->id, 'location' );
			if ( $location_id != $contact_location_id ) 
			{ ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Местное время', 'usam'); ?>:</div>
					<div class ="view_data__option"><?php echo 	usam_get_location_time( $location_id ); ?></div>
				</div>				
			<?php } 
			$company = usam_get_company( $this->data['company_id'] );
			if ( isset($company['name']) ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Работа', 'usam'); ?>:</div>
					<div class ="view_data__option">							
						<a href="<?php echo usam_get_company_url( $company['id'] ); ?>"><?php echo $company['name']; ?></a>
					</div>
				</div>
				<?php 
				$post = usam_get_contact_metadata($this->id, 'post');
				if ( $post ) { ?>	
					<div class ="view_data__row">
						<div class ="view_data__name"><?php esc_html_e( 'Должность', 'usam'); ?>:</div>
						<div class ="view_data__option">
							<?php echo htmlspecialchars($post); ?>
						</div>
					</div>
				<?php } ?>	
			<?php } ?>			
		</div>	
		<?php			
	}
	
	function display_tab_dossier()
	{						
		$this->display_properties(); 	
		$this->display_map('contact',  usam_get_full_contact_address( $this->id ) );
		if ( current_user_can('view_files') )
			usam_add_box( 'usam_files', __('Файлы','usam'), [$this, 'display_attachments']);
	}
}
?>