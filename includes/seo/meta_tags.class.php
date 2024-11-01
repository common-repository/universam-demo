<?php
class USAM_Meta_Tags
{
	protected $options = [];
	public function __construct()
	{	
		$metas = get_option('usam_metas', []);
		if ( !$metas )
			$metas = [];
		$default = ['pages' => [''], 'terms' => [], 'post_types' => [], 'knowledgegraph_type' => 'company'];
		$this->options = array_merge( $default, $metas );
	}
		
	protected function get_args( ) 
	{
		return ['site_name' => get_bloginfo('name'), 'phone' => usam_get_shop_phone(), 'email' => usam_get_shop_mail(), 'year' => date("Y")];
	}	
	
	public function get_site_name( ) { return get_bloginfo('name'); }
	public function get_noindex( ) 
	{
		$result = $this->get_tag_meta( 'noindex' );
		if( $result == 2 )
			return 0;
		return $result;
	}
	
	public function get_nofollow( ) 
	{
		$result = $this->get_tag_meta( 'nofollow' );
		if( $result == 2 )
			return 0;
		return $result;
	}
	public function get_title( ) { return ''; }	
	public function get_description( ) { return ''; }		
	public function get_open_graph_title( ) { return $this->get_title(); }
	public function get_open_graph_description( ) { return $this->get_description(); }	
	public function get_open_graph_article_modified_time( ) { return ''; }
	public function get_open_graph_published_time( ) { return ''; }	
	public function get_open_graph_url( ) { return ''; }

	public function get_schema() 
	{			
		$homeUrl = trailingslashit( home_url() );
		$graphs = [];
		$graphs[]    = [
			'@type'       => 'WebSite',
			'@id'         => $homeUrl . '#website',
			'url'         => $homeUrl,
			'name'        => html_entity_decode( get_bloginfo('name'), ENT_QUOTES ),
			'description' => html_entity_decode( get_bloginfo('description'), ENT_QUOTES ),
			'inLanguage'  => str_replace( '_', '-', $this->current_language_code()),
			'publisher'   => [ '@id' => $homeUrl . '#' . $this->get_publisher() ],			
		];
		if ( $this->get_publisher() == 'organization' )
			$graphs[] = $this->get_organization();		
		$graphs[] = $this->get_breadcrumb_list();
		$graphs[] = $this->get_web_page();
		$schema = ['@context' => 'https://schema.org', '@graph' => $graphs];
		return wp_json_encode( $schema );
	}
	
	private function get_breadcrumb_list( ) 
	{
		$position = 1;
		$lists = [[
			'@type'       => 'ListItem',
			'@id'         => get_option('home') . '#listItem',			
			'position'    => $position,
			'item'        => ["@type" => "WebPage", '@id' => get_option('home'), 'name' => __('Главная', 'usam'), 'url' => get_option('home')],	//description		
		]];			
		$breadcrumbs = USAM_Breadcrumbs::instance();
		foreach( $breadcrumbs::$breadcrumbs as $breadcrumb )
		{			
			$position++;
			$lists[] = [
				'@type'       => 'ListItem',
				'@id'         => $breadcrumb['url'] . '#listItem',			
				'position'    => $position,
				'item'        => ["@type" => "WebPage", '@id' => $breadcrumb['url'], 'name' => $breadcrumb['name'], 'url' => $breadcrumb['url']],	//description		
			];			
		}
		$homeUrl = trailingslashit( home_url() );
		$data[]    = [
			'@type'       => 'BreadcrumbList',
			'@id'         => $homeUrl . '#organization',			
			'itemListElement' => $lists,
		];
		return $data;
	}
	
	protected function get_term( ) 
	{
		global $wp_query;
		$term = $wp_query->get_queried_object();
		if ( !empty($term->term_id) && !is_wp_error($term) )
			$term->url = get_term_link($term->term_id, $term->taxonomy);
		else
			$term = null;
		return $term;
	}
	
	private function get_web_page( ) 
	{
		global $post;
		$homeUrl = trailingslashit( home_url() );
		if ( is_single() )
			$url = get_permalink($post->ID);
		elseif ( is_home()  )
			$url = $homeUrl;	
		elseif ( is_tax() )
		{
			$term = $this->get_term();
			$url = !empty($term->url)?$term->url:'';
		}	
		else
			$url = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
 
		$data[]    = [
			'@type' => 'WebPage',
			'@id'   => $url . '#webpage',			
			'url'   => $url,
			'name'  => $this->get_title(),	
			'description'  => $this->get_description(),		
			'inLanguage' => str_replace( '_', '-', $this->current_language_code()),	
			'isPartOf'    => [ '@id' => $homeUrl . '#website' ],
			'breadcrumb'  => [ '@id' => $url . '#breadcrumblist' ]
		];
		if ( is_single() )
		{
			$image = usam_the_product_thumbnail( $post->ID, 'single' );
			if ( $image )
			{ 
				$data['image'] = ['@type' => 'ImageObject', '@id' => $homeUrl. '#mainImage', 'url' => $image['src'], 'width' => $image['width'], 'height' => $image['height']];
				$data['primaryImageOfPage'] = ['@id' => $url. '#mainImage'];
			}
			$data['datePublished'] = mysql2date( DATE_W3C, $post->post_date_gmt, false );
			$data['dateModified']  = mysql2date( DATE_W3C, $post->post_modified_gmt, false );			
		}
		elseif ( is_tax() )
		{			
			$term = $this->get_term();
			if ( !empty($term->term_id) )
			{
				$attachment_id = (int)get_term_meta($term->term_id, 'thumbnail', true);
				if ( $attachment_id )
				{
					$image = wp_get_attachment_image_src( $attachment_id, 'medium-single-product' );	
					if ( $image )
					{
						$data['image'] = ['@type' => 'ImageObject', '@id' => $homeUrl. '#mainImage', 'url' => $image[0], 'width' => $image[1], 'height' => $image[2]];	
						$data['primaryImageOfPage'] = ['@id' => $url. '#mainImage'];					
					}
				}
			}
		}
		return $data;
	}
		
	private function get_organization( ) 
	{
		$homeUrl = trailingslashit( home_url() );
		$requisites = usam_shop_requisites_shortcode();
		$data[]    = [
			'@type' => 'Organization',
			'@id'   => $homeUrl . '#organization',			
			'name'  => html_entity_decode( $requisites['full_company_name'], ENT_QUOTES ),
			'url'   => $homeUrl,			
		];
		if ( isset($requisites['logo_url']) )
			$data['logo'] = ['@type' => 'ImageObject', '@id' => $homeUrl . '#organizationLogo', 'url' => $requisites['logo_url'], 'width' => $requisites['logo_width'], 'height' => $requisites['logo_height'], 'caption' => $requisites['logo_caption']];
		else
			$data['logo'] = $this->get_schema_logo();
		$social = usam_get_shop_social();
		$data['image'] = ['@id' => trailingslashit( home_url() ) . '#organizationLogo'];
		$data['sameAs'] = array_values($social);
		$data['contactPoint'] = ["@type" => "ContactPoint", "telephone" => usam_get_shop_phone(false), "contactType" => "Billing Support"];		
		return $data;
	}
	
	private function get_schema_logo( $graphId = 'organizationLogo' ) 
	{	
		$id = get_theme_mod( 'custom_logo' );
		if ( empty($id) )
			return false;			
		$image = wp_get_attachment_image_src( $id, 'full' );
		if ( empty($image) )
			return false;			
		return ['@type' => 'ImageObject', '@id' => trailingslashit( home_url() ) . '#' . $graphId, 'url' => $image[0], 'width' => $image[1], 'height' => $image[2], 'caption' => $this->get_image_caption($id)];
	}
	
	private function get_image_caption( $attachment_id ) 
	{
		$caption = wp_get_attachment_caption( $attachment_id );
		if ( ! empty( $caption ) )
			return $caption;
		return get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	}
	
	public function get_publisher() 
	{
		return 'company' === $this->options['knowledgegraph_type'] ? 'organization' : 'person';
	}
	
	public function current_language_code() 
	{
		global $wp_version;
		if ( version_compare( $wp_version, '5.0', '<' ) )
			return get_locale();
		return determine_locale();
	}
	
	public function get_open_graph_locale() 
	{
		$locale = get_locale();
		$validLocales = [
			'af_ZA', // Afrikaans.
			'ak_GH', // Akan.
			'am_ET', // Amharic.
			'ar_AR', // Arabic.
			'as_IN', // Assamese.
			'ay_BO', // Aymara.
			'az_AZ', // Azerbaijani.
			'be_BY', // Belarusian.
			'bg_BG', // Bulgarian.
			'bp_IN', // Bhojpuri.
			'bn_IN', // Bengali.
			'br_FR', // Breton.
			'bs_BA', // Bosnian.
			'ca_ES', // Catalan.
			'cb_IQ', // Sorani Kurdish.
			'ck_US', // Cherokee.
			'co_FR', // Corsican.
			'cs_CZ', // Czech.
			'cx_PH', // Cebuano.
			'cy_GB', // Welsh.
			'da_DK', // Danish.
			'de_DE', // German.
			'el_GR', // Greek.
			'en_GB', // English (UK).
			'en_PI', // English (Pirate).
			'en_UD', // English (Upside Down).
			'en_US', // English (US).
			'em_ZM',
			'eo_EO', // Esperanto.
			'es_ES', // Spanish (Spain).
			'es_LA', // Spanish.
			'es_MX', // Spanish (Mexico).
			'et_EE', // Estonian.
			'eu_ES', // Basque.
			'fa_IR', // Persian.
			'fb_LT', // Leet Speak.
			'ff_NG', // Fulah.
			'fi_FI', // Finnish.
			'fo_FO', // Faroese.
			'fr_CA', // French (Canada).
			'fr_FR', // French (France).
			'fy_NL', // Frisian.
			'ga_IE', // Irish.
			'gl_ES', // Galician.
			'gn_PY', // Guarani.
			'gu_IN', // Gujarati.
			'gx_GR', // Classical Greek.
			'ha_NG', // Hausa.
			'he_IL', // Hebrew.
			'hi_IN', // Hindi.
			'hr_HR', // Croatian.
			'hu_HU', // Hungarian.
			'ht_HT', // Haitian Creole.
			'hy_AM', // Armenian.
			'id_ID', // Indonesian.
			'ig_NG', // Igbo.
			'is_IS', // Icelandic.
			'it_IT', // Italian.
			'ik_US',
			'iu_CA',
			'ja_JP', // Japanese.
			'ja_KS', // Japanese (Kansai).
			'jv_ID', // Javanese.
			'ka_GE', // Georgian.
			'kk_KZ', // Kazakh.
			'km_KH', // Khmer.
			'kn_IN', // Kannada.
			'ko_KR', // Korean.
			'ks_IN', // Kashmiri.
			'ku_TR', // Kurdish (Kurmanji).
			'ky_KG', // Kyrgyz.
			'la_VA', // Latin.
			'lg_UG', // Ganda.
			'li_NL', // Limburgish.
			'ln_CD', // Lingala.
			'lo_LA', // Lao.
			'lt_LT', // Lithuanian.
			'lv_LV', // Latvian.
			'mg_MG', // Malagasy.
			'mi_NZ', // Maori.
			'mk_MK', // Macedonian.
			'ml_IN', // Malayalam.
			'mn_MN', // Mongolian.
			'mr_IN', // Marathi.
			'ms_MY', // Malay.
			'mt_MT', // Maltese.
			'my_MM', // Burmese.
			'nb_NO', // Norwegian (bokmal).
			'nd_ZW', // Ndebele.
			'ne_NP', // Nepali.
			'nl_BE', // Dutch (Belgie).
			'nl_NL', // Dutch.
			'nn_NO', // Norwegian (nynorsk).
			'nr_ZA', // Southern Ndebele.
			'ns_ZA', // Northern Sotho.
			'ny_MW', // Chewa.
			'om_ET', // Oromo.
			'or_IN', // Oriya.
			'pa_IN', // Punjabi.
			'pl_PL', // Polish.
			'ps_AF', // Pashto.
			'pt_BR', // Portuguese (Brazil).
			'pt_PT', // Portuguese (Portugal).
			'qc_GT', // Quiché.
			'qu_PE', // Quechua.
			'qr_GR',
			'qz_MM', // Burmese (Zawgyi).
			'rm_CH', // Romansh.
			'ro_RO', // Romanian.
			'ru_RU', // Russian.
			'rw_RW', // Kinyarwanda.
			'sa_IN', // Sanskrit.
			'sc_IT', // Sardinian.
			'se_NO', // Northern Sami.
			'si_LK', // Sinhala.
			'su_ID', // Sundanese.
			'sk_SK', // Slovak.
			'sl_SI', // Slovenian.
			'sn_ZW', // Shona.
			'so_SO', // Somali.
			'sq_AL', // Albanian.
			'sr_RS', // Serbian.
			'ss_SZ', // Swazi.
			'st_ZA', // Southern Sotho.
			'sv_SE', // Swedish.
			'sw_KE', // Swahili.
			'sy_SY', // Syriac.
			'sz_PL', // Silesian.
			'ta_IN', // Tamil.
			'te_IN', // Telugu.
			'tg_TJ', // Tajik.
			'th_TH', // Thai.
			'tk_TM', // Turkmen.
			'tl_PH', // Filipino.
			'tl_ST', // Klingon.
			'tn_BW', // Tswana.
			'tr_TR', // Turkish.
			'ts_ZA', // Tsonga.
			'tt_RU', // Tatar.
			'tz_MA', // Tamazight.
			'uk_UA', // Ukrainian.
			'ur_PK', // Urdu.
			'uz_UZ', // Uzbek.
			've_ZA', // Venda.
			'vi_VN', // Vietnamese.
			'wo_SN', // Wolof.
			'xh_ZA', // Xhosa.
			'yi_DE', // Yiddish.
			'yo_NG', // Yoruba.
			'zh_CN', // Simplified Chinese (China).
			'zh_HK', // Traditional Chinese (Hong Kong).
			'zh_TW', // Traditional Chinese (Taiwan).
			'zu_ZA', // Zulu.
			'zz_TR', // Zazaki.
		];
		$fixLocales = [
			'ca' => 'ca_ES',
			'en' => 'en_US',
			'el' => 'el_GR',
			'et' => 'et_EE',
			'ja' => 'ja_JP',
			'sq' => 'sq_AL',
			'uk' => 'uk_UA',
			'vi' => 'vi_VN',
			'zh' => 'zh_CN',
		];

		if ( isset( $fixLocales[ $locale ] ) )
			$locale = $fixLocales[ $locale ];
		if ( 2 === strlen( $locale ) )
			$locale = strtolower( $locale ) . '_' . strtoupper( $locale );
		if ( ! in_array( $locale, $validLocales, true ) ) 
		{
			$locale = strtolower( substr( $locale, 0, 2 ) ) . '_' . strtoupper( substr( $locale, 0, 2 ) );

			if ( ! in_array( $locale, $validLocales, true ) ) {
				$locale = 'en_US';
			}
		}
		return $locale;
	}	
}