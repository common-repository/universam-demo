<?php
class USAM_Tab_seo_tools extends USAM_Tab
{	
	protected $view = 'simple';
	protected $views = ['simple'];	
	public function get_title_tab()
	{	
		return __('SEO инструменты', 'usam');	
	}
	
	public function get_tab_sections() 
	{ 	//view_analysis
		$tables = ['analysis' => ['title' => __('Анализ страниц','usam'), 'type' => 'section'], 'product_editor' => ['title' => __('Редактор товаров','usam'), 'type' => 'table'], 'robots' => ['title' => 'Robots', 'type' => 'section']];	
		return $tables;
	}
	
	public function random_html_color( $number ) 
	{
		if ( $number == 1 )
			return '#32CD32';
		else
			return sprintf( '#%02X%02X%02X', 205-$number, 92+$number, 92+$number );
	}
	
	public function load_page_result() 
	{
		?>
		<table class ="wp-list-table widefat fixed striped">					
			<thead>
				<tr>					
					<td><?php _e( 'Описание', 'usam'); ?></td>
					<td><?php _e( 'Скорость', 'usam'); ?></td>												
				</tr>
			</thead>
			<tbody>		
				<tr>
					<td><?php _e( 'Индекс скорости загрузки', 'usam'); ?></td>
					<td><?php echo usam_convert_time( $this->speed_results['lighthouseResult']['audits']['metrics']['details']['items'][0]['speedIndex'], 'ms' ); ?></td>
				</tr>	
				<tr>
					<td><?php _e( 'Время загрузки первого контента', 'usam'); ?></td>
					<td><?php echo usam_convert_time( $this->speed_results['lighthouseResult']['audits']['metrics']['details']['items'][0]['observedFirstContentfulPaint'], 'ms' ); ?></td>
				</tr>	
				<tr>
					<td><?php _e( 'Время загрузки для взаимодействия', 'usam'); ?></td>
					<td><?php echo usam_convert_time( $this->speed_results['lighthouseResult']['audits']['metrics']['details']['items'][0]['interactive'], 'ms' ); ?></td>
				</tr>	
				<tr>
					<td><?php _e( 'Время загрузки достаточной части контента', 'usam'); ?></td>
					<td><?php echo usam_convert_time( $this->speed_results['lighthouseResult']['audits']['metrics']['details']['items'][0]['firstMeaningfulPaint'], 'ms' ); ?></td>
				</tr>	
				<tr>
					<td><?php _e( 'Время окончания работы ЦП', 'usam'); ?></td>
					<td><?php echo usam_convert_time( $this->speed_results['lighthouseResult']['audits']['metrics']['details']['items'][0]['firstCPUIdle'], 'ms' ); ?></td>
				</tr>	
				<tr>
					<td><?php _e( 'Приблизительное время задержки при вводе', 'usam'); ?></td>
					<td><?php echo $this->speed_results['lighthouseResult']['audits']['metrics']['details']['items'][0]['estimatedInputLatency']; ?></td>
				</tr>												
			</tbody>
		</table>	
		<?php		
	}
	
	public function important_data() 
	{
		?>
		<table class ="wp-list-table widefat fixed striped">					
			<thead>
				<tr>					
					<td><?php _e( 'Описание', 'usam'); ?></td>
					<td><?php _e( 'Значение', 'usam'); ?></td>								
				</tr>
			</thead>
			<tbody>	
				<tr>
					<td><?php _e( 'Тег - title', 'usam'); ?></td>
					<td><?php echo $this->data['tag']['seo']['title']; ?></td>									
				</tr>	
				<?php
				if ( !empty($this->speed_results['lighthouseResult']) )
				{
				?>					
				<tr>
					<td><?php _e( 'Максимальная глубина вложенности DOM', 'usam'); ?></td>
					<td><?php echo $this->speed_results['lighthouseResult']['audits']['dom-size']['details']['items'][0]['value']; ?></td>						
				</tr>	
				<?php
				}
				?>
			</tbody>
		</table>	
		<?php
	}
	
	public function display_statistics()
	{			
		if ( !empty($this->speed_results['lighthouseResult']) )
		{
		?>		
		<div class="crm-important-data">
			<div class="crm-start-row">
				<h3 class="title"><?php esc_html_e( 'Статистика', 'usam'); ?></h3>
				<div class="crm-start-row-result">
					<div class="crm-start-row-result-item">
						<div class="crm-start-row-result-item-title"><?php esc_html_e( 'Индекс скорости загрузки', 'usam'); ?></div>
						<div class="crm-start-row-result-item-total"><?php echo usam_convert_time( $this->speed_results['lighthouseResult']['audits']['metrics']['details']['items'][0]['speedIndex'], 'ms' ); ?></div>
					</div>
					<div class="crm-start-row-result-item">
						<div class="crm-start-row-result-item-title"><?php esc_html_e( 'Время загрузки первого контента', 'usam'); ?></div>
						<div class="crm-start-row-result-item-total"><?php echo usam_convert_time( $this->speed_results['lighthouseResult']['audits']['metrics']['details']['items'][0]['observedFirstContentfulPaint'], 'ms' ); ?></div>
					</div>
					<div class="crm-start-row-result-item">
						<div class="crm-start-row-result-item-title"><?php esc_html_e( 'Количество ссылок', 'usam'); ?></div>
						<div class="crm-start-row-result-item-total"><?php echo count($this->data['a']); ?></div>
					</div>				
				</div>
			</div>
		</div>
		<?php 	
		}
	}
		
	public function file_download_speed()
	{			
		?>		
		<table class ="wp-list-table widefat fixed striped">					
			<thead>
				<tr>					
					<td><?php _e( 'Файл', 'usam'); ?></td>									
					<td><?php _e( 'Скорость загрузки', 'usam'); ?></td>							
					<td><?php _e( 'Размер', 'usam'); ?></td>			
				</tr>
			</thead>
			<tbody>						
				<?php 						
				if ( !empty($this->speed_results['lighthouseResult']) )				
					foreach ( $this->speed_results['lighthouseResult']['audits']['critical-request-chains']['details']['chains'] as $chains ) 
					{									
						foreach ( $chains['children'] as $key => $item ) 
						{
							$time = round(($item['request']['endTime'] - $item['request']['startTime'])*1000,0);
							?>
							<tr>
								<td><?php echo usam_limit_words($item['request']['url'], 256); ?></td>
								<td><?php echo $time; ?></td>
								<td><?php echo size_format($item['request']['transferSize']); ?></td>									
							</tr>
							<?php
						}
					}	
				?>
			</tbody>
		</table>
		<?php 	
	}
	
	public function display_file_size()
	{			
		?>		
		<table class ="wp-list-table widefat fixed striped">					
			<thead>
				<tr>					
					<td><?php _e( 'Файл', 'usam'); ?></td>
					<td><?php _e( 'Размер', 'usam'); ?></td>								
				</tr>
			</thead>
			<tbody>						
				<?php 						
				if ( !empty($this->speed_results['lighthouseResult']) )				
					foreach ( $this->speed_results['lighthouseResult']['audits']['total-byte-weight']['details']['items'] as $item ) 
					{									
						?>
						<tr>
							<td><?php echo $item['url']; ?></td>
							<td><?php echo size_format($item['totalBytes']); ?></td>									
						</tr>
						<?php
					}	
				?>					
			</tbody>
		</table>		
		<?php 	
	}	
	
	public function display_optimize_image_size()
	{			
		?>		
		<p><?php _e( 'Используйте более эффективное сжатие, чтобы изображения загружались быстрее и потребляют меньше трафика', 'usam'); ?></p>
		<table class ="wp-list-table widefat fixed striped">					
			<thead>
				<tr>					
					<td><?php _e( 'Файл', 'usam'); ?></td>
					<td><?php _e( 'Размер', 'usam'); ?></td>		
					<td><?php _e( 'Потенциальная экономия', 'usam'); ?></td>							
				</tr>
			</thead>
			<tbody>						
				<?php 						
				if ( !empty($this->speed_results['lighthouseResult']) )				
					foreach ( $this->speed_results['lighthouseResult']['audits']['uses-optimized-images']['details']['items'] as $item ) 
					{									
						?>
						<tr>
							<td><img height="100px" src='<?php echo $item['url']; ?>' alt='<?php echo $item['url']; ?>'></td>
							<td><?php echo size_format($item['totalBytes']); ?></td>	
							<td><?php echo size_format($item['wastedBytes']); ?></td>	
						</tr>
						<?php
					}							
				?>					
			</tbody>
		</table>	
		<?php 	
	}	
	
	public function display_replace_compression_formats()
	{					
		?>		
		<p><?php _e( 'Для изображений в форматах JPEG 2000, JPEG XR и WebP используется более эффективное сжатие, поэтому они загружаются быстрее и потребляют меньше трафика, чем изображения PNG и JPEG', 'usam'); ?></p>
		<table class ="wp-list-table widefat fixed striped">					
			<thead>
				<tr>					
					<td><?php _e( 'Файл', 'usam'); ?></td>
					<td><?php _e( 'Имя', 'usam'); ?></td>	
					<td><?php _e( 'Размер', 'usam'); ?></td>								
					<td><?php _e( 'Потенциальная экономия', 'usam'); ?></td>							
				</tr>
			</thead>
			<tbody>						
				<?php 						
				if ( !empty($this->speed_results['lighthouseResult']) )				
					foreach ( $this->speed_results['lighthouseResult']['audits']['uses-webp-images']['details']['items'] as $item ) 
					{									
						?>
						<tr>
							<td><img height="50px" src='<?php echo $item['url']; ?>' alt=''></td>
							<td><?php echo basename($item['url']); ?></td>
							<td><?php echo size_format($item['totalBytes']); ?></td>	
							<td><?php echo size_format($item['wastedBytes']); ?></td>	
						</tr>
						<?php
					}							
				?>					
			</tbody>
		</table>		
		<?php 	
	}	
	
	public function display_words()
	{					
		?>		
		<table class ="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td><?php _e( 'Слова', 'usam'); ?></td>
					<td><?php _e( 'Номер', 'usam'); ?></td>
					<td><?php _e( 'Ключевое', 'usam'); ?></td>					
					<td><?php _e( 'Теги', 'usam'); ?></td>
					<td><?php _e( 'Вес', 'usam'); ?></td>
					<td><?php _e( 'Вхождений', 'usam'); ?></td>
					<td><?php _e( 'Ссылки', 'usam'); ?></td>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($this->data['words'] as $key => $keyword)
			{	
				if ( $keyword['occurrence'] === 1 && empty($keyword['tag']) && $keyword['a'] == 0 )
					continue;
				?>
				<tr>
					<td><?php echo $keyword['name']; ?></td>
					<td><?php echo $keyword['number']; ?></td>		
					<td><?php echo !empty($keyword['keyword'])?__("Да",'usam'):''; ?></td>						
					<td><?php echo implode(',',$keyword['tag']); ?></td>
					<td><?php echo $keyword['weight']; ?></td>
					<td><?php echo $keyword['occurrence']; ?></td>
					<td><?php echo $keyword['a']; ?></td>
				</tr>
			<?php }	?>
			</tbody>
		</table>		
		<?php 	
	}	
	
	public function display_links()
	{					
		?>		
		<table class ="wp-list-table widefat fixed striped">
			<thead>
				<tr>					
					<td><?php _e( 'Ссылка', 'usam'); ?></td>
					<td><?php _e( 'Количество', 'usam'); ?></td>	
					<td><?php _e( 'Описания', 'usam'); ?></td>								
				</tr>
			</thead>
			<tbody>
			<?php 	
			if ( isset($this->data['a']) ) 
			{						
				foreach ($this->data['a'] as $url => $anchors) 
				{	
					$titles = array();
					foreach ($anchors as $anchor) 
						$titles[] = !empty($anchor['keywords'])?"<strong>".$anchor['anchor']."</strong>":$anchor['anchor']; 
					?>
					<tr>
						<td><?php echo $url; ?></td>
						<td><?php echo count($anchors); ?></td>
						<td><?php echo "'".implode("','",$titles)."'"; ?></td>
					</tr>
				<?php
				}
			}
			?>
			</tbody>
		</table>		
		<?php 	
	}		
	
	public function display_headers()
	{					
		?>		
		<table class ="wp-list-table widefat fixed striped">
			<thead>
				<tr>					
					<td><?php _e( 'Заголовок', 'usam'); ?></td>
					<td><?php _e( 'Ключевое', 'usam'); ?></td>		
					<td><?php _e( 'Тег', 'usam'); ?></td>		
					<td><?php _e( 'Релевантность', 'usam'); ?></td>							
				</tr>
			</thead>
			<tbody>
			<?php 
			$tags = array( 'h1', 'h2', 'h3', 'h4' );
			foreach ( $tags as $tag ) 
			{						
				if ( isset($this->data['tags'][$tag]) ) 
				{						
					foreach ($this->data['tags'][$tag] as $value) 
					{	?>
						<tr>
							<td><?php echo $value['name']; ?></td>
							<td><?php echo !empty($value['keyword'])?__("Да",'usam'):''; ?></td>			
							<td><?php echo $tag; ?></td>
							<td><?php echo $value['relevance']; ?></td>
						</tr>
					<?php
					}
				}
			}	?>
			</tbody>
		</table>				
		<?php 	
	}	
			
	public function display_meta_tags()
	{					
		if ( !empty($this->data['meta_tags']) )
		{
		?>		
		<table class ="wp-list-table widefat fixed striped">
			<thead>
				<tr>							
					<td><?php _e( 'Тип', 'usam'); ?></td>			
					<td><?php _e( 'Значение', 'usam'); ?></td>									
				</tr>
			</thead>
			<tbody>
			<?php 
			foreach ($this->data['meta_tags'] as $tag => $title) 
			{						
				?>
				<tr>
					<td><?php echo $tag ; ?></td>
					<td><?php echo $title; ?></td>								
				</tr>
				<?php
			}	?>
			</tbody>
		</table>						
		<?php 	
		}
	}	

	public function display_blocking_resources()
	{					
		?>	
		<p><?php printf(__('Возможная оптимизация %s мс', 'usam'), usam_convert_time( $this->speed_results['lighthouseResult']['audits']['render-blocking-resources']['details']['overallSavingsMs'], 'ms' )); ?></p>
		<table class ="wp-list-table widefat fixed striped">					
			<thead>
				<tr>					
					<td><?php _e( 'Файл', 'usam'); ?></td>
					<td><?php _e( 'Размер', 'usam'); ?></td>			
					<td><?php _e( 'Скорость загрузки', 'usam'); ?></td>							
				</tr>
			</thead>
			<tbody>						
				<?php 						
				if ( !empty($this->speed_results['lighthouseResult']) )				
				foreach ( $this->speed_results['lighthouseResult']['audits']['render-blocking-resources']['details']['items'] as $item ) 
				{									
					?>
					<tr>
						<td><?php echo $item['url']; ?></td>
						<td><?php echo size_format($item['totalBytes']); ?></td>		
						<td><?php echo $item['wastedMs']; ?> ms</td>										
					</tr>
					<?php
				}	
				?>					
			</tbody>					
		</table>		
		<?php 	
	}					
	
	public function display_section_analysis() 
	{
		$url = !empty($_REQUEST['url'])?$_REQUEST['url']:'';
		?>
		<form method="post" class="link_analysis">			
			<div class="edit_form">	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='webform_code'><?php esc_html_e( 'Ссылка на страницу', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option edit_form__item_group">
						<input name="url" type="text" class="form-control" placeholder="<?php _e( 'Введите страничку для проверки…', 'usam'); ?>" value="<?php echo $url; ?>">
						<?php submit_button( __('Проверить' ), 'primary', false, false, array( 'id' => "submit" ) ); ?>
					</div>
				</div>
			</div>			
		</form>
		<?php		
		if ( $url != '' )
		{
			require_once( USAM_FILE_PATH . '/includes/seo/seo-analysis.class.php'   );				
			$analysis = new USAM_SEO_Link_Analysis( $url );
			$this->data = $analysis->get_results();	
			$this->speed_results = $analysis->get_speed_results( );	
			
			$this->display_statistics();
			if ( !empty($this->speed_results['lighthouseResult']) )
			{
			?>
			<div class="grid_columns">	
				<div class="grid_column">				
					<?php usam_add_box( 'usam_load_page_result', __('Имитация загрузки страницы','usam'), array($this, 'load_page_result') ); ?>					
				</div>
				<div class="grid_column">		
					<?php usam_add_box( 'usam_important_data', __('Важные данные','usam'), array($this, 'important_data') ); ?>
				</div>	
			</div>				
			<div class="grid_columns">	
				<div class="grid_column">	
					<?php usam_add_box( 'usam_file_download_speed', __('Скорость загрузки файлов','usam').' <span class ="status_result" style="background:'.$this->random_html_color( $this->speed_results['lighthouseResult']['audits']['critical-request-chains']['score'] ).'"></span>', array($this, 'file_download_speed') ); ?>
				</div>
				<div class="grid_column">	
					<?php usam_add_box( 'usam_file_size', __('Размер файлов, которые влияют на загрузку','usam').' <span class ="status_result" style="background:'.$this->random_html_color($this->speed_results['lighthouseResult']['audits']['total-byte-weight']['score']).'"></span>', array($this, 'display_file_size') ); ?>
				</div>	
			</div>	
			<?php usam_add_box( 'usam_blocking_resources', __('Файлы, которые влияют на скорость загрузки','usam').' <span class ="status_result" style="background:'.$this->random_html_color($this->speed_results['lighthouseResult']['audits']['render-blocking-resources']['score']).'"></span>', array($this, 'display_blocking_resources') ); ?>			
			<div class="grid_columns">	
				<div class="grid_column">	
					<?php usam_add_box( 'usam_optimize_image_size', __('Оптимизируйте размер изображений','usam').' <span class ="status_result" style="background:'.$this->random_html_color($this->speed_results['lighthouseResult']['audits']['uses-optimized-images']['score']).'"></span>', array($this, 'display_optimize_image_size') ); ?>
				</div>	
				<div class="grid_column">	
					<?php usam_add_box( 'usam_replace_compression_formats', __('Замените на современные форматы сжатия','usam').' <span class ="status_result" style="background:'.$this->random_html_color($this->speed_results['lighthouseResult']['audits']['uses-webp-images']['score']).'"></span>', array($this, 'display_replace_compression_formats') ); ?>
				</div>	
			</div>		
			<?php 
			}	
			if ( !empty($this->data) )
			{
				?>
				<div class="grid_columns">	
					<div class="grid_column">	
						<?php usam_add_box( 'usam_headers', __('Заголовки','usam'), array($this, 'display_headers') ); ?>
					</div>					
					<div class="grid_column">	
						<?php usam_add_box( 'usam_meta_tags', __('Мета теги','usam'), array($this, 'display_meta_tags') ); ?>
					</div>
				</div>
				<div class="grid_columns">	
					<div class="grid_column">					
						<?php usam_add_box( 'usam_words', __('Контент сайт','usam'), array($this, 'display_words') ); ?>
					</div>
					<div class="grid_column">	
						<?php usam_add_box( 'usam_links', __('Ссылки','usam'), array($this, 'display_links') ); ?>
					</div>
				</div>
				<?php	
			}
		}
	}
	
	public function display_section_robots()
    {	
		?>
		<div class="edit_form">				
			<div class ="edit_form__item">
				<textarea v-model="robots" rows="25"></textarea>
			</div>
			<div class = "edit_form__buttons">	
				<button @click="save" class="button button-primary"><?php _e('Сохранить', 'usam') ?></button>
				<button @click="get_default" class="button"><?php _e('По умолчанию', 'usam') ?></button>		
			</div>
		</div>
		<?php 
    }		
}