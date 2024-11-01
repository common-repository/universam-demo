<?php
require_once( USAM_FILE_PATH.'/admin/includes/update.class.php' );
class USAM_Tab_Update extends USAM_Page_Tab
{
	protected $views = ['simple'];
	public function get_title_tab()
	{			
		return __('Обновление программного комплекса УНИВЕРСАМ', 'usam');	
	}	
	
	public function display() 
	{
		if ( usam_needs_upgrade()  )
		{ 	
			if ( !empty($_REQUEST['start_update']) )
			{ 
				$screen = get_current_screen();	
				if ( wp_verify_nonce( $_REQUEST['usam_name_nonce_field'], 'usam-'.$screen->id ) )
				{
					ob_implicit_flush( true );
					
					echo __('Обновление...', 'usam') . '<br><br>';	
					
					USAM_Update::start_update();

					echo __('УНИВЕРСАМ успешно обновлен!', 'usam');			
					ob_implicit_flush( false );
				}
			}
			else
			{ 							
				?>
				<em><?php esc_html_e( 'Примечание: Если сервер прекратит работу или не хватает памяти, просто обновите эту страницу и сервер начнет работу там, где он остановился.', 'usam'); ?></em>
				<br />
				<form action="" method="post" id="setup">
					<input type="hidden" name="start_update" value="1" />						
					<?php $this->nonce_field('start_update_nonce');  ?>								
					<p class="step">
						<input type="submit" class="button" value="<?php esc_attr_e( 'Обновление УНИВЕРСАМа', 'usam'); ?>" name="submit">
					</p>
				</form>
			<?php
			}
		}
		else
		{
			?><h2><?php esc_html_e( 'Вы используете самую последнюю версию магазина.', 'usam'); ?></h2><?php			
		}
	}	
}