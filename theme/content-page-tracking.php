<?php
/*
Описание: Шаблон отслеживания отправления
*/ 
?>
<div id="tracking" class="tracking search_info" v-cloak>
	<div class="tracking__search" v-if="tab=='search'">
		<h2 class="title"><?php _e('Поиск отправлений по трек-номеру','usam'); ?></h2>	
		<div class="tracking__keyword search_info__keyword">		
			<input class="search_info__input option-input" ref="search" v-on:keyup.enter="search" type="text" placeholder="<?php _e( 'Трек-номер отправления', 'kodi' ); ?>" v-model="tracking" autocomplete="off">
			<span class="search_info__button" @click="search"><?php usam_svg_icon("search"); ?></span>					
		</div>
		<div class="search_info__no_data" v-if="no_data"><?php _e( 'Нет информации', 'usam'); ?></div>
	</div>
	<div class="tracking__operations" v-else>			
		<div class="search_info__title">
			<h2><a class="search_info__back" @click="back"><?php usam_svg_icon("angle-down-solid")?></a><?php _e('История отправления','usam'); ?> {{tracking}}</h2>
			<div class ="search_info__description" v-if="history.issued"><?php _e( 'Вручено', 'kodi' ); ?> {{history.date_delivery}}</div>		
		</div>
		<div class="tracking_history">	
			<div class="tracking_history__item" v-for="(operation, k) in history.operations">
				<div class ="tracking_history__item_name" v-html="operation.name"></div>
				<div class ="tracking_history__item_description">{{operation.date}}, <span v-html="operation.description"></span></div>
			</div>
		</div>			
	</div>
</div>