<?php	
require_once( USAM_FILE_PATH .'/admin/includes/view_form.class.php' );	
class USAM_Form_seller extends USAM_View_Form
{	
	protected function get_title_tab()
	{ 	
		return sprintf( __('Продавец %s', 'usam'), $this->data['name'] );
	}
	
	protected function get_data_tab()
	{ 	
		$this->data = usam_get_seller( $this->id );	
		$this->tabs = array( 		
		//	array( 'slug' => 'report', 'title' => __('Отчет','usam') ),		
			array( 'slug' => 'commissions', 'title' => __('Комиссия','usam') ), 	
		);		
		if ( !empty($this->data) )
		{
			$this->header_title = __('Описание', 'usam');
			$this->header_content = $this->data['description'];
		}		
	}	
	
	protected function main_content_cell_1( ) 
	{			
		$timestamp = strtotime( $this->data['date_insert'] );	
		$date = human_time_diff( $timestamp, time() );	
		?>							
		<div class="view_data">					
			<?php 
			if ( $this->data['company_id'] ) 
			{ 
				$company = usam_get_company( $this->data['company_id'] );		
				?>		
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Компания', 'usam'); ?>:</div>
					<div class ="view_data__option"><a href='<?php echo usam_get_company_url( $company['id'] ); ?>'><?php echo $company['name']; ?></a></div>
				</div>		
			<?php } ?>	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Зарегистрирована', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $date; ?></div>
			</div>				
		</div>	
		<?php	
	}	
	
	protected function main_content_cell_2( ) 
	{ 				
		?>		
		<div class="view_data">				
			<div class ="view_data__row">
				<div class ="view_data__name"><label><?php esc_html_e( 'Статус', 'usam'); ?>:</label></div>
				<div class ="view_data__option">
					<?php echo usam_get_seller_status_name( $this->data['status'] ); ?>
				</div>
			</div>				
			<?php 
			if ( $this->data['status'] == 'approved' ) 
			{ 			
				$timestamp = strtotime( $this->data['date_status_update'] );	
				$date = human_time_diff( $timestamp, time() );	
				?>
				<div class ="view_data__row">
					<div class ="view_data__name"><?php esc_html_e( 'Одобрен', 'usam'); ?>:</div>
					<div class ="view_data__option"><?php echo $date; ?></div>
				</div>	
				<?php 
			}	
			?>	
			<div class ="view_data__row">
				<div class ="view_data__name"><?php esc_html_e( 'Рейтинг', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo $this->data['rating']; ?></div>
			</div>				
		</div>	
		<?php
	}	

	function display_tab_commissions()
	{
		$this->list_table( 'commissions_form' );			
	}
}
?>