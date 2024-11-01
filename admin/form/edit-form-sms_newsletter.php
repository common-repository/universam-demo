<?php
require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_sms_newsletter extends USAM_Edit_Form
{	
	protected $vue = true;
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить СМС рассылку','usam');
		else
			$title = __('Добавить СМС рассылку', 'usam');	
		return $title;
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function get_data_tab(  )
	{		
		$default = ['id' => 0, 'lists' => [], 'subject' => '', 'body' => '', 'class' => 'simple', 'type' => 'sms', 'status' => 0];		
		if ( $this->id != null )	
		{
			$this->data = usam_get_newsletter( $this->id );	
			$this->data['lists'] = usam_get_newsletter_list( $this->id );
			$this->data['body'] = (string)usam_get_newsletter_metadata( $this->id, 'body' );
		}
		$this->data = usam_format_data( $default, $this->data );			
	}
	
	public function display_left( )
	{  
		?>
		<div class='event_form_head'>						
			<div class='event_form_head__title'>
				<input type="text" v-model="data.subject" placeholder="<?php _e('Название', 'usam') ?>" required class="titlebox show_internal_text" maxlength="255" autofocus>
			</div>	
			<div class="form_description">			
				<textarea rows='10' autocomplete='off' v-model="data.body" class="event_form_head__message"></textarea>
			</div>	
			<div class="event_form_head__message_length"><span v-if="data.body">{{data.body.length}}</span></div>
			<div class="edit_form">
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_type'><?php esc_html_e( 'Тип рассылки', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select-list @change="data.class=$event.id" :lists="[{id:'simple', name:'<?php _e('Стандартная', 'usam'); ?>'},{id:'template', name:'<?php _e('Шаблон для рассылки', 'usam'); ?>'}]" :selected="data.class"></select-list>
					</div>
				</div>
			</div>
		</div>	
		<?php 
	} 
	
	public function dispaly_lists( )
	{
		$lists = usam_get_mailing_lists();
		foreach ( $lists as $list )
		{
			?>
			<label for="user-list-<?php echo $list->id; ?>">							
			<input type="checkbox" v-model="data.lists" value="<?php echo $list->id; ?>"><?php echo $list->name; ?>
			</label><br>
			<?php
		}
	}
	
	public function display_right( )
	{
		usam_add_box(['id' => 'usam_lists', 'title' => __('Списки подписчиков', 'usam'), 'function' => [$this, 'dispaly_lists'], 'tag_parameter' => ['v-if="data.class===`simple`"']]);
	}
	
	protected function get_toolbar_buttons( ) 
	{
		if ( $this->change )
		{
			$links = [		
				['vue' => ["@click='saveForm'"], 'primary' => true, 'name' => '<span v-if=" data.id>0">'.__('Сохранить','usam').'</span><span v-else>'.__('Добавить','usam').'</span>'],
				['vue' => ["@click='send'", "v-if='data.status != 5 && data.id>0'"], 'primary' => false, 'name' => '<span v-if="data.class==`simple`">'.__('Отправить','usam').'</span><span v-if="data.class==`trigger` || data.class==`template`">'.__('Готово','usam').'</span>'],
				['vue' => ["@click='changeStatus(0)'", "v-if='data.status == 5'"], 'primary' => false, 'name' => __('В черновик','usam')],
			];
		}
		return $links;
	}
	
	protected function toolbar_buttons( ) 
	{						
		$this->display_toolbar_buttons();
		$this->main_actions_button();
	}
}
?>