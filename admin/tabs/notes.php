<?php
class USAM_Tab_notes extends USAM_Tab
{
	protected $views = ['simple'];
	public function get_title_tab()
	{		
		return __('Заметки', 'usam');	
	}		
	
	protected function get_tab_forms()
	{
		return [['button' => 'add', 'title' => __('Добавить', 'usam') ]];
	}	
	
	public  function display()
	{					
		?>
		<div id="notes" class="notes" v-cloak> 
			<div id ="note_writepad" class="notes__writepad"><textarea ref="writepad" class="form-control autogrow" v-model="item.note" v-on:keyup.enter="save" @blur="save"></textarea></div>
			<div class="notes__lists">
				<div class="notes__lists_item" v-for="(list, k) in lists" :key="list.id" @click="open(k)" :class="[list.id==item.id?'current':'']">
					<strong v-html="list.name"></strong>
					<span v-html="list.des"></span><a class='button_delete' @click="del(k)"></a>
				</div>
			</div>
		</div>
		<?php	
	}	
}
