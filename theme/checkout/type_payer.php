<?php
//Выбор типа платильщиков
?>
<div class="view_form types_payers_block" v-if="types_payers.length>1">			
	<div class ="view_form__title"><?php _e('Тип плательщика', 'usam'); usam_change_block( admin_url( "admin.php?page=orders&tab=orders&view=table&table=types_payers" ), __("Добавить или изменить тип плательщика", "usam") ); ?></div>
	<div class ="view_form__row" v-for="(value, k) in types_payers">					
		<label><input class="option-input radio" type='radio' v-model="selected.type_payer" :value="value.id"/>{{value.name}}</label>
	</div>	
</div>
<?php 