<?php
//Vue таблица товаров
abstract class USAM_Table_Form
{
	protected $data = [];
	protected $type = [];
	protected $add_items_table = true;
	protected static $table_form = [];
	
	public function __construct( $type, &$data = [] ) 
	{ 
		$this->type = $type;
		$this->data = $data;
	}	
	
	protected function product_table_tools( ){ }
	protected function display_body_table(){ }
	protected function display_total_table(){ }	
	
	protected function display_special_columns_table()
	{
		?>
		<td :class="'column-'+column" v-for="column in settingsTables.user_columns">
			<span v-html="product[column]"></span>
		</td>
		<?php
	}
	
	protected function display_items_empty()
	{
		?><tr class = "items_empty" v-if="lists!==null && items.length==0"><td :colspan = 'table_columns.length'><?php _e( 'Ничего нет', 'usam'); ?></td></tr><?php
	}
	
	public function get_user_columns()
	{
		$user_columns = get_user_option( 'usam_columns_document' );	
		if ( empty($user_columns) )
			$user_columns = [];
		if ( empty($user_columns[$this->type]) )
			$user_columns[$this->type] = [];			
		return $user_columns[$this->type];
	}
		
	public function set_js_data( $columns )
	{	
		self::$table_form[$this->type] = ['user_columns' => $this->get_user_columns(), 'columns' => $columns, 'column_names' => []];
		$this->print_js_data();
	}
	
	public function add_js_data( $key, $value )
	{	
		self::$table_form[$this->type][$key] = $value;
	}
	
	public function print_js_data()
	{
		remove_action('admin_footer', [__CLASS__, 'js_data']);
		add_action('admin_footer', [__CLASS__, 'js_data']);			
	}
	
	public static function js_data()
	{
		?>
		<script>				
			var settingsTables = <?php echo json_encode( self::$table_form ); ?>;
		</script>	
		<?php
	}
}