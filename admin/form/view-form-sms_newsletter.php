<?php
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_sms_newsletter extends USAM_View_Form
{	
	protected function get_title_tab()
	{ 	
		return sprintf( __('Статистика СМС рассылки &laquo;%s&raquo;','usam'), $this->data['subject'] );
	}
	
	protected function form_attributes( )
    {		
		?>v-cloak<?php
	}
	
	protected function form_class( )
    {		
		return 'form_newsletter_report';
	}
	
	protected function get_data_tab(  )
	{		
		$this->data = usam_get_newsletter( $this->id );			
		$this->tabs = array( 
			array( 'slug' => 'newsletter_contact', 'title' => __('Контакты','usam') ), 			 	
			array( 'slug' => 'newsletter_company', 'title' => __('Компании','usam') ), 				
		);		
		if ( !empty($this->data) )
		{
		//	$this->header_title = __('Описание', 'usam');
			//$this->header_content = $this->data['description'];
		}						
	}	
	
	protected function toolbar_buttons( ) {}
	
	protected function main_content_cell_1( ) 
	{	
		?>				
		<div class="view_data">
			<div class ="view_data__row view_data__highlighted">
				<div class ="view_data__name"><?php _e('Отправлено', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $this->data['number_sent']; ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Дата отправления', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo usam_local_date( $this->data['sent_at']); ?></div>
			</div>
		</div>
		<?php	
	}	
	
	protected function main_content_cell_2( ) 
	{ 		
		?>		
		<div class="view_data">
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Открыли', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $this->data['number_opened']; ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Нажали', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $this->data['number_clicked']; ?></div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Отписались', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $this->data['number_unsub']; ?></div>
			</div>
		</div>	
		<?php	
	}		
	
	protected function main_content_cell_3( ) 
	{ 
		if ( $this->data['number_sent'] > 0 )
		{
			$rate_opened = round($this->data['number_opened']*100/$this->data['number_sent'],1);
			$rate_clicked = round($this->data['number_clicked']*100/$this->data['number_sent'],1);
			$rate_unsub = round($this->data['number_unsub']*100/$this->data['number_sent'],1);
		}
		else
		{
			$rate_opened = 0;	
			$rate_clicked = 0;	
			$rate_unsub = 0;	
		}
		?>		
		<div class="view_data">
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Открыли', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $rate_opened; ?>%</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Нажали', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $rate_clicked; ?>%</div>
			</div>
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Отписались', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $rate_unsub; ?>%</div>
			</div>
		</div>	
		<?php	
	}
		
	function display_tab_newsletter_company()
	{
		include( usam_get_filepath_admin('templates/template-parts/email-newsletter-report-companies-table.php') );
	}
		
	function display_tab_newsletter_contact()
	{
		include( usam_get_filepath_admin('templates/template-parts/email-newsletter-report-contacts-table.php') );
	}
}
?>