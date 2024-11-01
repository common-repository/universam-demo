<?php	
require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_contacting extends USAM_View_Form
{	
	protected $ribbon = true;
	protected function get_data_tab()
	{ 	
		$this->data = usam_get_contacting( $this->id );			
		if( !$this->data )
			return;
		if ( !current_user_can('view_contacting') )
		{
			$this->data = [];
			return;
		}				
		$this->data['color'] = '';
		$this->data['time_diff'] = human_time_diff( time(), strtotime( $this->data['date_insert'] ) );		
		$this->data['status_name'] = usam_get_object_status_name( $this->data['status'], 'contacting' );				
		$this->tabs = [
			['slug' => 'webform', 'title'   => __('Веб-форма','usam')],
			['slug' => 'analytics', 'title'   => __('Аналитика','usam')], 
		];
		usam_vue_module('list-table');
		add_action('usam_after_view_form',function() {
			include( usam_get_filepath_admin('templates/template-parts/modal-panel/modal-panel-employees.php') );
		});
		add_action( 'admin_footer', [__CLASS__, 'product_viewer']);
	}	
	
	protected function form_attributes( )
    {		
		?>v-cloak<?php
	}
	
	protected function get_title_tab()
	{ 		
		$title = usam_get_event_type_name( 'contacting' );		
		return $title." № $this->id ".__("от","usam").' '.usam_local_date( $this->data['date_insert'] ).' <span class="dashicons dashicons-star-filled importance important" v-if="data.importance" @click="data.importance=!data.importance"></span><span class="dashicons dashicons-star-empty importance" @click="data.importance=!data.importance" v-else></span>';
	}	
		
	protected function header_view()
	{	
		?>		
		<div class = "header_main">
			<div id='header_main_content' class = "header_main_content">
				<div class = "main_content_cell"><?php $this->main_content_cell_1(); ?></div>
				<div class = "main_content_cell"><?php $this->main_content_cell_2(); ?></div>				
			</div>	
			<h4 class="header_content_name" v-if="data.request_solution"><?php _e('Решение вопроса','usam'); ?></h4>
			<div class = "main_content_footer" v-if="data.request_solution" v-html='data.request_solution.replace(/\n/g,"<br>")'></div>	
		</div>	
		<?php	
	}	
		
	protected function toolbar_buttons( ) 
	{	
		?>
		<div class="action_buttons__button" v-if="data.status=='not_started' || data.status=='stopped' || data.status=='canceled'"><button type='submit' class='button button-primary' @click="data.status='started'"><?php _e('Начать выполнять', 'usam'); ?></button></div>	
		<div class="action_buttons__button" v-if="data.status!='completed' && data.status!='not_started'"><button type='submit' class='button button-primary' @click="data.status='completed'"><?php _e('Завершить', 'usam'); ?></button></div>
		<?php	
		if( current_user_can('add_order') )
		{
			?><div class="action_buttons__button" v-if="post!==null && post.post_type=='usam-product'"><button type='submit' class='button button-primary' @click="add_order"><?php _e('Создать заказ', 'usam'); ?></button></div><?php	
		}
		if( current_user_can('edit_contacting') )
		{
			?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button" v-if="data.status!='completed'"><?php _e('Изменить','usam'); ?></a></div><?php
		}
	}
	
	
	protected function main_content_cell_1()
	{			
		?>			
		<div class="view_data">			
			<div class ="view_data__row" v-if="data.contact_id>0">
				<div class ="view_data__name"><?php esc_html_e( 'Посетитель', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<div class='user_block'>	
						<a :href="'<?php echo admin_url("admin.php?page=crm&tab=contacts&form=view&form_name=contact"); ?>&id='+contact.id"><div class='image_container usam_foto'><img :src='contact.foto'></div></a>
						<a class='user_name' :href="'<?php echo admin_url("admin.php?page=crm&tab=contacts&form=view&form_name=contact"); ?>&id='+contact.id" v-html="contact.name"></a>	
					</div>
				</div>
			</div>				
			<div class ="view_data__row" v-if="post!==null">
				<div class ="view_data__name"><?php esc_html_e( 'Страница', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<div class="product" v-if="post.post_type=='usam-product'">					
						<span class='js-product-viewer-open viewer_open product_image image_container' :product_id='post.ID'><img :src='post.thumbnail'></span>
						<a :href="post.url" class='product_title_link' v-html="post.post_title"></a> 
						<div class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard" v-html="post.sku"></span></div>
					</div>
					<a v-else :href="post.url" v-html="post.post_title"></a>	
				</div>
			</div>									
		</div>	
		<?php	
	}
	
	protected function main_content_cell_2()
	{			
		?>						
		<div class="view_data">
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
				<div class ="view_data__option">{{data.status_name}}</div>
			</div>	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Время обработки', 'usam'); ?>:</div>
				<div class ="view_data__option">{{data.time_diff}}</div>
			</div>			
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Ответственный', 'usam'); ?>:</div>
				<div class ="view_data__option">
					<div class='user_block' v-if="data.manager_id>0" @click="sidebar('managers')">	
						<a class='user_name' v-html="manager.appeal"></a>	
					</div>
					<a v-else @click="sidebar('managers')"><?php esc_html_e( 'Выбрать менеджера', 'usam'); ?></a>
				</div>
			</div>							
		</div>		
		<?php	
	}	
		
	public function display_tab_webform( )
	{
		$webform_code = usam_get_contacting_metadata( $this->id, 'webform');	
		$webform = usam_get_webform( $webform_code, 'code' );
		?>
		<usam-box :id="'usam_webform'" :handle="false" :title="'<?php echo __('Веб-форма','usam').' - '.$webform['title']; ?>'">
			<template v-slot:body>
				<?php include( usam_get_filepath_admin('templates/template-parts/crm/webform.php') ); ?>
			</template>
		</usam-box>			
		<?php 
	}
	
	public function display_tab_analytics( )
	{
		?>
		<usam-box :id="'usam_analytics'" :handle="false" :title="'<?php _e( 'Аналитика', 'usam'); ?>'">
			<template v-slot:body>				
				<div class="view_data" v-if="data.campaign !== undefined">
					<div class ="view_data__row" v-if="Object.keys(data.campaign).length">
						<div class ="view_data__name"><?php esc_html_e( 'Рекламная компания', 'usam'); ?>:</div>
						<div class ="view_data__option"><a :href="'<?php echo admin_url("admin.php?page=marketing&tab=advertising_campaigns&form=view&form_name=advertising_campaign&id="); ?>'+data.campaign.id"v-html="data.campaign.title"></a></div>
					</div>
					<div class ="view_data__row" v-if="Object.keys(data.visit).length">
						<div class ="view_data__name"><?php esc_html_e( 'Номер визита', 'usam'); ?>:</div>
						<div class ="view_data__option" v-html="data.visit.id"></div>
					</div>
					<div class ="view_data__row" v-if="Object.keys(data.visit).length">
						<div class ="view_data__name"><?php esc_html_e( 'Источник', 'usam'); ?>:</div>
						<div class ="view_data__option" v-html="data.visit.source_name.short"></div>
					</div>
					<div class ="view_data__row" v-if="Object.keys(data.visit).length">
						<div class ="view_data__name"><?php esc_html_e( 'Просмотров', 'usam'); ?>:</div>
						<div class ="view_data__option"><a :href="'<?php echo admin_url("admin.php?page=feedback&tab=monitor&table=pages_viewed&visit_id="); ?>'+data.visit.id" v-html="data.visit.views"></a></div>
					</div>
					<div class ="view_data__row" v-if="Object.keys(data.visit).length">
						<div class ="view_data__name"><?php esc_html_e( 'Устройство', 'usam'); ?>:</div>
						<div class ="view_data__option" v-if="data.visit.device=='PC'"><?php _e('ПК','usam'); ?></div>
						<div class ="view_data__option" v-else><?php _e('Мобильные','usam'); ?></div>
					</div>					
				</div>			
			</template>
		</usam-box>	
		<?php	
	}
}
?>