<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Search_Order extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'search-order';		
	public function register_block_type() 
	{ 
		register_block_type(
			$this->namespace . '/' . $this->block_name,
			array(
				'render_callback' => array( $this, 'render' ),
				'editor_script'   => "usam-{$this->block_name}-block",		
				'attributes'      => $this->get_attributes(), 
			)
		);
	}
	
	public function render( $attributes = [], $content = '' ) 
	{ 
		remove_filter( 'the_content', 'wptexturize' ); // иначе искажается html
		ob_start();
		?>
		<div id="search_my_order" class="search_info" v-cloak>
			<div class="search_info__search" v-if="tab=='search'">			
				<h2 class="title"><?php _e('Информация по вашему заказу','usam'); ?></h2>	
				<div class="search_info__keyword">		
					<input class="search_info__input option-input" ref="search" v-on:keyup.enter="search" type="text" placeholder="<?php _e( 'Введите номер заказа', 'kodi' ); ?>" v-model="id" autocomplete="off">
					<span class="search_info__button" @click="search"><?php usam_svg_icon("search"); ?></span>					
				</div>
				<div class="search_info__no_data" v-if="no_data"><?php _e( 'Нет информации', 'usam'); ?></div>	
			</div>
			<div class="tracking__operations" v-else>	
				<div class="search_info__title">
					<h2><a class="search_info__back" @click="back"><?php usam_svg_icon("angle-down-solid") ?></a><?php _e('Заказ','usam'); ?> {{order.id}}</h2>
					<div class ="search_info__description"><?php _e( 'Дата', 'kodi' ); ?> {{order.date_insert}}</div>		
				</div>
				<div class ='view_form' v-if="order">
					<div class ='view_form__item'>	
						<div class ='view_form__name'><?php _e('Статус заказа','usam') ?></div>	
						<div class ='view_form__option' v-html="order.status_name"></div>	
					</div>			
					<div class ='view_form__item'>	
						<div class ='view_form__name'><?php _e('Описание статуса','usam') ?></div>	
						<div class ='view_form__option' v-html="order.status_description"></div>	
					</div>
					<div class ='view_form__item'  v-if="order.shipping_method">	
						<div class ='view_form__name'><?php _e('Способ получения заказа','usam') ?></div>	
						<div class ='view_form__option' v-html="order.shipping_method"></div>	
					</div>		
					<div class ='view_form__item' v-if="order.track_id">	
						<div class ='view_form__name'><?php _e('Номер отслеживания','usam') ?></div>	
						<div class ='view_form__option'><a :href="'<?php echo usam_get_url_system_page('tracking') ?>?track_id='+order.track_id" title="<?php _e('История отправления','usam') ?>">{{order.track_id}}</a></div>	
					</div>						
				</div>							
			</div>
		</div>		
		<?php		
		return ob_get_clean();
	}
	
	protected function get_attributes() {
		return array( );
	}
}