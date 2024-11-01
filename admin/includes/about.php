<?php
if ( ! defined( 'USAM_VERSION' ) ) 
{
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
wp_enqueue_script( 'underscore' );
?>
<style>
.about-wrap .usam_tabs_style1 .header_tab .tab{border:1px solid #ddd; width:200px; margin:0}
.about-wrap .usam_tabs_style1 .header_tab a{padding:10px 3px; text-align:center; line-height:18px; font-size:18px; font-weight:600;}	
</style>
<div class="wrap about-wrap">

	<h1><?php printf( __('Добро пожаловать в УНИВЕРСАМ %s!', 'usam'), USAM_VERSION);	?></h1>
	<p class="about-text">
		<?php esc_html_e( 'Спасибо за обновление платформы для интернет-магазина УНИВЕРСАМ!','usam'); ?>
	</p>

	<div class="usam_logo"></div>

	<div id='usam_tabs' class ="usam_tabs usam_tabs_style1">
		<div class='header_tab'>							
			<a class="tab" href='#tab_new'><?php _e('Что нового', 'usam') ?></a>									
			<a class="tab" href='#tab_premium'><?php _e('Лицензии', 'usam') ?></a>
			<a class="tab" href='#tab_privacy'><?php _e('Приватность', 'usam') ?></a>
		</div>
		<div class = "countent_tabs">
			<div id ="tab_new" class = "tab">
				<?php require_once( USAM_FILE_PATH . '/admin/includes/about/new.php' ); ?>							
			</div>										
			<div id ="tab_premium" class = "tab">
				<h3>Любой труд должен быть оплачен. Мы потратили много лет на разработку этой платформы.</h3>
				<p>Вам доступна бесплатная версия. Для увеличения возможностей рекомендуем приобрести расширенную версию.</p>	
				<p>Список вариантов поставок доступен на <a href="http://wp-universam.ru/products/" target="_blank" rel="noopener">этой странице</a>.</p>				
			</div>
			<div id ="tab_rights" class = "tab">
				<h3>Программа "Универсам" является результатом интеллектуальной деятельности и объектом авторских прав как программа для ЭВМ, которые регулируются и защищены законодательством Российской Федерации об интеллектуальной собственности и нормами международного права.</h3>

				<p>Программа содержит коммерческую тайну и иную конфиденциальную информацию, принадлежащую Лицензиару. Любое использование программы в нарушение условий настоящего Соглашения рассматривается как нарушение прав Лицензиара и является достаточным основанием для лишения Пользователя предоставленных по настоящему Соглашению прав.</p>

				<h3>В случае нарушения авторских прав предусматривается ответственность в соответствии с действующим законодательством Российской Федерации.</h3>
			</div>
			<div id ="tab_privacy" class = "tab">
				<p class="about-description">Время от времени ваш сайт Универсам может отсылать данные на wp-universam.ru список плагинов и технические характеристики вашего сервера.</p>

				<p>Эти данные используются для общих улучшений Универсама и оптимизации скриптов. Также они используются для оказания помощи при обращении в техническую поддержку.</p>

				<p>Мы серьезно относимся к приватности и прозрачности сбора данных. Чтобы узнать больше о собираемых нами данных и как мы их используем, посетите <a href="https://wp-universam.ru">wp-universam.ru</a>.</p>
			</div>
		</div>	
	</div>
	<script>
		(function( $ ) {
			$( function() {
				var $window = $( window );
				var $adminbar = $( '#wpadminbar' );
				var $sections = $( '.floating-header-section' );
				var offset = 0;

				// Account for Admin bar.
				if ( $adminbar.length ) {
					offset += $adminbar.height();
				}

				function setup() {
					$sections.each( function( i, section ) {
						var $section = $( section );
						// If the title is long, switch the layout
						var $title = $section.find( 'h2' );
						if ( $title.innerWidth() > 300 ) {
							$section.addClass( 'has-long-title' );
						}
					} );
				}

				var adjustScrollPosition = _.throttle( function adjustScrollPosition() {
					$sections.each( function( i, section ) {
						var $section = $( section );
						var $header = $section.find( 'h2' );
						var width = $header.innerWidth();
						var height = $header.innerHeight();

						if ( $section.hasClass( 'has-long-title' ) ) {
							return;
						}

						var sectionStart = $section.offset().top - offset;
						var sectionEnd = sectionStart + $section.innerHeight();
						var scrollPos = $window.scrollTop();

						// If we're scrolled into a section, stick the header
						if ( scrollPos >= sectionStart && scrollPos < sectionEnd - height ) {
							$header.css( {
								position: 'fixed',
								top: offset + 'px',
								bottom: 'auto',
								width: width + 'px'
							} );
						// If we're at the end of the section, stick the header to the bottom
						} else if ( scrollPos >= sectionEnd - height && scrollPos < sectionEnd ) {
							$header.css( {
								position: 'absolute',
								top: 'auto',
								bottom: 0,
								width: width + 'px'
							} );
						// Unstick the header
						} else {
							$header.css( {
								position: 'static',
								top: 'auto',
								bottom: 'auto',
								width: 'auto'
							} );
						}
					} );
				}, 100 );

				function enableFixedHeaders() {
					if ( $window.width() > 782 ) {
						setup();
						adjustScrollPosition();
						$window.on( 'scroll', adjustScrollPosition );
					} else {
						$window.off( 'scroll', adjustScrollPosition );
						$sections.find( '.section-header' )
							.css( {
								width: 'auto'
							} );
						$sections.find( 'h2' )
							.css( {
								position: 'static',
								top: 'auto',
								bottom: 'auto',
								width: 'auto'
							} );
					}
				}
				$( window ).resize( enableFixedHeaders );
				enableFixedHeaders();
			} );
		})( jQuery );
	</script>

</div>