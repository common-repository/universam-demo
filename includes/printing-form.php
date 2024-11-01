<?php		
class USAM_Printing_Form
{	
	protected $id;	
	protected $bank_account_id;	
	protected $options;	
	protected $edit;
	protected $data = array();	
	protected $products = array();
	protected $printed_form = '';	
	
	public function __construct( $id )
	{		
		if ( !empty($id) )			
			$this->id = $id;	
	}		
	
	public function get_template( )
	{ 
		ob_start();	
		
		$file_path = usam_get_template_file_path( $this->printed_form, 'printing-forms' );
		if ( file_exists( $file_path ) )	
			require( $file_path );	
		return ob_get_clean();
	}
	
	public function get_edit_printing_forms( $printed_form, $company )
	{			
		$this->edit = true;
		$this->printed_form = $printed_form;
		$this->bank_account_id = $company;		
		
		$this->get_printing_forms_options( );	
		
		$out = $this->get_template( );			
		if ( empty($out) )
			return false;							

		$pattern = '#%%(.+?)%%#s';	
		preg_match_all($pattern, $out, $result, PREG_SET_ORDER);	
		$parent = array();
		$i = 0;
		foreach ( $result as $arr ) 
		{
			$args = explode(' ', $arr[1] );	
			$html = '';
			
			preg_match_all('~"(.*?)(?:"|$)|([^"]-)~',$arr[1], $title ); 
			if ( !empty($this->options['data'][$args[0]]) )			
				$value = $this->options['data'][$args[0]];	
			elseif ( isset($title[1][0]) )
				$value = $title[1][0];	
			else
				$value = '';				
	
			$parent["##$i##"] = $value;
			$value = "##$i##";
			$i++;
			
			$types = array( 'style' => 'width:100%; border-color: #8B0000', 'name' => $args[0] );
			if ( isset($title[1][0]) )
			{
				$parent["##$i##"] = $title[1][0];				
				$i++;
				$parent["##$i##"] = isset($title[1][1])?$title[1][1]:$title[1][0];
				$types['title'] = "##$i##";
				$types['placeholder'] = "##$i##";
				$i++;	
			}					
			$str = '';
			foreach ( $types as $key => $v ) 
				$str .= " $key='$v'";
	
			if ( !empty($args[1]) && $args[1] == 'input' )
				$html = "<input type='text' class='option_form option_form_input' value='$value' $str/>";
			elseif ( $args[1] == 'textarea' ) 
				$html = "<textarea rows='9' class='option_form option_form_textarea' $str >$value</textarea>";				
			
			$out = str_replace( $arr[0], $html, $out );
		}	
		$out = $this->process_args( $out, 'no' );	
	
		$values = array_values($parent);
		$keys = array_keys($parent);
		$out = str_replace( $keys, $values, $out );	
		$out = $out;
		return $out;		
	}	
	
	public function get_printing_forms_options( )
	{
		$printing_form_options = get_option( 'usam_printing_form', array() );			
		if ( isset($printing_form_options[$this->bank_account_id]) )
		{			
			if ( isset($printing_form_options[$this->bank_account_id][$this->printed_form]) )
				$this->options = $printing_form_options[$this->bank_account_id][$this->printed_form];			
		}
		if ( !isset($this->options['table']) )			
			$this->options['table'] = array();	

		if ( !isset($this->options['data']) )			
			$this->options['data'] = array();
		
		return $this->options;
	}
	
	public function get_option( $name_option )
	{		
		 $result = false;
		if ( !isset($this->options['data'][$name_option]) )			
			 $result = $this->options['data'][$name_option];
		
		return $result;
	}
		
	protected function display_table_thead( )
	{				
		echo "<tr>";
		foreach ( $this->options['table'] as $column ) 
		{								
			if ( $this->edit ) 
				echo "<th class='column-".$column['name']."' title='".__('Тяни, чтобы переместить','usam')."'><input type='text' class='table_colum_edit' placeholder='".$column['title']."' value='".$column['title']."' name='".$column['name']."' /></th>";	
			else
				echo "<th class='column-".$column['name']."'>".$column['title']."</th>";
		}	
		echo "</tr>";		
	}
	
	public function display_customer_details()
	{
		$groups = usam_get_property_groups();
		$properties = usam_get_properties(['type' => 'order','fields' => 'code=>data']);	
		?>
		<table class = "usam_details_box">	
			<tr>		
			<?php 	
			$i = 0;
			foreach( $groups as $group )
			{							
				$output = '';
				foreach ( $properties as $code => $property ) 			
				{ 		
					if ( $group->code === $property->group )
					{						
						$single = $property->field_type == 'checkbox'?false:true;
						$value = usam_get_order_metadata( $this->data['id'], $code, $single );
						if ( $value !== false && $value !== '' )
							$output .= '<tr><td><strong>'.$property->name.':</strong></td><td><span id = "item-'.$property->code.'">'.usam_get_formatted_property( $value, $property ).'</span></td></tr>';
					}
				}
				if ( $output )
				{
					$i++;
					if( $i % 3 == 0 ) 
						echo "</tr><tr>";
					?>	
					<td valign="top">
						<h2><?php echo $group->name; ?></h2>				
						<table class = 'table_details'>
							<?php echo $output; ?>					
						</table>	
					</td>			
					<?php
				}			
			}
			?>
			</tr>
		</table>
		<?php 
	}
	
	protected function load_table( $columns )
	{ 		
		if ( empty($this->options['table']) )		
		{
			$this->options['table'] = array();
			foreach ( $columns as $name => $title ) 
			{
				$this->options['table'][] = ['title' => sanitize_text_field($title), 'name' => sanitize_title($name)];
			}			
		}			
		$post_ids = array();
		foreach ( $this->products as $product ) 
		{
			$post_ids[] = $product->product_id;
		}
		if ( !empty($post_ids) )
			usam_get_products( array( 'post__in' => $post_ids, 'update_post_term_cache' => true ), true );			
	
		if ( $this->edit ) 
		{			
			?>
			<style type="text/css">		
				.primary{background:#a4286a; color:#ffffff; padding:10px; border-radius:5px; box-shadow: 0 1px 0 #a4286a; cursor:pointer}
				.table_colum_edit{width:100%; border-color: #8B0000}
				#products-table thead th{cursor: move; padding: 10px 5px;}
				input{padding:5px; font-family: initial;}					
			</style>	
			<script>		
				window.onload = function() {					
					parent.iframe_blanks_loaded();					
				}		
			</script>
			<?php 			
			?>
			<br>
			<br>
			<a id="add-columns" class="primary"><?php _e('Управление столбцами таблицы', 'usam'); ?></a>
			<br>
			<br>		
			<?php 
		} 		
	}
	
	protected function style( )
	{ 
		?>
		<style type="text/css">		
			@page {	margin: 0; }		
			@media print {
				.more{page-break-after: always;} 
			} 
			included unicode fonts:*  serif: 'dejavu serif'*  sans: 'devavu sans'		
			body {font-size: 10px; margin: 30px;font-family:"dejavu serif", Helvetica, Arial, Verdana, sans-serif;}
			h1, h2, p, td, th, *{font-family: 'dejavu serif';}		
			h1{font-size:16px; font-weight:bold; text-align: center;}
			h1 span {font-size:0.8em;}
			h2{font-size:12px; margin:5px 0}
			h3 {margin: 0 0 0.4em 0;}
			h4 {margin: 0 0 0.4em 0;}	
			td, th {font-size: 10px; padding:3px; white-space: normal;}
			p {font-size: 11px; margin:2px; white-space: normal; text-align: left;}	
			div {font-size: 13px; margin:2px;}
			.counterparty{margin-bottom:10px}
			table {border-collapse: collapse; width:100%;}		
			thead th{background-color:#efefef; border:0.2pt solid #606060; text-align: center; padding:5px}				
			#products-table{width:100%;}
			#products-table thead .column-name{white-space: nowrap}
			#products-table td{padding:3px 3px; text-align:right;}	
			#products-table tbody td{border:0.1pt solid #606060;}
			#products-table tbody .column-image{text-align:center;}			
			#products-table .column-name{text-align: left;white-space:normal;line-height:normal;}
			#products-table tbody .column-barcode{text-align:center;}	
			#products-table tbody .column-barcode div{white-space: nowrap; margin: 0;}		
			#products-table tbody .column-barcode img{ margin: 0; }		
			#products-table tbody .column-price p,
			#products-table tbody .column-discount_price p,
			#products-table tbody .column-total p{white-space:pre }	
			#products-table tfoot th{text-align: right;}	
			#products-table tfoot td{white-space: nowrap}	
			#products-table td.column-sku{white-space: nowrap}		
			.totalprice{font-size:16px; font-weight: 700;}	
			.sign{margin-top:5px;}
			.sign td{border: none;}
			.total_price_word:first-letter {text-transform: uppercase;}	
			.invoice-footer{white-space: pre-wrap}				
		</style>				
		<?php 
	}
	
	protected function display_table( $columns )
	{ 		
		$this->load_table( $columns );
		?>		
		<table id="products-table" style="width:100%;border-collapse:collapse;">
			<thead><?php $this->display_table_thead( ); ?></thead>
			<tbody><?php $this->display_table_tbody( ); ?></tbody>
			<tfoot><?php $this->display_table_tfoot( ); ?></tfoot>
		</table>
		<?php	
	}
	
	protected function display_table_tbody( )
	{	
		$data_table = $this->get_data_table(); 
		foreach ( $data_table as $data ) 
		{			
			$row = '';
			foreach ( $data as $column => $value ) 
			{							
				$row .= "<td class ='column-{$column}'><p>{$value}</p></td>";
			}
			echo "<tr>$row</tr>";
		}
	
	
	}	
	protected function display_table_tfoot( ){	}	
	
	protected function get_data_table( )
	{
		return array();
	}
	
	protected function get_args( )
	{
		return array();
	}
	
	private function process_args( $html, $replacement = 'all' ) 
	{ 
		$args =	$this->get_args();
		$files = usam_get_files( array('type' => 'seal') );
		foreach ( $files as $file )		
			$args['seal_'.$file->id] = "<img src='".USAM_UPLOAD_URL.$file->file_path."'/>";
			
		$shortcode = new USAM_Shortcode();				
		return $shortcode->process_args( $args, $html, $replacement );
	}
		
	public function get_printing_forms( $printed_form )
	{				
		$this->printed_form = $printed_form;					
		$this->edit = false;
		
		$this->get_printing_forms_options( );

		$html = $this->get_template( );	
		if ( empty($html) )
			return false;
	
		if ( empty($this->data) )
			return false;
	
	//	$pattern = '/\[#[^\[\]]+#\]/';
		$pattern = '#%%(.+?)%%#s';	
		preg_match_all( $pattern, $html, $result, PREG_SET_ORDER);	
		foreach ( $result as $arr ) 
		{			
			$args = explode(' ', $arr[1] );		
			$str = '';
			if ( isset($this->options['data'][$args[0]]) )
			{
				$str = $this->options['data'][$args[0]];	
			}
			elseif ( isset($arr[1]) )
			{
				preg_match('~"(.*?)(?:"|$)|([^"]-)~',$arr[1], $r ); 
				if ( isset($r[1]) )
					$str = $r[1];				
			}				
			$html = str_replace( $arr[0], $str, $html );
		}
		$html = $this->process_args( $html );
		return $html;		
	}	
			
	public function get_export_forms_to_xlsx( $name )
	{ 		
		$this->printed_form = $name;
		$file_path = usam_get_template_file_path( $this->printed_form, 'xlsx-forms' );
		if ( !$file_path )	
			return false;
		require( $file_path );	
		
		$args =	$this->get_args();
		$this->get_printing_forms_options( );
		
		if ( empty($this->options['table']) )		
		{
			$this->options['table'] = array();
			foreach ( $columns as $name => $title ) 
			{
				$this->options['table'][] = ['title' => sanitize_text_field($title), 'name' => sanitize_title($name)];
			}			
		}		
		$files = usam_get_files( array('type' => 'seal') );
		foreach ( $files as $file )		
			$args['seal_'.$file->id] = "<img src='".USAM_UPLOAD_URL.$file->file_path."'/>";
			
		$headers = [];
		foreach ( $this->options['table'] as $data ) 
		{			
			$headers[] = $data['title'];
		}
		$table = $this->get_data_table();
		$values = array_values($args);
		$keys = array();
		foreach ( $args as $key => $value ) 
			$keys[] = "%$key%";		
			
		$new_data = [];
		$i = 0;		
		foreach ( $data_export as $key => $data ) 
		{			
			$j = 0;
			foreach ( $data as $v )
			{						
				if ( !is_array($v) )
					$v = ['value' => $v];
				if ( !isset($v['value']) )
					$v['value'] = '';
				if ( $v['value'] == '%table%' )
				{
					$y = $j;
					foreach ( $table as $rows )
					{
						foreach ( $rows as $row )
						{
							$new_data[$i][$j]['value'] = $row;
							$new_data[$i][$j]['border'] = ['color' => '#000000'];
							$j++;
						}
						$j = $y;
						$i++;
					}
				}
				else
				{
					$new_data[$i][$j] = $v;
					$new_data[$i][$j]['value'] = str_replace( $keys, $values, $v['value'] );
				}
				$j++;
			}
			$i++;
			unset($data_export[$key]);
		} 
		return usam_write_exel_file( $new_data );
	}	
}

function usam_get_export_form_to_pdf( $name, $id = null ) 
{ 		
	$data = usam_get_data_printing_forms( $name );	
	if ( $data == false )
		return false;
		
	$file_path = USAM_FILE_PATH . "/includes/printing-forms/printing-forms-".$data['type'].".php";	
	$printing = new USAM_Printing_Form( $id );	
	if ( !empty($data['type']) && file_exists($file_path) )
	{ 	
		require_once( $file_path );	
		$class = "USAM_Printing_Form_".$data['type'];
		if ( class_exists($class) ) 
		{ 	
			$printing = new $class( $id );				
		}	
	}
	$html = $printing->get_printing_forms( $name );		
	return usam_export_to_pdf( $html, $data );
}

function usam_get_export_form_to_xlsx( $name, $id = null ) 
{ 		
	$data = usam_get_data_printing_forms( $name );	
	if ( $data == false )
		return false;
		
	$file_path = USAM_FILE_PATH . "/includes/printing-forms/printing-forms-".$data['type'].".php";	
	$printing = new USAM_Printing_Form( $id );	
	if ( !empty($data['type']) && file_exists( $file_path ) )
	{ 	
		require_once( $file_path );	
		$class = "USAM_Printing_Form_".$data['type'];
		if ( class_exists($class) ) 
			$printing = new $class( $id );	
	}
	return $printing->get_export_forms_to_xlsx( $name );
}

function usam_get_printing_forms( $printed_form, $id = null ) 
{	
	$data = usam_get_data_printing_forms( $printed_form );	
	if ( $data == false )
		return false;

	$printing = new USAM_Printing_Form( $id );		
	if ( !empty($data['type']))
	{
		$file_path = USAM_FILE_PATH . "/includes/printing-forms/printing-forms-".$data['type'].".php";
		if ( file_exists( $file_path ) )	
		{  
			require_once( $file_path );	
			$class = "USAM_Printing_Form_".$data['type'];		
			if ( class_exists($class) ) 		
			{
				$printing = new $class( $id );
			}
		}		
	} 
	return $printing->get_printing_forms( $printed_form );
}

function usam_get_edit_printing_forms( $printed_form, $company, $id = null ) 
{ 
	$data = usam_get_data_printing_forms( $printed_form );
	if ( $data == false )
		return false;
	
	$file_path = USAM_FILE_PATH . "/includes/printing-forms/printing-forms-".$data['type'].".php";		
	if ( file_exists( $file_path ) )	
	{
		require_once( $file_path );	
		$class = "USAM_Printing_Form_".$data['type'];	
		if ( class_exists($class) ) 
		{ 
			$printing = new $class( $id );
		}		
		else
			$printing = new USAM_Printing_Form( $id );		
	}
	else
		$printing = new USAM_Printing_Form( $id );
	return $printing->get_edit_printing_forms( $printed_form, $company );
}

// Получить данные формы
function usam_get_data_printing_forms( $printed_form ) 
{ 
	$args = ['title' => 'Printing Forms', 'description' => 'Description', 'type' => 'type', 'orientation' => 'orientation', 'object_type' => 'object_type', 'object_name' => 'object_name'];
	$file_path = usam_get_template_file_path( $printed_form, 'printing-forms' );
	return get_file_data( $file_path, $args );
}

function usam_get_printed_forms_document( $document = null, $type = 'printing-forms' ) 
{ 
	$codes = [];
	$results = [];
	foreach ( [USAM_THEMES_PATH, USAM_CORE_THEME_PATH] as $path ) 
	{
		$files = usam_list_dir( $path. $type );			
		foreach ( $files as $file ) 
		{
			if ( stristr( $file, '.php' ) ) 		
			{
				$parts = explode( '.', $file );			
				if ( !empty($parts[0]) && !in_array($parts[0], $codes) )
				{
					$codes[] = $parts[0];
					$data = usam_get_data_printing_forms( $parts[0] );
					if ( !$document || $data['object_name'] == $document )
					{
						$data['id'] = $parts[0];
						$results[] = $data;
					}
				}
			}		
		}
	}	
	return $results;
}
?>