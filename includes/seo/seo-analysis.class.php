<?php
class USAM_SEO_Link_Analysis 
{	
	public $result;
	public $url;
	public function __construct( $url ) 
	{
		if ( !empty($url) )
		{
			$this->url = $url;
			$this->result = $this->analysis( $url );			
		}
	}
	
	public function get_results ( ) 
	{
		return $this->result;
	}
	
	private function get_words( $content ) 
	{
		$strings = preg_split('#[\s,]+#', $content);
		$exclude_words = array ( 'и', 'в', 'без', 'до', 'из', 'к', 'на', 'по', 'о', 'от', 'перед', 'при', 'через', 'с', 'у', 'за', 'над', 'об', 'под', 'про', 'для', 'руб' );
		$j = 0;
		$words = array();
		foreach ($strings as $word) 	
		{				
			$word = preg_replace('/[^a-zA-Zа-яА-Я]-/ui', '',$word ); 
			$strtolower = mb_strtolower($word);			
			if ( !empty($strtolower) && !in_array($strtolower, $exclude_words) && mb_strlen($word) > 2 )
			{
				$words[] = $word;
			}
		}
		return $words;
	}
	
	public function analysis ( $url ) 
	{
		require_once( USAM_FILE_PATH . '/includes/parser/parser.function.php' );
		$URL_OBJ = usam_get_url_object( $url );		
		$result = false;
		if( $URL_OBJ )
		{ 
			$content = preg_replace('#(<script.*</script>)#sU', '', $URL_OBJ['content']);
			$content = preg_replace('#(<style.*</style>)#sU', '', $content);	
			
			require_once( USAM_FILE_PATH . '/includes/seo/keywords_query.class.php' );
			$keywords  = usam_get_keywords( array('fields' => 'keyword', 'check' => 1 ) );
			
			$dom = new DOMDocument();				
			@$dom->loadHTML( $content );
			$body = $dom->getElementsByTagName('body')->item(0)->nodeValue;		
			$tags = array( 'h1', 'h2', 'h3', 'h4' );			
			
			$words = $this->get_words( $body );	
			$count_words = count($words);
			foreach ($tags as $tag) 
			{
				foreach ($dom->getElementsByTagName($tag) as $node) 
				{
					$str = $node->nodeValue;	
					
					$tag_words = explode(' ', $str);
					$keyword_result = array();					
					$relevance = 0;
					foreach ($tag_words as $tag_word ) 
					{
						foreach ($keywords as $keyword ) 
						{
							if ( mb_stripos($keyword, $tag_word) !== false )
							{
								$keyword_result[] = $keyword;
							}
						}						
						foreach ($words as $word ) 
						{
							if ( mb_stripos($word, $tag_word) !== false )
							{
								$relevance++;
							}
						}	
					}
					$result['tags'][$tag][] = array( 'name' => $str, 'keyword' => $keyword_result, 'relevance' => round($relevance/$count_words,2) );
				}
			}				
			foreach ($dom->getElementsByTagName('a') as $node) 
			{ 
				if( $node->hasAttribute( 'href' ) ) 
				{
					$url = $node->getAttribute( 'href' );	
					$anchor = trim($node->nodeValue);	
					$keyword_result = array();
					if ( $anchor != '' )
						foreach ($keywords as $keyword ) 
						{
							if ( mb_stripos($keyword, $anchor) !== false )
							{
								$keyword_result[] = $keyword;
							}
						}	
					$result['a'][$url][] = array( 'anchor' => $anchor, 'keywords' => $keyword_result );						
				}
			}				
			$str = $dom->getElementsByTagName('title')->item(0)->nodeValue;	
			$result['tag']['seo']['title'] = $str;	

			$result['meta_tags'] = @get_meta_tags( $url );	
			$result['keywords'] = array();
		/*	foreach ($words as $word) 	
			{
				if ( in_array($word, $results_words) )
				{
					$key = array_search($word, $results_words);
					
					if ( isset($result['keywords'][$key]) )
						$result['keywords'][$key]['occurrence']++;
					else
						$result['keywords'][$key] = array( 'name' => $word, 'occurrence' => 1, 'tag' => '' );
				}
			}*/			
			$buf = $words;
			$results_words = array();
			$max = 0;
			$j = 0;
			foreach ($words as $word) 	
			{						
				if ( in_array($word, $buf) )
				{
					$j++;
					$key = array_search($word, $buf);					
					if ( isset($results_words[$key]) )
					{
						$results_words[$key]['occurrence']++;
						if ( $max < $results_words[$key]['occurrence'] )
							$max = $results_words[$key]['occurrence'];
					}
					else
					{
						$in_tags = array();
						foreach ($result['tags'] as $tag => $tags) 
						{ 
							foreach ( $tags as $value ) 
							{ 
								if( stristr($value['name'], $word) !== false ) 								
								{
									$in_tags[] = $tag;
								}
							}
						}
						foreach ($result['tag']['seo'] as $tag => $title) 
						{
							if( stristr($title, $word) !== false ) 								
							{
								$in_tags[] = $tag;
							}	
						}
						$a = 0;
						if ( !empty($result['a']) ) 
						{						
							foreach ($result['a'] as $url => $anchors) 
							{
								foreach ($anchors as $anchor ) 
								{
									if ( !empty($anchor['anchor']) && mb_stripos($anchor['anchor'], $word) !== false )
										$a++;
								}
							}							
						}
						$keyword_result = array();
						foreach ($keywords as $keyword ) 
						{
							if ( mb_stripos($keyword, $word) !== false )
							{
								$keyword_result[] = $keyword;
							}
						}						
						$results_words[$key] = array( 'name' => $word, 'keyword' => $keyword_result, 'number' => $j, 'name' => $word, 'occurrence' => 1, 'tag' => $in_tags,  'a' => $a );
					}
				}
			}
			$count = count($results_words);
		//	$average = round($max / 3);
			foreach ($results_words as $key => &$keyword) 	
			{
				$keyword['weight'] = round($keyword['occurrence']/$count*100,2);
				
				//if ( $keyword['occurrence'] < $average )
					//unset( $results_words[$key] );				
			}						
			usort($results_words, function($a, $b){   
				if( $a['occurrence'] == $b['occurrence'])
					return ( $b['a'] - $a['a'] );
				else 
					return ( $b['occurrence'] - $a['occurrence'] );				
			});
			
			$result['words'] = $results_words;			
		
		}		
		return $result;		
	}
	
	public function get_speed_results( ) 
	{		
		require_once( USAM_FILE_PATH . '/includes/seo/google/pagespeed.class.php' );		
		$pagespeed = new USAM_Google_Page_Speed();	
		$result = $pagespeed->get_pagespeed( $this->url );
		return $result;		
	}
}