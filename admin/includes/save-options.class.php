<?php
class USAM_Save_Option
{				
	protected  $local_option = 'usam_options';
	protected  $site_option  = 'usam_site_options';	
	public function display( $options ) 
	{ 
		?>		
		<div class='edit_form'>
			<?php $this->row_option( $options ); ?>	
		</div>
		<?php	
	}		
	
	private function get_default_radio() 
	{
		return  ['0' => __('Нет', 'usam'), '1' => __('Да', 'usam')];
	}
	
	
	public function row_option( $options ) 
	{		
		foreach ( $options as $key => $option )	
		{	
			$option_bd = '';
			if ( empty($option['description']) )
				$option['description'] = '';
			
			if ( empty($option['attribute']) )
				$option['attribute'] = array();
			
			if ( !isset($option['default']) )
				$option['default'] = '';
						
			if ( isset($option['option']) )
			{
				$option_key = $option['option'];	
				if ( usam_is_multisite() && !empty($option['global']) )
					$local_option = $this->site_option;
				else
					$local_option = $this->local_option;
				$name = "{$local_option}[{$option_key}]";				
				if ( !isset($option['attribute']['value']) )
				{ 	
					$option_key = $option['option'];	
					if ( usam_is_multisite() && !empty($option['global']) )
						$option_bd = get_site_option( 'usam_'.$option_key, $option['default'] );	
					else
						$option_bd = get_option( 'usam_'.$option_key, $option['default'] );						
					
					if ( !empty($option['group']) )						
					{						
						$option_bd = isset($option_bd[$option['group']])?$option_bd[$option['group']]:$option['default'];						
						$name .= "[".$option['group']."]";
						$option_key .= '-'.$option['group'];
					}
					if ( !empty($option['key']) )						
					{						
						$option_bd = isset($option_bd[$option['key']])?$option_bd[$option['key']]:$option['default'];						
						$name .= "[".$option['key']."]";
						$option_key .= '-'.$option['key'];
					}							
				}
				else
					$option_bd = $option['attribute']['value'];
			}		
			else
			{
				$name = '';
				$option_key = '';
			}
			?>
			<div id = "<?php echo 'usam_row-'.$option_key; ?>" class ="edit_form__item edit_form__item_<?php echo $option['type']; ?>">
			<?php
				$default_attribute = ['id' => $option_key, 'value' => $option_bd, 'name' => $name];
				switch ( $option['type'] )
				{
					case 'checkbox' :
						$default_attribute = array( 'id' => $option_key, 'name' => $name );	
						$option['attribute'] = array_merge( $default_attribute, $option['attribute'] );	
						$option['value'] = $option_bd;	
						
						$this->row_option_checkbox( $option );	
					break;
					case 'radio' :
						if ( empty($option['radio']) )
							$option['radio'] = $this->get_default_radio();	
						
						$option['attribute'] = array_merge( $default_attribute, $option['attribute'] );		
						
						$this->row_option_radio( $option );	
					break;
					case 'hidden' :
						$option['attribute'] = array_merge($default_attribute, $option['attribute'] );
						$this->row_option_hidden( $option );	
					break;
					case 'input' :						
						$option['attribute'] = array_merge( $default_attribute, $option['attribute'] );			
						$this->row_option_input( $option );	
					break;
					case 'password' :						
						$option['attribute']['value'] = !empty($option_bd)?'******':'';
						$option['attribute'] = array_merge( $default_attribute, $option['attribute'] );	
						$this->row_option_input( $option );	
					break;
					case 'select' : 
						$default_attribute = ['id' => $option_key, 'name' => $name];	
						$option['attribute'] = array_merge( $default_attribute, $option['attribute'] );	
						$option['value'] = $option_bd;	
						if ( !empty($option['options']) )
							$this->row_option_select( $option );	
					break;
					case 'text' :
						$this->row_option_text( $option );	
					break;					
					case 'checklist' :
						$default_attribute = array( 'id' => $option_key, 'name' => $name );	
						$option['attribute'] = array_merge( $default_attribute, $option['attribute'] );	
						$option['value'] = $option_bd;
						
						$this->row_option_checklist( $option );	
					break;					
					case 'textarea' :
						$default_attribute = array('id' => $option_key, 'name' => $name, 'style' => 'width:100%; height:200px;');	
						$option['attribute'] = array_merge( $default_attribute, $option['attribute'] );							
						$option['value'] = $option_bd;
						
						$this->row_option_textarea( $option );	
					break;
					case 'media' :
						$default_attribute = array('id' => $option_key, 'name' => $name );	
						$option['attribute'] = array_merge( $default_attribute, $option['attribute'] );							
						$option['value'] = $option_bd;
						
						$this->row_option_media( $option );	
					break;					
				}	
			?>
			</div>
			<?php
		}
	}
	
	protected function row_option_media( $option ) 
	{		
		$anonymous_function = function() { 				
			USAM_Admin_Assets::set_thumbnails();
			return true; 
		};
		add_action('admin_footer', $anonymous_function, 1);	
		$image_attributes = wp_get_attachment_image_src( $option['value'], 'thumbnail' );
		$thumbnail = $image_attributes[0];
		if ( empty($thumbnail) )
		{
			$thumbnail = USAM_CORE_IMAGES_URL . '/no-image-uploaded-100x100.png';	
			$hide = 'hide';
		}
		else
			$hide = '';		
		?>
		<div class ="edit_form__item_name"><label><?php echo $option['title']; ?>:</label></div>
		<div class ="edit_form__item_option">
			<div class="usam_thumbnail">
				<a data-attachment_id="<?php echo $option['value']; ?>" data-title="<?php echo $option['title']; ?>" data-button_text="<?php  _e( 'Задать миниатюру', 'usam'); ?>" href="<?php echo esc_url( admin_url( 'media-upload.php?tab=gallery&TB_iframe=true&width=640&height=566' ) ) ?>" class="js-thumbnail-add image_container">
					<img src="<?php echo esc_url( $thumbnail ); ?>" alt="">
				</a>				
				<input type='hidden' class='js-thumbnail-id' name ="<?php echo $option['attribute']['name']; ?>" value='<?php echo $option['value']; ?>' />		
				<div class="js-thumbnail-remove <?php echo $hide; ?>"><?php esc_html_e( 'Удалить миниатюру', 'usam'); ?></div>
			</div>				
			<p class = "description" ><?php echo $option['description']; ?></p>
		</div>
		<?php
	}
	
	protected function row_option_textarea( $option ) 
	{			
		$attr = '';		
		foreach ( $option['attribute'] as $attribute_name => $attribute )		
			$attr .= " $attribute_name = '$attribute'";
			
		$option['value'] = str_replace('\n', chr(10), $option['value'] );
		?>		
		<div class ="edit_form__item_name"><label for ="<?php echo $option['attribute']['id']; ?>"><?php echo $option['title']; ?>:</label></div>
		<div class ="edit_form__item_option">
			<textarea <?php echo $attr; ?>><?php echo esc_textarea( $option['value'] ); ?></textarea>
			<p class = "description" ><?php echo $option['description']; ?></p>
		</div>
		<?php
	}
	
	protected function row_option_text( $option ) 
	{				
		?>
		<div class ="edit_form__item_name"><?php echo $option['title']?$option['title'].':':''; ?></div>
		<div class ="edit_form__item_option">
			<?php echo $option['html']; ?>
			<p class = "description" ><?php echo $option['description']; ?></p>
		</div>
		<?php
	}
	
	protected function row_option_hidden( $option ) 
	{ 
		$attr = '';
		foreach ( $option['attribute'] as $attribute_name => $attribute )		
			$attr .= " $attribute_name = '$attribute'";					
		?>				
		<input <?php echo $attr; ?> type='hidden'/><?php
	}
			
	protected function row_option_input( $option ) 
	{
		?>	
		<div class ="edit_form__item_name"><label for ="<?php echo $option['attribute']['id']; ?>"><?php echo $option['title']; ?>:</label></div>
		<div class ="edit_form__item_option">
			<?php 
			$attr = '';
			foreach ( $option['attribute'] as $attribute_name => $attribute )		
				$attr .= " $attribute_name = '$attribute'";					
			?>				
			<input <?php echo $attr; ?> type='text'/>
			<p class = "description" ><?php echo $option['description']; ?></p>
		</div>
		<?php
	}
	
	protected function row_option_checkbox( $option ) 
	{				
		?>	
		<div class ="edit_form__item_name"><label><?php echo $option['title']; ?>:</label></div>
		<div class ="edit_form__item_option">
			<input type='hidden' value='0' name='<?php echo $option['attribute']['name']; ?>' />
			<input <?php checked($option['value'], 1); ?> type="checkbox" value="1"  name='<?php echo $option['attribute']['name']; ?>' id='<?php echo $option['attribute']['id']; ?>' />
			<p class = "description" ><?php echo $option['description']; ?></p>
		</div>
		<?php
	}
	
	protected function row_option_radio( $option ) 
	{		
		?>
		<div class ="edit_form__item_name"><label><?php echo $option['title']; ?>:</label></div>
		<div class ="edit_form__item_option">
			<?php										
			foreach( $option['radio'] as $value => $title )
			{				
				?>
				<input type='radio' value='<?php echo $value; ?>' name='<?php echo $option['attribute']['name']; ?>' id='<?php echo $option['attribute']['id'].'-'.$value; ?>' <?php checked($option['attribute']['value'], $value); ?> />  <label for='<?php echo $option['attribute']['id'].'-'.$value; ?>'><?php echo $title; ?></label> &nbsp;						
				<?php 
			} 
			?>
			<p class = "description" ><?php echo $option['description']; ?></p>
		</div>
		<?php
	}
	
	protected function row_option_select( $option ) 
	{
		$attr = '';		
		foreach ( $option['attribute'] as $attribute_name => $attribute )		
			$attr .= " $attribute_name = '$attribute'";
		?>	
		<div class ="edit_form__item_name"><label for ="<?php echo $option['attribute']['id']; ?>"><?php echo $option['title']; ?>:</label></div>
		<div class ="edit_form__item_option">
			<select <?php echo $attr; ?>>
				<?php				
				foreach ( $option['options'] as $key => $value ) 
				{
					?>
					<option value='<?php echo $key; ?>' <?php selected( $key, $option['value'] ); ?>><?php echo $value; ?></option>
					<?php
				}															
				?>
			</select>
			<p class = "description" ><?php echo $option['description']; ?></p>
		</div>
		<?php
	}
	
	protected function row_option_checklist( $option ) 
	{
		?>		
		<div class ="edit_form__item_name"><label><?php echo $option['title']; ?>:</label></div>
		<div class ="edit_form__item_option">	
			<div class="categorydiv">
				<div class="tabs-panel">
					<input type="hidden" name="<?php echo $option['attribute']['name']; ?>[]">
					<ul id="groups_checklist" class="categorychecklist form-no-clear">
						<?php echo usam_get_checklist( $option['attribute']['name'], $option['options'], $option['value'] ); ?>		
					</ul>						
				</div>							
			</div>		
		</div>
		<?php
	}
}
?>