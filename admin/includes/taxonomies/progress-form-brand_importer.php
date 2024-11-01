<?php	
require_once( USAM_FILE_PATH .'/admin/includes/importer.class.php' );	
class USAM_Brand_Rule_Importer extends USAM_Importer
{		
	protected $rule_type = 'brands';	
	protected $template = false;
	public function get_steps()
	{
		return ['file' => __('Выбор файла', 'usam'), 'columns' => __('Назначение столбцов', 'usam'), 'finish' => __('Импорт', 'usam')];
	}
	
	protected function get_columns()
	{
		return ['id' => __('Номер бренда', 'usam'), 'name' => __('Название бренда', 'usam')];
	}
	
	public function process_finish()
	{
		?><p><?php _e('Процесс импорта идет. Ожидайте...' , 'usam'); ?></p><?php 
	} 
}
?>