<div id='view_tape' :class="{'open':openWindow}" v-cloak>
	<div class='view_tape__panel'>
		<span class='dashicons dashicons-no-alt view_tape__close' @click="openWindow=false"></span>
		<div class='panel_icons'>
			<span class='panel_icons__icon dashicons dashicons-controls-volumeon' @click="window='notifications'" :class="{'active':window=='notifications'}"></span>
			<span class='panel_icons__icon dashicons dashicons-editor-justify' @click="window='tape'" :class="{'active':window=='tape'}"></span>
		</div>
		<div class='view_tape__content' :class="{'load':request}">
			<div class='content-events tab' :class="{'active':window=='notifications'}" v-if="window=='notifications'">
				<div class='title'><?php esc_html_e( 'Уведомления', 'usam'); ?><span @click='lookedAllNotifications' v-if="statusStarted" class='button'><?php esc_html_e( 'Посмотрел все', 'usam'); ?></span></div>
				<div class='feed_posts'>
					<div class='feed_post feed_post_event' v-for="(item, k) in items">
						<div class='feed_post_wrap'>
							<div class='author_image'><img :src='item.author.foto'></div>
							<div class='post'>
								<div class='post_title' v-html="item.title"></div>
								<div class='post_name'>
									<span class='author_name author_post'>
										<span class="event_status dashicons dashicons-hidden" v-if="item.status=='started'"></span>
										<span class="event_status dashicons dashicons-visibility" v-else></span>
										<span v-html="item.author.appeal" v-if="Object.keys(item.author).length"></span>
									</span>
									<div class='feed_post_objects'>
										<span class='feed_post_object' v-for="(object, i) in item.objects">
											<span class='feed_post_object_icon'></span>
											<span class='feed_post_object_name_type' v-html="object.name"></span>
											<a class='feed_post_object_name_link' :href='object.url' v-html="object.title"></a>
										</span>
									</div>
								</div>
								<div class='post_footer'>
									<div class='date'>{{localDate(item.date_insert,'d.m.Y')}}</div>
								</div>
								<div class='feed_post_buttons'></div>
							</div>
						</div>						
					</div>		
					<div class='js-load-more'></div>					
				</div>
			</div>
			<div class='content-tape tab' :class="{'active':window=='tape'}" v-if="window=='tape'">
				<div class='title'><?php esc_html_e( 'Лента событий', 'usam'); ?></div>
				<div class='feed_posts'>
					<div class='feed_post' v-for="(item, k) in items">
						<div class='feed_post_wrap'>
							<div class='author_image'><img :src='item.author.foto'></div>
							<div class='post'>
								<a :href='item.url' class='post_title' v-html="item.title"></a>
								<div class='post_name'>
									<span class='author_name author_post' v-html="item.author.appeal" v-if="Object.keys(item.author).length"></span>
									<span v-if="Object.keys(item.object).length">
										<span class='feed_post_object_icon'></span>
										<span class='feed_post_object_name_type' v-html="item.object.name"></span>
										<a class='feed_post_object_name_link' :href='item.object.url' v-html="item.object.title"></a>
									</span>
								</div>
								<div class='post_footer'>
									<div class='date'>{{localDate(item.date_insert,'d.m.Y')}}</div>
								</div>
								<div class='feed_post_buttons'>
									<span @click="item.comment=!item.comment"><?php esc_html_e( 'Комментировать', 'usam'); ?></span>
								</div>
								<div class='comment_message' v-if="item.comment">
									<textarea class='event_comment_input event_input_text' v-model="item.new_comment" placeholder="<?php esc_html_e( 'Напишите комментарий…', 'usam'); ?>"></textarea>
									<div class='comment_post_buttons'>
										<button class='button button-primary' @click="addComment(k)"><?php esc_html_e( 'Отправить', 'usam'); ?></button>
										<button class='button' @click="item.comment=!item.comment; item.new_comment=''"><?php esc_html_e( 'Отменить', 'usam'); ?></button>
									</div>
								</div>
								<div id='post_comments' class='post_comments'>
									<div class='comments'>
										<div class='feed_post_comment' v-for="(comment, i) in item.comments.items" v-show="comment.show">
											<div class='author_image'>
												<img :src="comment.author_image">
											</div>
											<div class='comment'>
												<div class='user_comment__user'>
													<span class='user_comment__user_name' v-html="comment.author.appeal"></span>
													<span class='user_comment__date'>{{localDate(comment.date_insert,'d.m.Y')}}</span>
												</div>
												<div class='comment_text' v-html="comment.message_html"></div>
											</div>
										</div>	
									</div>
									<span @click="loadСomments(k)" v-if="item.comments.items.length > 1 && item.comments.items.length<item.comments.count" class='load_more_comments'><?php esc_html_e('Показать еще', 'usam'); ?></span>
								</div>
							</div>
						</div>
					</div>	
					<div class='js-load-more'></div>						
				</div>
			</div>
		</div>
	</div>
</div>