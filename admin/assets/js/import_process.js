if( document.getElementById('product_import') )
{ 
	new Vue({
		el: '#product_import',
		mixins: [importer],	
		data() {
			return {	
				default_columns:{category:[],companies:[]},				
			};
		},	
		mounted() {	
			usam_api('filters',{category: '', companies:{fields: 'autocomplete', type: 'contractor', orderby: 'name', count:1000}}, 'POST', (r) => this.default_columns = r);
		},
	})
}
else if( document.querySelector('#company_import') )
{
	new Vue({
		el: '#company_import', 
		mixins: [importer],
		data() {
			return {	
				groups:[{0:''}],				
			};
		},
		created() {
			usam_api('groups',{type: 'company', count:1000}, 'GET', (r) => this.groups = r.items);
		}
	})	
}
else if( document.querySelector('#contact_import') )
{
	new Vue({
		el: '#contact_import', 
		mixins: [importer],
		data() {
			return {	
				groups:[{0:''}],				
			};
		},
		created() {
			usam_api('groups',{type: 'contact', count:1000}, 'GET', (r) => this.groups = r.items);
		}
	})	
}
else if( USAM_Importer.rule_type && document.querySelector('#'+USAM_Importer.rule_type) )
{
	new Vue({el: '#'+USAM_Importer.rule_type, mixins: [importer]})	
}