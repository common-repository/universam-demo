<modal-panel ref="modalobjects" :backdrop="false">
	<template v-slot:title><?php _e('Выбор объектов', 'usam'); ?></template>
	<template v-slot:body="modalProps">
		<site-slider>
			<template v-slot:body="sliderProps">	
				<div class="section_tabs">
					<span class="section_tab" @click="typeObjects='companies'" :class="{'active':typeObjects=='companies'}"><?php _e('Компании', 'usam'); ?></span>
					<span class="section_tab" @click="typeObjects='contacts'" :class="{'active':typeObjects=='contacts'}"><?php _e('Контакты', 'usam'); ?></span>
					<span class="section_tab" @click="typeObjects='orders'" :class="{'active':typeObjects=='orders'}"><?php _e('Заказы', 'usam'); ?></span>
					<span class="section_tab" @click="typeObjects='leads'" :class="{'active':typeObjects=='leads'}"><?php _e('Лиды', 'usam'); ?></span>
					<span class="section_tab" @click="typeObjects='invoices'" :class="{'active':typeObjects=='invoices'}"><?php _e('Счета', 'usam'); ?></span>
					<span class="section_tab" @click="typeObjects='suggestions'" :class="{'active':typeObjects=='suggestions'}"><?php _e('Коммерческие', 'usam'); ?></span>
					<span class="section_tab" @click="typeObjects='contracts'" :class="{'active':typeObjects=='contracts'}"><?php _e('Договоры', 'usam'); ?></span>			
				</div>
			</template>
		</site-slider>	
		<list-table :load="modalProps.show" :query="typeObjects" :args="typeObjects=='companies' || typeObjects=='contacts'?{add_fields:['logo'], orderby:'name', order:'ASC'}:{order:'DESC'}">
			<template v-slot:thead>
				<th class="column_title"><?php _e( 'Название', 'usam'); ?></th>	
				<th></th>	
			</template>
			<template v-slot:tbody="slotProps">
				<tr v-for="(item, k) in slotProps.items" @click="selectObjects(item); sidebar('objects')">
					<td class="column_title">
						<a class='user_block' v-if="typeObjects=='companies'">	
							<div class='image_container usam_foto'><img :src='item.logo'></div>
							<div class='user_name' v-html="item.name"></div>							
						</a>
						<a class='user_block' v-else-if="typeObjects=='contacts'">	
							<div class='image_container usam_foto'><img :src='item.foto'></div>
							<div class='user_name' v-html="item.appeal"></div>						
						</a>
						<a class='document_block' v-else>						
							<div class='document_name'>
								<div class='document_number'>№ {{item.number}}</div>
								<div class='document_totalprice' v-html="item.totalprice_currency"></div>	
							</div>
							<div class='document_date'><?php _e( 'от', 'usam'); ?> {{localDate(item.date_insert,'d.m.Y')}}</div>							
						</a>							
					</td>
					<td class="column_action"><button class="button"><?php _e( 'Выбрать', 'usam'); ?></button></td>
				</tr>
			</template>
		</list-table>
	</template>
</modal-panel>