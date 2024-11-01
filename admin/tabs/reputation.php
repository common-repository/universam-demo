<?php
class USAM_Tab_reputation extends USAM_Tab
{	
	private $mailing = [];	
	private $seo = [];	
	protected $views = ['simple'];
		
	public function get_title_tab()
	{			
		return __('Управление репутацией', 'usam');	
	}
	
	public function display() 
	{				
		$this->load_mailing();		
		$this->load_seo();
		$this->display_statistics();
		usam_add_box( 'usam_spam', __('Качество рассылки с вашего домена', 'usam'), [$this, 'display_spam'] );		
		usam_add_box( 'usam_seo', __('Репутация в поисковиках', 'usam'), [$this, 'display_search_engines'] );
		usam_add_box( 'usam_catalogs', __('Ваша компания в каталогах', 'usam'), [$this, 'display_catalogs'] );			
	}	
	
	public function load_seo()
	{		
		$this->seo['robots'] = file_exists(ABSPATH.'robots.txt')?1:0;	
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id )
		{
			$this->seo['custom_logo_alt'] = get_post_meta( $custom_logo_id, '_wp_attachment_image_alt', true )?1:0;
		}
	}
	
	public function display_search_engines()
	{			
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		$domain = usam_get_domain_information( get_bloginfo('url') );
		?>
		<div class="view_data">		
			<div class="view_data__row">
				<div class="view_data__name <?php echo $this->seo['robots']?'status_result':'status_error'; ?>"><?php _e('Файл robots.txt','usam'); ?>:</div>
				<div class="view_data__option"><?php echo $this->seo['robots']?__('Найден','usam'):__('Не существует','usam'); ?></div>
			</div>
			<?php if ( isset($this->seo['custom_logo_alt']) && !$this->seo['custom_logo_alt'] ) { ?>
			<div class="view_data__row">
				<div class="view_data__name <?php echo $this->seo['custom_logo_alt']?'status_result':'status_error'; ?>"><?php _e('alt у логотипа','usam'); ?>:</div>
				<div class="view_data__option"><?php echo $this->seo['custom_logo_alt']?__('Добавлен','usam'):__('Не указан','usam'); ?><a href="<?php echo admin_url("upload.php?item=".$custom_logo_id); ?>"> <?php _e('добавить alt сейчас','usam'); ?></a></div>
			</div>
			<?php } ?>
			<div class="view_data__row">
				<div class="view_data__name status_result"><?php _e('Домен оплачен до','usam'); ?>:</div>
				<div class="view_data__option"><?php echo $domain['free']; ?></a></div>
			</div>
		</div>
		<?php
	}
	
	public function display_statistics()
	{		
		$error = 0;
		if ( empty($this->mailing['dmarc']) )		
			$error++;
		if ( empty($this->mailing['dkim']) )
			$error++;
		if ( empty($this->mailing['spf']) )
			$error++;
		if ( !$this->seo['robots'] )
			$error++;	
		if ( isset($this->seo['custom_logo_alt']) && !$this->seo['custom_logo_alt'] )
			$error++;		
		
		$index = $error ? round(100/$error) : 100;
		?>		
		<div class="crm-important-data">
			<div class="crm-start-row">
				<h3 class="title"><?php esc_html_e( 'Статистика', 'usam'); ?></h3>
				<div class="crm-start-row-result">
					<div class="crm-start-row-result-item">
						<div class="crm-start-row-result-item-title"><?php esc_html_e( 'Индекс репутации', 'usam'); ?></div>
						<div class="crm-start-row-result-item-total"><?php echo $index; ?>%</div>
					</div>
					<div class="crm-start-row-result-item">
						<div class="crm-start-row-result-item-title"><?php esc_html_e( 'Количество проблем', 'usam'); ?></div>
						<div class="crm-start-row-result-item-total"><?php echo $error; ?></div>
					</div>								
				</div>
			</div>
		</div>
		<?php 	
	}
	
	public function display_catalogs()
	{
		$catalogs = array( 
			array('url' => 'https://yandex.ru/sprav', 'name' => __('Яндекс Каталог','usam'), 'description' => __('Ваша компания в Яндекс Каталог','usam')),
			array('url' => 'https://business.google.com/dashboard', 'name' => __('Google Мой бизнес','usam'), 'description' => __('Ваша компания в Google Мой бизнес','usam') )
		);
		?>	
		<div class="usam_customization">					
			<div class="usam_customization_steps">
				<?php	
				foreach( $catalogs as $catalog )
				{
					?>
					<a href="<?php echo $catalog['url']; ?>" class="usam_customization__step" target="_blank">		
						<div class="usam_customization__content">
							<div class="usam_customization__title"><?php echo $catalog['name']; ?></div>  
							<div class="usam_customization__description"><?php echo $catalog['description']; ?></div>  
						</div>  
					</a>     
					<?php			
				}
				?>
			</div>
		</div>
		<?php 
	}
	
	private function load_mailing()
	{
		$domain = $_SERVER['SERVER_NAME'];
		$this->mailing['dmarc'] = @dns_get_record('_dmarc.'.$domain,DNS_TXT);
		$this->mailing['dkim'] = @dns_get_record('mail._domainkey.'.$domain.'.',DNS_TXT);
		if ( !$this->mailing['dkim'] )
			$this->mailing['dkim'] = @dns_get_record('mailru._domainkey.'.$domain.'.',DNS_TXT);
		$this->mailing['txt'] = @dns_get_record($domain,DNS_TXT);		
	//	$results = dns_get_record('selector._domainkey.'.$domain.'.',DNS_TXT);	
		if ( !empty($this->mailing['txt']) )
		{
			foreach( $this->mailing['txt'] as $result )
			{			
				if (stripos($result['txt'], 'v=spf1') !== false)
				{
					$record = 'v=spf1 redirect=_spf.';
					$this->mailing['spf'] = array('record' => $result['txt']);
					if (stripos($result['txt'], $record) !== false)
					{
						$this->mailing['spf']['service'] = str_replace($record, "", $result['txt']);
					}
				}
			}
		}		
	}	
	
	public function display_spam()
	{
		?>
		<div class="view_data"><?php			
		if ( !empty($this->mailing['dmarc']) )
		{
			?>
			<div class="view_data__row">
				<div class="view_data__name status_result"><?php _e('Запись DMARC найдена','usam'); ?>:</div>
				<div class="view_data__option"><?php echo $this->mailing['dmarc'][0]['txt']; ?></div>
			</div>				
			<?php		
		}
		else
		{
			?>	
			<div class="view_data__row">
				<div class="view_data__name status_error"><?php _e('DMARC','usam'); ?>:</div>
				<div class="view_data__option"><?php _e('Не найдено','usam'); ?></div>
			</div>
			<?php
		}
		if ( !empty($this->mailing['dkim']) )
		{
			?>
			<div class="view_data__row">
				<div class="view_data__name status_result"><?php _e('Домен прошел испытание DKIM','usam'); ?>:</div>
				<div class="view_data__option"><?php echo $this->mailing['dkim'][0]['txt']; ?></div>
			</div>
			<?php
		}		
		else
		{
			?>	
			<div class="view_data__row">
				<div class="view_data__name status_error"><?php _e('Подпись DKIM','usam'); ?>:</div>
				<div class="view_data__option"><?php _e('Не подключена','usam'); ?></div>
			</div>
			<?php
		}			
		if ( !empty($this->mailing['spf']) )
		{
			?>	
			<div class="view_data__row">
				<div class="view_data__name status_result"><?php _e('SPF-запись','usam'); ?>:</div>
				<div class="view_data__option"><?php echo $this->mailing['spf']['record']; ?></div>
			</div>		
			<?php	
			if ( !empty($this->mailing['spf']['service']) )
			{
				?>
				<div class="view_data__row">
					<div class="view_data__name status_result"><?php _e('Подключена отправка писем от','usam'); ?>:</div>
					<div class="view_data__option"><?php echo $this->mailing['spf']['service']; ?></div>
				</div>					
				<?php
			}
		}
		else
		{
			?>	
			<div class="view_data__row">
				<div class="view_data__name status_error"><?php _e('SPF-запись','usam'); ?>:</div>
				<div class="view_data__option"><?php _e('не найдена','usam'); ?></div>
			</div>					
			<?php	
		}
		?>
		</div>
		<?php
	}
}