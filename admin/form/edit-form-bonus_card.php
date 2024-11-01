<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_bonus_card extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		return '<span v-if="data.code">'.__('Изменить данные карты','usam').'</span><span v-else>'.__('Добавить бонусную карту','usam').'</span>';
	}
	
	protected function get_data_tab(  )
	{			
		$default = ['code' => '', 'user_id' => 0, 'percent' => '0.00', 'status' => 1]; 
		if ( $this->id !== null )
			$this->data = usam_get_bonus_card( $this->id );
		else	
			$this->data['code'] = usam_generate_bonus_card(); 
		$this->data = array_merge( $default, $this->data );		
		
		$this->js_args['user'] = ['id' => 0, 'appeal' => '', 'foto' => '', 'url' => ''];
		$contact = usam_get_contact( $this->data['user_id'], 'user_id' );
		if( $contact )
		{
			$this->js_args['user'] = $contact;
			$this->js_args['user']['foto'] = usam_get_contact_foto( $contact['id'] );
			$this->js_args['user']['url'] = usam_get_contact_url( $contact['id'] );	
		}
	}	
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}

	function display_left()
	{			
		?>		
		<usam-box :id="'usam_settings'" :handle="false" :title="'<?php _e( 'Настройки', 'usam'); ?>'">
			<template v-slot:body>
				<div class="edit_form">
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Код карты', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" required name="code" autocomplete="off" v-model="data.code">
						</div>
					</label>				
					<label class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Процент по карте', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<input type="text" name="percent" maxlength="5" v-model="data.percent">
						</div>
					</label>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<?php $statuses = usam_get_statuses_bonus_card( ); ?>
							<select name = "status" v-model="data.status">							
								<?php				
								foreach ( $statuses as $key => $name ) 
								{					
									?><option value='<?php echo$key; ?>' <?php selected($this->data['status'], $key); ?>><?php echo $name; ?></option><?php
								}
								?>
							</select>	
						</div>
					</div>			
				</div>	
			</template>
		</usam-box>	
		<?php 
    }
	
	function display_right()
	{				
		?>		
		<usam-box :id="'usam_user'" :handle="false">
			<template v-slot:button>	
				<?php _e('Клиент','usam'); ?>
				<a @click="sidebar('contacts', 'contacts')">					
					<span v-if="data.user_id"><?php _e( 'Сменить', 'usam'); ?></span>
					<span v-else><?php _e( 'Выбрать', 'usam'); ?></span>
				</a>
			</template>
			<template v-slot:body>
				<div class='user_block' v-if="data.user_id>0">	
					<div class='user_foto'><a :href="user.url" class='image_container usam_foto'><img :src='user.foto'></a></div>	
					<a class='user_name':href="user.url" v-html="user.appeal"></a>			
				</div>
				<div class='user_block' v-else><?php _e('Не выбрано', 'usam'); ?></div>
				<input type="hidden" name="user_id" v-model="data.user_id"/>
			</template>			
			<?php
			usam_vue_module('list-table');
			add_action('usam_after_edit_form',function() {
				require_once( USAM_FILE_PATH.'/admin/templates/template-parts/modal-panel/modal-panel-contacts.php' );
			});
			?>
		</usam-box>	
		<?php	
    }
}
?>