<?php	
require_once( USAM_FILE_PATH .'/includes/personnel/department.class.php' );
require_once( USAM_FILE_PATH . '/admin/includes/form/view-form-customer.php' );
class USAM_Form_employee extends USAM_Form_customer
{	
	protected function get_data_tab()
	{ 		
		$this->change = current_user_can('edit_employee');
		
		$this->data = usam_get_contact( $this->id );	
		if( !$this->data )
			return false;
		$metas = usam_get_contact_metadata( $this->id );
		if ( $metas )
			foreach($metas as $metadata )
				$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);		
		$this->tabs = [
			['slug' => 'report', 'title' => __('Отчет','usam')],		
			['slug' => 'dossier', 'title' => __('Досье','usam')],				
		];		
		$this->header_title = __('Описание', 'usam');
		$this->header_content = usam_get_contact_metadata($this->id, 'about');	
	}	
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function ability_to_delete( )
	{
		return current_user_can('delete_employee');
	}	
	
	protected function toolbar_buttons( ) 
	{
		if ( $this->id != null )
		{
			$this->newsletter_templates( 'contact' );
			if ( $this->change ) { ?>	
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
				<h2 class ="customer_title" id="customer_name">{{data.full_name}}
				<?php	
				$birthday = usam_get_contact_metadata($this->id, 'birthday');
				if ( $birthday ) 
				{ 
					?><span class ="customer_years"><?php echo human_time_diff( strtotime($birthday), current_time('timestamp') ); ?></span><?php 
				}
				?>
				</h2>
				<?php
				$address = usam_get_full_contact_address( $this->id );	
				if ( $address ) 
				{ 
					?><div class ="customer_address"><?php echo $address; ?></div><?php 
				}
				usam_display_status( $this->data['status'], 'employee' );
				?>
				<div class ="social_networks" v-if="Object.keys(socialNetworks).length">					
					<a :href="property.value" v-for="(property, k) in socialNetworks" target='_blank' rel="noopener">
						<img class='svg_icon' :class="'svg_icon_'+property.code" :src="'<?php echo USAM_SVG_ICON_URL; ?>#'+property.code+'-usage'" width = '20' height='20'>
					</a>			
				</div>				
			</div>									
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
							?><a href="<?php echo add_query_arg(['user_id' => $this->data['user_id'] ], admin_url('user-edit.php') ); ?>"><?php echo $user->user_login; ?></a><?php
						}
						?>
					</div>
				</div>
			<?php } ?>
			<?php $company = usam_get_company( $this->data['company_id'] );	?>		
			<?php if ( isset($company['name']) ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Компания', 'usam'); ?>:</div>
					<div class ="view_data__option">							
						<a href="<?php echo usam_get_company_url( $company['id'] ); ?>"><?php echo $company['name']; ?></a>
					</div>
				</div>
			<?php } ?>			
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Работает', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<?php echo human_time_diff( strtotime( $this->data['date_insert'] ), time() ); ?>
				</div>
			</div>
			<?php 
			$department_id = usam_get_contact_metadata( $this->id, 'department' );
			if ( $department_id ) 
			{ 
				$department = usam_get_department( $department_id );
				?>	
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Отдел', 'usam'); ?>:</div>
					<div class ="view_data__option"><?php echo $department['name']; ?></div>
				</div>
			<?php } ?>		
			<?php 
			$post = usam_get_contact_metadata($this->id, 'post');
			if ( $post !='' ) { ?>	
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Должность', 'usam'); ?>:</div>
					<div class ="view_data__option"><?php echo $post; ?></div>
				</div>
			<?php }	?>							
		</div>	
		<?php	
	}
		
	function display_tab_dossier()
	{		
		$this->display_properties();
		if ( current_user_can('view_files') )
			usam_add_box( 'usam_files', __('Файлы','usam'), [$this, 'display_attachments']);		
		$this->display_map('contact',  usam_get_full_contact_address( $this->id ) );
	}
}
?>