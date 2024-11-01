<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_crosssell extends USAM_Edit_Form
{		
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить правило','usam');
		else
			$title = __('Добавить правило', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_data($this->id, 'usam_crosssell_conditions');	
		else
			$this->data = array( 'active' => 0, 'words' => array(), 'conditions' => array() );
	}	
	
	private function output_row( $word = '' ) 
	{		
		?>
			<tr>
				<td><input type="text" name="crosssell[words][]" value="<?php echo $word; ?>" size="4" /></td>				
				<td class="column_actions">
					<?php 
					usam_system_svg_icon("plus", ["class" => "action add"]);
					usam_system_svg_icon("minus", ["class" => "action delete"]);
					?>
				</td>
			</tr>
		<?php
	}
	
	private function output_row_conditions( $condition = array() ) 
	{				
		if ( empty($condition) )
			$condition = array( 'type' => 'name' ,'logic' => '' ,'value' => '', 'logic_operator' => 'AND' );
		
		if ( $condition['logic_operator'] == 'AND' )
		{
			$class_logic = 'condition_logic_and';
			$title_logic = __('И','usam');
			$value_logic = 'AND';
		}
		else
		{
			$class_logic = 'condition_logic_or';
			$title_logic = __('ИЛИ','usam');
			$value_logic = 'OR';
		}		
		?>
			<tr>
				<td class="center">
					<div class="condition_type">					
						<select id ="check_type" name="conditions[type][]">
							<option value="name" <?php selected( $condition['type'], 'name'); ?>><?php echo esc_html__('Название товара', 'usam'); ?></option>		
							<option value="attr" <?php selected($condition['type'], 'attr'); ?>><?php echo esc_html__('Свойство товара', 'usam'); ?></option>	
							<option value="category" <?php selected($condition['type'], 'category'); ?>><?php echo esc_html__('Категория товара', 'usam'); ?></option>	
						</select>				
						<div class = "condition-logic <?php echo $class_logic; ?>"><span><?php echo $title_logic; ?></span>			
							<input type="hidden" name="conditions[logic_operator][]" value="<?php echo $value_logic; ?>"/>
						</div>
					</div>					
				</td>
				<td class="center">
					<div id="logics">							
						<div id="Radio">
							<select name="conditions[logic][]">
							<?php
							$logics = array( 'equal' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,subtotal', 'title' => __('равно', 'usam') ),
								'not_equal' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,subtotal', 'title' => __('не равно', 'usam') ),
								'greater' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,subtotal', 'title' => __('больше', 'usam') ),
								'less' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,subtotal', 'title' => __('меньше', 'usam') ),
								'eg' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,subtotal', 'title' => __('больше либо равно', 'usam') ),
								'el' => array( 'property' => 'total_price,discount,coupons_amount,bonuses,cart_item_count,subtotal', 'title' => __('меньше либо равно', 'usam') ),
								'contains' => array( 'property' => 'item_name', 'title' => __('содержит', 'usam') ),
								'not_contain' => array( 'property' => 'item_name', 'title' => __('не содержит', 'usam') ),
								'begins' => array( 'property' => 'item_name', 'title' => __('начинается с', 'usam') ),
								'ends' => array( 'property' => 'item_name', 'title' => __('заканчивается на', 'usam') ),									
							);
							foreach ($logics as $key => $value)	
							{	
								?><option value="<?php echo $key; ?>"<?php selected($condition['logic'], $key); ?>><?php echo $value['title']; ?></option>	<?php
							}	
							?>	
							</select>									
						</div>             
					</div>
				</td>
				<td class="td_condition_value">							
					<div id="check_name" class="check_blok <?php echo $condition['type']=='name'?'show':'hidden'; ?>">	
						<input class ="condition_value" type="text" name="conditions[value][]" value="<?php echo $condition['value']; ?>" size="4" <?php echo $condition['type']=='name'?'':'disabled = "disabled"'; ?>/>	
					</div>
					<div id="check_attr" class="check_blok <?php echo $condition['type']=='attr'?'show':'hidden'; ?>">	
						<select class ="condition_value" name="conditions[value][]" <?php echo $condition['type']=='attr'?'':'disabled = "disabled"'; ?>>
							<option value=""><?php _e( 'Не выбрано', 'usam'); ?></option>
							<?php 
							$terms = get_terms( array( 'hide_empty' => 0, 'taxonomy' => 'usam-product_attributes', 'update_term_meta_cache' => false ) );	
							foreach ($terms as $term)	
							{									
								?><option value="<?php echo $term->slug; ?>"<?php selected($condition['value'], $term->slug); ?>><?php echo $term->name; ?></option><?php
							}	
							?>	
						</select>	
					</div>
					<div id="check_category" class="check_blok <?php echo $condition['type']=='category'?'show':'hidden'; ?>">	
						<select class ="condition_value" name="conditions[value][]" <?php echo $condition['type']=='category'?'':'disabled = "disabled"'; ?>>
							<option value=""><?php _e( 'Не выбрано', 'usam'); ?></option>	
							<?php 
							$terms = get_terms( array('taxonomy' => 'usam-category', 'hide_empty' => 0, 'update_term_meta_cache' => false ) );	
							foreach ($terms as $term)	
							{									
								?><option value="<?php echo $term->term_id; ?>"<?php selected($condition['value'], $term->term_id); ?>><?php echo $term->name; ?></option><?php
							}	
							?>	
						</select>	
					</div>
				</td>
				<td class="column_actions">
					<?php 
					usam_system_svg_icon("plus", ["class" => "action add"]);
					usam_system_svg_icon("minus", ["class" => "action delete"]);
					?>
				</td>
			</tr>
		<?php
	}
		
    public function display_settings( )
	{	
		?>	
		<table class = "table_rate">
			<thead>
				<tr>
					<th><?php _e('Название товаров', 'usam'); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>				
				<?php if ( !empty( $this->data['words'] ) ): ?>
					<?php
						foreach( $this->data['words'] as $id => $word )
							$this->output_row( $word );							
					?>
				<?php else: ?>
					<?php $this->output_row(); ?>
				<?php endif ?>
			</tbody>
		</table>
      <?php
	}      
	
	public function display_conditions( )
	{			
		?>	
		<div class = "usam_table_container conditions">
			<table class = "table_rate">
				<thead>
					<tr>
						<th><?php _e('Что проверить', 'usam'); ?></th>
						<th><?php _e('Логика', 'usam'); ?></th>
						<th><?php _e('Значение', 'usam'); ?></th>
					</tr>
				</thead>
				<tbody>					
					<?php if ( !empty( $this->data['conditions'] ) ): ?>
						<?php
							foreach( $this->data['conditions'] as $id => $condition )
								$this->output_row_conditions( $condition );							
						?>
					<?php else: ?>
						<?php $this->output_row_conditions(); ?>
					<?php endif ?>
				</tbody>
			</table>	
		</div>	
        <?php
	}      
	
	function display_left()
	{				
		usam_add_box( 'usam_crosssell_words', __('Товары','usam'), array( $this, 'display_settings' ) );			
		usam_add_box( 'usam_conditions', __('Условия','usam'), array( $this, 'display_conditions' ) );	
    }		
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );		
    }
}
?>