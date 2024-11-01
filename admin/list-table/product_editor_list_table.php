<?php
require_once( USAM_FILE_PATH .'/admin/includes/product_list_table.php' );
class USAM_List_Table_product_editor extends USAM_Product_List_Table
{	
	protected $orderby = 'ID';
	protected $order   = 'desc';
	
	function __construct( $args = [] )
	{	
		parent::__construct( $args );
		add_action( 'usam_form_display_table_before', [&$this, 'select_product_price']);		
	}	
	
	public function select_product_price( ) 
	{				
		global $type_price;			
		$name_pice = usam_get_name_price_by_code( $type_price );
		?>
		<div class="usam_global_operation">	
			<div class="name">
				<div class="title"><?php echo __('Глобальная замена', 'usam') ?></div>		
				<a href="#" id="save-button"><?php _e('Сохранить', 'usam') ?></a>		
			</div>	
			<div class="global_operation">
				<div class="content">	
					<select id="where_replace" >
						<option value="title"><?php _e('В названии','usam'); ?></option>
						<option value="charact"><?php _e('В характеристиках','usam'); ?></option>
						<option value="desc"><?php _e('В описании','usam'); ?></option>
					</select>
					<input type="text" id = "what_replaced" placeholder="<?php _e('Что заменить', 'usam') ?>" value="">
					<input type="text" id = "how_to_replace" placeholder="<?php _e('Чем заменить', 'usam') ?>" value="">
					
					<?php
					submit_button( __('Apply' ), 'action', '', false, array( 'id' => "button_replaced" ) );
					?>
				</div>					
			</div>
		</div>
		<?php
	}
	
	function column_product_title( $item ) 
	{		
		?>		
		<input type="text" id="product_title_<?php echo $item->ID; ?>" data-product_id="<?php echo $item->ID; ?>" value="<?php echo htmlspecialchars($item->post_title, ENT_QUOTES); ?>" class = "show_change"/><br>
		<?php	
		echo __('Артикул','usam').': '.usam_get_product_meta($item->ID, 'sku', true )."<br>";
		echo __('Статус','usam').': '.$item->post_status;
		?>	
		<br><br>
		<a href="<?php echo usam_product_url( $item->ID ); ?>"><?php _e( 'Посмотреть', 'usam'); ?></a>
		| <a href="<?php echo get_edit_post_link( $item->ID ); ?>"><?php _e( 'Изменить', 'usam'); ?></a>		
		<?php
	}
	
	function column_post_excerpt( $item ) 
	{			
		?><textarea cols="" rows="" class="overflow_y show_change" data-product_id="<?php echo $item->ID; ?>" id="product_excerpt_<?php echo $item->ID; ?>"><?php echo htmlspecialchars($item->post_excerpt, ENT_QUOTES); ?></textarea>	
		<?php
	}
	
	function column_post_content( $item ) 
	{			
		?><textarea cols="" rows="" class="overflow_y show_change" data-product_id="<?php echo $item->ID; ?>" id="product_content_<?php echo $item->ID; ?>"><?php echo $item->post_content; ?></textarea>	
		<?php
	}
				
	function get_columns()
	{
        $columns = [
			'image' => '',
			'product_title' => __('Название', 'usam'),
			'post_content'  => __('Содержание', 'usam'),
			'post_excerpt'  => __('Описание', 'usam'), 
        ];		
        return $columns;
    }		
}