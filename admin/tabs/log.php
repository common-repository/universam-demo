<?php
/*
Просмотр лог-файлов
*/
class USAM_Tab_Log extends USAM_Page_Tab
{	
	protected  $views = ['simple'];
	private $_files = array();
    private $_currentFile = false;	
	private $realfile = '';   
	
	public function __construct()
	{
		$files = $this->getFiles();  
		if (isset($_REQUEST['file']))
		{
			$selected_file = stripslashes($_REQUEST['file']);
			foreach ($files as $file)
			{
				if ( $selected_file == $file )
				{
					$this->_currentFile = $file;
					break;
				}
			}
		}
        if ( !$this->_currentFile && isset($files[0]) )
             $this->_currentFile = $files[0];	      
        $this->realfile = $this->transformFilePath( $this->_currentFile );	
		
		if ( $this->realfile != '' && !is_file($this->realfile))	
			$this->set_user_screen_error( sprintf( __('Не удалось загрузить файл %s.', 'usam'), $this->realfile ) );	
	}
	
	public function get_title_tab()
	{	
		return __('Логи магазина', 'usam');	
	}
			
	public static function transformFilePath( $file )
    {
        $path = realpath(USAM_UPLOAD_DIR .'Log' . DIRECTORY_SEPARATOR . $file);
		if ( !is_file($path) )	
			$path = '';
        return $path;
    }		
		
    public function getFiles()
    {
        if (empty($this->_files)) {
            $this->_updateFiles();
        }
        return $this->_files;
    }

    public function hasFiles()
    {
        $this->getFiles();
        if (empty($this->_files)) {
            return false;
        }
        return true;
    }

    private function _updateFiles()
    {
        $this->_files = array();

        $wp_c = realpath(USAM_UPLOAD_DIR .'Log');

        $str     = $wp_c . DIRECTORY_SEPARATOR . "*.txt";
        $f       = glob($str);
        $str_rep = $wp_c . DIRECTORY_SEPARATOR;

        foreach ($f as $file) {
            $this->_files[] = str_replace($str_rep, "", $file);
        }
    }	

    public function display()
    {				
        require 'helper.inc';
		if (!$this->hasFiles()) 
		{
            ?><p><?php echo __('Нет фалов в папке','usam').': '.USAM_UPLOAD_DIR; ?></p><?php            
        }		
		else
		{
			$files = $this->getFiles();       

			$writeable = is_writeable($this->realfile);		     
			?>       
			<div class = "columns-2">
				<div class = "page_main_content">
					<div class="header_menu">            
						<strong><?php printf('%1$s %2$s - ', __('Текущий файл','usam'), $this->_currentFile); 						
						if ( substr($this->_currentFile, strlen($this->_currentFile) - strlen('merchant_gateway.txt')) == 'merchant_gateway.txt' )
							_e('логи модулей оплаты', 'usam');
						elseif ( substr($this->_currentFile, strlen($this->_currentFile) - strlen('merchant_gateway.txt')) == 'log.txt' ) 
							_e('общие логи', 'usam');
						else
							_e('другие логи', 'usam');	
						?>  
						</strong>
						<div class="tablenav top">		
							<?php 	
							if ($writeable) 
							{ 
								?>
								<div class="alignleft">                      
									<input type="hidden" name="file" value="<?php echo $this->_currentFile; ?>"/>
									<select v-model="action">
										<option selected="selected" value=""><?php _e('Действия для файла'); ?></option>
										<option value="empty"><?php _e('Очистить','usam'); ?></option>					
										<option value="delete"><?php _e('Удалить','usam'); ?></option>	
									</select>          
									<button @click="bulkaction" class="button"><?php _e( 'Выполнить', 'usam'); ?></button>						
								</div>
							<?php } ?>	
							<div class="alignright">   
								<input type="checkbox" v-model="autorefresh"/>
								<label for="autorefresh"><?php _e('Автообновление','usam'); ?></label>	
							</div>	
						</div>
					</div>
					<div>
						<?php if ( is_file($this->realfile) ) : ?>						
							<textarea id="newcontent" name="newcontent" rows="25" cols="70" readonly="readonly"><?php echo file_get_contents($this->transformFilePath($this->_currentFile), false); ?></textarea>
						<?php endif; ?>
						<div>
							<h3><?php _e('Информация для текущего файла','usam'); ?></h3>
							<dl>
								<dt><?php _e('Путь к файлу:','usam'); ?></dt>
								<dd><?php echo $this->realfile; ?></dd>
								<dt><?php _e('Последнее обновление: ','usam'); ?></dt>
								<dd><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($this->realfile)); ?></dd>
								<dt><?php _e('Размер файла: ','usam'); ?></dt>
								<dd><?php echo filesize($this->realfile)." ".__('байт ','usam'); ?></dd>
							</dl>
						</div>
					</div>
				</div>
				<div class = "page_sidebar">
					<div id="file_list" class = "file_list">						
						<h3><input type="checkbox" @click="checked_all" value = ""/><?php _e('Файлы логов','usam'); ?></h3>
						<ul>
							<?php 	
							foreach ($files as $file):
								if ($this->_currentFile === $file) 
								{
									?><li class="active"><?php
								} else {                        
									?><li><?php
								}
								?>
								<input type="checkbox" v-model="ids" value = "<?php echo $file; ?>"/>
								<a href="<?php echo add_query_arg( array('file' => $file ), admin_url('tools.php?page=shop&tab=log')); ?>" style = "display:inline-block;"><?php echo $file; ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
						<input type="submit" @click="del" class="button button-primary" value="<?php _e( 'Удалить', 'usam'); ?>">			
					</div>
				</div>				
			</div>
			<?php   
		}
    }
}