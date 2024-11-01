<comments :type="'event'" @count="comments=$event" :edit="!data.status_is_completed && rights.comments" inline-template>
	<div class="ribbon">
		<div class='ribbon_header' v-if="edit">					
			<div class='new_comment'>
				<div class='post_comments'>
					<textarea class="event_comment_input event_input_text" @focus="showAddElement=true" placeholder="<?php _e('Напишите комментарий…','usam'); ?>" tabindex="1" dir="auto" v-model="message"></textarea>
				</div>
				<div class='new_element__buttons' v-show="showAddElement">
					<button type="button" class="button" @click="addComment" ref="add_comment_button"><?php _e( 'Добавить', 'usam'); ?></button>
					<a @click="showAddElement=!showAddElement"><?php _e( 'Отменить', 'usam'); ?></a>
				</div>	
			</div>					
		</div>
		<div class="ribbon_block">
			<div class='comments__item user_comment' v-for="(item, k) in items">			
				<div class='comments__item_content'>
					<div class='comments__item_view' v-if="!item.editor">
						<div class='comments__item_header'>
							<span class='comments__item_author' v-html="item.author.appeal" v-if="Object.keys(item.author).length"></span>
							<span class='comments__item_date'>{{localDate(item.date_insert,'d.m.Y')}}</span>
							<a class="delete_item" v-if="item.mine" @click="deleteComment(k)"></a>
						</div>
						<div class='comments__item_message' @dblclick="openEdit(k)" v-html="item.message_html"></div>	
					</div>
					<div :id="'comment_item_edit_'+item.id" class='comments__item_edit' v-show="item.editor">
						<div class='post_comments'>
							<textarea class="event_comment_input event_input_text" ref="messages" tabindex="1" dir="auto" v-model="item.message"></textarea>
						</div>
						<button type="button" class="button" @click="clickUpdate(k)"><?php _e( 'Добавить', 'usam'); ?></button>	
					</div>	
				</div>									
			</div>
			<div class='js-show-more'></div>
		</div>
	</div>	
</comments>