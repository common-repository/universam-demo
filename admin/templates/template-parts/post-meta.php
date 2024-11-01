<div class="seo" v-if="Object.keys(meta).length !== 0">
	<div class = "edit_form">
		<div class="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e('Предпросмотр', 'usam'); ?>:</div>
			<div class ="option google_search_preview">
				<div class="google_search_preview__domain"><?php echo home_url(); ?></div>
				<div class="google_search_preview__title" v-html="meta.title"></div>
				<div class="google_search_preview__description" v-html="meta.description"></div>
			</div>
		</div>					
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e('Шорткоды', 'usam'); ?>:</div>
			<div class ="option add_metatags">
				<div class ="add_metatags__tag" v-for="(title, k) in meta.shortcode" @click="insert(title, k)">{{title}}</div>
			</div>
		</div>
		<div class="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e('Заголовок', 'usam'); ?>:</div>
			<div ref="title" class ="shortcode_editor" contenteditable="true" v-html="meta.title" @blur="blur('title')"></div>
			<input type="hidden" :name="name+'[title]'" v-model='meta.title'>	
		</div>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e('Мета-описание', 'usam'); ?>:</div>
			<div ref="description" class ="shortcode_editor shortcode_editor_description" @blur="blur('description')" contenteditable="true" v-html="meta.description"></div>
			<input type="hidden" :name="name+'[description]'" v-model='meta.description'>	
		</div>			
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e('Исключить из карты сайта', 'usam'); ?>:</div>
			<selector v-model="meta.exclude_sitemap" :items="[{id:'', name:'<?php _e('Нет', 'usam'); ?>'},{id:'1', name:'<?php _e('Да', 'usam'); ?>'}]"></selector>
			<input type="hidden" :name="name+'[exclude_sitemap]'" v-model='meta.exclude_sitemap'>
		</div>	
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e('Показывать в поиске?', 'usam'); ?>:</div>
			<selector v-model="meta.noindex" :items="[{id:'', name:'<?php _e('По умолчанию', 'usam'); ?>'},{id:'1', name:'<?php _e('Нет', 'usam'); ?>'},{id:'2', name:'<?php _e('Да', 'usam'); ?>'}]"></selector>
			<input type="hidden" :name="name+'[noindex]'" v-model='meta.noindex'>
		</div>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e('Должны ли поисковые системы проходить по ссылкам?', 'usam'); ?>:</div>
			<selector v-model="meta.nofollow" :items="[{id:'', name:'<?php _e('По умолчанию', 'usam'); ?>'},{id:'1', name:'<?php _e('Нет', 'usam'); ?>'},{id:'2', name:'<?php _e('Да', 'usam'); ?>'}]"></selector>
			<input type="hidden" :name="name+'[nofollow]'" v-model='meta.nofollow'>
		</div>	
		<div class="edit_form__title"><?php esc_html_e( 'Мета-теги для социальных сетей', 'usam'); ?></div>			
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e('Шорткоды', 'usam'); ?>:</div>
			<div class ="option add_metatags">
				<div class ="add_metatags__tag" v-for="(title, k) in meta.shortcode" @click="insert(title, k)">{{title}}</div>
			</div>
		</div>
		<div class="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e('Заголовок', 'usam'); ?>:</div>
			<div ref="opengraph_title" class ="shortcode_editor" contenteditable="true" v-html="meta.opengraph_title" @blur="blur('opengraph_title')"></div>
			<input type="hidden" :name="name+'[opengraph_title]'" v-model='meta.opengraph_title'>
		</div>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e('Описание', 'usam'); ?>:</div>
			<div ref="opengraph_description" class ="shortcode_editor shortcode_editor_description" @blur="blur('opengraph_description')" contenteditable="true" v-html="meta.opengraph_description"></div>
			<input type="hidden" :name="name+'[opengraph_description]'" v-model='meta.opengraph_description'>
		</div>					
	</div>
</div>