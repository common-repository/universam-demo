<div id="variant_management" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Перенести варианты товаров','usam'); ?></div>
	</div>
	<div class='modal-body'>
		<div class='edit_form'>
			<div class='edit_form__item'>
				<div class='edit_form__item_name'><?php _e('Вариант для переноса', 'usam') ?>:</div>
				<div class='edit_form__item_option'>
					<select id='variation1' class ='chzn-select'>
						<option value=''><?php _e('Выберите', 'usam') ?></option>
						<?php 																
						$variations = get_terms(['taxonomy' => 'usam-variation', 'hide_empty' => false]);
						foreach ( $variations as $variation )
						{	
							if ( $variation->parent )
							{
								?><option value='<?php echo $variation->term_id; ?>'><?php echo $variation->name; ?></option><?php 
							}
						}
						?>	
					</select>
				</div>
			</div>
			<div class='edit_form__item'>
				<div class='edit_form__item_name'><?php _e('В какой вариант перенести', 'usam') ?>:</div>
				<div class='edit_form__item_option'>
					<select id='variation2' class ='chzn-select'>
						<option value=''><?php _e('Выберите', 'usam') ?></option>
						<?php 																
						$variations = get_terms(['taxonomy' => 'usam-variation', 'hide_empty' => false]);
						foreach ( $variations as $variation )
						{	
							if ( $variation->parent )
							{
								?><option value='<?php echo $variation->term_id; ?>'><?php echo $variation->name; ?></option><?php 
							}
						}
						?>	
					</select>
				</div>
			</div>
		</div>
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e('Выполнить', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e('Закрыть', 'usam') ?></button>		
		</div>
	</div>
</div>
