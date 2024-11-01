<?php
new USAM_Category_Sale_Forms_Admin();
class USAM_Category_Sale_Forms_Admin
{
	function __construct( ) 
	{		
		add_action( 'created_usam-category_sale', array( $this, 'save' ), 10 , 2 ); //После создания
		add_action( 'edited_usam-category_sale', array( $this, 'save' ), 10 , 2 ); //После сохранения
		add_action( 'usam-category_sale_add_form_fields', array( $this, 'add_forms') ); // форма добавления
		add_action( 'usam-category_sale_edit_form_fields', array( $this, 'edit_forms'), 10, 2 ); // форма редактирования
		
		add_filter( 'manage_edit-usam-category_sale_columns', array( $this, 'custom_category_columns') );
		add_filter( 'manage_usam-category_sale_custom_column', array( $this, 'custom_category_column_data'), 10, 3);	
	}	
	
	/**
	 * Добавляет столбец изображения в категории колонке.
	 */
	function custom_category_columns( $columns ) 
	{
		unset( $columns["cb"] );		
		$custom_array = array( 'cb' => '<input type="checkbox" />' );
		$columns = array_merge( $custom_array, $columns );
		$columns['interval'] = __('Интервал', 'usam');
		$columns['sale_area'] = __('Зона', 'usam');
		return $columns;
	}
	
	/*
	 * Добавляет изображения в колонке на странице категорий
	 */
	function custom_category_column_data( $string, $column_name, $taxonomy_id )
	{				
		switch ( $column_name ) 
		{			
			case 'sale_area':				
				$area = usam_get_term_metadata($taxonomy_id, 'sale_area');	
				echo usam_get_name_sales_area( $area );
			break;
			case 'interval':
				$start_date = usam_get_term_metadata($taxonomy_id, 'start_date_stock');	
				$end_date = usam_get_term_metadata($taxonomy_id, 'end_date_stock');	
				$message = '';
				if ( !empty($start_date) )
					$message .= sprintf(__('c %s','usam'), usam_local_date( $start_date ) ).' ';
				
				if ( !empty($end_date) )
				{
					$message .=  sprintf(__('до %s','usam'), usam_local_date( $end_date ) );
				}
				echo $message;
			break;
		}
	}
	
	/**
	 * печатает левую часть страницы добавления новой категории
	 */
	function add_forms( ) 
	{	
		$settings = ['start_date' => '', 'end_date' => '', 'area' => '', 'company' => ''];
		?>
		<div id="add_new_term" class="postbox usam_box">
			<h3 class="usam_box__title"><?php esc_html_e('Настройка акции', 'usam'); ?></h3>
			<div class="inside">
				<table class ="form-table">					
					<?php					
						$this->display_settings( $settings );		
					?>
				</table>
			</div>
		</div>		
	  <?php
	}
	
	
	function display_settings( $settings ) 
	{	
		?>		
		<tr>
			<th scope="row" valign="top"><?php esc_html_e( 'Акция по фирме', 'usam'); ?></th>
			<td>
				<?php usam_select_companies( $settings['company'], ['name' => "company"]); ?>		
			</td>
		<tr>		
		<tr>
			<th scope="row" valign="top"><?php esc_html_e( 'Срок акции', 'usam'); ?></th>
			<td><?php usam_display_datetime_picker( 'start', $settings['start_date'] ); ?> - <?php usam_display_datetime_picker( 'end', $settings['end_date'] ); ?></td>
		<tr>
		<?php 
		$sales_area = usam_get_sales_areas(); 
		if ( $sales_area )
		{
			?>
			<tr>
				<th scope="row" valign="top"><?php esc_html_e( 'Регионы, для отображения', 'usam'); ?></th>
				<td><?php usam_select_sales_area( $settings['area'], ['name' => 'sale_area']); ?></td>
			<tr>		
			<?php
		}
	}	

	function edit_forms( $term, $taxonomy) 
	{					
		$start_date = usam_get_term_metadata($term->term_id, 'start_date_stock');	
		$end_date = usam_get_term_metadata($term->term_id, 'end_date_stock');	
		$area = usam_get_term_metadata($term->term_id, 'sale_area');			
		$company = usam_get_term_metadata($term->term_id, 'company');		
		$settings = ['start_date' => $start_date, 'end_date' => $end_date, 'area' => $area, 'company' => $company];		
		?>
		<tr>
			<td colspan="2"><h2><?php esc_html_e( 'Настройка акции', 'usam'); ?></h2></td>
		</tr>
		<?php
		$this->display_settings( $settings );
	}
	
	/**
	 * Сохраняет данные категории
	 */
	function save( $category_id, $tt_id )
	{		
		$start_date = usam_get_datepicker('start');
		$end_date = usam_get_datepicker('end');
	
		usam_update_term_metadata($category_id, 'start_date_stock',$start_date );
		usam_update_term_metadata($category_id, 'end_date_stock',$end_date );
		
		$company = !empty($_POST['company'])?absint($_POST['company']):0;
		usam_update_term_metadata($category_id, 'company',$company );			
		
		$sale_area = !empty( $_POST['sale_area'] ) ? (int)$_POST['sale_area']:0;
		usam_update_term_metadata($category_id, 'sale_area', $sale_area );
	}
}
?>