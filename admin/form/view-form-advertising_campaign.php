<?php		
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_advertising_campaign extends USAM_View_Form
{	
	protected function get_title_tab()
	{ 	
		return sprintf(__('Компания &#8220;%s&#8221;', 'usam'), $this->data['title']);
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
		
	protected function toolbar_buttons( ) 
	{ 
		if ( $this->id != null )
		{	
			?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button"><?php _e('Изменить','usam'); ?></a></div><?php
			$this->delete_button();	
		}
	}
	
	protected function get_data_tab()
	{ 		
		$this->data = usam_get_advertising_campaign( $this->id );			
		$this->tabs = array( 
			array( 'slug' => 'report', 'title' => __('Отчет','usam') ),		
			array( 'slug' => 'visits', 'title' => __('Визиты','usam') ),
			array( 'slug' => 'contacts', 'title' => __('Контакты','usam') ),
			array( 'slug' => 'orders', 'title' => __('Заказы','usam') )
		);
	}
	
	protected function main_content_cell_1( ) 
	{ 						
		?>		
		<h4><?php _e('Компания', 'usam'); ?></h4>
		<div class="view_data">			
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php _e( 'Код компании','usam'); ?>:</label></div>
				<div class ="view_data__option"><?php echo $this->data['code']; ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php _e( 'Источник','usam'); ?>:</label></div>
				<div class ="view_data__option"><?php echo usam_get_name_source_advertising_campaign( $this->data['source'] ); ?></div>
			</div>	
			<?php if ( !empty($this->data['medium']) ) { ?>
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php _e( 'Канал компании','usam'); ?>:</label></div>
				<div class ="view_data__option"><?php echo $this->data['medium']; ?></div>
			</div>
			<?php } ?>		
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php _e( 'Перенаправлять на ссылку','usam'); ?>:</label></div>
				<div class ="view_data__option"><a href="<?php echo $this->data['redirect']; ?>"><?php echo $this->data['redirect']; ?></a></div>
			</div>			
		</div>	
		<?php	
	}		
	
	protected function main_content_cell_2( ) 
	{ 		
		$main_url = home_url( '/' ).'ac/'.$this->data['code'];
		$url = add_query_arg(["utm_source" => $this->data['source'], 'utm_medium' => $this->data['medium'], 'utm_campaign' => $this->data['code']], $main_url );
		if ( $this->data['content'] )
			$url = add_query_arg( array( 'utm_content' => $this->data['content'] ),$url );
		if ( $this->data['term'] )
			$url = add_query_arg( array( 'utm_term' => $this->data['term'] ),$url );
		?>	
		<h4><?php _e('Ссылки', 'usam'); ?></h4>
		<div class="view_data">		
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php _e( 'Короткая','usam'); ?>:</label></div>
				<div class ="view_data__option js-copy-clipboard"><?php echo $main_url; ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php _e( 'UTM-метками','usam'); ?>:</label></div>
				<div class ="view_data__option js-copy-clipboard"><?php echo $url; ?></div>
			</div>		
		</div>	
		<?php	
	}
	
	protected function main_content_cell_3( ) 
	{				
		?>		
		<h4><?php _e('Содержание объявления', 'usam'); ?></h4>
		<div class="view_data">
			<?php if ( !empty($this->data['content']) ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Содержание объявления','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $this->data['content']; ?></div>
				</div>
			<?php } ?>		
			<?php if ( !empty($this->data['term']) ) { ?>
				<div class ="view_data__row">
					<div class ="view_data__name"><label><?php _e( 'Ключевое слово','usam'); ?>:</label></div>
					<div class ="view_data__option"><?php echo $this->data['term']; ?></div>
				</div>
			<?php } ?>					
		</div>	
		<?php	
	}
	
	function display_tab_visits()
	{
		include( usam_get_filepath_admin('templates/template-parts/visits-advertising-campaign-table.php') );
	}
		
	function display_tab_contacts()
	{
		$this->list_table( 'contacts_advertising_campaign' );
	}
	
	function display_tab_orders()
	{
		$this->list_table( 'orders_advertising_campaign' );
	}	
}