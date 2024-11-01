<?php
require_once( USAM_FILE_PATH .'/admin/includes/product_list_table.php' );
class USAM_List_Table_products extends USAM_Product_List_Table
{
	protected $type_price;
	
	function __construct( $args = [] )
	{				
		$selected = $this->get_filter_value( 'tprice' );
		if ( $selected )
		{
			$this->type_price = sanitize_title($selected);
		}
		else
		{		
			$prices = usam_get_prices(['base_type' => '0']);
			if ( !empty($prices) )
			{
				$this->type_price = $prices[0]['code'];
			}		
		} 
		parent::__construct( $args );		
		add_action( 'usam_form_display_table_before', array( &$this, 'select_product_price' ) );		
	}	
	
	public function return_post()
	{
		return ['tprice'];
	}	
	
	public function select_product_price( ) 
	{						
		?>
		<div class="usam_global_operation">	
			<div class="name">
				<div class="title"><?php echo __('Цена для редактирования', 'usam') ?>:</div>
				<div class="select_product_price">
					<form method='GET' action='' id='usam_select_product_price_form'>
						<input type='hidden' value='<?php echo $this->page; ?>' name='page' />
						<input type='hidden' value='<?php echo $this->tab; ?>' name='tab' />					
						<?php echo usam_get_select_prices( $this->type_price, array( 'onChange' => 'this.form.submit()', 'name' => 'tprice' ), false, array( 'base_type' => '0' ) ); ?>
					</form>	
				</div>	
				<div class="save_product_price"><a href="#" id="save-button"><?php _e('Сохранить цены', 'usam'); ?></a></div>	
			</div>	
			<div class="global_operation">
				<div class="content">	
					<select id="price_global_operation">				
						<option value='+'><?php _e('Увеличить', 'usam'); ?></option>			
						<option value='-'><?php _e('Уменьшить', 'usam'); ?></option>						
					</select>
					<?php
					echo usam_get_select_type_md( '', array( 'id' => 'price_global_type_operation' ));
					echo "<input type='text' id='price_global_value' value=''/>";
					submit_button( __('Apply' ), 'action', '', false, array( 'id' => "apply" ) );
				?>
				</div>					
			</div>
		</div>
		<?php
	}
	
	function column_price( $item ) 
	{	
		$price = usam_get_product_price( $item->ID, $this->type_price );
		echo "<input class ='show_change' type='text' data-product_id='$item->ID' value='$price'/>";
	}		
	    
	function get_sortable_columns()
	{
		$sortable = array(
			'product_title'  => array('product_title', false),	
			'price'          => array('price', false),	
			'stock'          => array('stock', false),				
			'date'           => array('date', false),		
			);
		return $sortable;
	}
		
	function get_columns()
	{       
		$prices = usam_get_prices(['type' => 'P']);	
		$columns = ['product_title'  => __('Имя', 'usam')];		
		foreach( $prices as $price )
		{
			$columns['type_price_'.$price['code']] = $price['title'];
		}
		$columns += [	
			'price'          => __('Цена', 'usam'),											
			'stock'          => __('Запас', 'usam'),
			'date'           => __('Дата', 'usam')			
        ];		
        return $columns;
    }
}