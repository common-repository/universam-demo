document.addEventListener("DOMContentLoaded", function(e) 
{
	if( document.getElementById('usam-possibilities') && sectiontabs !== undefined )
	{
		new Vue({
			el:'#usam-possibilities .inside',		
			data() {
				return {					
					tab:'list',
					sectionTab:'',
					sectionTabs:{},
					properties:[],
					meta:{},
					data:{id:'', name:'', field_type:'text', code:'', sort:''},
					newdata:'',
					post_type:'',
					id:0,
				};
			},			
			created() {	
				this.sectionTabs = sectiontabs;
				this.sectionTab = Object.keys(this.sectionTabs)[0];
				this.post_type = document.querySelector('#post_type').value;
				this.id = document.querySelector('#post_ID').value;
				usam_api('properties', {type:this.post_type, post_id:this.id, add_fields:['post']}, 'GET', (r)=>this.properties=r.items);
				usam_api('post/metatags', {'id':this.id}, 'GET', (r) => this.meta = r);
			},	
			methods: {				
				add() { 
					var data = structuredClone(this.data);
					data.type = this.post_type;			
					usam_api('property', data, 'POST', (r)=>{
						data.id = r;
						this.properties.push(data);
					});
					this.newdata = false;
				},
				del(k, e) { 			
					e.preventDefault();	
					usam_api('property/'+this.properties[k].id, 'DELETE');
					this.properties.splice(k, 1);
				}
				
			}	
		})
	}
})