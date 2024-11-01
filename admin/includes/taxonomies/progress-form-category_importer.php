<?php	
require_once( USAM_FILE_PATH .'/admin/includes/importer.class.php' );	
class USAM_Category_Rule_Importer extends USAM_Importer
{		
	protected $rule_type = 'category';	
	protected $template = false;
	public function get_steps()
	{
		return ['file' => __('Выбор файла', 'usam'), 'columns' => __('Назначение столбцов', 'usam'), 'finish' => __('Импорт', 'usam')];
	}
	
	protected function get_columns()
	{
		return ['id' => __('Номер категории', 'usam'), 'name' => __('Название категории', 'usam'), 'parent_id' => __('Номер вложенности', 'usam')];
	}
	
	public function process_finish()
	{
		?><p><?php _e('Процесс импорта идет. Ожидайте...' , 'usam'); ?></p><?php 
	} 
}
?>