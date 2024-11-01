<?php
class USAM_Mail_Styling
{	
	private $template;
	function __construct( $value, $type = 'mailbox_id' )
	{		
		if ( $type == 'template_name' )
		{
			$this->template = usam_get_email_template( $value );	
		}
		elseif ( $type == 'mailbox_id' )
		{
			$mailbox = usam_get_mailbox( $value );
			$this->template = $mailbox['template'];
		}
		else
		{
			$this->template = $value;
		}
		if ( $this->template == '' )
			$this->template = '%mailcontent%';
	}
		
	function class_in_style( $classes, $block )
	{
		foreach($classes as $class => $styles) 
		{			
			$inlineStyles = '';
			foreach($styles as $key => $st)
				 $inlineStyles .= $key.':'.$st.';';
			$styles = preg_replace('/(\n*)/', '', $styles);
			if(in_array($class, array('h1-link', 'h2-link', 'h3-link'))) 
			{
				$classes['#<([^ /]+) ((?:(?!>|style).)*)(?:style="([^"]*)")?((?:(?!>|style).)*)class="[^"]*'.$class.'[^"]*"((?:(?!>|style).)*)(?:style="([^"]*)")?((?:(?!>|style).)*)>#Ui'] = '<$1 $2$4$5$7 style="'.$inlineStyles.'">';
			} 
			else 
			{
				$classes['#<([^ /]+) ((?:(?!>|style).)*)(?:style="([^"]*)")?((?:(?!>|style).)*)class="[^"]*'.$class.'[^"]*"((?:(?!>|style).)*)(?:style="([^"]*)")?((?:(?!>|style).)*)>#Ui'] = '<$1 $2$4$5$7 style="$3$6'.$inlineStyles.'">';
			}	
			unset($classes[$class]);
		}	
		$styledBlock = preg_replace(array_keys($classes), $classes, $block);	
		return $styledBlock;
	}
	
	function get_message( $message )
	{		
		$args = array(			
			'mailcontent' => $message,
		);
		$shortcode = new USAM_Shortcode();
		return $shortcode->process_args( $args, $this->template );		
	}
}
?>