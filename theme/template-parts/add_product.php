<div class="add_product" v-show="productLoaded && tab=='product'">
	<div class="add_product__categories_columns" v-show="!product.category">
		<div class="add_product__categories_column">
			<div class="add_product__categories_block_name"><?php _e( 'Категория', 'usam') ?></div>				
			<div class="add_product__categories_name" v-for="(item, i) in parentsCategories" @click="openCategory(item)" v-html="item.name"></div>
		</div>
		<div class="add_product__categories_column" v-show="selectedCategories.includes(category.term_id)" v-for="(category, i) in categories" v-if="category.children">
			<div class="add_product__categories_block_name" v-html="category.name"></div>
			<div class="add_product__categories_name" v-for="(item, i) in categories" v-if="item.parent==category.term_id" @click="openCategory(item)" v-html="item.name"></div>
		</div>
	</div>				
	<div class="add_product__options" v-show="product.category">			
		<div class="add_product__options_title"><?php _e('Параметры', 'usam'); ?></div>
		<div class="edit_form">
			<div class="edit_form__row">
				<div class ="edit_form__item">
					<div class ="edit_form__name"><?php _e( 'Категория', 'usam'); ?>*</div>
					<div class ="edit_form__option add_product__selected_category">
						<div class="add_product__selected_category_name" v-for="(term_id, i) in selectedCategories">			
							<div class="add_product__selected_category_name_term" v-for="(item, i) in categories" v-if="item.term_id==term_id" @click="product.category=0" v-html="item.name"></div>
							<span v-if="i!=selectedCategories.length-1" class="add_product__selected_category_separator">/<span>
						</div>
					</div>
				</div>
			</div>
			<div class="edit_form__row">
				<div class ="edit_form__item">
					<div class ="edit_form__name"><?php _e( 'Наименование', 'usam'); ?>*</div>
					<div class ="edit_form__option">
						<input type="text" v-model="product.post_title" class="option-input">
					</div>
				</div>
			</div>				
			<div class="edit_form__row">
				<div class ="edit_form__item">
					<div class ="edit_form__name"><?php _e( 'Цена', 'usam') ?></div>
					<div class ="edit_form__option">
						<input type="text" v-model="product.price" class="option-input">
					</div>
				</div>
			</div>
		</div>
		<div class="add_product__options_title"><?php _e('Подробности', 'usam'); ?></div>
		<div class="edit_form">
			<div class="edit_form__row">
				<div class ="edit_form__item">
					<div class ="edit_form__name"><?php _e( 'Текст объявления', 'usam') ?></div>
					<div class ="edit_form__option">
						<textarea v-model="product.post_content" class="option-input"></textarea>
					</div>
				</div>
			</div>
			<div class ="edit_form__row edit_form__item" v-for="(property, k) in product.attributes">					
				<div class ="edit_form__title" v-if="property.parent==0">{{property.name}}</div>
				<div class ="edit_form__name" v-else>{{property.name}} <span v-if="property.mandatory">*</span></div>
				<div class ="edit_form__option" v-if="property.parent">		
					<?php usam_include_template_file('product-property', 'template-parts'); ?>
				</div>
			</div>				
			<div class="edit_form__row">
				<div class ="edit_form__item">
					<h3><?php _e( 'Наличие', 'usam') ?></h3>
				</div>
			</div>
			<div class="edit_form__row">
				<div class ="edit_form__item">
					<div class ="edit_form__name"><?php _e( 'Товар доступен', 'usam') ?></div>
					<div class ="edit_form__option">
						<selector v-model="product.under_order" :items="[{id:0, name:'<?php _e( 'В наличии', 'usam') ?>'},{id:1, name:'<?php _e( 'Под заказ', 'usam') ?>'}]"></selector>
					</div>
				</div>
			</div>
			<div class="edit_form__row">
				<div class ="edit_form__item">
					<div class ="edit_form__name"><?php _e( 'Несколько позиций', 'usam') ?></div>
					<div class ="edit_form__option">
						<div class="switch-checkbox" @click="product.not_limited=!product.not_limited" :class="{'on':product.not_limited}"></div>
					</div>
				</div>
			</div>
			<div class="edit_form__row" v-if="product.not_limited">
				<div class ="edit_form__item">
					<div class ="edit_form__name"><?php _e( 'Количество', 'usam') ?></div>
					<div class ="edit_form__option">
						<input type="text" v-model="product.stock" class="option-input">
					</div>
				</div>
			</div>				
			<div class="edit_form__row">
				<div class="add_product__options_title"><?php _e( 'Фото', 'usam') ?></div>
				<div class ="add_product__image_box">
					<div class ="add_product__image_main" v-for="(image, i) in product.images" v-if="i==imageViewing">
						<div class ="add_product__image_main_wap"><img :src='image.medium_image'></div>
						<div class ="add_product__image_instruments">
							<span class ="add_product__image_instrument" @click="imageMain(i)">
								<?php usam_svg_icon("star-selected"); ?>
								<span class="add_product__image_instrument_text"><?php _e( 'Главная', 'usam') ?></span>
							</span>
							<span class ="add_product__image_instrument" @click="imageTurn(i)">
								<?php usam_svg_icon("turn"); ?>
								<span class="add_product__image_instrument_text"><?php _e( 'Повернуть', 'usam') ?></span>
							</span>
							<span class ="add_product__image_instrument" @click="imageDelete(i)">
								<?php usam_svg_icon("close"); ?>
								<span class="add_product__image_instrument_text"><?php _e( 'Удалить', 'usam') ?></span>
							</span>
						</div>
					</div>
					<div class ="add_product__images">
						<div class ="add_product__image" v-for="(image, i) in product.images" @click="imageViewing=i">
							<?php usam_svg_icon("star-selected", ['v-if' => "image.thumbnail"]); ?>
							<img v-show="image.small_image !== undefined" :src='image.small_image'>				
							<progress-circle v-show="image.load" :percent="image.percent"></progress-circle>
						</div>
						<div class ="add_product__image add_product__image_add" @click="imageDrop"><?php _e( 'Добавить фото', 'usam') ?></div>
						<input type='file' @change="imageUpload" multiple style="display:none">
					</div>						
				</div>
			</div>			
			<div class='usam_message message_error' v-for="error in mandatoryProperty">
				<div class='validation-error' v-if="error=='post_title'"><?php _e('Введите название товара', 'usam'); ?></div>
				<div class='validation-error' v-else-if="error=='post_content'"><?php _e('Текст объявления', 'usam'); ?></div>
				<div class='validation-error' v-else-if="error=='category'"><?php _e('Укажите категорию', 'usam'); ?></div>
				<div class='validation-error' v-else-if="error=='price'"><?php _e('Укажите цену', 'usam'); ?></div>
			</div>
			<div class ="edit_form">
				<div class="edit_form__buttons">	
					<button class="button main-button" @click="updateProduct":disabled="codeError" v-if="id"><?php _e( 'Сохранить товар', 'usam'); ?></button>
					<button class="button main-button" @click="addProduct":disabled="codeError" v-else><?php _e( 'Добавить товар', 'usam'); ?></button>
				</div>
			</div>				
		</div>
	</div>
</div>
<?php 