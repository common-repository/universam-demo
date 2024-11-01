<div class="home_page">
	<div class="home_page__item" v-for="(block, k) in blocks" @dragover="allowDrop($event, k)" @dragstart="drag($event, k)" @dragend="dragEnd($event, k)">
		<div class="home_page__item_name">
			<span class="home_page__item_name_id" draggable="true">{{block.id}}</span>
			<span class="home_page__item_name_text" draggable="true" title="<?php _e('Перетащите, чтобы поменять местами блоки', 'usam'); ?>">{{block.html_name}}</span>
			<span class='home_page__item_name_status'>		
				<span class="item_status item_status_valid" v-if="block.active" @click="block.active=0"><?php _e( 'Включен', 'usam'); ?></span>
				<span class="item_status status_blocked" @click="block.active=1" v-else><?php _e( 'Выключен', 'usam'); ?></span>
			</span>
			<a class="home_page__item_delete" @click="blocks.splice(k, 1)"><?php _e('Удалить', 'usam'); ?></a>
		</div>			
		<div class='home_page__settings'>
			<div class="section_tabs">
				<div class="section_tab" @click="block.sectionTab='options'" :class="{'active': block.sectionTab=='options'}"><?php _e( 'Настройки', 'usam'); ?></div>
				<div class="section_tab" @click="block.sectionTab='data'" :class="{'active': block.sectionTab=='data'}"  v-if="block.data.length"><?php _e( 'Быбор данных', 'usam'); ?></div>
				<div class="section_tab" @click="block.sectionTab='content_style'" :class="{'active': block.sectionTab=='content_style'}" v-if="block.content_style.length"><?php _e( 'Стили элементов', 'usam'); ?></div>
				<div class="section_tab" @click="block.sectionTab='style'" :class="{'active': block.sectionTab=='style'}"><?php _e( 'Стили блока', 'usam'); ?></div>
				<div class="section_tab" @click="block.sectionTab='display'" :class="{'active': block.sectionTab=='display'}"><?php _e( 'Отображение', 'usam'); ?></div>
				<div class="section_tab" @click="block.sectionTab='html'" :class="{'active': block.sectionTab=='html'}">HTML</div>
				<div class="section_tab" @click="block.sectionTab='info'" :class="{'active': block.sectionTab=='info'}"><?php _e( 'Информация', 'usam'); ?></div>
			</div>	
			<properties :lists="block.options" v-if="block.sectionTab=='options'">					
				<template v-slot:head>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Название блока на сайте', 'usam'); ?></div>
						<div class="edit_form__item_option">
							<input v-model="block.name" type='text' placeholder="<?php _e('Название блока на сайте', 'usam'); ?>"/>
						</div>
					</div>
				</template>
				<template v-slot:body="{property, getProperty}">
					<?php include( usam_get_filepath_admin('templates/template-parts/type-option.php') ); ?>
				</template>
				<template v-slot:footer>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php _e( 'Отображать в блок здесь', 'usam'); ?></div>
						<div class="edit_form__item_option">
							<select-list @change="block.hooks=$event.id" :lists="hooks" :selected="block.hooks" :multiple='1'></select-list>
						</div>
					</div>	
				</template>
			</properties>	
			<properties :lists="block.data" v-if="block.sectionTab=='data'">					
				<template v-slot:body="{property, getProperty}">
					<?php include( usam_get_filepath_admin('templates/template-parts/type-option.php') ); ?>
				</template>				
			</properties>				
			<div class='edit_form' v-if="block.sectionTab=='info'">					
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php _e( 'Код блока', 'usam'); ?></div>
					<div class="edit_form__item_option">{{block.code}}</div>
				</div>			
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php _e( 'Путь к шаблону', 'usam'); ?></div>
					<div class="edit_form__item_option">{{block.template}}</div>
				</div>	
			</div>			
			<properties :lists="block.html" v-if="block.sectionTab=='html'">					
				<template v-slot:body="{property, getProperty}">
					<?php include( usam_get_filepath_admin('templates/template-parts/type-option.php') ); ?>
				</template>				
			</properties>
			<properties :lists="block.content_style" v-if="block.sectionTab=='content_style'">					
				<template v-slot:body="{property, getProperty}">
					<?php include( usam_get_filepath_admin('templates/template-parts/type-option.php') ); ?>
				</template>				
			</properties>	
			<div class='edit_form' v-if="block.sectionTab=='display'">					
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php _e( 'Активность', 'usam'); ?></div>
					<div class="edit_form__item_option">
						<selector v-model="block.active"></selector>
					</div>
				</div>	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php _e( 'Показывать на устройствах', 'usam'); ?></div>
					<div class="edit_form__item_option">
						<select v-model="block.device">						
							<option value="0"><?php _e( 'На всех устройствах', 'usam'); ?></option>
							<option value="mobile"><?php _e( 'Только на мобильных', 'usam'); ?></option>
							<option value="desktop"><?php _e( 'Только на компьютерах', 'usam'); ?></option>
						</select>
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php _e( 'Загрузка', 'usam'); ?></div>
					<div class="edit_form__item_option">
						<selector v-model="block.loading" :items="[{id:'eager', name:'<?php _e( 'При загрузке страницы', 'usam'); ?>'}, {id:'lazy', name:'<?php _e( 'При видимости', 'usam'); ?>'}]"></selector>
					</div>
				</div>	
			</div>		
		</div>				
	</div>
</div>