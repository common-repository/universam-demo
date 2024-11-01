<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_balance_information extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить таблицу &laquo;%s&raquo;','usam'), $this->data['name'] );
		else
			$title = __('Добавить таблицу', 'usam');	
		return $title;
	}	
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )					
			$this->data = usam_get_data($this->id, 'usam_balance_information', false);
		else	
			$this->data = ['name' => '', 'category' => [], 'brands' => [], 'catalogs' => []];
	}	

	function display_table_rate( )
	{		
		?>
		<div class="usam_table_container">
		<table class = "table_rate">
			<thead>
				<tr>
					<th><?php _e('Остаток', 'usam'); ?></th>
					<th><?php _e('Информация клиенту', 'usam'); ?></th>
				</tr>
			</thead>
			<tbody>						
				<?php 
				if ( !empty( $this->data['layers'] ) )
				{
					foreach( $this->data['layers'] as $quantity => $info )
						$this->output_row( $quantity, $info );	
					$this->output_row(); 									
				}
				else 
				{
					$this->output_row(); 
					$this->output_row(); 
				}
				?>
			</tbody>
		</table>
		</div>
		<?php
	}
	
	private function output_row( $quantity = '', $info = '' ) 
	{	
		?>
		<tr>
			<td><input type="text" name="quantity[]" value="<?php echo esc_attr( $quantity ); ?>" size="4" /></td>
			<td><input type="text" name="info[]" value="<?php echo esc_attr( $info ); ?>" size="4" /></td>
			<td class="column_actions">
				<?php 
					usam_system_svg_icon("plus", ["class" => "action add"]);
					usam_system_svg_icon("minus", ["class" => "action delete"]);
				?>
			</td>
		</tr>
		<?php
	}	
	
	public function display_conditions( ) 
	{		
		$this->checklist_meta_boxs(['category' => $this->data['category'], 'brands' => $this->data['brands'], 'catalog' => $this->data['catalogs']]);
	}	  
	
	function display_left()
	{			
		$this->titlediv( $this->data['name'] );	
		usam_add_box( 'usam_table_rate', __('Таблица сообщений','usam'), array( $this, 'display_table_rate' ) );		
		usam_add_box( 'usam_conditions', __('Глобальные условия','usam'), array( $this, 'display_conditions' ) );		
    }
}
?>