<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );	
require_once( USAM_FILE_PATH . '/includes/exchange/feed.class.php');
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
class USAM_Form_trading_platform extends USAM_Edit_Form
{
	protected $platform_instance;
	protected function get_title_tab()
	{ 			
		if ( $this->id != null )
		{
			$title = sprintf( __('Изменить выгрузку &laquo;%s&raquo;','usam'), $this->data['name'] );
		}
		else
			$title = __('Добавить выгрузку на сайт торговых площадок', 'usam');	
		return $title;
	}	
	
	public function get_form_id() 
	{
		if ( $this->data['platform'] )
			return $this->data['platform'];
		else
			return $this->form_name;
	}
	
	protected function form_class( ) 
	{ 
		return 'trading_platform';
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function get_data_tab(  )
	{		
		if ( $this->id != null )
		{
			$this->platform_instance = usam_get_trading_platforms_class( $this->id );
			if ( is_object($this->platform_instance) )
			{
				$this->data = $this->platform_instance->get_data();
				$this->data += $this->platform_instance->get_js_form_data();				
				if ( !empty($this->data['product_characteristics']) )					
				{
					$terms = get_terms(['include' => $this->data['product_characteristics'], 'hide_empty' => 0]);
					$this->data['product_characteristics'] = [];
					foreach ( $terms as $term )
						$this->data['product_characteristics'][] = ['id' => $term->term_id, 'name' => $term->name];
				}
				else
					$this->data['product_characteristics'] = [];
			}
		}
		else
			$this->data = ['name' => __('Новая торговая платформа','usam'), 'platform' => '', 'orderby' => '', 'order' => '','location_id' => '', 'active' => '', 'phone' => '', 'start_date' => '', 'end_date' => '', 'from_price' => '', 'to_price' => '', 'from_stock' => '', 'to_stock' => '', 'limit' => '', 'from_views' => '','to_views' => '','type_price' => '', 'category' => [], 'category_sale' => [], 'brands' => [], 'contractors' => [], 'product_characteristics' => []];
	}		
	
	public function display_trading_platform_settings() 
	{		
		if ( is_object($this->platform_instance) )
		{
			ob_start();
			$this->platform_instance->get_form( ); 
			$html = ob_get_clean();
		}
		else
			$html = '';
		if ( $html )
		{
			?>
			<div class="edit_form">
				<?php echo $html; ?>
			</div>
			<?php
		}
	}
	
	public function display_settings() 
	{		
		$terms = usam_get_product_attributes();
		?>
		 <div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='type_price'><?php esc_html_e( 'Тип цены', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php echo usam_get_select_prices( $this->data['type_price'] ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Название товара', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select-list @change="data.product_title=$event.id" :lists="attributes" :selected="data.product_title" :none="'<?php _e( 'Название товара', 'usam'); ?>'"></select-list>
					<input type="hidden" name="product_title" v-model="data.product_title">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Использовать описание из', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select-list @change="data.product_description=$event.id" :lists="attributes" :selected="data.product_description" :none="'<?php _e( 'Описание товара', 'usam'); ?>'"></select-list>
					<input type="hidden" name="product_description" v-model="data.product_description">
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Интервал активности', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<?php usam_display_datetime_picker( 'start', $this->data['start_date'] ); ?> - <?php usam_display_datetime_picker( 'end', $this->data['end_date'] ); ?>
				</div>
			</div>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Выгружать следующее характеристики', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<div class="autocomplete_filter checklist">
						<div class="checklist__search_selected">										
							<div class="filter_counterparty usam_autocomplete">													
								<autocomplete :clearselected="1" @change="addAttribute" :request="'product_attributes'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>			
							</div>
							<div class="checklist__selected" v-if="data.product_characteristics.length">
								<div class="checklist__selected_name" v-for="(list, k) in data.product_characteristics"><span v-html="list.name"></span><a class='button_delete' @click="data.product_characteristics.splice(k,1)"></a><input type="hidden" :name ="'product_characteristics[]'" v-model="list.id"></div>
							</div>
						</div>									
					</div>	
				</div>
			</div>			
		</div>
		<?php
	}	
		
	public function display_filter() 
	{		
		?>
		<div class="edit_form">						
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_day'><?php esc_html_e('Товары, добавленные за указанные дни', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $selected = usam_get_feed_metadata( $this->id, 'from_day' ); ?>
					<input type="number" name="from_day" id="from_day" value="<?php echo $selected; ?>" class="interval" placeholder="<?php esc_html_e( 'от', 'usam'); ?>"/> -
					<?php $selected = usam_get_feed_metadata( $this->id, 'to_day' ); ?>					
					<input type="number" name="to_day" value="<?php echo $selected; ?>" class="interval" placeholder="<?php esc_html_e( 'до', 'usam'); ?>"/>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_price'><?php esc_html_e('Цена', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $from_price = usam_get_feed_metadata( $this->id, 'from_price' ); ?>
					<?php $to_price = usam_get_feed_metadata( $this->id, 'to_price' ); ?>
					<input type="text" name="from_price" id="from_price" value="<?php echo $from_price; ?>" class="interval" placeholder="<?php esc_html_e( 'от', 'usam'); ?>"/> - 
					<input type="text" name="to_price" value="<?php echo $to_price; ?>" class="interval" placeholder="<?php esc_html_e( 'до', 'usam'); ?>"/>	
				</div>
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_price'><?php esc_html_e( 'Доступный остаток', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $from_stock = usam_get_feed_metadata( $this->id, 'from_stock' ); ?>
					<?php $to_stock = usam_get_feed_metadata( $this->id, 'to_stock' ); ?>
					<input type="text" name="from_stock" id="from_stock" value="<?php echo $from_stock; ?>" class="interval" placeholder="<?php esc_html_e( 'от', 'usam'); ?>"/> - 
					<input type="text" name="to_stock" value="<?php echo $to_stock; ?>" class="interval" placeholder="<?php esc_html_e( 'до', 'usam'); ?>"/>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='from_price'><?php esc_html_e( 'Просмотры', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $from_views = usam_get_feed_metadata( $this->id, 'from_views' ); ?>
					<?php $to_views = usam_get_feed_metadata( $this->id, 'to_views' ); ?>
					<input type="text" name="from_views" id="from_views" value="<?php echo $from_views; ?>" class="interval" placeholder="<?php esc_html_e( 'от', 'usam'); ?>"/> - 
					<input type="text" name="to_views" value="<?php echo $to_views; ?>" class="interval" placeholder="<?php esc_html_e( 'до', 'usam'); ?>"/>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_limit'><?php esc_html_e( 'Максимальное количество', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $limit = usam_get_feed_metadata( $this->id, 'limit' ); ?>
					<input type="text" name="limit" id="option_limit" value="<?php echo $limit; ?>" />
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_orderby'><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $orderby = usam_get_feed_metadata( $this->id, 'orderby' ); ?>
					<select name="orderby" id="option_orderby" >				
						<option value='date' <?php selected('date', $orderby); ?>><?php _e('По дате','usam') ?></option>	
						<option value='modified' <?php selected('modified', $orderby); ?>><?php _e('По дате изменения','usam') ?></option>
						<option value='title' <?php selected('title', $orderby); ?>><?php _e('По названию','usam') ?></option>	
						<option value='author' <?php selected('author', $orderby); ?>><?php _e('По автору','usam') ?></option>						
						<option value='menu_order' <?php selected('menu_order', $orderby); ?>><?php _e('По ручной сортировке','usam') ?></option>
						<option value='rand' <?php selected('rand', $orderby); ?>><?php _e('Случайно','usam') ?></option>				
						<option value='views' <?php selected('views', $orderby); ?>><?php _e('По просмотрам','usam') ?></option>			
						<option value='rating' <?php selected('rating', $orderby); ?>><?php _e('По рейтингу','usam') ?></option>			
						<option value='price' <?php selected('price', $orderby); ?>><?php _e('По цене','usam') ?></option>		
						<option value='stock' <?php selected('stock', $orderby); ?>><?php _e('По остаткам','usam') ?></option>							
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_order'><?php esc_html_e( 'Направление сортировки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $order = usam_get_feed_metadata( $this->id, 'order' ); ?>
					<select name="order" id='option_order'>				
						<option value='ASC' <?php selected('ASC', $order); ?>><?php _e('По порядку','usam') ?></option>	
						<option value='DESC' <?php selected('DESC', $order); ?>><?php _e('Обратный порядок','usam') ?></option>						
					</select>
				</div>
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__title"><label><?php esc_html_e( 'Выберите из каких групп выбирать товары', 'usam'); ?></label></div>				
			</div>	
			<div class ="edit_form__item">				
				<?php 
				$taxonomies = get_taxonomies(['object_type' => ['usam-product'], 'public' => true, 'show_in_rest' => true], 'objects');
				$lists = []; //product_attributes
				foreach ( $taxonomies as $tax )
				{
					$name = str_replace('usam-','',$tax->name);
					$lists[$name] = (array)usam_get_feed_metadata( $this->id, $name );
				}
				$lists['contractors'] = (array)usam_get_feed_metadata( $this->id, 'contractors' ); 
				$this->checklist_meta_boxs( $lists ); 
				?>	
			</div>
		</div>
		<?php 
	}

	function display_left()
	{					
		$this->titlediv( $this->data['name'] );		
		usam_add_box( 'usam_settings_exporter', __('Общие настройки экспорта','usam'), [$this, 'display_settings']);	
		usam_add_box( 'usam_trading_platform_exporter', __('Настройки платформы','usam'), array( $this, 'display_trading_platform_settings' ));	
		usam_add_box( 'usam_product_select', __('Настройки выбора товаров','usam'), [$this, 'display_filter']);		
    }	
			
	protected function get_toolbar_buttons( ) 
	{
		$links = [
			['action_url' => home_url('trading-platform/feed/'.$this->id), 'name' => __('Посмотреть выгрузку','usam'), 'display' => 'not_null'],
			['submit' => "save", 'name' => $this->id ? __('Сохранить','usam'):__('Добавить','usam'), 'display' => 'all']
		];
		return $links;
	}	
	
	function display_right()
	{	
		$this->add_box_status_active( $this->data['active'] );
	}
	
	protected function toolbar_buttons( ) 
	{
		if ( $this->id === null )
			return submit_button( __('Выбрать', 'usam').' &#187;', 'primary button_save_close', '', false, array( 'id' => 'submit-add' ) );
		else
		{
			$this->display_toolbar_buttons();
			$this->delete_button();
		}
	}
	
	public function display_form( ) 
	{
		if ( $this->id != null )
		{
			?><input type='hidden' value='<?php echo $this->data['platform']; ?>' name='platform' /><?php		
			parent::display_form( );
		}
		else
		{
			?> 
			<div class="settings_list">
				<?php
				foreach (usam_get_data_integrations( 'trading-platforms', ['name' => 'Name', 'icon' => 'Icon'] ) as $key => $item)
				{
					?><label class="settings_list__item js-checked-item">		
						<?php echo usam_get_icon( $item['icon'], 80 ); ?>					
						<input type="radio" name="platform" class="input-radio" value="<?php echo $key; ?>">						
						<div class="settings_list__title"><?php echo $item['name']; ?></div>						
					</label><?php 	
				}
				?>
			</div>
			<?php	
		}
	}	
}
?>