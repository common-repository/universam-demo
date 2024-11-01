var interface_filters = {}, manager_chat = {}, media_browser={};

const store = Pinia.defineStore('main',{
    state:() => {
		return { location_ids:[] }
	},
    actions:{
		setLoctions( id ) {
		  if (Array.isArray(id))
                this.location_ids = id;
            else {
                var i = this.location_ids.indexOf(id);
                if (i === -1)
                    this.location_ids.push(id);
                else
                    this.location_ids.splice(i, 1);
            }
		},
		/*async htmlBlock() {
			try {
				const data = await axios.get('https://jsonplaceholder.typicode.com/users')
				this.users = data.data
			}
			catch (error) {
				alert(error)
				console.log(error)
			}
		}*/
	}
})
Vue.use(Pinia.PiniaVuePlugin)
const pinia = Pinia.createPinia()

function usam_get_loader() {
    html = '<div id="cube-loader" class="cube_loader"><div class="cube_loader__cube cube_loader__loader_1"></div><div class="cube_loader__cube cube_loader__loader_2"></div><div class="cube_loader__cube cube_loader__loader_4"></div><div class="cube_loader__cube cube_loader__loader_3"></div></div>';
    return html;
}

function usam_load_attachments(t) {
    usam_addScript('jquery.knob.js');
    usam_addScript('jquery.iframe-transport.js');
    var script = usam_addScript('jquery.fileupload.js');
    var drop = t.find('.js-file-drop');
    drop.on('click', function (e) {
        jQuery(this).siblings('input[type="file"]').click();
    });
    script.onload = function () {
        t.on('.js_delete_action', 'click', function (e) {
            e.preventDefault();
            jQuery(this).parent().remove();
        });
        t.find('input[type="file"]').fileupload({
            url:drop.attr('fileupload_url'),
            dataType:'json',
            formData:[],
            dropZone:drop,
            add:function (e, data) {
                jQuery('input[name="action"]').prop("disabled", true);
                var tpl = '<input type="text" value="0" data-width="48" data-height="48" data-fgColor="#0788a5" data-readOnly="1" data-bgColor="#3e4043"/>';
                var $html = jQuery("<div class='usam_attachments__file js_delete_block'><a class='js_delete_action'></a><div class='attachment_icon'>" + tpl + "</div><div class='attachment__file_data'><div class='filename'>" + usam_get_attachment_title(data.files[0].name) + "</div><div class='attachment__file_data__filesize'>" + formatFileSize(data.files[0].size) + "</div></div></div>");

                data.context = $html.appendTo(jQuery(e.target).parents('.js-attachments'));
                $html.find('input').knob();
                $html.find('.js_delete_action').on('click', function (e) {
                    if ($html.hasClass('working')) {
                        jqXHR.abort();
                    }
                    $html.fadeOut(function () {
                        $html.remove();
                    });
                });
                var jqXHR = data.submit();
            },
            progress:function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                data.context.find('input').val(progress).change();
                if (progress == 100) {
                    data.context.removeClass('working');
                }
            },
            done:function (e, data) {
                if (data._response.result.status == 'success') {
                    var file_input_name = jQuery(e.target).parents('.js-attachments').attr('file_input_name');
                    jQuery('input[name="action"]').prop("disabled", false);
                    var attachment = "<input type='hidden' name='" + file_input_name + "' value='" + data._response.result.id + "'/>";
                    data.context.append(attachment);
                    data.context.attr('data-id', data._response.result.id);
                    data.context.find('.attachment_icon').html('<img src="' + data._response.result.icon + '"/>');
                } else {
                    data.context.addClass('loading_error');
                    data.context.find('.attachment_icon').html(data._response.result.result.error_message);
                }
            },
            fail:function (e, data) {
                data.context.addClass('loading_error');
            }
        });
    }
}

function usam_admin_notice(r, t) {
    t = t === undefined ? 'save' :t;
    t = r ? 'notice_' + t :'notice_not_' + t;
    if (USAM_Admin[t] !== undefined)
        usam_notifi({
            'text':USAM_Admin[t]
        });
}

function usam_active_loader() {
    if (jQuery('.loader__full_screen').length) {
        jQuery('.loader__full_screen').show();
    } else {
        var loader = usam_get_loader();
        loader = "<div class='loader__full_screen'>" + loader + "<div class='loader_backdrop'></div></div>";
        if (jQuery('.loader__full_screen').length == 0)
            jQuery('body').append(loader);
    }
}

function usam_loopit(dir, t, i) {
    var degs = t.data("prec");
    if (dir == "c")
        i++;
    else
        i--;
    if (i < 0)
        i = 0;
    if (i > degs)
        i = degs;
    var prec = (100 * i) / 360;
    t.find(".js-active-border-prec").html(Math.round(prec) + "%");
    if (i <= 180)
        t.css('background-image', 'linear-gradient(' + (90 + i) + 'deg, transparent 50%, var(--main-color-excretion) 50%),linear-gradient(90deg, var(--main-color-excretion) 50%, var(--main-color3) 50%)');
    else
        t.css('background-image', 'linear-gradient(' + (i - 90) + 'deg, transparent 50%, var(--main-color3) 50%),linear-gradient(90deg, var(--main-color-excretion) 50%, var(--main-color3) 50%)');
    if (i != degs) {
        setTimeout(function () {
            usam_loopit("c", t, i);
        }, 1);
    }
}

jQuery(document).ready(function ($) {
    $(document).on('click', '.js-sticky-seller-toggle', function (e) {
        e.preventDefault();
        var t = $(this),
        id = t.attr('seller');
        usam_api('list/seller', {
            list:'sticky',
            seller_id:id
        }, 'POST', (r) => {
            if (r == 'deleted')
                t.removeClass('list_selected');
            else if (r == 'add')
                t.addClass('list_selected');
        });
    });

    $('.js-active-circle').each(function () {
        usam_loopit("c", $(this), 0);
    })
    $('.js-autocomplete').each(function () {
        let t = $(this);
        if (typeof t.data('minlength') === typeof undefined)
            minlength = t.data('minlength');
        else
            minlength = 2;

        t.autocomplete({
            source:t.data('url'),
            minLength:minlength,
            autoFocus:true,
            select:function (e, ui) {
                if (ui.item.text != '') {
                    t.siblings().val(ui.item.value).trigger("change", [ui]);
                    t.trigger('autocomplete_select', [ui]);
                    ui.item.value = ui.item.text;
                } else
                    return false;
            }
        }).data("ui-autocomplete")._renderItem = function (ul, item) {
            if (typeof item.autocomplete_title !== typeof undefined)
                return jQuery("<li class='autocomplete_title'></li>").data("item.autocomplete", item).append(item.autocomplete_title).appendTo(ul);
            v = t.val();
            var reg = new RegExp(v, 'gi');
            if (v) {
                if (typeof item.label !== typeof undefined)
                    return jQuery("<li></li>").data("item.autocomplete", item).append(item.label.replace(reg, '<b>' + v[0].toUpperCase() + v.slice(1) + '</b>')).appendTo(ul);
                else
                    return jQuery("<li></li>").data("item.autocomplete", item).append(item.text.replace(reg, '<b>' + v[0].toUpperCase() + v.slice(1) + '</b>')).appendTo(ul);
            }
        };
    })
    if (jQuery(".js-date-picker").length) {
        jQuery(".js-date-picker").datepicker({
            dateFormat:"dd.mm.yy",
            numberOfMonths:1,
            showButtonPanel:true,
            hideIfNoPrevNext:true
        });
    }
    if (jQuery("input.js-color").length) {
        jQuery('input.js-color').wpColorPicker();
    }
    if (jQuery("a.js-move-block").length) {
        jQuery("a.js-move-block").on("click", function (e) {
            var anchor = jQuery(this);
            var href = anchor.attr('href');
            var str = href.split('#');
            var top = jQuery("#" + str[1]).offset().top;
            if (jQuery(".sticky").length) {
                top -= jQuery(".sticky").height();
            }
            if (jQuery("#wpadminbar").length) {
                top -= jQuery("#wpadminbar").height() + 30;
            }
            jQuery('html, body').stop().animate({
                scrollTop:top
            }, 777);
            e.preventDefault();
            return false;
        });
    }

    $('body').on('click', '.js-copy-clipboard', (e) => usam_copy_clipboard(e.target, 'Скопировано'));

    if ($('.js-attachments input[type="file"]').length > 0) {
        $('.js-attachments').each(function (i, elem) {
            usam_load_attachments($(this));
        })
    }

    //было сделено изменение
    $('body').on('change', 'input.show_change, textarea.show_change, select.show_change', function () {
        jQuery(this).addClass("change_made");
    });

    $('.chzn-select').each(function (i, elem) {
        if ($(this).is(":visible")) {
            $(this).chosen();
        }
    });

    $('.help_text_box').on({
        mouseenter:function () {
            $(this).find(".help_text").fadeIn();
        },
        mouseleave:function () {
            $(this).find(".help_text").hide("slow");
        }
    })

    jQuery('body').on('click', '.js-checked-item', function () {
        if (jQuery(this).hasClass('checked')) {
            jQuery(this).find('input[type="checkbox"]').attr('checked', false);
            jQuery(this).removeClass('checked');
        } else {
            jQuery(this).find('input[type="checkbox"]').attr('checked', true);
            jQuery(this).addClass('checked');
        }
    });

    jQuery('body').on('click', '.usam_radio__item', function (e) {
        if (!jQuery(this).find('input[type="radio"]').prop('checked')) {
            jQuery(this).find('input[type="radio"]').prop('checked', true).trigger('change');
            items = jQuery(this).siblings('.checked');
            items.removeClass('checked');
            items.find('input[type="radio"]').prop('checked', false);
            jQuery(this).addClass('checked');
        }
    });

    if (USAM_Admin.dragndrop && adminpage == "edit-php") { // это делает таблицу списка продуктов сортируемым
        jQuery('table.widefat:not(.tags)').sortable({
            update:function (e, ui) {
                items = jQuery('table.widefat').sortable('toArray');
                var data = {
                    items:[]
                }
                for (i = 0; i < items.length; i++)
                    data.items.push({
                        ID:Number(items[i].replace(/[^0-9]/g, "")),
                        menu_order:i
                    });
                usam_api('products', data, 'PUT', usam_admin_notice);
            },
            items:'tbody tr',
            axis:'y',
            containment:'table.widefat tbody',
            placeholder:'product-placeholder',
            cursor:'move',
            cancel:'tr.inline-edit-usam-product'
        });
    }
});

(function ($) {
    $.extend(USAM_Admin, {
        init:function () {
            $(function () {
                $('body')
                .on('click', '.table_rate .add', USAM_Admin.event_add_table_rate_layer)
                .on('click', '.table_rate .delete', USAM_Admin.event_delete_table_rate_layer)
                .on('keypress', '.table_rate input[type="text"]', USAM_Admin.event_enter_key_pressed)
                .on('click', '.usam_box .handlediv', USAM_Admin.toggle_usam_box)
                .on('click', '.js_delete_action', USAM_Admin.delete_block)
                .on('click', '#regenerate_thumbnails', USAM_Admin.regenerate_thumbnails)
                .on('click', '#posts-filter #doaction, #posts-filter #doaction2', USAM_Admin.bulkactions);

                $('.table_rate tbody').sortable({
                    cursor:"move",
                    items:'tr',
                    axis:'y',
                    containment:'parent'
                });
            });
        },

        regenerate_thumbnails:function (e) {
            e.preventDefault();
            var ids = [];
            if ($(this).parents('tr').length)
                ids[0] = $(this).parents('tr').attr('id').replace('post-', '');
            else
                ids[0] = $('#post_ID').val();

            usam_send({
                nonce:USAM_Admin.bulkactions_nonce,
                action:'bulkactions',
                a:'regenerate_thumbnails',
                item:'posts',
                cb:ids
            });
        },

        bulkactions:function (e) {
            if (USAM_Admin.screen_id != 'edit-usam-product' && USAM_Admin.screen_id != 'edit-post' && USAM_Admin.screen_id != 'upload')
                return true;

            var action = $(this).siblings('select').val();
            if (action == 'trash' || action == 'untrash' || action == 'delete' || action == 'regenerate_thumbnails') {
                e.preventDefault();
                usam_active_loader();
                var ids = [];
                var i = 0;
                jQuery('.wp-list-table tbody .check-column input:checkbox:checked').each(function () {
                    ids[i] = jQuery(this).val();
                    i++;
                });
                usam_send({
                    nonce:USAM_Admin.bulkactions_nonce,
                    action:'bulkactions',
                    a:action,
                    item:'posts',
                    cb:ids
                }, (r) => {
                    for (i = 0; i < ids.length; i++) {
                        jQuery('.wp-list-table tbody .post-' + ids[i]).remove();
                    }
                });
            }
        },

        delete_block:function (e) {
            e.preventDefault();
            $(this).parents('.js_delete_block').remove();
        },

        event_enter_key_pressed:function (e) {
            var code = e.keyCode ? e.keyCode :e.which;
            if (code == 13) {
                var row = $(this).closest('td');
                var add_button = row.next().find('.add');
                if (add_button.length)
                    add_button.trigger('click', [true]);
                else
                    row.siblings('td').find('input').focus();
                e.preventDefault();
            }
        },

        //Добавить уровень
        event_add_table_rate_layer:function (e) {
            e.preventDefault();
            var table = $(this).closest('table'),
            row = $(this).closest('tr');
            this_row = table.find('.js-new-row');
            if (!this_row.length)
                this_row = row;
            var clone = this_row.clone().removeClass('js-new-row');
            clone.find('input').val('');
            clone.find('select option').prop('selected', false);
            clone.find('.cell-wrapper').hide();
            if (clone.find('.chzn-select'))
                clone.find('.chosen-container').remove();
            clone.insertAfter(row).find('input').eq(0).slideDown(150, function () {
                clone.find('input').eq(0).focus();
                if (clone.find('.chzn-select'))
                    clone.find('.chzn-select').chosen({
                        width:'100%'
                    });
                if (clone.find('.column_number')) {
                    table.find('.column_number').each(function (i, elem) {
                        $(this).html(i + 1);
                    })
                }
            });
            table.trigger('add_table_layer', clone);
            return false;
        },

        //Удалить уровень
        event_delete_table_rate_layer:function (e) {
            e.preventDefault();
            var this_row = $(this).closest('tr');
            var table = this_row.closest('table');
            if (table.find('tr:not(.js-warning)').length == 1) {
                this_row.find('input').val('');
                this_row.fadeOut(150, function () {
                    $(this).fadeIn(150);
                });
            } else if (table.find('tr').length > 2) {
                this_row.slideUp(150, function () {
                    this_row.remove();
                });
            }
            return false;
        },

        toggle_usam_box:function () {
            var t = $(this).parents('.usam_box');
            hidden = t.hasClass('closed') ? 0 :1;
            t.toggleClass("closed");
            t.find('.chzn-select').chosen();
            usam_send({
                nonce:USAM_Admin.save_nav_menu_metaboxes_nonce,
                action:'save_nav_menu_metaboxes',
                id:t.attr('id'),
                hidden:hidden
            });
        },
    });
})(jQuery);
USAM_Admin.init();

Vue.component('group-management', {
    props:{
        type:{
            type:String,
            required:true,
        default:
            ''
        },
        id:{
            type:Number,
            required:true,
        default:
            0
        },
    },
    data() {
        return {
            groups:[],
            newGroup:'',
            search:'',
            oldIndex:'',
            allowGroupСhanges:true
        };
    },
    mounted() {
        this.queryAPI()
    },
    methods:{
        queryAPI() {
            usam_api('groups', {
                type:this.type,
                id:this.id,
                count:200
            }, 'POST', (r) => {
                for (let k in r.items) {
                    r.items[k].editor = false;
                    if (!this.id)
                        r.items[k].checked = false;
                }
                this.groups = r.items;
                this.$watch('groups', this.change, {
                    deep:true,
                    immediate:true
                });
            });
        },
        change() {
            var groups = [];
            for (let k in this.groups)
                if (this.groups[k].checked)
                    groups.push(this.groups[k].id);
            this.$emit('change', groups);
        },
        allowDrop(e, k) {
            e.preventDefault();
            if (this.oldIndex != k) {
                let v = Object.assign({}, this.groups[this.oldIndex]);
                this.groups.splice(this.oldIndex, 1);
                this.groups.splice(k, 0, v);
                this.oldIndex = k;
            }
        },
        drag(e, k) {
            this.oldIndex = k;
            if (e.target.hasAttribute('draggable'))
                e.currentTarget.classList.add('draggable');
            else
                e.preventDefault();
        },
        dragEnd(e, i) {
            e.currentTarget.classList.remove('draggable');
        },
        drop(e, k) {
            e.preventDefault();
            let data = [];
            for (i = 0; i < this.groups.length; i++)
                data[i] = {
                    id:this.groups[i].id,
                    sort:i
                };
            usam_api('groups', {
                items:data
            }, 'PUT');
        },
        add(e) {
            e.preventDefault();
            this.newGroup = e.target.innerText;
            if (this.newGroup) {
                let g = {
                    type:this.type,
                    name:this.newGroup
                };
                usam_api('group', g, 'POST', (r) => {
                    g.id = r;
                    g.editor = false;
                    g.checked = false;
                    this.groups.push(g);
                });
                this.newGroup = '';
                e.target.innerText = '';
            }
        },
        group_focus(k) {
            this.groups[k].editor = true;
            setTimeout(() => {
                this.$refs['checklist_editor' + this.groups[k].id][0].focus()
            }, 100);
        },
        group_delete(e, k) {
            e.preventDefault();
            usam_api('group/' + this.groups[k].id, 'DELETE');
            this.groups.splice(k, 1);
        },
        save(e, k) {
            e.preventDefault();
            if (e.target.innerText) {
                this.groups[k].name = e.target.innerText;
                this.groups[k].editor = false;
                data = this.groups[k];
                usam_api('group/' + this.groups[k].id, data, 'POST');
            }
        }
    }
})

undoRedoHistory = {
    data() {
        return {
            watchState:null,
            history:[],
            currentIndex:-1
        }
    },
    mounted() {
        this.watchState = this.$watch('data', this.addState, {
            deep:true,
            immediate:true
        });
    },
    methods:{
        addState(state) {
            if (this.currentIndex + 1 < this.history.length)
                this.history.splice(this.currentIndex + 1);
            this.history.push(structuredClone(state));
            this.currentIndex++;
        },
        undo() {
            if (this.watchState !== null)
                this.watchState();
            if (this.history.length && this.history[this.currentIndex - 1] !== undefined) {
                this.data = structuredClone(this.history[this.currentIndex - 1]);
                this.currentIndex--;
            }
            this.watchState = this.$watch('data', this.addState, {
                deep:true
            });
        },
        redo() {
            if (this.watchState !== null)
                this.watchState();
            if (this.history.length && this.history[this.currentIndex + 1] !== undefined) {
                this.data = structuredClone(this.history[this.currentIndex + 1]);
                this.currentIndex++;
            }
            this.watchState = this.$watch('data', this.addState, {
                deep:true
            });
        }
    }
}
var formTools = {
    data() {
        return {
            data:{},
            form_tab:'',
            form_type:'edit',
            form_tabs:[],
            sidebarActive:false,
            sidebardata:null,
            crmGroups:[],
            changed:false
        }
    },
    created() {
		if (typeof form_args !== typeof undefined)
			for (let k in form_args)
				this[k] = form_args[k];
        let url = new URL(document.location.href);
        if (this.form_tabs.length)
            this.form_tab = url.searchParams.has('subtab') ? url.searchParams.get('subtab') :this.form_tabs[0].slug;
        this.form_type = url.searchParams.has('form') ? url.searchParams.get('form') :this.form_type;
    },
    methods:{
        getGroups(){
            usam_api('groups', {type:this.crm_type, objects:[this.data.id], count:200}, 'POST', (r) => this.crmGroups = r.items);
        },
        getDataJSON(){
            return this.data;
        },
		uploadToJSON() {
            var data = JSON.stringify(this.getDataJSON());
            var a = document.createElement("a");
            var file = new Blob([data], {type:'text/plain'});
            a.href = URL.createObjectURL(file);
            a.download = this.nameJSONFile+'.json';
            a.click();
        },
        sidebar(type, code){
            this.sidebardata = code;
            this.$refs['modal' + type].show = !this.$refs['modal' + type].show;
            this.sidebarActive = this.$refs['modal' + type].show ? type :false;
        },
        openloadFromJSON() {
            this.sidebar('loadfromjson');
        },
        loadFromJSON(e) {
            this.sidebar('loadfromjson');
            this.loadDataJSON(e);
        },
        loadDataJSON(e) {
            if (Object.keys(e).length) {
                e.id = this.data.id;
                this.data = {};
                this.dataTypeProcessing(e);
                this.dataFormatting(e);
            }
        },
        dataFormatting(r) {
            this.data = r;
        },
        dataTypeProcessing(d) {
            for (i in d)
                if (typeof d[i] === 'string') {
                    if (d[i] === 'true')
                        d[i] = true;
                    else if (d[i] === 'false')
                        d[i] = false;
                    else if (d[i] === 'null')
                        d[i] = null;
                    else if (d[i] !== '' && !isNaN(Number(d[i])))
                        d[i] = parseInt(d[i]);
                } else if (typeof d[i] === 'array' || typeof d[i] === 'object')
                    this.dataTypeProcessing(d[i]);
        },
		getSidebarSelected() {
            if (this.data.manager_id !== undefined)
                return [this.data.manager_id];
            else
                return [];
        },
        sidebar_checks_elected(item) {
            return this.getSidebarSelected().includes(item.user_id);
        },       
        backList() {
            let url = new URL(document.location.href);
            url.searchParams.delete('id');
            url.searchParams.delete('form_name');
            url.searchParams.delete('form');
            window.location.replace(url.href);
        },
        afterDelete(r) {
            usam_admin_notice(r, 'delete');
            this.backList();
        },
        openEditName() {
            this.editName = !this.editName;
            setTimeout(() => this.$refs.formname.focus(), 100)
        },
        selectTemplate(template) {
            usam_active_loader();
            usam_api('template/' + template, 'GET', this.loadDataJSON);
        },
        setParamsUrl(k, v) {
            let url = new URL(document.location.href);
            url.searchParams.set(k, v);
            history.pushState({
                'url':url.href
            }, '', url.href);
        },
        afterAdding(id) {
            if (Number.isInteger(id)) {
                this.data.id = id;
                this.setParamsUrl('id', id);
                usam_admin_notice(id);
            }
        },
        addNew() {
            let url = new URL(document.location.href);
            url.searchParams.set('id', 0);
            window.location.replace(url.href);
        },
        statusStyle(d, type) {
            var style = '';
            for (let k in this.statuses)
                if (d.status == this.statuses[k].internalname && this.statuses[k].type == type) {
                    style = this.statuses[k].color ? 'background:' + this.statuses[k].color + ';' :'' + this.statuses[k].text_color ? 'color:' + this.statuses[k].text_color + ';' :'';
                    break;
                }
            return style;
        },
        statusName(d, type) {
            var name = '';
            for (let k in this.statuses)
                if (d.status == this.statuses[k].internalname && this.statuses[k].type == type) {
                    name = this.statuses[k].name
                        break;
                }
            return name;
        },
        localDate(date, format) {
            return local_date(date, format);
        },
        to_currency(s) {
            return to_currency(s, '', this.rounding);
        },
        formatNumber(v, d) {
            if (v === undefined)
                return '';
            d = d === undefined ? 2 :d;
            if (typeof v == 'string')
                v = Number(v);
            return v.toFixed(d).toString().replace('.', decimal_separator).replace(/\B(?=(\d{3})+(?!\d))/g, thousands_separator);
        },
        isNumber(e) {
            e = (e) ? e :window.event;
            var code = (e.which) ? e.which :e.keyCode;
            if ((code > 31 && (code < 48 || code > 57)) && code !== 46)
                e.preventDefault();
            else
                return true;
        },
        formatted_number(number, r) {
            if (number === undefined)
                return '';
            r = r === undefined ? this.rounding :r;
            if (typeof number == 'string')
                number = Number(number);
            return number.toFixed(r).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        },
        addManager(item) {
            this.data.manager_id = item.user_id;
            this.manager = item;
            this.sidebar('managers');
        },
        addYourManager() {
            usam_api('contact', {add_fields:['foto','post','url']}, 'GET', (r) => {
                this.data.manager_id = r.user_id;
                this.manager = r;
            });
        },
        openEmail() {
            this.$refs.ribbon.sidebar('sendemail');
        },
        call(p) {
            this.$refs.ribbon.call(p);
        }
    }
}

var parser = {
    data() {
        return {
            data:{},
            test_url:'',
            dataTags:{},
            resultTest:'',
            advanced:false,
            prices:[],
            blocks_variations:[]
        }
    },
    created() {
        this.dataFormatting(form_data);
        usam_api('type_prices', {base_type:0}, 'GET', (r) => {
            for (i in r.items)
                this.prices.push({
                    id:r.items[i].code,
                    name:r.items[i].title
                });
        });
    },
    computed:{
        nameJSONFile() {
            return this.data.name;
        }
    },
    methods:{
        testLogin() {
            usam_active_loader();
            usam_api('parser/test/login/' + this.data.id, {id:this.data.id,login_page:this.data.login_page, authorization_parameters:this.data.authorization_parameters}, 'POST', (r) => this.resultTest = r);
        },
        testTags() {
            if (this.test_url) {
                usam_active_loader();
                usam_api('parser/test/' + this.data.id, {url:this.test_url}, 'POST', (r) => this.validation(r));
            }
        },
        validation(r) {
            this.dataTags = {};
            for (k in this.data.tags) {
                this.dataTags[k] = {
                    tag:r[k],
                    validate:false
                };
                if (r[k]) {
                    if (this.data.tags[k].plural) {
                        for (i in r[k])
                            this.dataTags[k].validate = this.validate(r[k][i], k);
                    } else
                        this.dataTags[k].validate = this.validate(r[k], k);
                }
            }
        },
        validate(d, k) {
            switch (this.data.tags[k].type) {
            case 'url':
                return validateURL(d);
                break;
            case 'number':
                return Number.isInteger(d);
                break;
            }
            return true;
        },
        deleteItem() {
            if (this.data.id)
                usam_api('parser/' + this.data.id, 'DELETE', this.afterDelete);
        },
        saveForm() {
            var data = structuredClone(this.data);
            if (this.data.id) {
                usam_active_loader();
                usam_api('parser/' + this.data.id, data, 'POST', usam_admin_notice);
            } else {
                usam_api('parser', data, 'POST', (id) => this.afterAdding(id));
            }
        }
    }
}

var changeProductProperties = {
    methods:{
        processProduct(r) {
            this.processProperties(r.attributes);
        },
        processProperties(attrs) {
            let p = [];
            attrs = this.propertyProcessing(attrs);
            for (i in attrs) {
                if (attrs[i].parent == 0) {
                    p.push(attrs[i]);
                    for (let j in attrs) {
                        if (attrs[i].term_id == attrs[j].parent)
                            p.push(attrs[j]);
                    }
                }
            }
            this.properties = p;
            for (let k in this.properties)
                this.$watch(['properties', k].join('.'), this.propertyChange, {
                    deep:true
                });
        }       
    }
}

var mediaBrowser = {
    data() {
        return {
            images:[],
            tab:'images',
            open:false,
            zoom:false,
            image_key:0,
            fullScreen:false,
            title:'',
        };
    },
    watch:{
        open() {
            this.zoom = false;
        }
    },
    created() {
        this.setScreen();
        document.addEventListener("webkitfullscreenchange", this.setScreen);
        document.addEventListener("mozfullscreenchange", this.setScreen);
        document.addEventListener("fullscreenchange", this.setScreen);
    },
    methods:{
        setScreen(e) {
            this.fullScreen = document.fullscreenElement ? true :false;
        },
        fullScreenChange(e) {
            if (document.fullscreenElement)
                document.exitFullscreen();
            else
                document.documentElement.requestFullscreen();
        }
    }
}

var files = {
    data() {
        return {
            files:[],
            cFile:{
                type:'loaded'
            },
        };
    },
    methods:{
        fDownload() {
            usam_api('files', this.cFile, 'POST', (r) => this.files = r.items);
        },
        fDelete(k) {
            this.files.splice(k, 1);
        },
        fDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('over');
            this.fUpload(e.dataTransfer.files);
        },
        fAttach(e) {
            let el = e.target.querySelector('input[type="file"]');
            if (el)
                el.click();
            else if (e.currentTarget.nextElementSibling)
                e.currentTarget.nextElementSibling.click();
        },
        aDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.add('over');
        },
        fChange(e) {
            if (!e.target.files[0])
                return;
            this.fUpload(e.target.files);
        },
        fUpload(f) {
            for (var i = 0; i < f.length; i++) {
                let k = this.files.length;
                Vue.set(this.files, k, {
                    name:'',
                    title:f[i].name,
                    size:formatFileSize(f[i].size),
                    icon:'',
                    percent:0,
                    load:true,
                    error:false
                });
                var fData = new FormData();
                fData.append('file', f[i]);
                for (let j in this.cFile)
                    fData.append(j, this.cFile[j]);
                usam_form_save(fData, (r) => Vue.set(this.files, k, r), (e) => this.files[k].percent = e.loaded * 100 / e.total, 'upload');
            }
        }
    }
}
var grid_view = {
    data() {
        return {
            columns:[],
            items:[],
            object_type:'',
            object_id:0,
            contact:{
                emails:{},
                phones:{}
            },
            sum:0,
            phone:{},
            request_counter:0,
            draggable:false
        };
    },
    computed:{
	   selectedItems() {
            return this.items.filter(x => x.checked);
        },
		numberSelectedItems() {
            return this.selectedItems.length;
        },
        formatted_sum() {
            return to_currency(this.sum, '');
        }
    },
    methods:{
        scrollGrid() {
            setTimeout(() => {
                if (screen.width <= this.columns.length * 320) {
                    const slider = document.querySelector('.grid_view_wrapper');
                    let mouseDown = false;
                    let startX,
                    scrollLeft;
                    let startDragging = (e) => {
                        if (!e.target.classList.contains("grid_item__row") && !e.target.closest('.grid_item__row')) {
                            mouseDown = true;
                            if (e.type == 'touchstart')
                                startX = e.changedTouches[0].pageX - slider.offsetLeft;
                            else
                                startX = e.pageX - slider.offsetLeft;
                            scrollLeft = slider.scrollLeft;
                        }
                    };
                    let stopDragging = (e) => mouseDown = false;
                    let moveDragging = (e) => {
                        if (!e.target.classList.contains("grid_item__row") && !e.target.closest('.grid_item__row') && mouseDown) {
                            if (e.type == 'touchmove')
                                x = e.changedTouches[0].pageX - slider.offsetLeft;
                            else
                                x = e.pageX - slider.offsetLeft;
                            slider.scrollLeft = scrollLeft - (x - startX);
                        }
                    }
                    slider.addEventListener('mousedown', startDragging, false);
                    slider.addEventListener('touchstart', startDragging, false);
                    slider.addEventListener('mousemove', moveDragging, false);
                    slider.addEventListener('touchmove', moveDragging, false);
                    slider.addEventListener('mouseup', stopDragging, false);
                    slider.addEventListener('touchend', stopDragging, false);
                    slider.addEventListener('mouseleave', stopDragging, false);
                }
            }, 100);
        },
        dataProcessing() {
            if (this.request_counter > 1) {
                let item,
                sum,
                number;
                for (let k in this.columns) {
                    item = [];
                    sum = 0;
                    number = 0;
                    for (let i in this.items) {
                        if (this.items[i].status == this.columns[k].internalname) {
                            this.sum += this.items[i].totalprice;
                            sum += this.items[i].totalprice;
                            number++;
                        }
                    }
                    Vue.set(this.columns[k], 'sum', sum);
                    Vue.set(this.columns[k], 'formatted_sum', to_currency(sum, ''));
                    Vue.set(this.columns[k], 'current_number', number);
                }
            }
        },
        allowDrop(e) {
            e.preventDefault();
            this.draggable = true;
            document.querySelectorAll('.drop_area').forEach((el) => {
                el.classList.remove('hover_drop');
            });
            var el = e.target.closest('.drop_area');
            if (el)
                el.classList.add('hover_drop');
        },
        drag(e, i, k) {
            e.currentTarget.style.opacity = '1';
            if (e.target.hasAttribute('draggable')) {
                e.dataTransfer.setData("item", i);
                e.currentTarget.classList.add('draggable');

                //elem = document.createElement("div").addClass('');
                //	document.querySelector('.grid_column .grid_items').prepend(elem);
            } else
                e.preventDefault();
        },
        dragEnd(e, i) {
            e.currentTarget.classList.remove('draggable');
            document.querySelectorAll('.drop_area').forEach((el) => {
                el.classList.remove('hover_drop');
            });
            this.draggable = false;
        },
        drop(e, k) {
            e.preventDefault();
            document.querySelectorAll('.drop_area').forEach((el) => {
                el.classList.remove('hover_drop');
            });
            this.draggable = false;
            if (this.numberSelectedItems) {
                for (let i in this.items)
                    if (this.items[i].checked) {
                        this.changeСolumn(k, i);
                        this.items[i].checked = false;
                    }
            } else {
                let i = e.dataTransfer.getData("item");
                this.changeСolumn(k, i);
            }
        },
        changeСolumn(k, i) {
            let id = this.columns[k].id;
            let old_k = '';
            for (let j in this.columns)
                if (this.columns[j].internalname == this.items[i].status) {
                    old_k = j;
                    break;
                }
            if (this.columns[k].internalname != this.items[i].status) {
                this.saveStatus(this.items[i].id, this.columns[k].internalname);
                this.columns[old_k].number--;
                this.columns[old_k].current_number--;
                this.columns[old_k].sum -= this.items[i].totalprice;
                this.columns[old_k].formatted_sum = to_currency(this.columns[old_k].sum, '');
                if (this.columns[k].close) {
                    this.sum -= this.items[i].totalprice;
                    this.items.splice(i, 1);
                } else {
                    this.columns[k].sum += this.items[i].totalprice;
                    this.columns[k].formatted_sum = to_currency(this.columns[k].sum, '');
                    this.columns[k].number++;
                    this.columns[k].current_number++;
                    this.items[i].status = this.columns[k].internalname;
                }
                return true;
            }
            return false;
        },
        saveStatus(id, status) {},
        localDate(date, format) {
            return local_date(date, format);
        },
        checked(k, e) {
            if (e.target.tagName !== 'A')
                this.items[k].checked = !this.items[k].checked
        },
        cancelSelected() {
            for (let i in this.items)
                this.items[i].checked = false;
        },
        openEmail(i) {
            this.contact.emails = this.items[i].emails;
            this.object_id = this.items[i].id;
            this.sidebar('sendemail', i);
        },
        addEmail(r) {
            this.sidebar('sendemail');
        },
        openSMS(i) {
            this.contact.phones = this.items[i].phones;
            this.object_id = this.items[i].id;
            this.sidebar('sendsms', i);
        },
        addSMS(r) {
            this.sidebar('sendsms');
        },
        sidebar(type, k) {
            this.elKey = k;
            this.$refs['modal' + type].show = !this.$refs['modal' + type].show;
            this.sidebarActive = this.$refs['modal' + type].show ? type :false;
        },
        getConnections(type, properties) {
            let v = {};
            for (let k in properties) {
                if (properties[k].field_type == type && properties[k].value) {
                    let code = properties[k].hidden ? properties[k].private :properties[k].value;
                    v[code] = properties[k].value;
                }
            }
            return v;
        },
        call(p, i) {
            this.object_id = this.items[i].id;
            this.phone = {
                number:p.hidden ? p.private :p.value,
                display:p.value
            }
        }
    }
}

var grid_view_event = {
    data() {
        return {
            query_vars:{},
            today_start:'',
            today_end:'',
            current_date:'',
            tomorrow_end_day:''
        };
    },
    beforeMount() {
        this.columns = USAM_Grid.columns;
        this.scrollGrid();
        this.requestData();
    },
    methods:{
        requestData(data) {
            if (data == undefined)
                data = {};
            else
                usam_active_loader();
            this.items = [];
            for (let k in this.columns)
                Vue.set(this.columns[k], 'number', 0);
            data.add_fields = 'last_comment';
            data.fields = ['users', 'reminder', 'author'];
            data.count = 1000;
            data.status = ['started', 'not_started'];
            Object.assign(data, this.query_vars);
            usam_api('events', data, 'POST', (r) => {
                for (let k in r.items)
                    r.items[k].checked = false;
                this.items = r.items;
                this.current_date = new Date();
                this.today_start = new Date(this.current_date.getFullYear(), this.current_date.getMonth(), this.current_date.getDate(), 0, 0, 0, 0);
                this.today_end = new Date(this.current_date.getFullYear(), this.current_date.getMonth(), this.current_date.getDate(), 23, 59, 0, 0);
                this.tomorrow_end_day = new Date(this.current_date.getFullYear(), this.current_date.getMonth(), this.current_date.getDate() + 1, 23, 59, 0, 0);
                var future_end_day = new Date(this.current_date.getFullYear(), this.current_date.getMonth(), this.current_date.getDate() + 6, 23, 59, 0, 0);
                var event_start,
                event_end;
                for (let k in this.items) {
                    this.items[k].start = event_start = new Date(this.items[k].start);
                    this.items[k].end = event_end = new Date(this.items[k].end);
                    Vue.set(this.items[k], 'start_hour', local_date(event_start, 'H:i'));
                    Vue.set(this.items[k], 'end_hour', local_date(event_end, 'H:i'));
                    if (event_end < this.today_start) {
                        Vue.set(this.items[k], 'column', 'overdue');
                        this.columns.overdue.number++;
                    } else if (this.today_start <= event_start && this.today_end >= event_start || this.today_start >= event_start && this.today_start <= event_end) {
                        Vue.set(this.items[k], 'column', 'day');
                        this.columns.day.number++;
                    } else if (this.today_end < event_start && this.tomorrow_end_day > event_start || this.today_end > event_start && this.today_end < event_end) {
                        Vue.set(this.items[k], 'column', 'tomorrow');
                        this.columns.tomorrow.number++;
                    } else if (event_start >= this.tomorrow_end_day && event_start <= future_end_day) {
                        Vue.set(this.items[k], 'column', 'future');
                        this.columns.future.number++;
                    }
                }
            });
        },
        allowDrop(e, k) {
            if (k == 'overdue')
                return false;
            e.preventDefault();
            this.draggable = true;
        },
        changeСolumn(k, i) {
            let id = this.columns[k].id;
            let old_k = this.items[i].column;
            if (this.items[i].column != k) {
                this.updateEvent(i, {
                    start:this.columns[k].start,
                    end:this.columns[k].end
                });
                this.columns[old_k].number--;
                this.columns[k].number++;
                this.items[i].start = this.columns[k].start;
                this.items[i].end = this.columns[k].end;
                this.items[i].display_end = local_date(this.columns[k].end);
                this.items[i].column = k;
            }
            return false;
        },
        dropStatus(e) {
            e.preventDefault();
            document.querySelectorAll('.drop_area').forEach((el) => {
                el.classList.remove('hover_drop');
            });
            this.draggable = false;
            if (this.numberSelectedItems) {
                for (let i in this.items)
                    if (this.items[i].checked) {
                        this.changeStatus(i);
                        this.items[i].checked = false;
                    }
            } else {
                let i = e.dataTransfer.getData("item");
                this.changeStatus(i);
            }
        },
        changeStatus(i) {
            let old_k = this.items[i].column;
            this.updateEvent(i, {
                status:'completed'
            });
            this.columns[old_k].number--;
            this.items.splice(i, 1);
        },
        updateEvent(i, data) {
            this.items[i] = Object.assign(this.items[i], data);
            usam_api('event/' + this.items[i].id, data, 'POST');
        }
    }
}

var shipped_document = {
    computed:{
        storagePickup() {
            for (let k in this.delivery) {
                if (this.data.method == this.delivery[k].id)
                    return this.delivery[k];
            }
            return '';
        },
    },
    methods:{
        orderProduct(i, key) {
            for (let j in this.products)
                if (this.data.products[i].product_id == this.products[j].product_id && this.data.products[i].unit_measure == this.products[j].unit_measure)
                    return this.products[j][key];
            return '';
        },
        addProduct(j) {
            var ok = false;
            for (let i in this.data.products)
                if (this.data.products[i].product_id == this.products[j].product_id && this.data.products[i].unit_measure == this.products[j].unit_measure) {
                    ok = true;
                    if (this.data.products[i].quantity < this.products[j].quantity) {
                        this.data.products[i].quantity++;
                        this.data.products[i].reserve = this.data.products[i].quantity;
                    }
                    break;
                }
            if (!ok) {
                var p = structuredClone(this.products[j]);
                p.reserve = p.quantity;
                p.storage = '';
                this.data.products.push(p);
            }
        },
        statusStyle(d, type) {
            var style = '';
            for (let k in this.statuses)
                if (d.status == this.statuses[k].internalname && this.statuses[k].type == type) {
                    style = this.statuses[k].color ? 'background:' + this.statuses[k].color + ';' :'' + this.statuses[k].text_color ? 'color:' + this.statuses[k].text_color + ';' :'';
                    break;
                }
            return style;
        },
        statusName(d, type) {
            var name = '';
            for (let k in this.statuses)
                if (d.status == this.statuses[k].internalname && this.statuses[k].type == type) {
                    name = this.statuses[k].name
                        break;
                }
            return name;
        },
        localDate(date, format) {
            return local_date(date, format);
        },
        addReserve() {
            for (let k in this.data.products)
                this.data.products[k].reserve = this.data.products[k].quantity;
        },
        addOrderTransportCompany() {
            usam_api('shipped/order/transport/' + this.data.id, 'GET', (id) => {
                let url = new URL(document.location.href);
                url.searchParams.set('form_name', 'movement');
                url.searchParams.set('form', 'edit');
                url.searchParams.set('id', id);
                window.location.replace(url.href);
            });
        },
        deleteShipped() {
            this.data.status = 'delete';
        },
        sendTracking() {
            usam_api('shipped/tracking/' + this.data.id, 'GET', (r) => usam_notifi({
                    'text':r ? 'Отправлено' :'Не отправлено'
                }));
        },
        recalculateShipped() {
            usam_api('shipped/recalculate/' + this.data.id, 'GET', (price) => {
                usam_notifi({
                    'text':'Пересчитано'
                });
                this.data.price = price;
            });
        },
        createMove() {
            usam_api('shipped/move/' + this.data.id, 'GET', (id) => {
                let url = new URL(document.location.href);
                url.searchParams.set('form_name', 'movement');
                url.searchParams.set('form', 'edit');
                url.searchParams.set('id', id);
                window.location.replace(url.href);
            });
        },
    }
}

var order_document = {
    mixins:[formTools],
    data() {
        return {
            storages_stock:{},
            cancellation_reason:'',
            total_paid:0,
            manager:{},
            contact:{},
            statuses:[],
            files:{},
            units:{},
            bank_accounts:[],
            payment_gateways:[],
            user_columns:{
                'shipped':{},
                'order':{},
                'payment':{}
            },
            shippeds:[],
            orders_contractor:null,
            contractors:{},
            storages:{},
            delivery_problems:{},
            couriers:{},
            delivery:[],
            payment_types:{},
            payments:null,
            table_name:'order',
            crm_type:'order',
            edit_form:false,
            edit_data:false,
            add_shipping:0,
            change_shipping:0,
            bonuses:0,
            bonus_card:{},
            addresses:[],
            payers:[],
            rounding:'',
            updateShippeds:false,
            updatePayments:false,
            updateOrdersSupplier:false,
            prevent_notification:false,
            subtab:''
        }
    },
    computed:{
        accruedBonuses() {
            var b = 0;
            for (let k in this.products)
                b += this.products[k].bonus
                return b;
        },
        change_payment() {
            if (this.payments === null)
                return '0';
            var p = this.payments.filter(x => x.status == 1);
            return p.length ? p[0].id :0;
        },
        payment_required() {
            return this.data.totalprice - this.total_paid;
        },
        possibility_pay() {
            if (!this.data.date_pay_up)
                return false;
            let c = new Date();
            let p = new Date(this.data.date_pay_up);
            return c.getTime() > p.getTime();
        },
        changeHistoryArgs() {
            return {
                object_type:'order',
                object_id:this.data.id
            };
        },
        typePayer() {
            if (this.payers.length) {
                var p = this.payers.filter(x => x.id === this.data.type_payer);
                return p[0].type;
            }
            return 'contact';
        }
    },
    created() {
        this.data = form_data;
    },
    mounted() {
        let url = new URL(document.location.href);
        this.subtab = url.searchParams.get('subtab');
        this.rounding = this.data.rounding;
        this.loadData();
        this.pLoaded = true;
        if (this.data.id) {
            this.product_taxes = this.data.product_taxes;
            let j = 0;
            for (let k in form_data.products) {
                form_data.products[k].discount = form_data.products[k].old_price > 0 ? 100 - form_data.products[k].price * 100 / form_data.products[k].old_price :0;
                form_data.products[k].discount = form_data.products[k].discount.toFixed(4);
                form_data.products[k].type = 'p';
                j++;
            }
            this.products = form_data.products;
        }
        usam_api('type_prices', {
            type:'R'
        }, 'GET', (r) => this.type_prices = r.items);

        if (this.data.contact_id)
            usam_api('addresses', {
                contact_id:this.data.contact_id
            }, 'POST', (r) => this.addresses = r.items);
        if (this.data.id) {
            var sum = 0;
            usam_api('payments', {
                document_id:this.data.id
            }, 'POST', (r) => {
                for (let k in r.items) {
                    if (r.items[k].status == 3)
                        sum += r.items[k].sum;
                    r.items[k].edit = false;
                    r.items[k].toggle = false;
                }
                this.total_paid = sum;
                this.payments = r.items
                    this.$watch('payments', this.changePayments, {
                        deep:true
                    });
            });
            usam_api('shippeds', {
                order_id:this.data.id,
                add_fields:['document_products', 'storage_data']
            }, 'POST', (r) => this.shippeds = r.items);
            const ob = new IntersectionObserver((es, o) => {
                es.forEach((e) => {
                    if (e.isIntersecting) {
                        usam_api('companies', {
                            type:'contractor',
                            fields:'id=>data',
                            orderby:'name',
                            count:1000
                        }, 'POST', (r) => this.contractors = r.items);
                        usam_api('orders_contractor', {
                            child_document:{
                                id:this.data.id,
                                type:'order'
                            },
                            add_fields:['document_products', 'note'],
                            count:1000
                        }, 'POST', (r) => this.orders_contractor = r.items);
                        o.unobserve(e.target);
                    }
                })
            }, {
                rootMargin:'0px 0px 50px 0px'
            });
            if (document.getElementById('orders_contractor'))
                ob.observe(document.getElementById('orders_contractor'));
            else if (document.getElementById('add_orders_spplier'))
                ob.observe(document.getElementById('add_orders_spplier'));
        }
        this.recountProducts();
        usam_api('statuses', {type:['order', 'payment', 'shipped', 'order_contractor']}, 'GET', (r) => this.statuses = r);
        usam_api('employees', {role__in:['courier'],source:'all',fields:'user_id=>name'}, 'POST', (r) => this.couriers = r.items);
        usam_api('delivery/problems', 'GET', (r) => this.delivery_problems = r);
        usam_api('delivery/services', {order_id:this.data.id}, 'GET', (r) => this.delivery = r);
        usam_api('tables/columns', {types:['order', 'payment', 'shipped', 'order_contractor']}, 'POST', (r) => this.user_columns = r);
        usam_api('units', {fields:'code=>short'}, 'GET', (r) => this.units = r);
        usam_api('accounts', {company_type:'own', fields:'id=>data', add_fields:['bank_account_name']}, 'POST', (r) => this.bank_accounts = r.items);
        if (this.data.user_ID)
            usam_api('bonus/cards', {user_id:this.data.user_ID}, 'GET', (r) => {
                if (r.items.length)
                    this.bonus_card = r.items[0]
            });
    },
    methods:{
        loadData() {
            usam_api('order/' + this.data.id, {
                add_fields:'storages_stock,files,properties,groups,manager,contact'
            }, 'GET', (r) => {
                this.preparationData(r);
                r.contact.phones = this.getConnections('mobile_phone')
                    r.contact.emails = this.getConnections('email')
                    for (k of['manager', 'contact', 'files']) {
                        this[k] = structuredClone(r[k]);
                        delete r[k];
                    }
                    Vue.set(this, 'storages_stock', r.storages_stock);
                Vue.set(this, 'storages', r.storages);
            });
            usam_api('types_payers', 'GET', (r) => this.payers = r.items);
        },
        saveForm() {
            if (this.data.id) {
                usam_active_loader();
                var data = structuredClone(this.data);
                if (this.bonuses) {
                    data.bonuses = this.bonuses;
                    this.bonus_card.sum -= this.bonuses
                    this.data.used_bonuses += parseInt(this.bonuses);
                    this.bonuses = 0;
                }
                data.properties = this.getValues();
                if (this.prevent_notification)
                    data.prevent_notification = 1;
                usam_api('order/' + this.data.id, data, 'POST', (r) => {
                    this.savePayment();
                    this.saveShipped();
                });
            } else
                usam_api('order', this.data, 'POST', (id) => this.afterAdding(id));
        },
        changePayments() {
            var date_paid = local_date(new Date(), "Y-m-d H:i", false);
            var sum = 0;
            for (let i in this.payments) {
                if (this.payments[i].status == 3) {
                    if (this.payments[i].date_payed == '')
                        this.payments[i].date_payed = local_date(new Date(), "Y-m-d H:i", false);
                    sum += this.payments[i].sum;
                    if (!date_paid)
                        date_paid = this.payments[i].date_payed;
                } else
                    this.payments[i].date_payed = '';
                if (this.payments[i].gateway_id)
                    for (let j in this.payment_gateways)
                        if (this.payment_gateways[j].id == this.payments[i].gateway_id)
                            this.payments[i].name = this.payment_gateways[j].name;
            }
            this.total_paid = sum;
            if (this.total_paid >= this.data.totalprice) {
                if (this.data.paid != 2) {
                    this.data.paid = 2;
                    this.data.date_paid = date_paid;
                }
            } else if (this.total_paid > 0) {
                if (this.data.paid != 1) {
                    this.data.paid = 1;
                    this.data.date_paid = '';
                }
            } else if (this.total_paid <= 0) {
                if (this.data.paid != 0) {
                    this.data.paid = 0;
                    this.data.date_paid = '';
                }
            }
        },
        to_currency(s) {
            return to_currency(s, '', this.rounding);
        },
        addPayment() {
            if (this.data.id) {
                const d = new Date();
                var p = {
                    edit:true,
                    toggle:false,
                    number:'',
                    id:0,
                    bank_account_id:this.data.bank_account_id,
                    payment_type:'card',
                    gateway_id:0,
                    document_id:this.data.id,
                    manager_id:0,
                    name:'',
                    status:1,
                    date_payed:'',
                    transactid:'',
                    external_document:'',
                    sum:this.data.totalprice,
                    date_insert:local_date(d, "Y-m-d H:i:s")
                }
                if (this.payment_gateways.length) {
                    p.gateway_id = this.payment_gateways[0].id;
                    p.name = this.payment_gateways[0].name;
                }
                this.payments.push(p);
            }
        },
        deletePayment(i) {
            this.payments[i].status = 'delete';
        },
        savePayment() {
            if (this.updatePayments)
                return false;
            this.updatePayments = true;
            var items = [];
            var deleteItems = [];
            for (let i in this.payments)
                if (this.payments[i].status === 'delete')
                    deleteItems.push(this.payments[i].id);
                else
                    items.push(this.payments[i]);
            if (items.length)
                usam_api('payments', {
                    items:items
                }, 'PUT', (r) => {
                    for (let i in this.payments)
                        if (this.payments[i].id === 0) {
                            this.payments[i].id = r[i].id;
                            this.payments[i].number = r[i].number;
                        }
                    usam_admin_notice(r);
                    this.updatePayments = false;
                });
            if (deleteItems.length)
                usam_api('payments', {
                    args:{
                        include:deleteItems
                    }
                }, 'DELETE', (r) => {
                    usam_admin_notice();
                    this.updatePayments = false;
                });
        },
        saveShipped() {
            if (this.updateShippeds)
                return false;
            this.updateShippeds = true;
            var items = [];
            var deleteItems = [];
            for (let i in this.shippeds)
                if (this.shippeds[i].status === 'delete')
                    deleteItems.push(this.shippeds[i].id);
                else
                    items.push(this.shippeds[i]);
            if (items.length)
                usam_api('shippeds', {
                    items:items
                }, 'PUT', (r) => {
                    for (let i in this.shippeds)
                        if (this.shippeds[i].id === 0)
                            this.shippeds[i] = Object.assign(this.shippeds[i], r[i]);
                    usam_admin_notice(r);
                    this.updateShippeds = false;
                });
            if (deleteItems.length)
                usam_api('shippeds', {
                    args:{
                        include:deleteItems
                    }
                }, 'DELETE', (r) => {
                    usam_admin_notice();
                    this.updateShippeds = false;
                });
        },
        addShipped() {
            if (this.data.id) {
                const d = new Date();
                var gateway_id = 0;
                if (this.payment_gateways.length)
                    gateway_id = this.payment_gateways[0].id;
                var products = [];
                for (let j in this.products) {
                    var p = structuredClone(this.products[j]);
                    p.reserve = p.quantity;
                    p.storage = '';
                    products.push(p);
                }
                var shipped = {
                    number:'',
                    id:0,
                    bank_account_id:this.data.bank_account_id,
                    order_id:this.data.id,
                    manager_id:0,
                    name:'',
                    status:'pending',
                    external_document:'',
                    price:0,
                    totalprice:0,
                    tax_value:0,
                    date_insert:local_date(d, "Y-m-d H:i:s"),
                    method:'',
                    customer_type:'',
                    customer_id:0,
                    seller_id:0,
                    type_price:this.data.type_price,
                    track_id:'',
                    courier:'',
                    storage_pickup:'',
                    storage:'',
                    include_in_cost:0,
                    tax_id:0,
                    products:products
                }
                this.shippeds.push(shipped);
            }
        },
        selectStorage(e) {
            for (let i in this.shippeds) {
                if (this.sidebardata.id == this.shippeds[i].id) {
                    this.shippeds[i][this.sidebardata.code] = e.id;
                    this.shippeds[i][this.sidebardata.code + '_data'] = e;
                    break;
                }
            }
        },
        saveOrderSupplier() {
            if (this.updateOrdersSupplier || this.data.id === 0)
                return false;
            this.updateOrdersSupplier = true;
            var items = [];
            var deleteItems = [];
            for (let i in this.orders_contractor)
                if (this.orders_contractor[i].status === 'delete')
                    deleteItems.push(this.orders_contractor[i].id);
                else {
                    var data = structuredClone(this.orders_contractor[i]);
                    if (this.orders_contractor[i].id == 0) {
                        data.links = [{
                                document_id:this.data.id,
                                document_type:'order'
                            }
                        ];
                    }
                    items.push(data);
                }

            if (items.length)
                usam_api('orders_contractor', {items:items}, 'PUT', (r) => {
                    for (let i in this.orders_contractor)
                        if (this.orders_contractor[i].id === 0)
                            this.orders_contractor[i] = Object.assign(this.orders_contractor[i], r[i]);
                    usam_admin_notice(r);
                    this.updateOrdersSupplier = false;
                });
            if (deleteItems.length)
                usam_api('orders_contractor', {args:{include:deleteItems}}, 'DELETE', (r) => {
                    usam_admin_notice();
                    this.updateOrdersSupplier = false;
                });
        },
        addOrdersSpplier() {
            if (this.orders_contractor === null)
                return false;
            var su = [];
            var add;
            for (let i in this.products)
                if (this.products[i].contractor && !su.includes(this.products[i].contractor)) {
                    add = true;
                    for (let j in this.orders_contractor)
                        if (this.orders_contractor[j].customer_id == this.products[i].contractor) {
                            add = false;
                            break;
                        }
                    if (add)
                        su.push(this.products[i].contractor);
                }
            if (su.length) {
                for (let i in su) {
                    var products = [];
                    for (let j in this.products)
                        if (this.products[j].contractor == su[i])
                            products.push(structuredClone(this.products[j]));
                    this.pushOrderSupplier(su[i], products);
                }
                this.saveOrderSupplier();
            }
        },
        addOrderSupplier() {
            var ids = Object.keys(this.contractors);
            if (ids.length) {
                var products = [];
                for (let j in this.products)
                    products.push(structuredClone(this.products[j]));
                this.pushOrderSupplier(this.contractors[ids[0]].id, products);
            }
        },
        pushOrderSupplier(customer_id, products) {
            if (this.data.id) {
                const d = new Date();
                var n = {
                    number:'',
                    id:0,
                    bank_account_id:this.data.bank_account_id,
                    manager_id:0,
                    name:'',
                    status:'draft',
                    external_document:'',
                    price:0,
                    totalprice:0,
                    tax_value:0,
                    date_insert:local_date(d, "Y-m-d H:i:s"),
                    type:'order_contractor',
                    customer_type:'company',
                    customer_id:customer_id,
                    type_price:this.data.type_price,
                    track_id:'',
                    products:products
                }
                this.orders_contractor.push(n);
            }
        },
        statusStyle(d, type) {
            var style = '';
            for (let k in this.statuses)
                if (d.status == this.statuses[k].internalname && this.statuses[k].type == type) {
                    style = this.statuses[k].color ? 'background:' + this.statuses[k].color + ';' :'' + this.statuses[k].text_color ? 'color:' + this.statuses[k].text_color + ';' :'';
                    break;
                }
            return style;
        },
        statusName(d, type) {
            var name = '';
            for (let k in this.statuses)
                if (d.status == this.statuses[k].internalname && this.statuses[k].type == type) {
                    name = this.statuses[k].name
                        break;
                }
            return name;
        },
        change_user_id(e) {
            this.data.user_ID = e.id;
        },
        objectStatus(e) {
            this.save({
                status:this.data.status
            });
        },
        saveElement(e) {
            var data = {
                products:this.products,
                change_payment:this.change_payment,
                coupon_name:this.data.coupon_name
            };
            if (this.bonuses) {
                data.bonuses = this.bonuses;
                this.bonus_card.sum -= this.bonuses
                this.data.used_bonuses += parseInt(this.bonuses);
                this.bonuses = 0;
            }
            if (this.add_shipping) {
                data.add_shipping = this.add_shipping;
                this.add_shipping = 0;
            } else
                data.change_shipping = this.change_shipping;
            this.save(data);
        },
        selectBuyer(item) {
            if (this.typePayer)
                usam_api(this.typePayer + '/' + item.id, {
                    add_fields:['order_properties']
                }, 'GET', (r) => this.properties = r.properties);
        },
        save_customer(e) {
            let data = {
                properties:this.getValues(),
                user_ID:this.data.user_ID,
                type_payer:this.data.type_payer
            };
            this.save(data);
        },
        change_document(e) {
            this.data[e.code] = e.id;
        },
        load_customer_details(customer_id, customer_type) {
            for (let k in this.payers) {
                if (this.payers[k].type == customer_type)
                    this.data.type_payer = this.payers[k].id;
            }
            usam_api('order/customer/load', {
                customer_id:customer_id,
                customer_type:customer_type
            }, 'GET', (r) => {
                this.properties = this.propertyProcessing(r.properties)
                    delete r.properties;
                this.data = Object.assign(this.data, r);
                this.edit_data = true;
            });
        },
        save(data) {
            if (this.prevent_notification)
                data.prevent_notification = 1;
            usam_api('order/' + this.data.id, data, 'POST', usam_admin_notice);
        },
        selectStatus() {
            if (this.data.status != 'canceled' || this.data.cancellation_reason !== '')
                this.save({
                    status:this.data.status,
                    cancellation_reason:this.data.cancellation_reason
                });
        },
        selectManager() {
            this.save({
                manager_id:this.data.manager_id
            });
        },
        deleteItem() {
            if (this.data.id > 0)
                usam_api('order/' + this.data.id, 'DELETE', this.afterDelete);
        },
        calculate_totalprice(sum) {
            this.totalprice = sum + this.data.shipping;
        },
        requestData(data, e) {
            if (this.subtab == 'report')
                this.loadReport();
            else {
                usam_active_loader();
                USAM_Tabs.table_view(data, jQuery('.usam_tab_table'));
            }
        },
        pushElement(item) {
            this.addToDocument(item);
        },
        getElementQuery() {
            return {
                add_fields:['small_image', 'sku', 'price', 'unit_measure', 'units', 'bonus'],
                type_price:this.data.type_price
            };
        },
        addUser(item) {
            this.addManager(item);
        }
    }
}

var lead_document = {
    mixins:[formTools],
    data() {
        return {
            prevent_notification:false,
            crm_type:'lead',
            propertyСhange:'',
            edit:false,
            edit_data:false,
            units:[],
            contact:{},
            manager:{},
            type_prices:[],
            bank_accounts:[],
            statuses:[],
            payers:[],
            rounding:''
        }
    },
    computed:{
        changeHistoryArgs() {
            return {
                object_type:'lead',
                object_id:this.data.id
            };
        },
        typePayer() {
            if (this.payers.length) {
                var p = this.payers.filter(x => x.id === this.data.type_payer);
                return p[0].type;
            }
            return 'contact';
        }
    },
    created() {
        this.data = form_data;
        this.loadProperties();
    },
    mounted() {
        for (let k in this.data)
            this.$watch('data.' + k, (val, oldVal) => this.changed = val !== oldVal);
        this.rounding = this.data.rounding;
        this.pLoaded = true;
        if (this.data.id) {
            this.product_taxes = this.data.product_taxes;
            let j = 0;
            for (let k in form_data.products) {
                form_data.products[k].discount = form_data.products[k].old_price > 0 ? 100 - form_data.products[k].price * 100 / form_data.products[k].old_price :0;
                form_data.products[k].discount = form_data.products[k].discount.toFixed(4);
                form_data.products[k].type = 'p';
                j++;
            }
            this.products = form_data.products;
        }
        usam_api('type_prices', {
            type:'R'
        }, 'GET', (r) => this.type_prices = r.items);
        usam_api('statuses', {
            type:'lead'
        }, 'GET', (r) => this.statuses = r);
        usam_api('accounts', {company_type:'own', fields:'id=>data', add_fields:['bank_account_name']}, 'POST', (r) => this.bank_accounts = r.items);
        usam_api('units', {fields:'code=>short'}, 'GET', (r) => this.units = r);
    },
    methods:{
        loadProperties() {
            if (!this.data.id) {
                usam_api('property_groups', {
                    type:'order'
                }, 'POST', (r) => this.propertyGroups = r.items);
                usam_api('properties', {
                    type:'order',
                    fields:'code=>data'
                }, 'POST', (r) => this.properties = this.propertyProcessing(r.items));
            } else
                usam_api('lead/' + this.data.id, {
                    add_fields:'properties,document_products,groups,manager,contact'
                }, 'GET', (r) => {
                    this.preparationData(r)
                    r.contact.phone = this.getConnections('mobile_phone')
                        r.contact.emails = this.getConnections('email')
                        for (k of['manager', 'contact']) {
                            this[k] = structuredClone(r[k]);
                            delete r[k];
                        }
                        this.data = Object.assign(this.data, r);
                });
            usam_api('types_payers', 'GET', (r) => this.payers = r.items);
        },
        change_user_id(e) {
            this.data.user_ID = e.id;
        },
        selectStatus() {
            this.changed = true;
            this.save({
                status:this.data.status
            });
        },
        selectManager() {
            this.save({
                manager_id:this.data.manager_id
            });
        },
        selectBuyer(item) {
            if (this.typePayer)
                usam_api(this.typePayer + '/' + item.id, {
                    add_fields:['order_properties']
                }, 'GET', (r) => this.properties = r.properties);
        },
        saveForm(add) {
            var data = structuredClone(this.data);
            data.properties = this.getValues();
            this.changed = true;
            if (this.prevent_notification)
                data.prevent_notification = 1;
            if (this.data.id)
                usam_api('lead/' + this.data.id, data, 'POST', (r) => {
                    if (add === true)
                        this.addNew();
                    usam_admin_notice(r);
                });
            else
                usam_api('lead', data, 'POST', (id) => {
                    if (add === true)
                        this.addNew();
                    else
                        this.afterAdding(id);
                });
        },
        save(data) {
            if (this.changed) {
                this.changed = false;
                if (this.prevent_notification)
                    data.prevent_notification = 1;
                usam_api('lead/' + this.data.id, data, 'POST', usam_admin_notice);
            }
        },
        save_customer(e) {
            let data = {
                properties:this.getValues(),
                user_ID:this.data.user_ID,
                type_payer:this.data.type_payer
            };
            usam_api('lead/' + this.data.id, data, 'POST', usam_admin_notice);
        },
        change_document(e) {
            this.data[e.code] = e.id;
        },
        deleteItem() {
            if (this.data.id > 0)
                usam_api('lead/' + this.data.id, 'DELETE', this.afterDelete);
        },
        load_customer_details(customer_id, customer_type) {
            for (let k in this.payers) {
                if (this.payers[k].type == customer_type)
                    this.data.type_payer = this.payers[k].id;
            }
            usam_api('order/customer/load', {customer_id:customer_id, customer_type:customer_type}, 'GET', (r) => {
                this.properties = this.propertyProcessing(r.properties)
                    delete r.properties;
                this.data = Object.assign(this.data, r);
                this.edit_data = true;
            });
        },
        requestData(data, e) {
            if (this.form_tab == 'report')
                this.loadReport();
        },
        pСhange(p) {
            this.propertyСhange = p;
            var f = (e) => {
                if (e.target.tagName !== 'INPUT') {
                    document.removeEventListener("click", f);
                    let data = {};
                    data[this.propertyСhange] = this.data[this.propertyСhange];
                    this.save(data);
                    this.propertyСhange = '';
                }
            };
            setTimeout(() => {
                document.addEventListener("click", f);
            }, 30);
        },
        addUser(item) {
            this.addManager(item);
        }
    }
}

var form_document = {
    mixins:[formTools],
    data() {
        return {
            data:{},
            edit:false,
            crm_type:'',
            cFile:{
                type:'document'
            },
            units:{},
            manager:{},
            contact:{},
            type_prices:[],
            bank_accounts:[],
            contacts:[],
            statuses:[],
            args_contacts:{
                add_fields:['foto']
            },
            args_contracts:{},
            storages_args:{
                add_fields:['city', 'address']
            },
            externalDocument:false,
        }
    },
    computed:{
        changeHistoryArgs() {
            return {
                object_type:this.data.type,
                object_id:this.data.id
            };
        }
    },
    created() {
        let url = new URL(document.location.href);
        this.id = url.searchParams.get('id');
        this.data = form_data;
        this.crm_type = this.data.type;
        this.rounding = this.data.rounding;
        if (this.data.id > 0)
            usam_api('contacts', {document_ids:[this.data.id], source:'all', number:20, add_fields:['foto']}, 'POST', (r) => this.contacts = r.items);
        usam_api('statuses', {type:this.data.type}, 'GET', (r) => this.statuses = r);
        usam_api('accounts', {company_type:'own', fields:'id=>data', add_fields:['bank_account_name']}, 'POST', (r) => this.bank_accounts = r.items);
        this.args_contracts = this.data.customer_type === 'company' ? {companies:[this.data.customer_id]} :{contacts:[this.data.customer_id]};
    },
    methods:{
        loadTableData() {
            usam_api('type_prices', {
                type:'R'
            }, 'GET', (r) => this.type_prices = r.items);
            usam_api('units', {
                fields:'code=>short'
            }, 'GET', (r) => this.units = r);
        },
        loadFileManagement() {
            this.cFile.object_id = this.data.id;
            if (document.querySelector('.usam_attachments') && this.data.id)
                this.fDownload();
        },
        enableSavingChange() {
            this.$watch('data.manager_id', this.selectManager);
            this.$watch('data.status', this.selectStatus);
        },
        selectStatus() {
            this.saveDocument({
                status:this.data.status
            });
        },
        selectManager() {
            this.saveDocument({
                manager_id:this.data.manager_id
            });
        },
        saveProducts(e) {
            var data = {
                products:this.data.products
            };
            this.saveDocument(data);
        },
        selectStorage(e) {
            this.storage = e;
            this.data.store_id = e.id;
        },
        selectContract(e) {
            this.contract = e;
            this.data.contract = e.id;
        },
        addCompany(e) {
            this.company = e;
            this.data.customer_id = e.id;
        },
        addContacts(item) {
            var add = true;
            for (let k in this.contacts)
                if (this.contacts[k].id === item.id) {
                    add = false;
                    break;
                }
            if (add)
                this.contacts.push(item);
        },
        deleteContact(k) {
            this.contacts.splice(k, 1);
        },
        saveForm(e) {
            e.preventDefault();
            var data = structuredClone(this.data);
            if (typeof tinyMCE !== typeof undefined) {
                var t = tinyMCE.get('document_content');
                if (t !== null)
                    data.document_content = t.getContent();
            }
            data.contacts = [];
            for (let k in this.contacts)
                data.contacts.push(this.contacts[k].id);
            if (this.data.id)
                this.saveDocument(data);
            else
                usam_api(this.data.type, data, 'POST', (id) => {
                    this.afterAdding(id);
                });
        },
        saveDocument(data) {
            usam_api(this.data.type + '/' + this.data.id, data, 'POST', usam_admin_notice);
        },
        deleteItem(data) {
            if (this.data.id > 0)
                usam_api(this.data.type + '/' + this.data.id, 'DELETE', this.afterDelete);
        },
        addAct() {
            usam_active_loader();
            usam_api('acts', {
                fields:'id',
                child_document:{
                    id:this.data.id,
                    type:this.data.type
                },
                number:1
            }, 'POST', (r) => {
                if (r.items.length)
                    this.setUrl('act', r.items[0]);
                else {
                    var data = structuredClone(this.data);
                    data.type = 'act';
                    data.links = [{
                            document_id:this.data.id,
                            document_type:this.data.type
                        }
                    ];
                    usam_api('act', data, 'POST', (id) => this.setUrl('act', id))
                }
            });
        },
        setUrl(form, id) {
            let url = new URL(document.location.href);
            url.searchParams.set('form_name', form);
            url.searchParams.set('form', 'edit');
            url.searchParams.set('id', id);
            history.pushState({
                'url':url.href
            }, '', url.href);
            window.location.replace(url.href);
        },
        addUser(item) {
            this.addManager(item);
        }
    }
}

rulerEditor = {
    data() {
        return {
            rulerTop:0,
        }
    },
    mounted() {
        var c = document.getElementById("ruler_top_offset");
        if (c) {
            var ctx = c.getContext("2d");
            this.rulerTop = (this.$refs.slides.offsetWidth - this.$refs.layers.offsetWidth) / 2;
            for (var a = 0; a < c.offsetWidth; a += 100) {
                ctx.moveTo(a, 0);
                ctx.lineTo(a, 15);
                ctx.font = "10px Arial";
                ctx.fillStyle = "rgba(183,187,192,0.5)";
                ctx.fillText(a - 1200, a + 5, 10);
            }
            for (var a = 0; a < c.offsetWidth; a += 10) {
                if (a % 100 != 0) {
                    ctx.moveTo(a, 10);
                    ctx.lineTo(a, 15);
                }
            }
            ctx.strokeStyle = "#414244";
            ctx.stroke();
        }
        var c = document.getElementById("ruler_left_offset");
        if (c) {
            var ctx = c.getContext("2d");
            for (var a = 0; a < c.offsetHeight; a += 100) {
                ctx.moveTo(0, a);
                ctx.lineTo(15, a);
                //	ctx.rotate(90deg);
                ctx.font = "10px Arial";
                ctx.fillStyle = "#5b5d60";
                ctx.fillText(a, 1, a + 5);
            }
            for (var a = 0; a < c.offsetHeight; a += 10) {
                if (a % 100 != 0) {
                    ctx.moveTo(10, a);
                    ctx.lineTo(15, a);
                }
            }
            ctx.strokeStyle = "#414244";
            ctx.stroke();
        }
        var c = document.getElementById("time_linear_canvas");
        if (c) {
            var ctx = c.getContext("2d");
            for (var a = 0; a < c.offsetWidth; a += 10) {
                if (a % 100 != 0) {
                    ctx.moveTo(a, 24);
                    ctx.lineTo(a, 28);
                } else {
                    ctx.moveTo(a, 28);
                    ctx.lineTo(a, 14);
                    ctx.font = "12px Arial";
                    ctx.fillStyle = "rgba(183,187,192,0.5)";
                    ctx.fillText((a / 100) + 's', a + 5, 20);
                }
            }
            ctx.strokeStyle = "#414244";
            ctx.stroke();
        }
        setTimeout(() => {
            var el = document.getElementById('slider_editor');
            el.addEventListener('mousemove', (e) => {
                var l = e.clientX - el.getBoundingClientRect().left;
                if (l > 0)
                    document.getElementById('ruler_hor_marker').style.left = l + 'px';
                var t = e.clientY - el.getBoundingClientRect().top;
                if (t > 0)
                    document.getElementById('ruler_ver_marker').style.top = t + 'px';
            });
        }, 30);
    },
    updated() {
        this.rulerTop = (this.$refs.slides.offsetWidth - this.$refs.layers.offsetWidth) / 2;
    }
}

slideEditor = {
    mixins:[undoRedoHistory, rulerEditor],
    data() {
        return {
            data:{},
            products:[],
            toolTabs:'layers',
            tabSettings:{},
            types:{},
            table_name:'banner',
            tab:'settings',
            section:{},
            device:'computer',
            dLayerCSS:{
                inset:'50px auto auto 50px',
                transform:'',
                css:{
                    cursor:'default',
                    width:'auto',
                    height:'auto',
                    'background-color':'transparent',
                    'background-image':'',
                    'background-size':'cover',
                    'background-position':'center center',
                    'background-repeat':'no-repeat',
                    'text-shadow':'none',
                    'text-decoration':'none',
                    'box-shadow':'none',
                    margin:'0px',
                    padding:'0px',
                    'white-space':'normal',
                    'border-style':'solid',
                    'border-width':'0',
                    'border-radius':'0px',
                    'border-color':'#000000',
                    'text-transform':'none',
                    'font-style':'normal',
                    'letter-spacing':'normal',
                    color:'#fff',
                    'font-weight':400,
                    'line-height':'1.3',
                    'font-size':'15px',
                    'font-family':'inherit',
                    'transform-style':'flat',
                    'box-sizing':'border-box',
                    'text-align':'center',
                    filter:'none',
                    opacity:1,
                    visibility:'visible'
                },               
				hover:{
                    'background-color':'transparent',
                    'background-image':'',
                    'background-position':'',
                    'text-shadow':'none',
                    'text-decoration':'none',
                    'box-shadow':'none',
                    'border-color':'inherit',
                    color:'#fff',
                    filter:'none',
                    opacity:''
                }
            },
            dLayerGroupCSS:{
                inset:'50px auto auto 50px',
                transform:'',
                css:{
                    width:'auto',
                    height:'auto',
                    'background-color':'transparent',
                    margin:'0px',
                    padding:'0px',
                    'border-style':'solid',
                    'border-width':'0',
                    'border-radius':'0px',
                    'border-color':'#000000',
                    'text-transform':'none',
                    'line-height':'1.3',
                    'box-sizing':'border-box',
                    filter:'none'
                }
            },
            defaultLayerText:{
                type:'text',
                name:'Слой',
                content:'Новый слой',
                hover_active:0,
				group:0,
                sizelock:false,
                visibility:true,
                lock:false,
                selected:false,
                classes:'',
                rotate:0,
                computer:{},
                notebook:{},
                tablet:{},
                mobile:{},
                duration:1,
                delay:0,
                easing:'',
                animation_in:'',
                transform:{
                    opacity:'',
                    x:0,
                    y:0,
                    z:'',
                    scalex:'',
                    scaley:'',
                    skewx:'',
                    skewy:'',
                    rotatex:'',
                    rotatey:'',
                    rotatez:'',
                    originx:'',
                    originy:'',
                    originz:''
                },
                boxShadow:{
                    active:0,
                    color:'rgba(0,0,0,0.25)',
                    x:'1px',
                    y:'1px',
                    radius:'30px',
                    spread:'1px'
                },
                textShadow:{
                    active:0,
                    color:'rgba(0,0,0,0.25)',
                    x:'1px',
                    y:'1px',
                    radius:'30px'
                },
                actions:{
                    type:'',
                    value:''
                }
            },
            layerActive:null,
            layerMedia:0,
            moveX:0,
            moveY:0,
            mouseType:null,
            panel:true,
            active:false,
            editorFocus:false,
            editName:false,
            webforms:[],
            regions:[],
            templates:[],
            roles:[],
            devicesLists:{},
            menuLayer:false,
            menuSlide:false,
            copy:null,
            watchState:null
        }
    },
    computed:{
        slideContainerCSS() {
            return (this.data.settings.layouttype == 'layout' || this.data.settings.layouttype == 'auto' ? "left:50%; transform:translate(-50%, 0%);width:" + this.data.settings.size[this.device].width + ";" :"left:0;right:0;") + ';';
        },
        screenSizeCSS() {
            switch (this.device) {
            case 'notebook':
                return 'max-width:1023px;';
                break;
            case 'tablet':
                return 'max-width:777px;';
                break;
            case 'mobile':
                return 'max-width:480px;';
                break;
            }
        },
        width() {
            return this.data.settings.layouttype == 'fullscreen' || this.data.settings.layouttype == 'fullscreenwidth' ? window.innerWidth + 'px' :this.data.settings.size[this.device].width;
        },
        layer() {
            return this.layers[this.layerActive];
        },
        slideAnimation() {
            return this.data.settings.effect && this.tab == 'slides' && this.section[this.tab] == 'animation' ? 'effect_' + this.data.settings.effect :'';
        },
        devices() {
            return this.data.settings === undefined ? {}
             :this.data.settings.devices;
        },
        selectedlayers() {
            return this.layers.filter(x => x.selected);
        },
        slideCSS() {
            var css = '';
            for (k in this.slide.settings.css)
                if (this.slide.settings.css[k] !== '')
                    css += k + ':' + this.slide.settings.css[k] + ';';
            if (this.data.settings.layouttype == 'image' && this.slide.settings.object_size.width)
                css += 'height:auto;padding:0 0 ' + this.slide.settings.object_size.height * 100 / this.slide.settings.object_size.width + '% 0;';
            if (this.slide.object_url)
                css += 'background-image:url(' + this.slide.object_url + ');'
                return css;
        },
        slideFonCSS() {
            var css = '';
            switch (this.data.settings.layouttype) {
            case 'fullscreen':
                css += 'height:' + window.innerHeight + 'px;'
                break;
            case 'fullscreenwidth':
                css += 'height:' + this.data.settings.size[this.device].height + ';'
                css += 'width:100%;'
                break;
            case 'fullscreenheight':
                css += 'height:100%;'
                css += 'width:' + this.data.settings.size[this.device].width + ';'
                break;
            case 'layout':
                css += 'height:' + this.data.settings.size[this.device].height + ';'
                css += 'width:' + this.data.settings.size[this.device].width + ';'
                break;
            case 'css':
                css += 'height:' + this.data.settings.area_size.height + ';'
                break;
            }
            if (this.slide.settings['background-color'])
                css += 'background:' + this.slide.settings['background-color'] + ';';
            if (this.data.settings.layouttype === 'layout')
                css += 'max-width:' + this.data.settings.area_size.width + ';max-height:' + this.data.settings.area_size.height + ';';
            else if (this.data.settings.layouttype === 'image')
                css += 'max-width:' + this.data.settings.area_size.width + ';';
            if (this.data.settings.padding)
                css += 'padding:' + this.data.settings.padding + ';';
            return css;
        }
    },
    watch:{
        devices:{
            handler(v, old) {
                for (i in this.layers) {
                    var l = this.layers[i];
                    for (k in v) 
					{	
						if( v[k] )
						{
							if( l[k] === undefined || !Object.keys(l[k]).length )															
								Vue.set(l, k, structuredClone(l.computer));
						}
                        else if (l[k] !== undefined)
						{
                            delete l[k];
                            this.device = 'computer';
                        }
                    }
                }				
                if (this.data.type == 'products')
                    for (let i in this.products) {
                        var l = this.products[i];
                        for (k in v) {
                            if (v[k]) {
                                if (l[k] === undefined)
                                    Vue.set(l, k, structuredClone(l.computer));
                            } else if (l[k] !== undefined) {
                                delete l[k];
                                this.device = 'computer';
                            }
                        }
                    }
            },
            deep:true
        }
    },
    created() {
        for (let k in form_args)
            this[k] = form_args[k];
        for (let k in this.tabSettings)
            Vue.set(this.section, k, Object.keys(this.tabSettings[k].icons)[0]);
        usam_api('webforms', 'GET', (r) => {
            for (let k in r)
                this.webforms.push({
                    id:r[k].code,
                    name:r[k].title
                })
        });
    },
    methods:{
        layersFormatting(layers) {
            var j = 1;
            for (i in layers) {
                var l = layers[i];
                l.editName = false;
                if (l.css !== undefined) {
                    l.css.margin = '';
                    l.computer = {css:l.css, inset:l.inset}
                    l = Object.assign(l, {
                        id:j,
                        visibility:true,
                        lock:false,
                        selected:false,
                        group:0
                    });
                    j++;
                } else {
                    /*	if( l.type === 'group' ){
                    delete l['white-space'];
                    delete l['text-transform'];
                    delete l['font-style'];
                    delete l['letter-spacing'];
                    delete l['font-weight'];
                    delete l['font-size'];
                    delete l['font-family'];
                    delete l['text-align'];
                    delete l.color;
                    delete l.opacity;
                    }*/
                    if (l.computer.transform === undefined)
                        l.computer.transform = '';
                    for (w of['computer', 'notebook', 'tablet', 'mobile']) {
                        if (l[w] !== undefined && l[w].hover === undefined)
                            l[w].hover = structuredClone(this.dLayerCSS.hover);
                    }
                    l.group = parseInt(l.group);
                    l.id = parseInt(l.id);
                    l.rotate = parseInt(l.rotate);
                    l.visibility = l.visibility === 'true' || l.visibility === true;
                    l.sizelock = l.sizelock !== undefined && l.sizelock === 'true' || l.sizelock === true;
                    l.lock = l.lock === 'true' || l.lock === true;
                    l.selected = l.selected === 'true' || l.selected === true;
                }
                Vue.set(layers, i, l);
            }
        },
        changeSettings(v, old) {
            if (v.type == old.type)
                return false;
            if (this.slide.type == 'vimeo' || this.slide.type == 'youtube')
                Vue.set(this.slide, 'settings', Object.assign(this.slide.settings, {
                        autoplay:1,
                        muted:0,
                        quality:'',
                        video_id:''
                    }));
            else if (this.slide.type == 'video')
                Vue.set(this.slide, 'settings', Object.assign(this.slide.settings, {
                        autoplay:1,
                        muted:0,
                        video_mp4:'',
                        video_webm:''
                    }));
            else if (this.slide.type == 'image' || this.slide.type == 'externalimage' || this.slide.type == 'products') {
                this.slide = Object.assign(this.slide, {
                    object_id:0,
                    object_url:''
                });
                this.slide.settings.object_size.width = '';
                this.slide.settings.object_size.height = '';
                Vue.set(this.slide, 'settings', this.slide.settings);
            }
        },
        addMedia(a) {
            this.slide.object_id = a.id;
            this.slide.object_url = a.url;
            this.slide.settings.object_size.width = a.width;
            this.slide.settings.object_size.height = a.height;
        },
        deleteMedia(a) {
            this.slide.object_id = 0;
            this.slide.object_url = '';
        },
        sortLayers(oldindex, index) {
            let v = structuredClone(this.layers[oldindex]);
            this.layers.splice(oldindex, 1);
            this.layers.splice(index, 0, v);
        },
        layerStyles(l, i) {
            var t = 'transform:' + l[this.device].transform;
            if (l.rotate)
                t += ' rotate(' + l.rotate + 'deg)';
            return 'z-index:' + (50 + this.layers.length - i) + ';inset:' + l[this.device].inset + ';' + t + ';';
        },
        layerStylesContent(l) {
            var css = '';
            if (l[this.device] === undefined)
                l[this.device] = structuredClone(this.dLayerCSS);
            for (k in l[this.device].css)
                if (l[this.device].css !== '')
                    css += k + ':' + l[this.device].css[k] + ';';
            if (l.boxShadow.active == 1)
                css += 'box-shadow:' + l.boxShadow.color + ' ' + l.boxShadow.x + ' ' + l.boxShadow.y + ' ' + l.boxShadow.radius + ' ' + l.boxShadow.spread + ';';
            if (l.textShadow.active == 1)
                css += 'text-shadow:' + l.textShadow.color + ' ' + l.textShadow.x + ' ' + l.textShadow.y + ' ' + l.textShadow.radius + ';';
            if (this.tab == 'editor' && this.section[this.tab] == 'animation') {
                if (l.transform.originx || l.transform.originy)
                    css += 'transform-origin:' + l.transform.originx + '% ' + l.transform.originy + '%;';
                t = '';
                if (l.transform.z)
                    t += ' translate3d(' + l.transform.x + ', ' + l.transform.y + ', ' + l.transform.z + ')';
                else if (l.transform.x || l.transform.y)
                    t += ' translate(' + l.transform.x + ', ' + l.transform.y + ')';
                if (l.transform.scalex || l.transform.scaley)
                    t += ' scale(' + l.transform.scalex + ', ' + l.transform.scaley + ')';
                if (l.transform.skewx || l.transform.skewy)
                    t += ' skew(' + l.transform.skewx + ', ' + l.transform.skewy + ')';
                if (l.transform.rotatex)
                    t += ' rotateX(' + l.transform.rotatex + ')';
                if (l.transform.rotatey)
                    t += ' rotateY(' + l.transform.rotatey + ')';
                if (l.transform.rotatez)
                    t += ' rotateZ(' + l.transform.rotatez + ')';
                if (t)
                    css += 'transform:' + t + ';';
                if (l.animation_in)
                    css += 'transition:' + l.animation_in + ' ' + l.duration + 's ' + l.easing + ' ' + l.delay + 's;';
            }
            return css;
        },
        markLayersSelection() {
            for (i in this.layers)
                if (this.layers[i].selected)
                    this.layers[i].selected = false;
        },
        groupLevel() {
            var g = this.layerStyle(this.dLayerGroupCSS);
            g.name = 'Группа';
            g.type = 'group';
            var i = this.layers.push(g);
            this.selectLayer(i - 1);
            for (i in this.layers)
                if (this.layers[i].selected) {
                    var n = this.layers[i];
                    n.selected = false;
                    n.visibility = true;
                    n.lock = false;
                    n.group = g.id;
                }
        },
        layerStyle(ds) {
            ds = ds !== undefined ? ds :this.dLayerCSS;
            var l = structuredClone(this.defaultLayerText);
            for (d in this.devices)
                if (this.devices[d]) {
                    l[d] = {};
                    l[d] = structuredClone(ds);
                }
            l.name += ' ' + this.layers.length + 1;
            l.id = this.layers.length ? this.layers[this.layers.length - 1].id + 1 :1;
            return l;
        },
        addLayerPrice(type) {
            var l = this.layerStyle();
            l.type = type;
            l.product_id = 0;
            l.content = '0.00';
            l.product_name = '';
            l[this.device].css['font-size'] = '55px';
            var i = this.layers.unshift(l);
            this.selectLayer(0);
        },
        addLayerOldPrice(type) {
            var l = this.layerStyle();
            l.type = type;
            l.product_id = 0;
            l.content = '0.00';
            l.product_name = '';
            l[this.device].css['font-size'] = '55px';
            var i = this.layers.unshift(l);
            this.selectLayer(0);
        },
        addLayerDescription(type) {
            var l = this.layerStyle();
            l.type = type;
            l.product_id = 0;
            l.content = 'Описание будет взято из товара';
            l.product_name = '';
            var i = this.layers.unshift(l);
            this.selectLayer(0);
        },
        addLayerProductTitle(type) {
            var l = this.layerStyle();
            l.type = type;
            l.product_id = 0;
            l.content = 'Название будет взято из товара';
            l.product_name = '';
            this.layers.unshift(l);
            this.selectLayer(0);
        },
        addLayerThumbnail(type) {
            var l = Object.assign(this.layerStyle(), {
                type:type,
                product_id:0,
                product_name:''
            });
            this.layers.unshift(l);
            this.selectLayer(0);
        },
        addLayerAddtocart(type) {
            var l = Object.assign(this.layerStyle(), {
                type:type,
                product_id:0,
                product_name:'',
                content:'Добавить в корзину'
            });
            l[this.device].css['border-radius'] = '5px';
            l[this.device].css['padding'] = '5px 10px';
            l[this.device].css['background-color'] = 'rgb(0, 122, 255)';
            this.layers.unshift(l);
            this.selectLayer(0);
        },
        selectProduct(e) {
            this.layer.product_id = e.id;
            this.layer.product_name = e.name;
            switch (this.layer.type) {
            case 'product-day-thumbnail':
            case 'product-thumbnail':
                this.layer.object_url = e.full_image;
                break;
            case 'product-day-price':
            case 'product-price':
                this.layer.content = e.price_currency;
                break;
            case 'product-day-oldprice':
            case 'product-oldprice':
                this.layer.content = e.price_currency;
                break;
            case 'product-day-description':
            case 'product-description':
                this.layer.content = e.description;
                break;
            case 'product-day-title':
            case 'product-title':
                this.layer.content = e.name;
                break;
            }
        },
        addLayerH() {
            var l = this.layerStyle();
            l.type = 'header';
            l[this.device].css['font-size'] = '55px';
            var i = this.layers.unshift(l);
            this.selectLayer(0);
        },
        addLayerContent() {
            var l = this.layerStyle();
            var i = this.layers.unshift(l);
            this.selectLayer(0);
        },
        addLayerImage(a) {
            var l = this.layerStyle({
                inset:'50px auto auto 50px',
                transform:'',
                css:{
                    width:'auto',
                    height:'auto',
                    'box-shadow':'none',
                    margin:'0px',
                    padding:'0px',
                    'border-style':'solid',
                    'border-width':'0',
                    'border-radius':'0px',
                    'border-color':'#000000',
                    filter:'none',
                    opacity:1,
                    overflow:'hidden'
                }
            });
            l.object_id = a.id;
            l.object_url = a.url;
            l.type = 'image';
            var i = this.layers.unshift(l);
            this.selectLayer(0);
        },
        addLayerElements(a) {
            var l = this.layerStyle({
                inset:'50px auto auto 50px',
                transform:'',
                css:{
                    width:'200px',
                    height:'200px',
                    color:'rgb(209, 209, 209)',
                    opacity:1
                }
            });
            l.element = a;
            l.type = 'element';
            var i = this.layers.unshift(l);
            this.selectLayer(0);
        },
        addLayerButton() {
            var l = this.layerStyle();
            l[this.device].css['border-radius'] = '5px';
            l[this.device].css['padding'] = '5px 10px';
            l[this.device].css['background-color'] = 'rgb(0, 122, 255)';
            l[this.device].css['cursor'] = 'pointer';
            l.type = 'button';
            var i = this.layers.unshift(l);
            this.selectLayer(0);
        },
        selectLayer(i) {
            this.layerActive = i;
            this.tab = 'editor';
            this.toolTabs = 'layers';
            var f = (e) => {
                if (e.target.closest('.slide_layer') || e.target.classList.contains("slide_layer") || !e.target.closest('#slider_editor'))
                    return false;

                this.layerActive = null;
                this.tab = 'settings';
                document.removeEventListener('click', f, false);
                document.removeEventListener('keyup', this.keyupLayer, false);
            }
            setTimeout(() => document.addEventListener('click', f), 1);
            document.addEventListener("keyup", this.keyupLayer);
        },
        keyupLayer(e) {
            var code = e.keyCode ? e.keyCode :e.which;
            if (code == 46 && this.layerActive !== null && this.editorFocus)
                this.deleteLayer(this.layerActive);
        },
        deleteLayer(i) {
            var l = this.layers[i];
            if (i === this.layerActive)
                this.layerActive = 0;
            if (this.layers.length == 1) {
                this.tab = 'settings';
                this.layerActive = null;
            }
            if (l.type == 'group')
                for (let k in this.layers)
                    if (this.layers[k].group == l.id)
                        this.layers[k].group = 0;
            this.layers.splice(i, 1);
            this.$refs['menu-layer'].style.display = 'none';
            document.removeEventListener('keyup', this.keyupLayer, false);
        },
        openMenuLayer(e, i) {
            e.preventDefault();
            var r = this.$refs['slides'].getBoundingClientRect();
            this.$refs['menu-layer'].style.inset = (e.clientY - r.top + 10) + 'px auto auto ' + (e.clientX - r.left - 25) + 'px';
            this.$refs['menu-layer'].style.display = 'block';
            this.$refs['menu'].style.display = 'none';
            this.menuLayer = i;
            setTimeout(() => document.addEventListener('click', this.closeMenu), 1);
        },
        openMenu(e) {
            if (e.target.closest('.slide_layer') || e.target.classList.contains("slide_layer"))
                return false;
            e.preventDefault();
            var r = this.$refs['slides'].getBoundingClientRect();
            this.$refs['menu'].style.inset = (e.clientY - r.top + 10) + 'px auto auto ' + (e.clientX - r.left - 25) + 'px';
            this.$refs['menu'].style.display = 'block';
            this.$refs['menu-layer'].style.display = 'none';
            setTimeout(() => document.addEventListener('click', this.closeMenu), 1);
        },
        openMenuSlide(e, i) {
            e.preventDefault();
            var r = this.$refs['controlpanel'].getBoundingClientRect();
            this.$refs['menu-slide'].style.inset = (e.clientY - r.top + 10) + 'px auto auto ' + (e.clientX - r.left - 25) + 'px';
            this.$refs['menu-slide'].style.display = 'block';
            this.menuSlide = i;
            setTimeout(() => document.addEventListener('click', this.closeMenu), 1);
        },
        closeMenu(e) {
            if (e.target.closest('.menu_content') || e.target.classList.contains("menu_content"))
                return false;
            this.menuLayer = false;
            this.$refs['menu'].style.display = 'none';
            this.$refs['menu-layer'].style.display = 'none';
            this.$refs['menu-slide'].style.display = 'none';
        },
        duplicateLayer(e, i) {
            this.cloneLayer(e, i);
            this.$refs['menu-layer'].style.display = 'none';
        },
        copyLayer(e) {
            this.copy = this.menuLayer;
            this.menuLayer = false;
            this.$refs['menu-layer'].style.display = 'none';
        },
        pasteLayer(e) {
            if (Number.isInteger(this.copy)) {
                this.cloneLayer(e, this.copy);
                this.$refs['menu'].style.display = 'none';
                this.$refs['menu-layer'].style.display = 'none';
                this.copy = null;
            }
        },
        cloneLayer(e, i) {
            var l = structuredClone(this.layers[i]);
            var r = this.$refs['layers'].getBoundingClientRect();
            l[this.device].inset = (e.clientY - r.top + 10) + 'px auto auto ' + (e.clientX - r.left - 25) + 'px';
            var i = this.layers.unshift(l);
            this.selectLayer(0);
        },
        positionLayer(e) {
            var p = {
                'top left':{
                    inset:'0 auto auto 0',
                    transform:''
                },
                'top center':{
                    inset:'0 50% auto auto',
                    transform:'translate(50%, 0)'
                },
                'top right':{
                    inset:'0 0 auto auto',
                    transform:''
                },
                'center left':{
                    inset:'50% auto auto 0',
                    transform:'translate(0, -50%)'
                },
                'center center':{
                    inset:'50% auto auto 50%',
                    transform:'translate(-50%, -50%)'
                },
                'center right':{
                    inset:'50% 0 auto auto',
                    transform:'translate(0, -50%)'
                },
                'bottom left':{
                    inset:'auto auto 0 0',
                    transform:''
                },
                'bottom center':{
                    inset:'auto 50% 0 auto',
                    transform:'translate(50%, 0)'
                },
                'bottom right':{
                    inset:'auto 0 0 auto',
                    transform:''
                }
            };
            this.layer[this.device] = Object.assign(this.layer[this.device], p[e]);
        },
        moveLayer(e) {
            var el = this.$refs['layer_' + this.layerActive][0];
            this.layer[this.device].inset = (el.offsetTop + e.pageY - this.moveY) + 'px auto auto ' + (el.offsetLeft + e.pageX - this.moveX) + 'px';
            this.layer[this.device].transform = '';
            this.moveX = e.pageX;
            this.moveY = e.pageY;
        },
        rotationLayer(e) {
            this.layer.rotate += (e.pageX - this.moveX) + (e.pageY - this.moveY);
            var r = this.layer.rotate / 360;
            if (r > 1)
                this.layer.rotate = this.layer.rotate - 360;
            this.moveX = e.pageX;
            this.moveY = e.pageY;
        },
        mousedown(e, i, type) {
            this.layerActive = i;
            this.mouseType = type;
            this.moveX = e.pageX;
            this.moveY = e.pageY;
        },
        mousemove(e) {
            if (this.layerActive !== null && this.mouseType !== null && !this.layer.lock) {
                if (this.watchState !== null)
                    this.watchState();
                if (this.mouseType === 'move')
                    this.moveLayer(e);
                else if (this.mouseType === 'rotation')
                    this.rotationLayer(e);
                else
                    this.handleMove(e);
            }
        },
        handleDown(e, type) {
            if (this.watchState !== null)
                this.watchState();
            this.mouseType = type;
            var el = this.$refs['layer_content_' + this.layerActive][0];
            if (this.layer[this.device].css.width == 'auto')
                this.layer[this.device].css.width = el.offsetWidth + 'px';
            if (this.layer[this.device].css.height == 'auto')
                this.layer[this.device].css.height = el.offsetHeight + 'px';
            this.moveX = e.pageX;
            this.moveY = e.pageY;
        },
        handleMove(e) {
            var el = this.$refs['layer_content_' + this.layerActive][0];
            var l = this.$refs['layer_' + this.layerActive][0];
            switch (this.mouseType) {
            case 'left':
                if (this.layer.sizelock) {
                    this.layer[this.device].inset = (l.offsetTop + e.pageX - this.moveX) + 'px auto auto ' + (l.offsetLeft + e.pageX - this.moveX) + 'px';
                    this.layer[this.device].css.height = (el.offsetHeight - e.pageX + this.moveX) + 'px';
                } else
                    this.layer[this.device].inset = l.offsetTop + 'px auto auto ' + (l.offsetLeft + e.pageX - this.moveX) + 'px';
                this.layer[this.device].css.width = (el.offsetWidth + this.moveX - e.pageX) + 'px';
                break;
            case 'right':
                this.layer[this.device].css.width = (el.offsetWidth + e.pageX - this.moveX) + 'px';
                if (this.layer.sizelock)
                    this.layer[this.device].css.height = (el.offsetHeight + e.pageX - this.moveX) + 'px';
                break;
            case 'top':
                if (this.layer.sizelock) {
                    this.layer[this.device].inset = (l.offsetTop + e.pageY - this.moveY) + 'px auto auto ' + (l.offsetLeft + e.pageY - this.moveY) + 'px';
                    this.layer[this.device].css.width = (el.offsetWidth - e.pageY + this.moveY) + 'px';
                } else
                    this.layer[this.device].inset = (l.offsetTop + e.pageY - this.moveY) + 'px auto auto ' + l.offsetLeft + 'px';
                this.layer[this.device].css.height = (el.offsetHeight - e.pageY + this.moveY) + 'px';
                break;
            case 'bottom':
                this.layer[this.device].css.height = (el.offsetHeight + e.pageY - this.moveY) + 'px';
                if (this.layer.sizelock)
                    this.layer[this.device].css.width = (el.offsetWidth + e.pageY - this.moveY) + 'px';
                break;
            }
            this.moveX = e.pageX;
            this.moveY = e.pageY;
        },
        handleUp(e) {
            this.watchState = this.$watch('data', this.addState, {
                deep:true,
                immediate:true
            });
            document.onmousemove = null;
            this.movePoint = false;
            this.mouseType = null;
            this.moveX = 0;
            this.moveY = 0;
        },
        dragstart(e) {
            return false
        }
    }
}

var new_event;
var add_event = {
    data() {
        return {
            defaultEvent:{
                id:0,
                title:'',
                description:'',
                type:'task',
                status:'',
                calendar:0,
                importance:'',
                start:'',
                end:'',
                reminder_date:'',
                links:[]
            },
            event:{},
            customer:{
                title:'',
                name:''
            },
            date:'',
            date_time:'',
            remind:0
        };
    },
    watch:{
        remind(v, old) {
            if (v) {
                const currentDate = new Date();
                const d = new Date(this.event.start);
                d.setDate(d.getDate() - 1);
                if (d.getTime() < currentDate.getTime()) {
                    const d = new Date(this.event.start);
                    this.event.reminder_date = local_date(d, "Y-m-d", false) + " 09:00:00";
                } else
                    this.event.reminder_date = local_date(d, "Y-m-d", false) + " 09:00:00";
            } else
                this.event.reminder_date = '';
        }
    },
    mounted() {
        const d = new Date();
        d.setDate(d.getDate() + 3);
        this.defaultEvent.start = local_date(d, "Y-m-d", false) + " 09:00:00";
        this.defaultEvent.end = local_date(d, "Y-m-d", false) + " 18:00:00";
        this.event = Object.assign({}, this.defaultEvent);
    },
    methods:{
        show_modal() {
            jQuery('#add_event').modal();
        },
        del() {
            if (this.event.id)
                usam_api('event/' + this.event.id, 'DELETE');
            this.event = Object.assign({}, this.defaultEvent);
            jQuery('#add_event').modal('hide');
        },
        save() {
            if (this.event.title == '')
                return;
            var data = Object.assign({}, this.event);
            if (!this.remind)
                delete data.reminder_date;
            jQuery('#add_event').modal('hide');
            if (this.event.id)
                usam_api('event/' + this.event.id, data, 'POST', this.handler);
            else {
                usam_api('event', data, 'POST', this.handler);
                this.$emit('add_event', this.event);
            }
            this.event = Object.assign({}, this.defaultEvent);
        },
        handler(r) {
            if (jQuery('.usam_tab_table').length)
                USAM_Tabs.update_table()
        }
    }
};

var crm_report = {
    mixins:[data_filters],
    data() {
        return {
            reports:[],
            vars:{},
            visits:[],
            id:0,
            statistics_block:{
                rttr:0
            },
            total:{},
            visits:[],
            visits_count:0,
            lists:{}
        };
    },
    created() {
        document.querySelectorAll('.js-lzy-list-data').forEach((e) => {
            Vue.set(this.lists, e.id, {
                data:[],
                more:false
            });
        })
    },
    mounted() {
        let url = new URL(document.location.href);
        this.id = Number(url.searchParams.get('id'));
        this.loadReport();
    },
    methods:{
        requestData(data) {
            this.vars = data;
            this.loadReport();
        },
        loadReport() {
            document.querySelectorAll('.js-lzy-graph').forEach((e) => {
                const o = this.observer('graph');
                o.observe(e);
            })
            document.querySelectorAll('.js-lzy-list-data').forEach((e) => {
                const o = this.observer('list');
                o.observe(e);
            })
            document.querySelectorAll('.js-lzy-total-results').forEach((e) => {
                const o = this.observer('total');
                o.observe(e);
            })
            document.querySelectorAll('.js-lzy-data-report').forEach((e) => {
                const o = this.observer('data');
                o.observe(e);
            })
        },
        observer(type) {
            options = {
                rootMargin:'0px 0px 200px 0px'
            };
            return new IntersectionObserver((entries, o) => {
                entries.forEach((e) => {
                    if (e.isIntersecting) {
                        switch (type) {
                        case 'graph':
                            this.load_graph(e);
                            break;
                        case 'list':
                            this.load_list_data(e.target, 0);
                            break;
                        case 'total':
                            this.total_results_report(e);
                            break;
                        case 'data':
                            this.data_report(e);
                            break;
                        }
                        o.unobserve(e.target);
                    }
                })
            }, options);
        },
        load_more_list_data(e) {
            const list = e.target.closest('.list_data');
            number = this.lists != null ? this.lists[list.id].data.length :0;
            this.load_list_data(list, number);
        },
        load_list_data(e, number) {
            var data = this.vars;
            data.nonce = USAM_Tabs.load_list_data_nonce;
            data.action = 'load_list_data';
            data.id = USAM_Tabs.id;
            data.type = e.id;
            data.number = number;
            usam_send(data, (r) => {
                more = r.length > 9 ? true :false;
                if (number) {
                    d = this.lists[e.id].data;
                    for (k in r) {
                        d[number] = r[k];
                        number++;
                    }
                } else
                    d = r;
                Vue.set(this.lists, e.id, {
                    data:d,
                    more:more
                });
                usam_lazy_image();
            });
        },
        load_graph(e) {
            var data = this.vars;
            var svg = e.target.childNodes[0];
            svg.innerHTML = '';
            data.nonce = USAM_Tabs.load_graph_data_nonce;
            data.action = 'load_graph_data';
            data.id = USAM_Tabs.id;
            data.type = svg.id;
            usam_send(data, (r) => {
                if (r) {
                    if (typeof r.statistics !== typeof undefined) {
                        var type = svg.id;
                        Vue.set(this.statistics_block, type.replace('_graph', ''), r.statistics);
                    }
                    if (r.graph == 'horizontal_bars' && r.data)
                        jQuery.graph_horizontal_bars(svg.id, r.data, '');
                    else if (r.graph == 'vertical_bars')
                        jQuery.vertical_bars(svg.id, r.data, '');
                }
            });
        },
        data_report(e) {
            var data = this.vars;
            var type = e.target.id;
            data.nonce = USAM_Tabs.total_results_report_nonce;
            data.action = 'total_results_report';
            data.id = USAM_Tabs.id;
            data.type = type;
            usam_send(data, (r) => {
                if (r)
                    Vue.set(this.reports, type, r);
            });
        },
        total_results_report(e) {
            var data = this.vars;
            var type = e.target.id;
            data.nonce = USAM_Tabs.total_results_report_nonce;
            data.action = 'total_results_report';
            data.id = USAM_Tabs.id;
            data.type = type;
            usam_send(data, (r) => {
                if (r)
                    Vue.set(this.total, type, r);
            });
        }
    }
}
var listTable = {
    data() {
        return {
            filtersData:{},
            tableName:'',
        };
    },
    mounted() {
        this.tableName = this.$refs['table_name'].value;
        /*
        data.order = $table.find('.js-table-order').val();
        data.orderby = $table.find('.js-table-orderby').val();
        data.table = $table.find('.js-table-name').val();
        data.screen_id = USAM_Tabs.screen_id;
        if( typeof list_args[data.table] !== typeof undefined ){
        for (k in list_args[data.table].query_vars)
        data[k] = list_args[data.table].query_vars[k];
        }*/

    },
    methods:{
        requestData(data) {
            usam_active_loader();
            this.filtersData = data;
            USAM_Tabs.table_view(data, jQuery('.table_view'));
        },
        getTableArgs() {
            var data = this.filtersData;
            data.table = this.tableName;
            if (typeof list_args[this.tableName] !== typeof undefined) {
                for (k in list_args[this.tableName].query_vars)
                    data[k] = list_args[this.tableName].query_vars[k];
            }
            let url = new URL(document.location.href);
            if (url.searchParams.has('form_name')) {
                data.form_name = url.searchParams.get('form_name');
                data.id = url.searchParams.get('id');
            }
            data.tab = USAM_Tabs.tab;
            data.page = USAM_Tabs.page;
            data.screen_id = USAM_Tabs.screen_id;
            return data;
        },
        exportTable(e) {
            e.preventDefault();
            data = this.getTableArgs();
            data.title = document.querySelector('.js-tab-title').innerText;
            data.action = 'export_table_to_excel';
            data.nonce = USAM_Tabs.export_table_to_excel_nonce;
            usam_send(data);
        },
        printTable(e) {
            e.preventDefault();
            data = this.getTableArgs();
            data.title = document.querySelector('.js-tab-title').innerText;
            data.action = 'print_table';
            data.nonce = USAM_Tabs.print_table_nonce;
            usam_send(data, (r) => {
                var WindowObject = window.open("", "PrintWindow", "width=" + document.documentElement.clientWidth + ",height=" + document.documentElement.clientHeight + ",top=50,left=50,toolbars=no,scrollbars=yes,status=no,resizable=yes");
                WindowObject.document.writeln(r);
                WindowObject.document.close();
                WindowObject.focus();
                WindowObject.print();
                WindowObject.close();
            });
        },
    }
}

var importer = {
    data() {
        return {
            steps:[],
            current_step:'',
            template_id:0,
            source:'file',
            file_settings:{
                type_file:'',
                encoding:'',
                start_line:'',
                end_line:''
            },
            rule:{
                headings:0,
                type_import:'',
                countries:[],
                lang:'ru'
            },
            itemProperties:[],
            filedata:[],
            data_loaded:false,
            timeoutId:false,
            value_name:[],
            default_columns:{
                category:[],
                companies:[]
            },
            file:{
                name:'',
                title:'',
                size:'',
                icon:'',
                load:false,
                error:false,
                error_message:'',
                percent:0
            },
        };
    },
    mounted() {
        if (typeof USAM_Importer !== typeof undefined) {
            this.steps = USAM_Importer.steps;
            this.itemProperties = USAM_Importer.itemProperties;
            this.current_step = Object.keys(this.steps)[0];
        }
        //	document.querySelector('#button-add').addEventListener("click", this.event_add );
        ///		document.addEventListener("beforeunload", this.update);
    },
    methods:{
        next_step() {
            if (this.source == 'file') {
                if (this.current_step == 'file' && this.file.name == '')
                    return false;
                if (this.current_step == 'columns' && this.value_name.length == 0)
                    return false;
            } else if (this.current_step == 'settings')
                this.current_step = 'columns';

            if (this.template_id)
                this.current_step = 'finish';
            else {
                current_step = false;
                for (k in this.steps) {
                    if (current_step) {
                        this.current_step = k;
                        break;
                    }
                    if (k == this.current_step)
                        current_step = true;
                }
            }
            switch (this.current_step) {
            case 'file':

                break;
            case 'settings':
                this.data_loaded = false;
                break;
            case 'columns':
                this.data_loaded = false;
                this.filedata = [];
                usam_api('importer/file/data', {
                    file:this.file.name,
                    file_settings:this.file_settings,
                    count:3
                }, 'POST', (r) => {
                    this.data_loaded = true;
                    if (r.length)
                        this.filedata = r[0];
                });
                break;
            case 'finish':
                this.startImport();
                break;
            }
        },
        startImport() {
            usam_api('importer', {source:this.source, file:this.file.name, rule:this.rule, type:USAM_Importer.rule_type, columns:this.value_name, file_settings:this.file_settings, template_id:this.template_id}, 'POST');
        },
        fileDelete(k) {
            this.file = {
                name:'',
                title:'',
                size:'',
                icon:'',
                percent:0,
                load:false,
                error:false
            };
        },
        fileDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('over');
            this.fileUpload(e.dataTransfer.files[0]);
        },
        fileAttach(e) {
            el = e.target.querySelector('input[type="file"]');
            if (el)
                el.click();
            else if (e.target.nextElementSibling)
                e.target.nextElementSibling.click();
        },
        allowDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.add('over');
        },
        fileChange(e) {
            if (!e.target.files[0])
                return;
            this.fileUpload(e.target.files[0]);
        },
        fileUpload(f) {
            this.file.load = true;
            this.file.percent = 0;
            this.file.error = '';

            var formData = new FormData();
            formData.append('file', f);
            var handler = (r) => {
                if (r.status == 'success') {
                    r.load = false;
                    for (k in r)
                        this.file[k] = r[k];
                } else
                    this.file.error = r.error_message;
            };
            usam_form_save(formData, handler, (e) => this.file.percent = e.loaded * 100 / e.total, 'importer/file/upload');
        },
        template() {
            if (this.value_name.length) {
                var data = Object.assign({
                    'action':'form_save',
                    nonce:USAM_Importer.form_save_nonce,
                    'a':'save',
                    'name':this.file.name,
                    'form_name':USAM_Importer.rule_type,
                    'columns':this.value_name
                }, this.file_settings);
                Object.assign(data, this.rule);
                usam_send(data);
            }
        },
        change_default(e) {
            this.rule[e.code] = e.id;
        }
    }
}

var exchangeRule = {
    created() {
        this.data = form_data;
    },
    methods:{
        saveForm(back) {
            var data = structuredClone(this.data);
            if (this.data.id)
                usam_api('exchange_rule/' + this.data.id, data, 'POST', (r) => back === true ? this.backList() :usam_admin_notice(r));
            else
                usam_api('exchange_rule', data, 'POST', this.afterAdding);
        },
        download() {
            if (this.data.id)
                usam_api('exchange_rule/download/' + this.data.id, 'GET');
        },
        deleteItem() {
            if (this.data.id)
                usam_api('exchange_rule/' + this.data.id, 'DELETE', this.afterDelete);
        }
    }
}

Vue.component('form-table', {
    template:'#form-table',
    props:{
        lists:{
            required:true,
        default:
            null
        },
        columns:{
            type:Object,
            required:true,
        default:
            () => {}
        },
        loaded:{
            type:Boolean,
        default:
            true
        },
        table:{
            type:String,
            required:false,
        default:
            ''
        },
        edit:{
            type:Boolean,
            required:false,
        default:
            true
        },
        table_name:{
            type:String,
            required:true,
        default:
            ''
        },
        column_names:{
            type:Object,
            required:false,
        default:
            () => {}
        },
    },
    data() {
        return {
            abilityChange:true,
            columns_tools:false,
            editTable:this.edit,
            items:this.lists,
            tableShow:false,
            user_columns:[]
        };
    },
    watch:{
        lists:{
            handler(v, old) {
                if (v !== null)
                    this.items = this.lists;
            },
            deep:true
        },
        user_columns(v, old) {
            usam_api('table/columns', {type:this.table_name, columns:this.user_columns}, 'POST');
        }
    },
    computed:{
        tableColumns() {
            let cols = [];
            for (let k in this.columns) {
                cols.push({
                    id:k,
                    name:this.columns[k]
                });
                if (k == 'title') {
                    for (let i in this.user_columns)
                        cols.push({
                            id:i,
                            name:this.column_names[this.user_columns[i]]
                        });
                }
            }
            return cols;
        },
    },
    created() {
        usam_api('table/columns', {
            type:this.table_name
        }, 'GET', (r) => this.user_columns = r);
    },
    methods:{
        tableLoading() {},
        checkElement(id) {
            let ok = true;
            for (let k in this.items) {
                if (this.items[k].id == id) {
                    ok = false;
                    break;
                }
            }
            return ok;
        },
        selectElement(e) {
            if (this.checkElement(e.id))
                this.$emit('add', e);
        },
        delElement(e, k) {
            e.preventDefault();
            this.items.splice(k, 1);
            this.$emit('change', this.items);
        },
    }
})
var table_products = {
    mixins:[data_filters, importer],
    data() {
        return {
            edit:true,
            importData:false,
            abilityChange:true,
            table_name:'',
            settingsTables:{
                columns:[],
                user_columns:[],
                column_names:[]
            },
            columns_tools:false,
            products:[],
            product_taxes:[],
            total_product_taxes:{},
            type_prices:[],
            type_price:0,
            discount:0,
            subtotal:0,
            taxtotal:0,
            totalprice:0,
            formatted_discount:0,
            formatted_subtotal:0,
            formatted_totalprice:0,
            pLoaded:false,
            tableShow:false,
            product_id:0,
            searchItem:'',
            id:0,
            product_discounts:'',
            add_sum:'',
            rounding:2,
        };
    },
    computed:{
        table_columns() {
            let cols = [];
            let j = 0;
            for (let k in this.settingsTables.columns) {
                cols[j] = {
                    id:k,
                    name:this.settingsTables.columns[k]
                };
                j++;
                if (k == 'title') {
                    for (let i in this.settingsTables.user_columns) {
                        cols[j] = {
                            id:i,
                            name:this.settingsTables.column_names[this.settingsTables.user_columns[i]]
                        };
                        j++;
                    }
                }
            }
            return cols;
        },
    },
    mounted() {
        if (typeof settingsTables !== typeof undefined && settingsTables[this.table_name] != undefined) {
            this.settingsTables = settingsTables[this.table_name];
            this.$watch('settingsTables.user_columns', this.updateUserColumns);
            this.tableLoading();
        }
    },
    watch:{
        product_taxes:{
            handler(val, oldVal) {
                for (let i in this.product_taxes) {
                    if (typeof this.total_product_taxes[this.product_taxes[i].tax_id] === typeof undefined)
                        this.total_product_taxes[this.product_taxes[i].tax_id] = {
                            tax:this.product_taxes[i].tax,
                            name:this.product_taxes[i].name,
                            is_in_price:this.product_taxes[i].is_in_price
                        };
                    else
                        this.total_product_taxes[this.product_taxes[i].tax_id].tax += this.product_taxes[i].tax;
                }
            },
            deep:true
        },
        edit(val, oldVal) {
            if (!this.abilityChange)
                this.edit = false;
        },
        type_price(val, oldVal) {
            if (val && oldVal !== 0) {
                var handler = (r) => {
                    for (let k in r.items) {
                        for (let i in this.products) {
                            if (r.items[k].ID == this.products[i].product_id) {
                                this.products[i].price = r.items[k].price;
                                this.products[i].old_price = r.items[k].old_price;
                            }
                        }
                    }
                };
                let ids = [];
                for (let k in this.products)
                    ids[k] = products[k].product_id;
                usam_api('products', {
                    post__in:ids,
                    status:['publish', 'draft'],
                    fields:['price'],
                    type_price:val,
                    count:1000
                }, 'POST', handler);
            }
        }
    },
    methods:{
        tableLoading() {},
        updateUserColumns() {
            usam_api('table/columns', {
                type:this.table_name,
                columns:this.settingsTables.user_columns
            }, 'POST');
        },
        clearSearch() {
            this.product_id = 0;
            this.searchItem = '';
        },
        delElement(e, k) {
            e.preventDefault();
            this.products.splice(k, 1);
        },
        selectElement(e) {
            if (this.checkProduct(e.id)) {
                this.product_id = e.id;
                this.searchItem = e.name;
            } else
                this.clearSearch();
        },
        checkProduct(id) {
            if (!id)
                return false;
            let ok = true;
            for (let k in this.products) {
                if (this.products[k].ID == id) {
                    if (typeof this.products[k].quantity !== typeof undefined)
                        this.products[k].quantity++;
                    ok = false;
                    break;
                }
            }
            return ok;
        },
        addElement(e) {
            if (this.product_id) {
                this.getElement(this.product_id);
                this.clearSearch();
            }
        },
        selectionProducts(id) {
            if (this.checkProduct(id))
                this.getElement(id);
        },
        recountProducts() {
            this.subtotal = 0;
            this.discount = 0;
            this.taxtotal = 0;
            let n = 0;
            let d = 0;
            let taxes = 0;
            let sum = 0;
            let p = {};
            for (let k in this.products) {
                p = this.products[k];
                if (typeof p.price === typeof undefined || typeof p.quantity === typeof undefined)
                    continue;
                if (typeof p.old_price === typeof undefined)
                    p.old_price = p.price;
                n = p.old_price;
                if (typeof n == 'string') {
                    if (n === '')
                        n = 0;
                    else
                        n = parseFloat(n.replace(/\s/g, ''));
                    p.old_price = n;
                }
                if (typeof p.discount === typeof undefined || p.discount === '')
                    p.discount = 0;
                else {
                    d = parseFloat(p.discount);
                    if (d > 0) {
                        if (p.type == 'p') {
                            if (d > 100) {
                                d = 100;
                                p.discount = 100;
                            }
                            n = p.old_price - p.old_price * d / 100;
                        } else
                            n = p.old_price - d;
                    }
                    p.discount = d - d.toFixed(2) > 0 ? d.toFixed(2) :d;
                    p.price = n;
                }
                p.formatted_price = this.formatted_number(p.price);
                p.formatted_discount = this.formatted_number(p.discount, 2);
                this.discount += (p.old_price - p.price) * p.quantity;
                p.taxes = {};
                taxes = 0;
                for (let i in this.product_taxes) {
                    if (p.taxes[this.product_taxes[i].tax_id] === undefined)
                        p.taxes[this.product_taxes[i].tax_id] = {};
                    if (this.product_taxes[i].product_id == p.product_id && this.product_taxes[i].unit_measure == p.unit_measure) {
                        if (this.product_taxes[i].is_in_price)
                            n = p.price * this.product_taxes[i].rate / (100 + this.product_taxes[i].rate);
                        else {
                            n = p.price * this.product_taxes[i].rate / 100;
                            taxes += n;
                        }
                        p.taxes[this.product_taxes[i].tax_id] = {
                            tax:this.formatted_number(n),
                            is_in_price:this.product_taxes[i].is_in_price
                        };
                    }
                }
                p.total = (p.price + taxes) * p.quantity;
                this.subtotal += (p.old_price + taxes) * p.quantity;
                p.total = parseFloat(p.total.toFixed(this.rounding));
                sum += p.total;
                p.formatted_total = this.formatted_number(p.total);
                this.products[k] = p;
                this.taxtotal += taxes;
            }
            this.calculate_totalprice(sum);
            this.formatted_discount = this.formatted_number(this.discount);
            this.discount = this.discount.toFixed(this.rounding);
            this.formatted_totalprice = this.formatted_number(this.totalprice);
            this.formatted_subtotal = this.formatted_number(this.subtotal);
        },
        calculate_totalprice(sum) {
            this.totalprice = sum;
        },
        formatted_number(number, r) {
            r = r === undefined ? this.rounding :r;
            if (typeof number == 'string')
                number = Number(number);
            return number.toFixed(r).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        },
        charge_price(val) {
            val = parseFloat(val);
            if (!isNaN(val)) {
                var o = 0,
                s = 0;
                for (let k in this.products) {
                    s = val * (this.products[k].old_price * 100 / this.totalprice) / 100 + o;
                    m = Math.round(s);
                    this.products[k].old_price += m;
                    o = s - m;
                }
                this.recountProducts();
            }
        },
        changeDiscount(val) {
            val = parseFloat(val);
            if (!isNaN(val)) {
                for (let k in this.products)
                    this.products[k].discount = val;
                this.recountProducts();
            }
        },
        addBonuses(val) {
            val = parseFloat(val);
            var total = this.subtotal - this.discount - this.taxtotal;
            if (!isNaN(val) && val < total) {
                var prozent = val * 100 / total;
                var o = 0,
                s = 0,
                d = 0;
                for (let k in this.products) {
                    s = prozent * this.products[k].price / 100 + o;
                    this.products[k].used_bonuses = Math.round(s);
                    s = this.products[k].price - s;
                    d = Math.round(s);
                    o = s - d;
                    this.products[k].discount = 100 - (d / this.products[k].old_price * 100);
                }
                this.recountProducts();
            }
        },
        getElementQuery() {
            return {
                add_fields:['small_image', 'sku', 'price']
            };
        },
        getElement(id) {
            usam_api('product/' + id, this.getElementQuery(), 'GET', this.formattingElement);
        },
        formattingElement(item) {
            if (item.price === undefined)
                item.price = 0;
            if (item.old_price === undefined)
                item.old_price = item.price;
            if (item.quantity === undefined)
                item.quantity = 1;
            item.product_id = item.ID;
            item.id = '+' + this.products.length;
            this.pushElement(item);
        },
        addToDocument(item) {
            item.name = item.post_title;
            item.type = 'p';
            item.discounts = [];
            if (item.bonus === undefined)
                item.bonus = 0;
            item.formatted_bonus = 0;
            if (item.discount === undefined) {
                item.discount = item.old_price > 0 ? 100 - item.price * 100 / item.old_price :0;
                item.formatted_discount = this.formatted_number(item.discount);
                item.discount = item.discount.toFixed(2);
            }
            var add = true;
            for (let i in this.products) {
                if (this.products[i].product_id == item.product_id && this.products[i].unit_measure == item.unit_measure) {
                    this.products[i].quantity += 1;
                    this.recountProducts();
                    add = false;
                    break;
                }
            }
            delete item.ID;
            item.old_price = item.old_price || item.price;
            if (add) {
                this.products.push(item);
                this.recountProducts();
            }
        },
        pushElement(item) {
            this.products.push(item);
        },
        startImport() {
            var callback = (r) => {
                let property = false;
                let properties = {};
                let values = [];
                for (let i in this.value_name) {
                    if (!property && (this.value_name[i] == 'sku' || this.value_name[i] == 'barcode')) {
                        property = this.value_name[i];
                        for (let j in r) {
                            if (typeof r[j][i] == 'string')
                                r[j][i] = r[j][i].trim();
                            if (r[j][i]) {
                                values.push(r[j][i]);
                                properties[r[j][i]] = {}
                                for (let k in this.value_name) {
                                    if (this.value_name[k])
                                        properties[r[j][i]][this.value_name[k]] = r[j][k];
                                }
                            }
                        }
                        break;
                    }
                }
                if (property) {
                    var vars = {
                        productmeta:[{
                                key:property,
                                value:values,
                                compare:'IN'
                            }
                        ]
                    };
                    args = Object.assign(this.getElementQuery(), vars);
                    if (args.fields)
                        args.fields.push(property);
                    else
                        args.fields = [property];
                    this.getProducts(args, (r) => {
                        for (let i in r.items) {
                            if (this.checkProduct(r.items[i].ID)) {
                                r.items[i] = Object.assign(r.items[i], properties[r.items[i][property]]);
                                this.formattingElement(r.items[i]);
                            }
                        }
                    });
                }
            };
            usam_active_loader();
            usam_api('importer/file/data', {
                file:this.file.name,
                file_settings:this.file_settings
            }, 'POST', callback);
        },
        getProducts(vars, handler) {
            vars.count = 10000;
            vars.status = 'any';
            usam_api('products', vars, 'POST', handler);
        },
        deleteElements() {
            this.products = [];
        },
        requestData(data) {
            usam_active_loader();
            USAM_Tabs.table_view(data, jQuery('.usam_tab_table'));
        },
        viewer(k) {
            product_viewer.product = {
                ID:this.products[k].product_id === undefined ? this.products[k].ID :this.products[k].product_id
            };
            product_viewer.init();
        }
    }
}
var crm_form = {
    mixins:[files, edit_properties],
    data() {
        return {
            data:{},
            request:false,
            crm_type:'',
        }
    },
    beforeMount() {
        let url = new URL(document.location.href);
        this.crm_type = url.searchParams.get('form_name');
    },
    created() {
        this.data = form_data;
        this.cFile.user_id = this.data.user_id;
    },
    mounted() {
        if (document.querySelector('.usam_attachments'))
            this.fDownload();
        this.loadProperties();
    },
    methods:{
        loadProperties() {
            if (typeof this.data.id === typeof undefined || this.data.id === 0) {
                usam_api('property_groups', {
                    type:this.crm_type
                }, 'POST', (r) => this.propertyGroups = r.items);
                usam_api('properties', {
                    type:this.crm_type,
                    fields:'code=>data'
                }, 'POST', (r) => this.properties = this.propertyProcessing(r.items));
            } else
                usam_api(this.crm_type + '/' + this.data.id, {
                    add_fields:'groups,properties'
                }, 'GET', this.preparationData);
        },
        user_change(e) {
            this.user = e.user_login;
            this.data.user_id = e.ID;
            this.cFile.user_id = e.ID;
        },
        companyChange(e) {
            this.company = e;
            this.data.company_id = e.id;
        }
    }
}
var company = {
    mixins:[formTools],
    data() {
        return {
            company:{},
            manager:{},
            statuses:[],
            accounts:[],
			currencies:[],
			image:{},
            taggedAccounts:[],
            argsEmployees:{
                add_fields:['foto', 'status_data', 'post', 'affairs', 'communication', 'manager'],
                source:'all'
            },
            argsDivisions:{
                add_fields:['logo', 'status_data', 'affairs', 'communication', 'manager'],
                search_columns:['id', 'name', 'inn', 'email', 'phone', 'site', 'group']
            }
        }
    },
    created() {
        this.data = form_data;
        this.argsEmployees.company_id = this.data.id;
        this.argsDivisions.parents = this.data.id;
    },
    mounted() {
        usam_api('statuses', {type:'company'}, 'GET', (r) => this.statuses = r);
		usam_api('currencies', {fields:'autocomplete'}, 'GET', (r) => this.currencies = r.items);		
        if (this.data.id > 0) {
            const ObAccount = new IntersectionObserver((els, Observer) => {
                els.forEach((e) => {
                    if (e.isIntersecting) {
                        usam_api('accounts', {company:this.data.id, order:'ASC'}, 'POST', (r) => this.formatAccounts(r.items));
                        Observer.unobserve(e.target);
                    }
                })
            }, {
                rootMargin:'0px 0px 50px 0px'
            });
            var el = document.querySelector('#usam_company_accounts');
            if (el !== null)
                ObAccount.observe(el);
        }
    },
    methods:{
        getDataDirectory(e) {
            e.preventDefault();
            if (this.properties.inn)
                usam_api('directory/companies', {search:this.properties.inn.value}, 'GET', (r) => this.properties = this.propertyProcessing(r));
        },
        formatAccounts(items) {
            for (let i in items) {
                items[i].edit = false;
                items[i].show = true;
            }
            this.accounts = items
        },
        addAccount() {
            this.accounts.push({id:0, name:'', bic:'', company_id:0,address:'',swift:'',number:'',bank_ca:'',currency:form_data.currency,edit:true,show:true});
        },
        saveAccount(k) {
            if (this.request)
                return;
            this.request = true;
            this.accounts[k].edit = false;
            if (this.accounts[k].id > 0)
                usam_api('account/' + this.accounts[k].id, this.accounts[k], 'POST', (r) => {
                    this.request = false;
                    usam_admin_notice(r);
                });
            else {
                this.accounts[k].company_id = this.data.id;
                usam_api('account', this.accounts[k], 'POST', (r) => {
                    this.accounts[k] = Object.assign(this.accounts[k], r);
                    this.request = false;
                    usam_admin_notice(r);
                });
            }
        },
        markDeleteAccount(k) {
            if (this.accounts[k].id > 0)
                this.taggedAccounts.push(this.accounts[k]);
            this.accounts.splice(k, 1);
        },
        saveAccounts() {
            if (this.taggedAccounts.length) {
                var ids = [];
                this.request = true;
                for (let i in this.taggedAccounts)
                    ids.push(this.taggedAccounts[i].id);
                this.taggedAccounts = [];
                usam_api('accounts', {
                    items:ids
                }, 'DELETE', (r) => {
                    usam_admin_notice(r);
                    this.request = false;
                });
                usam_api('accounts', {
                    items:this.accounts
                }, 'PUT', (r) => {
                    this.formatAccounts(r);
                    usam_admin_notice(r);
                    this.request = false;
                });
            }
        },
        saveForm(add) {
            var data = Object.assign(this.data, this.getValues());
            if (typeof tinyMCE !== typeof undefined) {
                var t = tinyMCE.get('description_tinymce_1');
                if (t !== null)
                    data.description = t.getContent();
            }
            if (this.data.id)
                usam_api('company/' + this.data.id, data, 'POST', (r) => {
                    if (add === true)
                        this.addNew();
                    usam_admin_notice(r);
                });
            else
                usam_api('company', data, 'POST', (id) => {
                    if (add === true)
                        this.addNew();
                    else
                        this.afterAdding(id);
                });
        },
        deleteItem() {
            if (this.data.id)
                usam_api('company/' + this.data.id, 'DELETE', this.afterDelete);
        },        
        addUser(item) {
            this.addManager(item);
        }
    }
}

var contact = {
    mixins:[formTools],
    data() {
        return {
            lfp:'',
            favorite_shop:{},
            manager:{},
			image:{},
            statuses:[],
        }
    },
    computed:{
        lastname_f_p() {
            if (Object.keys(this.data).length)
                return this.getName();
            else
                return '';
        },
    },
    created() {
        this.data = form_data;
        this.lfp = this.data.lastname + ' ' + this.data.firstname + ' ' + this.data.patronymic;
        if (this.data.appeal === '' && !this.lfp)
            this.data.appeal = this.getName();
        this.data.appeal = this.data.appeal.trim()
            this.$watch('lfp', this.changeName);
        usam_api('statuses', {type:this.data.contact_source == 'employee' ? 'employee' :'contact'}, 'GET', (r) => this.statuses = r);
    },
    methods:{
        changeName(v, old) {
            if (v != old && old) {
                v = v.trimStart();
                this.lfp = v;
                v = v.trim();
                var m = v.split(' ');
                this.data.lastname = m[0];
                this.data.firstname = m[1] !== undefined ? m[1] :'';
                this.data.patronymic = m[2] !== undefined ? m[2] :'';
                if (v != '')
                    this.data.appeal = this.getName();
            }
        },
        getName() {
            var name = this.data.lastname
                if (this.data.firstname != '')
                    name += ' ' + this.data.firstname[0] + '.';
                if (this.data.patronymic != '')
                    name += ' ' + this.data.patronymic[0] + '.';
                return name;
        },
        save(add, ob) {
            var data = Object.assign(this.getValues(), this.data);
            if (typeof tinyMCE !== typeof undefined) {
                var t = tinyMCE.get('description_tinymce_1');
                if (t !== null)
                    data.about = t.getContent();
            }
            if (this.data.id)
                usam_api(ob + '/' + this.data.id, data, 'POST', (r) => {
                    if (add === true)
                        this.addNew();
                    usam_admin_notice(r);
                });
            else
                usam_api(ob, data, 'POST', (id) => {
                    if (add === true)
                        this.addNew();
                    else
                        this.afterAdding(id);
                });
        },
        del(ob) {
            if (this.data.id)
                usam_api(ob + '/' + this.data.id, 'DELETE', this.afterDelete);
        },        
        addUser(item) {
            this.addManager(item);
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    if (typeof VueColor !== typeof undefined) {
        Vue.component('color-picker', {
            props:{
                value:{type:String, required:false, default:''},
                type:{type:String, required:false, default:'hex8'},
            },
            components:{
                'Sketch-picker':VueColor.Sketch
            },
            template:`<div class="color_picker"><div class="color_picker__result_container" @click="show=!show"><div class="color_picker__result" :style="style"></div></div><div class="color_picker__holder"><Sketch-picker v-show="show" v-model="color" @input="color=$event[type];" @ok="show=false" @cancel="color=oldColor;show=false"/></div></div>`,
            data() {
                return {
                    show:false,
                    oldColor:this.value,
                    color:this.value
                };
            },
            watch:{
                value(v) {
                    this.color = v;
                },
                show(v) {
                    return this.oldColor = this.value;
                },
                color(v) {
                    this.$emit('input', v)
                },
            },
            computed:{
                style() {
                    return 'background:' + (this.type == 'hex8' && this.value == '#00000000' || this.type == 'hex' && this.value == '#00000000' ? '' :this.value);
                }
            }
        });
    }
	if (document.querySelector('.report_view')) {
        var page_report = new Vue({
            el:'.report_view',
            mixins:[crm_report]
        });
    }
    if (document.getElementById('notes')) {
        new Vue({
            el:'#notes',
            data() {
                return {
                    lists:[],
                    item:{
                        id:0,
                        note:''
                    },
                };
            },
            mounted() {
                usam_api('notes', 'GET', (r) => {
                    if (r.length) {
                        for (k in r) {
                            this.lists[k] = r[k];
                            this.get_title(k);
                        }
                        this.item = r[0];
                    }
                });
                document.querySelector('#button-add').addEventListener("click", this.event_add);
                document.addEventListener("beforeunload", this.update);
            },
            methods:{
                save(e) {
                    if (this.item.id)
                        this.update();
                    else
                        this.add();
                },
                get_title(k) {
                    strings = this.lists[k].note.split(/\r?\n/);
                    i = 0;
                    for (j in strings) {
                        if (strings[j] != '') {
                            i++;
                            if (i == 1)
                                this.lists[k].name = strings[j];
                            else {
                                this.lists[k].des = strings[j];
                                break;
                            }
                        }
                    }
                    if (i == 0) {
                        this.lists[k].name = '';
                        this.lists[k].des = '';
                    } else if (i == 1)
                        this.lists[k].des = '';
                },
                update() {
                    for (k in this.lists) {
                        if (this.lists[k].id == this.item.id) {
                            this.lists[k].note = this.item.note;
                            this.get_title(k);
                            break;
                        }
                    }
                    usam_api('note/' + this.item.id, this.item, 'POST');
                },
                event_add(e) {
                    e.preventDefault();
                    this.item = {
                        id:0,
                        note:''
                    }
                    this.add();
                    this.$refs.writepad.focus();
                },
                add() {
                    usam_api('note', {
                        note:this.item.note
                    }, 'POST', (id) => {
                        if (id) {
                            this.item.id = id;
                            this.lists.unshift(this.item);
                            for (k in this.lists)
                                this.get_title(k);
                        }
                    });
                },
                del(k) {
                    id = this.lists[k].id;
                    usam_api('note/' + id, {}, 'DELETE');
                    if (this.item.id == id) {
                        i = k + 1;
                        if (typeof this.lists[i] !== typeof undefined)
                            this.item = this.lists[i];
                        else {
                            i = k - 1;
                            if (typeof this.lists[i] !== typeof undefined)
                                this.item = this.lists[i];
                            else
                                this.item = {
                                    id:0,
                                    note:''
                                }
                        }
                    }
                    this.lists.splice(k, 1);
                },
                open(k) {
                    if (typeof this.lists[k] !== typeof undefined) {
                        this.item = this.lists[k];
                        if (document.documentElement.clientWidth < 1024)
                            document.getElementById("note_writepad").scrollIntoView({
                                behavior:"smooth"
                            });
                    }
                }
            }
        })
    }
    if (document.getElementById('add_event')) {
        new_event = new Vue({
            el:'#add_event',
            mixins:[add_event],
        })
    }
    if (document.getElementById('events_reminder')) {
        new Vue({
            el:'#events_reminder',
            data() {
                return {
                    events:[],
                    load:false
                }
            },
            mounted() {
                usam_api('events', {
                    status:'started',
                    reminder:1
                }, 'POST', (r) => {
                    for (let i in r.items)
                        r.items[i].minute = 5;
                    this.events = r.items;
                    this.load = true;
                });
            },
            methods:{
                remind(k) {
                    usam_api('event/' + this.events[k].id, {
                        reminder_date:this.events[k].minute
                    }, 'POST');
                    this.events.splice(k, 1);
                },
                close(k) {
                    usam_api('event/' + this.events[k].id, {
                        reminder_date:''
                    }, 'POST');
                    this.events.splice(k, 1);
                }
            }
        })
    }
    if (document.getElementById('edit_form_email_clearing')) {
        new Vue({
            el:'#edit_form_email_clearing',
            data() {
                return {
                    mailbox:'',
                    day:14
                }
            },
            methods:{
                clearing() {
                    usam_active_loader();
                    usam_send({
                        nonce:USAM_Tabs.bulkactions_nonce,
                        action:'bulkactions',
                        a:'clearing_email',
                        item:'email',
                        clearing_day:this.day,
                        m:this.mailbox
                    });
                },
            }
        })
    }
    if (document.getElementById('add_items_gift')) {
        new Vue({
            el:'#add_items_gift',
            mixins:[table_products],
            data() {
                return {
                    table_name:'gift'
                }
            },
            mounted() {
                let url = new URL(document.location.href);
                this.id = url.searchParams.get('id');
                if (this.id) {
                    usam_api('products', {
                        productmeta:[{
                                key:'gift',
                                value:this.id,
                                compare:'='
                            }
                        ],
                        status:['publish', 'draft'],
                        add_fields:['small_image', 'sku'],
                        count:100
                    }, 'POST', (r) => {
                        this.products = r.items;
                        this.pLoaded = true;
                    });
                } else
                    this.pLoaded = true;
            }
        })
    }
    if (document.getElementById('edit_form_fix_price_discount')) {
        new Vue({
            el:'#edit_form_fix_price_discount',
            data() {
                return {
                    loaded:false,
                    query:{
                        add_fields:['small_image', 'sku', 'price', 'discount_price']
                    },
                    products:[]
                }
            },
            mounted() {
                let url = new URL(document.location.href);
                this.id = url.searchParams.get('id');
                if (this.id) {
                    usam_api('products', {
                        pricemeta:[{
                                key:'fix_price_' + this.id,
                                compare:'EXISTS'
                            }
                        ],
                        status:['publish', 'draft'],
                        add_fields:['small_image', 'sku', 'discount_price', 'price'],
                        rule_id:this.id,
                        count:10000
                    }, 'POST', (r) => {
                        for (let i in r.items)
                            r.items[i].product_id = r.items[i].ID;
                        this.products = r.items;
                        this.loaded = true;
                    });
                } else
                    this.loaded = true;
            },
            methods:{
                formattingProduct(products) {
                    for (let i in products)
                        if (!products[i].discount_price)
                            products[i].discount_price = products[i].price;
                    return products;
                },
            }
        })
    }
    if (document.getElementById('edit_form_product_day')) {
        new Vue({
            el:'#edit_form_product_day',
            mixins:[formTools],
            data() {
                return {
                    data:{},
                    oldIndex:0,
                    prices:[],
                    taxonomies:[],
                    terms:[]
                }
            },
            created() {
                this.data = form_data;
                usam_api('type_prices', {
                    fields:'code=>title'
                }, 'GET', (r) => this.prices = r.items);
                usam_api('taxonomies', {
                    object_type:'usam-product'
                }, 'POST', (r) => this.taxonomies = r);
                usam_api('terms', {
                    taxonomy_object:'usam-product',
                    hide_empty:0,
                    count:10000,
                    orderby:'sort',
                    order:'asc',
                    name_format:'hierarchy'
                }, 'POST', (r) => {
                    var t = {};
                    for (let i in r.items) {
                        if (typeof t[r.items[i].taxonomy] === typeof undefined)
                            t[r.items[i].taxonomy] = []
                            t[r.items[i].taxonomy].push({
                                id:r.items[i].term_id,
                                name:r.items[i].name
                            })
                    }
                    this.terms = t
                });
            },
            methods:{
                sortable(k, i) {
                    let v = structuredClone(this.data.products[i]);
                    this.data.products.splice(i, 1);
                    this.data.products.splice(k, 0, v);
                },
                formattingProduct(products) {
                    for (let i in products)
                        if (products[i].discount === undefined) {
                            products[i].discount = 0;
                            products[i].dtype = 'p';
                            products[i].status = 0;
                        }
                    this.data.products = products;
                },
                delElement(e, k) {
                    e.preventDefault();
                    this.data.products.splice(k, 1);
                },
                allowDrop(e, k) {
                    e.preventDefault();
                    if (this.oldIndex != k) {
                        let v = Object.assign({}, this.data.products[this.oldIndex]);
                        this.data.products.splice(this.oldIndex, 1);
                        this.data.products.splice(k, 0, v);
                        this.oldIndex = k;
                    }
                },
                drag(e, k) {
                    this.oldIndex = k;
                    if (e.target.hasAttribute('draggable'))
                        e.currentTarget.classList.add('draggable');
                    else
                        e.preventDefault();
                },
                dragEnd(e, i) {
                    e.currentTarget.classList.remove('draggable');
                    for (i = 0; i < this.data.products.length; i++)
                        this.data.products[i].sort = i;
                },
                deleteItem() {
                    if (this.data.id)
                        usam_api('product/day/' + this.data.id, 'DELETE', this.afterDelete);
                },
                saveForm() {
                    var data = structuredClone(this.data);
                    if (this.data.id) {
                        usam_active_loader();
                        usam_api('product/day/' + this.data.id, data, 'POST', usam_admin_notice);
                    } else {
                        usam_api('product/day', data, 'POST', (id) => this.afterAdding(id));
                    }
                },
            }
        })
    }
    if (document.getElementById('edit_form_loyalty_program')) {
        new Vue({
            el:'#edit_form_loyalty_program',
            data() {
                return {
                    data:{},
                }
            },
            created() {
                this.data = form_data;
            }
        })
    }
    if (document.getElementById('edit_form_accumulative')) {
        new Vue({
            el:'#edit_form_accumulative',
            data() {
                return {
                    data:{},
                }
            },
            created() {
                this.data = form_data;
            }
        })
    }
    if (document.getElementById('edit_form_set')) {
        new Vue({
            el:'#edit_form_set',
            mixins:[table_products],
            data() {
                return {
                    table_name:'set'
                }
            },
            mounted() {
                this.pLoaded = true;
                let url = new URL(document.location.href);
                this.id = url.searchParams.get('id');
                if (this.id) {
                    usam_api('set/' + this.id, 'GET', (r) => {
                        this.pLoaded = true;
                        this.products = r.products;
                    });
                }
            },
            methods:{
                getElementQuery() {
                    return {
                        add_fields:['small_image', 'sku', 'price', 'category']
                    };
                },
                pushElement(item) {
                    item.value = 0;
                    item.status = 1;
                    item.quantity = 1;
                    item.id = '+' + this.products.length;
                    if (item.category.length)
                        item.category_id = item.category[0].term_id;
                    else
                        item.category_id = 0;
                    this.products.push(item);
                }
            }
        })
    }
    if (document.querySelector('.applications_grid')) {
        new Vue({
            el:'.applications_grid',
            data() {
                return {
                    items:[],
                    start:false
                };
            },
            mixins:[data_filters],
            computed:{
                selectedItems() {
                    return this.items.filter(x => x.checked);
                },
				numberSelectedItems() {
					return this.selectedItems.length;
				},
            },			
            mounted() {
                data = {};
                let url = new URL(document.location.href);
                data.s = url.searchParams.get('s');
                this.requestData(data);
            },
            methods:{
                requestData(data) {
                    if (this.start)
                        usam_active_loader();
                    else
                        this.start = true;
                    if (document.getElementById('tab_installed_applications_content'))
                        data.installed = 1;
                    this.items = [];
                    usam_api('applications', data, 'POST', (r) => {
                        this.items = r.items;
                    });
                }
            }
        })
    }
    if (document.getElementById('edit_form_banner')) {
        new Vue({
            el:'#edit_form_banner',
            mixins:[table_products, slideEditor, formTools],
            data() {
                return {
                    register:{},
                    statuses:{},
                    tabSettings:{},
                    types:{},
                    table_name:'banner',
                    regions:[],
                    roles:[],
                }
            },
            computed:{
                nameJSONFile() {
                    return this.data.name;
                },
                slide() {
                    return this.data;
                },
                layers() {
                    return this.data.settings.layers;
                },
                selectedlayers() {
                    return this.data.settings.layers.filter(x => x.selected);
                },
            },
            mounted() {
                this.show = this.data.actuation_time;
                let ids = [];
                for (let k in this.data.settings.products)
                    ids[k] = this.data.settings.products[k].product_id;
                if (ids.length) {
                    usam_api('products', {post__in:ids, add_fields:['small_image', 'sku'], count:1000}, 'POST', (r) => {
                        let items = [];
                        let j = 0;
                        for (let k in this.data.settings.products) {
                            for (let i in r.items) {
                                if (r.items[i].ID == this.data.settings.products[k].product_id) {
                                    delete r.items[i].ID;
                                    items[j] = Object.assign(r.items[i], this.data.settings.products[k]);
                                    j++;
                                }
                            }
                        }
                        this.products = items;
                        this.pLoaded = true;
                    });
                } else
                    this.pLoaded = true;
            },
            created() {
                this.dataFormatting(form_data);
            },
            methods:{
                dataFormatting(r) {
                    this.data = r;
                    this.layersFormatting(this.data.settings.layers);
                    this.watchState = this.$watch('slide.type', this.changeSettings, {deep:true});
                },
                pushElement(item) {
                    item[this.device] = {inset:'50px auto auto 50px'}
                    this.products.push(item);
                },
                pointMousedown(i, e) {
                    this.toolTabs = 'products';
                    this.movePoint = true;
                    this.moveX = e.pageX;
                    this.moveY = e.pageY;
                },
                pointMousemove(i, e) {
                    if (this.movePoint) {
                        var y = (e.currentTarget.parentNode.offsetTop + e.pageY - this.moveY) * 100 / this.$refs.layers.offsetHeight;
                        var x = (e.currentTarget.parentNode.offsetLeft + e.pageX - this.moveX) * 100 / this.$refs.layers.offsetWidth;
                        this.products[i][this.device].inset = y.toFixed(2) + '% auto auto ' + x.toFixed(2) + '%';
                        this.moveX = e.pageX;
                        this.moveY = e.pageY;
                    }
                },
                saveForm() {
                    if (this.data.type == 'products') {
                        this.data.settings.products = []
                        for (let i in this.products) {
                            var p = {product_id:this.products[i].product_id};
                            for (let k in this.devices)
                                if (this.devices[k])
                                    p[k] = this.products[i][k];
                            this.data.settings.products.push(p);
                        }
                    } else if (this.data.type == 'html' && typeof tinyMCE !== typeof undefined) {
                        var t = tinyMCE.get('description_tinymce');
                        if (t !== null)							
                            this.data.settings.html = t.getContent();
						else
							this.data.settings.html = document.getElementById('description_tinymce').value;						
                    }
                    if (this.data.id)
                        usam_api('banner/' + this.data.id, this.data, 'POST', usam_admin_notice);
                    else
                        usam_api('banner', this.data, 'POST', (id) => this.afterAdding(id));
                },
                deleteItem() {
                    if (this.data.id)
                        usam_api('banner/' + this.data.id, 'DELETE', this.afterDelete);
                }
            }
        })
    } else if (document.getElementById('edit_form_slider')) {
        new Vue({
            el:'#edit_form_slider',
            mixins:[slideEditor, formTools],
            data() {
                return {
                    defaultSlide:{
                        title:'',
                        description:'',
                        type:'image',
                        settings:{
                            css:{
                                'border-radius':0,
                                'background-size':'cover',
                                'background-position':'center center',
                                'background-repeat':'no-repeat'
                            },
                            filter:'',
                            filter_opacity:1,
                            effect:'',
                            classes:'',
                            custom_css:'',
                            'background-color':'',
                            layers:[],
                            actions:{
                                value:'',
                                type:''
                            },
                            object_size:{
                                width:'',
                                height:''
                            }
                        },
                        object_url:'',
                        interval_from:'',
                        interval_to:''
                    },
                    tab:'settings',
                    settings:{},
                    slideActive:0,
                    toolTabs:'slides',
                    webforms:[],
                    regions:[],
                    roles:[],
                }
            },
            computed:{
                nameJSONFile() {
                    return this.data.name;
                },
                slide() {
                    if (this.data.slides === undefined)
                        return {};
                    return this.data.slides[this.slideActive];
                },
                buttonsCSS() {
                    var css = '';
                    switch (this.data.settings.button.position) {
                    case 'top left':
                        css += 'top:0px; left:0;';
                        break;
                    case 'top center':
                        css += 'top:0px; left:50%; transform:translate(-50%, 0%);';
                        break;
                    case 'center left':
                        css += 'top:50%; left:0; transform:translate(0, -50%);';
                        break;
                    case 'center center':
                        css += 'top:50%; left:50%; transform:translate(-50%, -50%);';
                        break;
                    case 'center right':
                        css += 'top:50%; right:0; transform:translate(0, -50%);';
                        break;
                    case 'bottom left':
                        css += 'bottom:0px; left:0;';
                        break;
                    case 'bottom right':
                        css += 'bottom:0px; right:0;';
                        break;
                    case 'bottom center':
                    default:
                        css += 'bottom:0px; left:50%; transform:translate(-50%, 0%);';
                        break;
                    }
                    if (this.data.settings.button.margin)
                        css += 'margin-left:' + this.data.settings.button.margin + ';';
                    if (this.data.settings.button.orientation)
                        css += 'flex-direction:' + this.data.settings.button.orientation + ';';
                    return css;
                },
                buttonCSS() {
                    var css = '';
                    for (k in this.data.settings.button.css)
                        css += k + ':' + this.data.settings.button.css[k] + ';';
                    return css;
                },
                layers() {
                    return this.data.slides[this.slideActive].settings.layers;
                },
                slideAnimation() {
                    return this.slide.settings.effect && this.tab == 'slides' && this.section[this.tab] == 'animation' ? 'effect_' + this.slide.settings.effect :'';
                },
                template() {
                    if (this.data.template)
                        return this.templates.filter(x => x.id == this.data.template)[0];
                    return {};
                },
            },
            watch:{
                active(v) {
                    this.data.active = v ? 1 :0;
                }
            },
            created() {
                this.dataFormatting(form_data);
            },
            mounted() {
                usam_api('sales_area', 'GET', (r) => this.regions = r.items);
            },
            methods:{
                dataFormatting(r) {
                    if (r.slides.length)
                        for (let k in r.slides) {
                            var d = structuredClone(this.defaultSlide);
                            r.slides[k] = Object.assign({}, d, r.slides[k]);
                            r.slides[k].settings = Object.assign({}, d.settings, r.slides[k].settings);
                            if (r.slides[k].settings.layers !== undefined)
                                this.layersFormatting(r.slides[k].settings.layers);
                        }
                    this.data = r;
                    this.watchState = this.$watch('slide.type', this.changeSettings, {
                        deep:true
                    });
                    this.active = this.data.active === 1;
                    if (!this.data.slides.length)
                        this.data.slides.push(structuredClone(this.defaultSlide));
                },
                saveForm() {
                    for (k in this.data.slides) {
                        for (i in this.data.slides[k].settings.layers) {
                            var l = this.data.slides[k].settings.layers[i];
                            l.inset = parseInt(l.top) / parseInt(this.height) * 100 + '% auto auto ' + parseInt(l.left) / parseInt(this.width) * 100 + '%'
                        }
                    }
                    if (this.data.id)
                        usam_api('slider/' + this.data.id, this.data, 'POST', (r) => usam_admin_notice(r));
                    else
                        usam_api('slider', this.data, 'POST', (id) => this.afterAdding(id));
                },
                sortSlides(oldindex, index) {
                    let v = structuredClone(this.data.slides[oldindex]);
                    this.data.slides.splice(oldindex, 1);
                    this.data.slides.splice(index, 0, v);
                    for (let k in this.data.slides)
                        this.data.slides[k].sort = k;
                },
                delSlides(k) {
                    this.watchState();
                    if (k === this.slideActive)
                        this.slideActive = 0;
                    this.data.slides.splice(k, 1);
                    this.watchState = this.$watch('slide.type', this.changeSettings, {
                        deep:true
                    });

                },
                cloneSlide(e, i) {
                    var l = structuredClone(this.data.slides[i]);
                    l.id = 0;
                    var i = this.data.slides.unshift(l);
                },
                duplicateSlide(e, i) {
                    this.cloneSlide(e, i);
                    this.$refs['menu-slide'].style.display = 'none';
                },
                addSlide() {
                    this.slideActive = this.data.slides.push(structuredClone(this.defaultSlide)) - 1;
                },
                deleteItem() {
                    if (this.data.id)
                        usam_api('slider/' + this.data.id, 'DELETE', this.afterDelete);
                },
                getDefaultButtonCSS(e) {
                    this.data.settings.button.design = e.id;
                },
            }
        })
    }
    document.querySelectorAll('.user_autocomplete').forEach((el) => {
        new Vue({
            el:el,
            data() {
                return {
                    user_id:0
                }
            },
            mounted() {
                this.user_id = el.getAttribute('user_id');
            },
            methods:{
                change(e) {
                    this.user_id = e.id;
                }
            }
        })
    });
    if (document.querySelector('#manager_chat')) {
        manager_chat = new Vue({
            el:'#manager_chat',
            mixins:[chat],
            mounted() {
                this.startUpdate = true;
            }
        })
    } else if (document.querySelector('.exchange_table')) {
        var ruleImporter = {
            data() {
                return {
                    jqXHR:'',
                    files:{},
                };
            },
            methods:{
                fileDelete(k) {
                    this.files = {}
                    // {name:'',title:'', size:'',icon:'', percent:0, load:false, error:false};
                },
                fileDrop(id, e) {
                    e.preventDefault();
                    var el = document.querySelector('.import_attachments.over');
                    if (el)
                        el.classList.remove('over');
                    this.fileUpload(id, e.dataTransfer.files[0]);
                },
                fileAttach(e) {
                    var el = e.target.querySelector('input[type="file"]');
                    if (el)
                        el.click();
                    else if (e.target.nextElementSibling)
                        e.target.nextElementSibling.click();
                },
                allowDrop(e) {
                    e.preventDefault();
                    var el = document.querySelector('.import_attachments.over');
                    if (el)
                        el.classList.remove('over');
                    e.currentTarget.classList.add('over');
                },
                fileChange(id, e) {
                    if (!e.target.files[0])
                        return;
                    this.fileUpload(id, e.target.files[0]);
                },
                fileUpload(id, f) {
                    Vue.set(this.files, id, {load:true, percent:0, error:''});
                    var formData = new FormData();
                    formData.append('file', f);
                    formData.append('template_id', id);
                    xhr = usam_form_save(formData, (r) => {
                        if (r.status == 'success') {
                            this.files = {};
                            if (r.file_library)
                                usam_api('importer', {template_id:id}, 'POST', (r) => USAM_Tabs.update_table());
                            else
                                usam_api('importer', {file:r.name, emplate_id:id}, 'POST', (r) => USAM_Tabs.update_table());
                        } else
                            this.files[id].error = r.error_message;
                    }, (e) => this.files[id].percent = e.loaded * 100 / e.total, 'importer/file/upload');
                },
            }
        };
        interface_filters = new Vue({
            el:'.table_view .interface_filters',
            mixins:[data_filters],
            methods:{
                requestData(data) {
                    usam_active_loader();
                    USAM_Tabs.table_view(data, jQuery('.table_view'));
                }
            }
        }); 
        var table = {mixins:[ruleImporter], el:'.exchange_table'}
        document.addEventListener('table-load', (e) => {
            new Vue(table);
        });
        new Vue(table);
    } else if (document.querySelector('.table_view .interface_filters')) { 
        interface_filters = new Vue({
            el:'.table_view',
            mixins:[data_filters, listTable],
            methods:{
                requestData(data) {
                    usam_active_loader();
                    USAM_Tabs.table_view(data, jQuery('.table_view'));
                },
            }
        });
    } else if (document.getElementById('post_filters')) {
        interface_filters = new Vue({
            el:'#post_filters',
            mixins:[data_filters],
            data() {
                return {
                    filtersData:{}
                }
            },
            methods:{
                requestData(data) {
                    usam_active_loader();
                    USAM_Products.table_view(data);
                },
                get_filters_bulk_actions() {
                    var products = [];
                    i = 0;
                    document.querySelectorAll('.wp-list-table tbody .check-column input:checked').forEach((el) => {
                        products[i] = parseInt(el.value);
                        i++;
                        el.checked = false;
                    });
                    var filters = {};
                    if (products.length)
                        filters.post__in = products;
                    else
                        filters = this.filtersData;
                    var url = new URL(document.location.href);
                    if (url.searchParams.get('post_status'))
                        filters.post_status = url.searchParams.get('post_status');
                    return filters;
                }
            }
        });
    } else if (document.querySelector('.map_view')) {
        new Vue({
            el:'.map_view',
            mixins:[data_filters],
            data() {
                return {
                    coordinates:[],
                    map:{},
                    markers:{},
                    select_contacts:[],
                    pick_group:'',
                    counter:0,
                }
            },
            mounted() {
                DG.then(() => {
                    return DG.plugin('https://2gis.github.io/mapsapi/vendors/Leaflet.markerCluster/leaflet.markercluster-src.js');
                }).then(() => {
                    this.map = DG.map('map', {
                        center:DG.latLng([USAM_Tabs.latitude, USAM_Tabs.longitude]),
                        zoom:13
                    });
                    this.map_view({
                        'latitude':USAM_Tabs.latitude,
                        'longitude':USAM_Tabs.longitude
                    });
                });
            },
            methods:{
                requestData(data, e) {
                    usam_active_loader();
                    this.markers.removeFrom(this.map);
                    this.map_view(data);
                },
                map_view(data) {
                    this.markers = DG.markerClusterGroup({
                        zoomToBoundsOnClick:false
                    });
                    data.nonce = USAM_Tabs.get_map_data_nonce;
                    data.action = 'get_map_data';
                    data.tab = USAM_Tabs.tab;
                    usam_send(data, (r) => {
                        var a = {};
                        var i = 0;
                        var marker;
                        for (i; i < r.points.length; i++) {
                            a = r.points[i];
                            marker = DG.marker([a.latitude, a.longitude], {
                                title:a.title,
                                id:a.id
                            });
                            marker.bindPopup(a.description);
                            this.markers.addLayer(marker);
                        }
                        this.markers.on('clusterclick', (e) => {
                            this.counter = this.counter + e.layer.getAllChildMarkers().length;
                            var markers = e.layer.getAllChildMarkers();
                            var j = this.select_contacts.length;
                            for (i = 0; i < markers.length; i++) {
                                Vue.set(this.select_contacts, j, markers[i].options.id);
                                j++;
                            }
                        });
                        this.map.addLayer(this.markers);
                    });
                },
                add_selected_layouts() {
                    usam_send({
                        nonce:USAM_Tabs.add_pick_group_nonce,
                        action:'add_pick_group',
                        group:this.pick_group,
                        tab:USAM_Tabs.tab,
                        ids:this.select_contacts
                    });
                }
            }
        });
    }
    if (document.querySelector('.form_event')) {
        new Vue({
            el:'.form_event',
            mixins:[formTools, files, edit_properties],
            data() {
                return {
                    id:0,
                    tab:'',
                    crm_type:'',
                    rights:{
                        edit_action:true,
                        add_action:true,
                        delete_action:true,
                        comments:true
                    },
                    data:{
                        type:'task',
                        status_is_completed:false,
                        status_name:'',
                        status:''
                    },
                    users:{
                        observer:[],
                        participant:[]
                    },
                    actions:[],
                    calendars:[],
                    responsible:{},
                    author:{},
                    show:0,
                    toolbar_tab:'main',
                    remind:0,
                    colors:['white', 'blue', 'brown', 'yellow', 'green', 'purple', 'gray'],
                    color_open:false,
                    status_name:false,
                    timing_planning:false,
                    statuses:[],
                    new_action:'',
                    typeObjects:'companies',
                    objectsCRMQuery:{
                        companies:'company',
                        contacts:'contact',
                        orders:'order',
                        leads:'lead',
                        invoices:'invoice',
                        suggestions:'suggestion',
                        contracts:'contract',
                        products:'product'
                    },
                    crm:[],
                    object_names:{},
                    comments:0,
                    contact:{
                        emails:{},
                        phones:{}
                    },
                }
            },
            watch:{
                remind(v, old) {
                    if (v) {
                        const currentDate = new Date();
                        const d = new Date(this.data.start);
                        d.setDate(d.getDate() - 1);
                        if (d.getTime() < currentDate.getTime())
                            d = new Date(this.data.start);
                        this.data.reminder_date = local_date(d, "Y-m-d", false) + " 09:00:00";
                    } else
                        this.data.reminder_date = '';
                }
            },
            computed:{
                objectsCRM() {
                    var d = [];
                    for (let k in this.objectsCRMQuery) {
                        for (let i in this.crm) {
                            if (this.crm[i].object_type == this.objectsCRMQuery[k]) {
                                d.push(this.objectsCRMQuery[k]);
                                break;
                            }
                        }
                    }
                    return d;
                },
                actionsPerformed() {
                    return this.actions.filter(x => x.status === 1).length;
                },
                actionsPerformedPercent() {
                    var l = this.actions.length;
                    return l > 0 ? Math.round(this.actionsPerformed / l * 100) :0;
                },
                changeHistoryArgs() {
                    return {
                        object_type:this.data.type,
                        object_id:this.data.id
                    };
                },
            },
            created() {
                let url = new URL(document.location.href);
                this.data = form_data;
                if (url.searchParams.get('id')) {
                    var f = (ev) => {
                        if (!ev.target.classList.contains('event_action_name') && !ev.target.classList.contains('text_element_edit')) {
                            for (let k in this.actions) {
                                if (this.actions[k].edit) {
                                    usam_api('event/action/' + this.actions[k].id, this.actions[k], 'POST');
                                    this.actions[k].edit = false;
                                }
                            }
                        }
                    };
                    document.addEventListener("click", f);
                }
                this.crm_type = url.searchParams.get('form_name');
                usam_api('calendars', 'POST', (r) => this.calendars = r.items);
                this.timing_planning = this.data.start !== null;
            },
            mounted() {
                this.loadData();
            },
            methods:{
                loadData() {
                    let url = new URL(document.location.href);
                    this.data.type = url.searchParams.get('form_name');
                    this.cFile.object_id = this.id;
                    this.cFile.type = this.data.type;
                    if (document.querySelector('.usam_attachments'))
                        this.fDownload();
                    if (url.searchParams.get('id')) {
                        this.id = url.searchParams.get('id');
                        usam_api('event/' + this.id, {
                            add_fields:['crm', 'actions', 'rights', 'author']
                        }, 'GET', (r) => {
                            for (let i in r.actions)
                                r.actions[i].edit = false;
                            for (k of['crm', 'rights', 'actions', 'author']) {
                                this[k] = structuredClone(r[k]);
                                delete r[k];
                            }
                            if (typeof r.properties !== typeof undefined) {
                                this.preparationData(r);
                                delete r.properties;
                            }
                            for (let k in r)
                                Vue.set(this.data, k, r[k]);
                            this.$watch('data.status', this.selectStatus);
                            Vue.set(this, 'remind', this.data.reminder_date ? 1 :0);
                        });
                    } else {
                        usam_api('contact', {
                            add_fields:['url', 'foto', 'post']
                        }, 'GET', (r) => this.author = r);
                        this.loadProperties();
                    }
                    usam_api('statuses', {
                        type:this.data.type,
                        fields:'code=>name'
                    }, 'POST', (r) => this.statuses = r);
                    this.$watch('users', this.selectUsers, {
                        deep:true
                    });
                },
                loadProperties() {
                    usam_api('properties', {
                        type:'event',
                        fields:'code=>data'
                    }, 'POST', (r) => this.properties = this.propertyProcessing(r.items));
                    usam_api('property_groups', {
                        type:'event'
                    }, 'POST', (r) => this.propertyGroups = r.items);
                },
                selectStatus() {
                    if (this.form_type == 'view') {
                        this.data.status_name = this.statuses[this.data.status];
                        usam_api('event/' + this.id, {
                            status:this.data.status
                        }, 'POST');
                    }
                },
                selectUsers() {
                    if (this.form_type == 'view')
                        this.saveForm();
                },
                action_status_update(k, status) {
                    this.actions[k].status = status;
                    usam_api('event/action/' + this.actions[k].id, {
                        status:status,
                        event_id:this.id
                    }, 'POST');
                },
                add_action(e) {
                    e.preventDefault();
                    var data = {
                        name:this.new_action,
                        status:0,
                        sort:this.actions.length,
                        event_id:this.id,
                        edit:false
                    };
                    usam_api('event/action', data, 'POST', (id) => {
                        data.id = id;
                        this.actions.push(data);
                    });
                    this.new_action = '';
                },
                action_edit(k, e) {
                    e.preventDefault();
                    for (let i in this.actions) {
                        if (k != i)
                            this.actions[i].edit = false;
                    }
                    this.actions[k].edit = true;
                },
                delete_action(k, e) {
                    e.preventDefault();
                    var d = this.actions[k];
                    d.edit = false;
                    usam_item_remove({
                        data:{
                            d:d
                        },
                        'callback':(data) => {
                            this.actions.splice(k, 0, data.d);
                            usam_api('event/action/' + data.d.id, {
                                status:0,
                                event_id:this.id
                            }, 'POST');
                        }
                    });
                    usam_api('event/action/' + d.id, {
                        status:2,
                        event_id:this.id
                    }, 'POST');
                    this.actions.splice(k, 1);
                },
                allowDrop(e) {
                    e.preventDefault();
                },
                drag_action(e, k) {
                    if (e.target.hasAttribute('draggable')) {
                        e.dataTransfer.setData("old_k", k);
                        e.currentTarget.classList.add('draggable');
                    } else
                        e.preventDefault();

                },
                drag_end_action(e, i) {
                    e.currentTarget.classList.remove('draggable');
                },
                drop_action(e, k) {
                    e.preventDefault();
                    let old_k = parseInt(e.dataTransfer.getData("old_k"));
                    let v = Object.assign({}, this.actions[old_k]);
                    this.actions.splice(old_k, 1);
                    this.actions.splice(k, 0, v);
                    let data = [];
                    for (i = 0; i < this.actions.length; i++)
                        data[i] = {
                            id:this.actions[i].id,
                            event_id:this.id,
                            sort:i
                        };
                    usam_api('event/actions', {
                        items:data
                    }, 'PUT');
                },
                getSidebarSelected() {
                    var k = this.sidebardata;
                    if (k === 'employee')
                        return [this.data.user_id];
                    else {
                        var ids = [];
                        for (let i in this.users[k])
                            ids.push(this.users[k][i].user_id);
                    }
                    return ids;
                },
                selectObjects(item) {
                    item.object_type = this.objectsCRMQuery[this.typeObjects];
                    this.crm.push(item);
                },
                saveForm(add) {
                    var data = Object.assign(this.getValues(), this.data);
                    if (typeof tinyMCE !== typeof undefined) {
                        var t = tinyMCE.get('description_tinymce_1');
                        if (t !== null)
                            data.description = t.getContent();
                    }
                    data.files = [];
                    for (let i in this.files)
                        data.files.push(this.files[i].id);
                    data.links = [];
                    for (let i in this.crm)
                        data.links.push({
                            object_id:this.crm[i].id,
                            object_type:this.crm[i].object_type
                        });
                    for (let type in this.users) {
                        if (type == 'responsible')
                            data[type] = this.users[type];
                        else {
                            if (data[type] === undefined)
                                data[type] = [];
                            for (let i in this.users[type])
                                data[type].push(this.users[type][i].user_id);
                        }
                    }
                    if (this.data.id)
                        usam_api('event/' + this.data.id, data, 'POST', (r) => {
                            if (add === true)
                                this.addNew();
                            usam_admin_notice(r);
                        });
                    else
                        usam_api('event', data, 'POST', (r) => {
                            if (add === true)
                                this.addNew();
                            else
                                this.afterAdding(r.id);
                        });
                },
                deleteItem() {
                    if (this.data.id)
                        usam_api('event/' + this.data.id, 'DELETE', this.afterDelete);
                },
                addUser(item, type) {
                    if (this.sidebardata == 'employee') {
                        this.sidebar('managers');
                        this.data.user_id = item.user_id;
                        this.user.appeal = item.appeal;
                        this.user.foto = item.foto;
                        this.user.url = item.url;
                    } else if (this.sidebardata == 'responsible') {
                        this.sidebar('managers');
                        this.data.responsible = item.user_id;
                        this.responsible.appeal = item.appeal;
                        this.responsible.foto = item.foto;
                        this.responsible.url = item.url;
                    } else {
                        if (this.users[this.sidebardata] === undefined)
                            Vue.set(this.users, this.sidebardata, []);
                        var ok = true;
                        for (let i in this.users[this.sidebardata[type]])
                            if (this.users[this.sidebardata][i].id == item.id) {
                                ok = false;
                                break;
                            }
                        if (ok)
                            this.users[this.sidebardata].push(item);
                    }
                }
            }
        })
    } else if (document.querySelector('.form_contacting')) {
        new Vue({
            el:'.form_contacting',
            mixins:[formTools, files, edit_properties],
            data() {
                return {
                    id:0,
                    tab:'',
                    crm_type:'contacting',
                    contact:{},
                    manager:{},
                    post:null,
                    data:{
                        status_is_completed:true,
                        status_name:'',
                        status:''
                    },
                    colors:['white', 'blue', 'brown', 'yellow', 'green', 'purple', 'gray'],
                    color_open:false,
                    status_name:false,
                    statuses:[],
                    comments:0
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                this.loadData();
            },
            methods:{
                loadData() {
                    let url = new URL(document.location.href);
                    if (url.searchParams.get('id')) {
                        this.id = url.searchParams.get('id');
                        usam_api('contacting/' + this.id, {
                            add_fields:['manager', 'contact', 'post', 'webform', 'request_solution', 'analytics']
                        }, 'GET', (r) => {
                            if (typeof r.properties !== typeof undefined) {
                                this.preparationData(r);
                                delete r.properties;
                            }
                            r.contact.phones = this.getConnections('mobile_phone')
                                r.contact.emails = this.getConnections('email')
                                for (k of['manager', 'contact', 'post']) {
                                    Vue.set(this, k, r[k]);
                                    delete r[k];
                                }
                                for (let k in r)
                                    Vue.set(this.data, k, r[k]);
                                this.$watch('data.status', this.selectStatus);
                            this.$watch('data.manager_id', this.selectManager);
                            this.$watch('data.importance', this.selectImportance);
                        });
                    } else
                        this.loadProperties();
                    usam_api('statuses', {
                        type:'contacting'
                    }, 'POST', (r) => {
                        for (let i in r)
                            Vue.set(this.statuses, r[i].internalname, r[i].name);
                    });
                },
                loadProperties() {
                    usam_api('properties', {
                        type:'event',
                        fields:'code=>data'
                    }, 'POST', (r) => this.properties = this.propertyProcessing(r.items));
                    usam_api('property_groups', {
                        type:'event'
                    }, 'POST', (r) => this.propertyGroups = r.items);
                },
                selectStatus() {
                    if (this.form_type == 'view') {
                        this.data.status_name = this.statuses[this.data.status];
                        this.saveObject({
                            status:this.data.status
                        });
                    }
                },
                selectManager() {
                    if (this.form_type == 'view')
                        this.saveObject({
                            manager_id:this.data.manager_id
                        });
                },
                selectImportance() {
                    if (this.form_type == 'view')
                        this.saveObject({
                            importance:this.data.importance
                        });
                },
                saveObject(data) {
                    usam_api('contacting/' + this.data.id, data, 'POST', usam_admin_notice);
                },
                add_order() {
                    usam_api('contacting/' + this.id + '/order', 'POST');
                },
                saveForm(add) {
                    var data = structuredClone(this.data);
                    data.webform = {};
                    for (k in this.properties)
                        data.webform[this.properties[k].code] = this.properties[k].value;
                    if (this.data.id)
                        usam_api('contacting/' + this.data.id, data, 'POST', (r) => {
                            if (add === true)
                                this.addNew();
                            usam_admin_notice(r);
                        });
                    else
                        usam_api('contacting', data, 'POST', (r) => {
                            if (add === true)
                                this.addNew();
                            else
                                this.afterAdding(r.id);
                        });
                },
                deleteItem() {
                    if (this.data.id)
                        usam_api('contacting/' + this.data.id, 'DELETE', this.afterDelete);
                },
                addUser(item) {
                    this.addManager(item);
                }
            }
        })
    } else if (document.getElementById('view_form_bonus_card')) {
        interface_filters = new Vue({
            mixins:[crm_report, formTools],
            el:'#view_form_bonus_card',
            data() {
                return {
                    transactions:[],
                    bonus:'',
                    description:'',
                    confirm:''
                }
            },
            computed:{
                changeHistoryArgs() {
                    return {
                        object_type:'bonus_card',
                        object_id:this.data.code
                    };
                },
                argsTransactions() {
                    return {
                        code:this.data.code, add_fields:['objects']
                    };
                }
            },
            watch:{
                bonus(v) {
                    this.bonus = v.replace(/[^0-9]/g, "");
                }
            },
            created() {
                this.data = form_data;
            },
            methods:{
                addTransactions() {
                    if (this.bonus > 0) {
                        usam_api('bonus/transaction/' + this.data.code, {
                            bonus:this.bonus,
                            description:this.description,
                            type_transaction:0
                        }, 'POST', (r) => {
                            this.$refs.transactions.queryAPI();
                            this.card();
                        });
                        this.bonus = '';
                        this.description = '';
                    }
                },
                transactionDel(k) {
                    this.confirm = '';
                    usam_api('bonus/transaction/' + this.transactions[k].id, 'DELETE', (r) => this.card());
                    this.transactions.splice(k, 1);
                },
                card() {
                    usam_api('bonus/card/' + this.data.code, 'GET', (r) => this.data = Object.assign(this.data, r));
                }
            }
        })
    } else if (document.querySelector('#view_form_customer_account')) {
        interface_filters = new Vue({
            el:'#view_form_customer_account',
            mixins:[crm_report, formTools],
            data() {
                return {
                    transactions:[],
                    sum:'',
                    description:'',
                    confirm:''
                }
            },
            computed:{
                changeHistoryArgs() {
                    return {
                        object_type:'customer_account',
                        object_id:this.data.id
                    };
                },
                argsTransactions() {
                    return {
                        account_id:this.data.id
                    };
                }
            },
            watch:{
                sum(v) {
                    this.sum = v.replace(/[^0-9]/g, "");
                }
            },
            created() {
                this.data = form_data;
            },
            methods:{
                addTransactions() {
                    if (this.sum > 0 && this.description !== '') {
                        usam_api('account/transaction/' + this.data.id, {
                            sum:this.sum,
                            description:this.description,
                            type_transaction:0
                        }, 'POST', (r) => {
                            this.$refs.transactions.queryAPI();
                            this.card();
                        });
                        this.sum = '';
                        this.description = '';
                    }
                },
                transactionDel(k) {
                    this.confirm = '';
                    usam_api('account/transaction/' + this.transactions[k].id, 'DELETE', (r) => this.card());
                    this.transactions.splice(k, 1);
                },
                card() {
                    usam_api('account/' + this.data.id, 'GET', (r) => this.data = Object.assign(this.data, r));
                }
            }
        })
    } else if (document.getElementById('edit_form_showcase')) {
        new Vue({
            mixins:[formTools],
            el:'#edit_form_showcase',
            data() {
                return {
                    contractors:[],
                    taxonomies:[],
                    terms:[],
                }
            },
            created() {
                this.data = form_data;
                usam_api('companies', {
                    type:'contractor',
                    fields:'id=>data',
                    orderby:'name',
                    count:1000
                }, 'POST', (r) => this.contractors = r.items);
                usam_api('taxonomies', {
                    object_type:'usam-product'
                }, 'POST', (r) => this.taxonomies = r);
                usam_api('terms', {
                    taxonomy_object:'usam-product',
                    hide_empty:0,
                    count:10000,
                    orderby:'sort',
                    order:'asc',
                    name_format:'hierarchy'
                }, 'POST', (r) => {
                    var t = {};
                    for (let i in r.items) {
                        if (typeof t[r.items[i].taxonomy] === typeof undefined)
                            t[r.items[i].taxonomy] = []
                            t[r.items[i].taxonomy].push({
                                id:r.items[i].term_id,
                                name:r.items[i].name
                            })
                    }
                    this.terms = t
                });
            },
            methods:{
                deleteItem() {
                    if (this.data.id)
                        usam_api('showcase/' + this.data.id, 'DELETE', this.afterDelete);
                },
                saveForm() {
                    var data = structuredClone(this.data);
                    if (this.data.id) {
                        usam_active_loader();
                        usam_api('showcase/' + this.data.id, data, 'POST', usam_admin_notice);
                    } else {
                        usam_api('showcase', data, 'POST', (id) => this.afterAdding(id));
                    }
                },
                checkAvailableProducts() {
                    if (this.data.id)
                        usam_api('showcases/check/available/products', 'GET', (r) => usam_admin_notice(r, 'add_event'));
                },
                synchronizationProducts() {
                    if (this.data.id)
                        usam_api('showcases/synchronization/products', 'GET', (r) => usam_admin_notice(r, 'add_event'));
                },
                deleteSynchronizationProducts() {
                    if (this.data.id)
                        usam_api('showcase/' + this.data.id + '/synchronization/products', 'DELETE', (r) => usam_admin_notice(r, 'add_event'));
                },
                removeLink() {
                    if (this.data.id)
                        usam_api('showcase/' + this.data.id + '/remove/products/link', 'GET', (r) => usam_admin_notice(r, 'ready'));
                },
                updatePrices() {
                    if (this.data.id)
                        usam_api('showcases/update/prices/products', 'GET', (r) => usam_admin_notice(r, 'add_event'));
                }
            }
        })
    } else if (document.getElementById('view_form_advertising_campaign')) {
        new Vue({
            mixins:[crm_report, formTools, data_filters, listTable],
            el:'#view_form_advertising_campaign',
			created() {
                this.data = form_data;
            },
        })
    } else if (document.querySelector('.form_newsletter_report')) {
        interface_filters = new Vue({
            mixins:[formTools],
            el:'.form_newsletter_report',
            data() {
                return {
                    data:{},
                    sending_statuses:{},
                    argsContact:{
                        add_fields:['source', 'status_data', 'newsletter_stat']
                    },
                    argsCompany:{
                        add_fields:['company_type', 'status_data', 'newsletter_stat']
                    },
                }
            },
            created() {
                this.data = form_data;
                this.argsContact.newsletter_id = this.data.id
                    this.argsCompany.newsletter_id = this.data.id
            },
        })
    } else if (document.querySelector('.edit_form_property')) {
        new Vue({
            el:'.edit_form_property',
            data() {
                return {
                    data:{},
                    limit_visibility:false,
                    roles:[]
                }
            },
            created() {
                this.data = form_data;
                this.limit_visibility = this.data.roles.length
                    usam_api('roles', {
                        fields:'code=>name'
                    }, 'GET', (r) => this.roles = r.items);
            },
        })
    } else if (document.getElementById('view_form_subscription')) {
        interface_filters = new Vue({
            mixins:[crm_report, formTools, edit_properties],
            el:'#view_form_subscription',
            data() {
                return {
                    data:{},
                    contact:{},
                    customer:{},
                    crm_type:'subscription',
                    pLoaded:true,
                    edit:false,
                    edit_form:false,
                    edit_data:true,
                    type_prices:[]
                }
            },
            computed:{
                changeHistoryArgs() {
                    return {
                        object_type:'subscription',
                        object_id:this.data.id
                    };
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                this.$watch('data.manager_id', this.selectManager);
                //	this.data.totalprice = this.formatted_number( this.data.totalprice );
                if (this.data.customer_id) {
                    if (this.data.customer_type == 'contact')
                        usam_api('contact/' + this.data.customer_id, {
                            add_fields:['properties']
                        }, 'GET', (r) => {
                            this.preparationData(r);
                            r.phones = this.getConnections('mobile_phone')
                                r.emails = this.getConnections('email')
                                this.contact = structuredClone(r);
                            this.customer = r
                        });
                    else
                        usam_api('company/' + this.data.customer_id, {
                            add_fields:['properties']
                        }, 'GET', (r) => this.customer = r);
                }
                usam_api('type_prices', {
                    type:'R'
                }, 'GET', (r) => this.type_prices = r.items);
            },
            methods:{
                selectManager() {
                    usam_api('subscription/' + this.data.id, this.data, 'POST', usam_admin_notice);
                },
                saveForm(e) {
                    e.preventDefault();
                    usam_api('subscription/' + this.data.id, this.data, 'POST', usam_admin_notice);
                },
                renew() {
                    usam_active_loader();
                    usam_api('subscription/renew/' + this.data.id, 'GET', (r) => {
                        if (document.querySelector('.subscription_renewal'))
                            USAM_Tabs.update_table();
                    });
                }
            }
        })
    } else if (document.getElementById('edit_form_subscription')) {
        interface_filters = new Vue({
            el:'#edit_form_subscription',
            mixins:[formTools],
            data() {
                return {
                    data:{},
                    pLoaded:true,
                    edit:true,
                    edit_data:true,
                    type_prices:[],
                    customer_name:''
                }
            },
            computed:{
                request() {
                    var d = {
                        company:'companies',
                        contact:'contacts'
                    };
                    return d[this.data.customer_type];
                }
            },
            created() {
                this.data = form_data;
                usam_api('type_prices', {
                    type:'R'
                }, 'GET', (r) => this.type_prices = r.items);
            },
            methods:{
                saveForm(e) {
                    e.preventDefault();
                    if (this.data.customer_id && this.data.products.length) {
                        if (this.data.id)
                            usam_api('subscription/' + this.data.id, this.data, 'POST', usam_admin_notice);
                        else
                            usam_api('subscription', this.data, 'POST', (id) => {
                                this.data.id = id
                                    let url = new URL(document.location.href);
                                url.searchParams.set('id', id);
                                window.location.replace(url.href);
                            });
                    }
                },
                addUser(item) {
                    this.addManager(item);
                }
            }
        })
    } else if (document.getElementById('edit_form_storage')) {
        new Vue({
            el:'#edit_form_storage',
            mixins:[formTools],
            data() {
                return {
                    prices:[],
                    sales_area:[],
                    regions:[],
                    images:[],
                    location:{},
                    thumbnail:'',
                    timing_planning:false
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                usam_api('type_prices', {fields:'autocomplete'}, 'GET', (r) => this.prices = r.items);
                usam_api('sales_area', 'GET', (r) => {
                    this.sales_area = r.items
                        for (let i in r.items)
                            if (this.data['sale_area_' + r.items[i].id])
                                this.regions.push(r.items[i].id)
                });
            },
            methods:{
                deleteItem() {
                    if (this.data.id)
                        usam_api('storage/' + this.data.id, 'DELETE', this.afterDelete);
                },
                saveForm() {
                    var data = structuredClone(this.data);
                    if (typeof tinyMCE !== typeof undefined) {
                        var t = tinyMCE.get('description_tinymce_1');
                        if (t !== null)
                            data.description = t.getContent();
                    }
                    for (let i in this.sales_area)
                        data['sale_area_' + this.sales_area[i].id] = this.regions.includes(this.sales_area[i].id)
                            if (this.data.id) {
                                usam_active_loader();
                                usam_api('storage/' + this.data.id, data, 'POST', usam_admin_notice);
                            } else {
                                usam_api('storage', data, 'POST', (id) => this.afterAdding(id));
                            }
                },
                addMedia(a) {
                    for (let i in a) 
					{
                        this.data.images.push(a[i].id);
                        this.images.push({ID:a[i].id, full:a[i].url});
                    }
                },
                deleteMedia(k) {
                    this.images.splice(k, 1);
                    this.data.images.splice(k, 1);
                },
            }
        })
    } else if (document.getElementById('edit_form_review')) {
        new Vue({
            mixins:[formTools, edit_properties],
            el:'#edit_form_review',
            data() {
                return {
                    data:{},
					webform:{},
					user:{},
                    tab:'',
                    timing_planning:false
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                this.loadProperties();
            },
            methods:{
                loadProperties() {
                    if (this.data.id)
                        usam_api('review/' + form_data.id, 'GET', this.preparationData);
                    else {
                        usam_api('properties', {
                            type:'webform',
                            fields:'code=>data'
                        }, 'POST', (r) => this.properties = this.propertyProcessing(r.items));
                        usam_api('property_groups', {
                            type:'webform'
                        }, 'POST', (r) => this.propertyGroups = r.items);
                    }
                    this.load = true;
                },
                formFileData(f, k) {
                    var fData = new FormData();
                    fData.append('file', f);
                    fData.append('property', this.properties[k].id);
                    fData.append('type', 'review');
                    fData.append('object_id', this.data.id);
                    return fData;
                }
            }
        })
    } else if (document.getElementById('edit_form_webform')) {
        new Vue({
            el:'#edit_form_webform',
            mixins:[edit_properties],
            data() {
                return {
                    data:{},
                    webform_properties:[],
                    show:0,
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                for (let k in form_args)
                    this[k] = form_args[k];
                this.show = this.data.actuation_time;
                //	this.loadProperties();
            },
            methods:{
                loadProperties() {
                    if (this.data.id)
                        usam_api('review/' + form_data.id, 'GET', this.preparationData);
                    else {
                        usam_api('properties', {
                            type:'webform',
                            fields:'code=>data'
                        }, 'POST', (r) => this.properties = this.propertyProcessing(r.items));
                        usam_api('property_groups', {
                            type:'webform'
                        }, 'POST', (r) => this.propertyGroups = r.items);
                    }
                    this.load = true;
                }
            }
        })
    } else if (document.querySelector('.file_form')) {
        new Vue({
            el:'.file_form',
            mixins:[files],
            data() {
                return {
                    data:{},
                    crm_type:'file',
                    edit_form:false,
                    edit:false,
                    allowGroupСhanges:false,
                    cFile:{
                        type:'loaded'
                    },
                    media:null,
                }
            },
            created() {
                this.data = form_data;
            },
            methods:{
                addWpMedia() {
                    if (this.media === null) {
                        this.media = wp.media.frames.images_file_frame = wp.media({
                            library:{
                                type:'image'
                            },
                            multiple:false
                        });
                        wp.media.frames.images_file_frame.on('open', () => {
                            var selection = wp.media.frames.images_file_frame.state().get('selection');
                            if (this.data.thumbnail_id > 0) {
                                attachment = wp.media.attachment(this.data.thumbnail_id);
                                attachment.fetch();
                                selection.add(attachment ? [attachment] :[]);
                            }
                        });
                        this.media.on('select', () => {
                            var a = this.media.state().get('selection').first().toJSON();
                            this.data.thumbnail_id = a.id;
                            this.data.thumbnail_url = a.url;
                        });
                    }
                    this.media.open();
                },
                save(e) {
                    usam_api('file/' + this.data.id, this.data, 'POST', usam_admin_notice);
                }
            }
        });
    } else if (document.getElementById('view_form_contact')) {
        interface_filters = new Vue({
            mixins:[crm_form, crm_report, formTools],
            el:'#view_form_contact',
            data() {
                return {
                    argsDocs:{
                        add_fields:['manager', 'status_data', 'currency']
                    },
                }
            },
            computed:{
                socialNetworks() {
                    return this.getPropertiesGroup('social_networks')
                },
                contact() {
                    let d = this.data;
                    d.phones = this.getConnections('mobile_phone')
                        d.emails = this.getConnections('email')
                        return d;
                }
            },
            created() {
                this.getGroups();
                this.argsDocs.contacts = [this.data.id];
            },
            methods:{
                requestData(data, e) {
                    if (this.subtab == 'report')
                        this.loadReport();
                    else {
                        usam_active_loader();
                        USAM_Tabs.table_view(data, jQuery('.usam_tab_table'));
                    }
                }
            }
        });
    } else if (document.getElementById('edit_form_contact')) {
        new Vue({
            mixins:[crm_form, contact],
            el:'#edit_form_contact',
            methods:{
                saveForm(add) {
                    this.save(add, 'contact');
                },
                deleteItem() {
                    this.del('contact');
                }
            }
        })
    } else if (document.getElementById('view_form_employee')) {
        interface_filters = new Vue({
            mixins:[crm_form, crm_report, formTools],
            el:'#view_form_employee',
            computed:{
                socialNetworks() {
                    return this.getPropertiesGroup('social_networks')
                },
                contact() {
                    let d = this.data;
                    d.phones = this.getConnections('mobile_phone')
                        d.emails = this.getConnections('email')
                        return d;
                }
            }
        });
    } else if (document.getElementById('edit_form_employee')) {
        new Vue({
            mixins:[crm_form, contact],
            el:'#edit_form_employee',
            data() {
                return {
                    departments:[],
                }
            },
            mounted() {
                usam_api('departments', 'POST', (r) => this.departments = r.items);
            },
            methods:{
                saveForm(add) {
					if( this.data.contact_source == 'employee' )
						this.save(add, 'employee');
					else
						usam_api('employee/dismissal/' + this.data.id, 'GET', (r) => {
							usam_admin_notice(r);
							this.backList();
						});
                },
                deleteItem() {
                    this.del('employee');
                },
            }
        })
    } else if (document.querySelector('.company_view_form')) {
        interface_filters = new Vue({
            mixins:[crm_form, crm_report, company],
            el:'.company_view_form',
            data() {
                return {
                    edit_form:false,
                    edit:false,
                    users:[],
                    connections:[],
                    argsDocs:{
                        add_fields:['manager', 'status_data', 'currency']
                    },
                }
            },
            computed:{
                contact() {
                    let d = {};
                    d.phones = this.getConnections('mobile_phone')
                        d.emails = this.getConnections('email')
                        return d;
                }
            },
            created() {
                this.argsDocs.companies = [this.data.id];
            },
            mounted() {
                if (this.data.id) {
                    this.getGroups();
                    const ObUsers = new IntersectionObserver((els, Observer) => {
                        els.forEach((e) => {
                            if (e.isIntersecting) {
                                usam_api('users', {
                                    company_personal_account:this.data.id
                                }, 'POST', (r) => this.users = r.items);
                                Observer.unobserve(e.target);
                            }
                        })
                    }, {
                        rootMargin:'0px 0px 50px 0px'
                    });
                    el = document.querySelector('.company_personal_accounts');
                    if (el !== null)
                        ObUsers.observe(el);
                    const ObCompany = new IntersectionObserver((els, Observer) => {
                        els.forEach((e) => {
                            if (e.isIntersecting) {
                                usam_api('companies', {
                                    connection:this.data.id,
                                    count:1000
                                }, 'POST', (r) => this.connections = r.items);
                                Observer.unobserve(e.target);
                            }
                        })
                    }, {
                        rootMargin:'0px 0px 50px 0px'
                    });
                    el = document.querySelector('.company_connections');
                    if (el !== null)
                        ObCompany.observe(el);
                } else
                    this.loaded = true;
            },
            methods:{
                saveUsers() {
                    if (this.data.id) {
                        var data = {
                            user_ids:[0]
                        };
                        for (let i in this.users)
                            data.user_ids[i] = this.users[i].ID;
                        this.save(data);
                    }
                },
                requestData(data, e) {
                    if (this.subtab == 'report')
                        this.loadReport();
                    else {
                        usam_active_loader();
                        USAM_Tabs.table_view(data, jQuery('.usam_tab_table'));
                    }
                },
                saveConnections() {
                    if (this.data.id) {
                        var data = {
                            connection_id:[0]
                        };
                        for (let i in this.connections)
                            data.connection_id[i] = this.connections[i].id;
                        this.save(data);
                    }
                },
                save(data) {
                    usam_api('company/' + this.data.id, data, 'POST', usam_admin_notice);
                },
                addUser(e) {
                    if (e.ID)
                        usam_api('user/' + e.ID, 'GET', (r) => this.users.push(r));
                },
                addConnection(e) {
                    if (e.id)
                        usam_api('company/' + e.id, 'GET', (r) => this.connections.push(r));
                },
                getDataDirectory(e) {
                    e.preventDefault();
                    if (this.properties.inn)
                        usam_api('directory/companies', {
                            search:this.properties.inn.value
                        }, 'GET', (r) => this.properties = this.propertyProcessing(r));
                },
                openEvent(item, type) {
                    Vue.set(new_event, 'customer', {
                        name:item.appeal !== undefined ? item.appeal :item.name
                    });
                    Vue.set(new_event.event, 'links', [{
                                object_id:item.id,
                                object_type:item.appeal !== undefined ? 'contact' :'company'
                            }
                        ]);
                    Vue.set(new_event.event, 'type', type);
                    new_event.show_modal();
                },
            }
        })
    } else if (document.getElementById('edit_form_company')) {
        new Vue({
            el:'#edit_form_company',
            mixins:[crm_form, company]
        })
    } else if (document.getElementById('view_form_payment')) {
        new Vue({
            el:'#view_form_payment',
            computed:{
                changeHistoryArgs() {
                    return {
                        object_type:'payment',
                        object_id:this.data.id
                    };
                }
            },
            created() {
                this.data = form_data;
            }
        })
    } else if (document.getElementById('view_form_invoice_payment')) {
        new Vue({
            mixins:[files, form_document],
            el:'#view_form_invoice_payment',
            data() {
                return {
                    id:0,
                    yourDecision:false,
                }
            },
            mounted() {
                this.id = form_data.id;
                this.yourDecision = form_data.your_decision;
                this.loadFileManagement();
                this.enableSavingChange();
                this.getGroups();
            },
            methods:{
                approve() {
                    this.yourDecision = 'approve';
                    usam_api('document/approve/' + this.id, {
                        status:this.yourDecision
                    }, 'POST');
                },
                doNotApprove() {
                    this.yourDecision = 'declained';
                    usam_api('document/approve/' + this.id, {
                        status:this.yourDecision
                    }, 'POST');
                }
            }
        })
    } else if (document.getElementById('edit_form_invoice_payment')) {
        new Vue({
            mixins:[files, form_document],
            el:'#edit_form_invoice_payment',
            data() {
                return {
                    args_contacts:{
                        add_fields:['foto'],
                        source:'employee'
                    },
                }
            },
            mounted() {
                this.loadFileManagement();
            },
        })
    } else if (document.querySelector('.edit_form_products_document')) {
        new Vue({
            el:'.edit_form_products_document',
            mixins:[form_document],
            data() {
                return {
                    edit:true,
                }
            },
            mounted() {
                this.loadTableData();
            }
        })
    } else if (document.getElementById('edit_form_movement')) {
        new Vue({
            el:'#edit_form_movement',
            mixins:[form_document],
            data() {
                return {
                    edit:true
                }
            },
            mounted() {
                this.loadTableData();
            },
            methods:{
                selectStorage(e) {
                    this[this.sidebardata] = e;
                    this.data[this.sidebardata] = e.id;
                }
            }
        })
    } else if (document.getElementById('edit_form_receipt')) {
        new Vue({
            el:'#edit_form_receipt',
            mixins:[form_document],
            data() {
                return {
                    edit:true
                }
            },
            mounted() {
                this.loadTableData();
            }
        })
    } else if (document.getElementById('view_form_decree')) {
        new Vue({
            el:'#view_form_decree',
            mixins:[files, form_document],
            mounted() {
                this.loadFileManagement();
                this.enableSavingChange();
                this.getGroups();
            }
        })
    } else if (document.getElementById('edit_form_decree')) {
        new Vue({
            mixins:[form_document, files],
            el:'#edit_form_decree',
            data() {
                return {
                    args_contacts:{add_fields:['foto'],source:'employee'},
                }
            },
            mounted() {
                this.loadFileManagement();
            },
        })
    } else if (document.getElementById('edit_form_order_contractor')) {
        new Vue({
            mixins:[form_document, files],
            el:'#edit_form_order_contractor',
            data() {
                return {
                    args_companies:{
                        type:'contractor',
                        add_fields:'logo',
                        orderby:'name',
                        order:'ASC'
                    },
                }
            },
            mounted() {
                this.loadTableData();
            },
        })
    } else if (document.getElementById('view_form_contract')) {
        interface_filters = new Vue({
            mixins:[files, data_filters, form_document, formTools],
            el:'#view_form_contract',
            mounted() {
                this.loadFileManagement();
                this.enableSavingChange();
            },
        })
    } else if (document.getElementById('edit_form_contract')) {
        interface_filters = new Vue({
            mixins:[form_document, files],
            el:'#edit_form_contract',
            data() {
                return {
                    agreements:[]
                }
            },
            mounted() {
                this.loadFileManagement();
                if (this.data.id > 0)
                    usam_api('additional_agreements', {
                        meta_query:[{
                                key:'contract',
                                value:this.data.id,
                                compare:'=',
                                type:'NUMERIC'
                            }
                        ]
                    }, 'POST', (r) => this.agreements = r.items);
            }
        })
    } else if (document.getElementById('edit_form_additional_agreement')) {
        interface_filters = new Vue({
            mixins:[form_document, files],
            el:'#edit_form_additional_agreement',
            mounted() {
                this.loadFileManagement();
                this.args_contracts = {};
            },
            methods:{
                selectContract(e) {
                    this.contract = e;
                    this.data.contract = e.id;
                    this.data.bank_account_id = e.bank_account_id;
                    this.data.customer_type = e.customer_type;
                    this.data.customer_id = e.customer_id;
                }
            }
        })
    } else if (document.getElementById('edit_form_reconciliation_act')) {
        interface_filters = new Vue({
            mixins:[form_document],
            el:'#edit_form_reconciliation_act'
        })
    } else if (document.getElementById('view_form_reconciliation_act')) {
        new Vue({
            el:'#view_form_reconciliation_act',
            mixins:[formTools, form_document],
            mounted() {
                this.enableSavingChange();
                this.getGroups();
            }
        })
    } else if (document.getElementById('view_form_additional_agreement')) {
        interface_filters = new Vue({
            el:'#view_form_additional_agreement',
            mixins:[files, data_filters, form_document, formTools],
            mounted() {
                this.enableSavingChange();
                this.loadFileManagement();
                this.getGroups();
            }
        })
    } else if (document.getElementById('edit_form_additional_agreement')) {
        interface_filters = new Vue({
            mixins:[files],
            el:'#edit_form_additional_agreement'
        })
    } else if (document.querySelector('.view_form_document')) {
        interface_filters = new Vue({
            el:'.view_form_document',
            mixins:[form_document, formTools],
            data() {
                return {
                    edit:false
                }
            },
            mounted() {
                this.loadTableData();
                this.enableSavingChange();
                this.getGroups();
            }
        })
    } else if (document.querySelector('.edit_form_document')) {
        new Vue({
            el:'.edit_form_document',
            mixins:[form_document],
            data() {
                return {
                    edit:true
                }
            }
        })
    } else if (document.getElementById('view_form_order')) {
        interface_filters = new Vue({
            mixins:[crm_report, table_products, edit_properties, order_document],
            el:'#view_form_order',
            data() {
                return {
                    edit:false
                }
            },
            mounted() {
                this.$watch('data.manager_id', this.selectManager);
                this.$watch('data.status', this.selectStatus);
                this.getGroups();
            }
        })
    } else if (document.getElementById('edit_form_order')) {
        interface_filters = new Vue({
            mixins:[crm_report, table_products, edit_properties, order_document],
            el:'#edit_form_order',
            data() {
                return {
                    edit_form:true,
                    edit_data:true
                }
            }
        })
    } else if (document.getElementById('edit_form_shipped')) {
        interface_filters = new Vue({
            mixins:[edit_properties, shipped_document, formTools],
            el:'#edit_form_shipped',
            data() {
                return {
                    edit:true,
                    show_button:false,
                    abilityChange:true,
                    pLoaded:true,
                    data:{},
                    query:{
                        add_fields:['small_image', 'sku', 'stock', 'unit_measure', 'price']
                    },
                    units:{},
                    statuses:[],
                    storages:{},
                    delivery_problems:{},
                    couriers:{},
                    delivery:[],
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                if (this.data.id)
                    usam_api('shipped/' + this.data.id, {
                        add_fields:['document_products', 'storage_data']
                    }, 'GET', (r) => this.data = r);
                usam_api('units', {
                    fields:'code=>short'
                }, 'GET', (r) => this.units = r);
                usam_api('employees', {
                    role__in:['courier'],
                    source:'all',
                    fields:'user_id=>name'
                }, 'POST', (r) => this.couriers = r.items);
                usam_api('delivery/problems', 'GET', (r) => this.delivery_problems = r);
                usam_api('delivery/services', {
                    order_id:this.data.id
                }, 'GET', (r) => this.delivery = r);
                usam_api('statuses', {
                    type:'shipped'
                }, 'GET', (r) => this.statuses = r);
                usam_api('storages', {
                    fields:'id=>data',
                    add_fields:['phone', 'schedule', 'city', 'address']
                }, 'POST', (r) => this.storages = r.items);
            },
            methods:{
                selectStorage(e) {
                    this.data[this.sidebardata.code] = e.id;
                    this.data[this.sidebardata.code + '_data'] = e;
                },
                saveForm() {
                    if (this.data.id)
                        usam_api('shipped/' + this.data.id, this.data, 'POST', usam_admin_notice);
                    else
                        usam_api('shipped', this.data, 'POST', (id) => this.afterAdding(id));
                },
                deleteItem() {
                    if (this.data.id)
                        usam_api('shipped/' + this.data.id, 'DELETE', this.afterDelete);
                }
            }
        })
    } else if (document.getElementById('view_form_shipped')) {
        interface_filters = new Vue({
            el:'#view_form_shipped',
            mixins:[shipped_document, formTools],
            data() {
                return {
                    edit:false,
                    show_button:true,
                    pLoaded:true,
                    data:{},
                    query:{
                        add_fields:['small_image', 'sku', 'stock', 'unit_measure', 'price']
                    },
                    units:{},
                    statuses:[],
                    storages:{},
                    delivery_problems:{},
                    couriers:{},
                    delivery:[],
                    crm_type:'shipped',
                    contact:{}
                }
            },
            computed:{
                changeHistoryArgs() {
                    return {
                        object_type:'shipped',
                        object_id:this.data.id
                    };
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                if (this.data.id)
                    usam_api('shipped/' + this.data.id, {
                        add_fields:'document_products'
                    }, 'GET', (r) => this.data = r);
                usam_api('units', {
                    fields:'code=>short'
                }, 'GET', (r) => this.units = r);
                usam_api('employees', {
                    role__in:['courier'],
                    source:'all',
                    fields:'user_id=>name'
                }, 'POST', (r) => this.couriers = r.items);
                usam_api('delivery/problems', 'GET', (r) => this.delivery_problems = r);
                usam_api('delivery/services', {
                    order_id:this.data.id
                }, 'GET', (r) => this.delivery = r);
                usam_api('statuses', {
                    type:'shipped'
                }, 'GET', (r) => this.statuses = r);
                usam_api('storages', {
                    fields:'id=>data',
                    add_fields:['phone', 'schedule', 'city', 'address']
                }, 'POST', (r) => this.storages = r.items);
            },
            methods:{
                objectStatus(e) {
                    usam_api('shipped/' + this.data.id, {
                        status:this.data.status
                    }, 'POST', usam_admin_notice);
                },
                saveElement(e) {
                    var data = {
                        products:this.products
                    };
                    usam_api('shipped/' + this.data.id, data, 'POST', usam_admin_notice);
                }
            }
        })
    } else if (document.getElementById('view_form_lead')) {
        interface_filters = new Vue({
            mixins:[crm_report, edit_properties, lead_document],
            el:'#view_form_lead',
            mounted() {
                this.$watch('data.manager_id', this.selectManager);
                this.$watch('data.status', this.selectStatus);
                this.getGroups();
            },
        })
    } else if (document.getElementById('edit_form_lead')) {
        new Vue({
            mixins:[edit_properties, lead_document],
            el:'#edit_form_lead',
            data() {
                return {
                    edit:true,
                    edit_data:true
                }
            }
        })
    } else if (document.querySelector('#view_form_cart')) {
        interface_filters = new Vue({
            el:'#view_form_cart',
            mixins:[formTools, data_filters],
            methods:{
                requestData(data, e) {
                    usam_active_loader();
                    USAM_Tabs.table_view(data, jQuery('.usam_tab_table'));
                }
            }
        })
    } else if (document.querySelector('#edit_form_email')) {
        interface_filters = new Vue({
            el:'#edit_form_email',
            mixins:[files],
            data() {
                return {
                    data:{},
                    cFile:{
                        type:'email'
                    }
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                this.cFile.object_id = this.data.id;
                if (document.querySelector('.usam_attachments'))
                    this.fDownload();
            }
        })
    } else if (document.getElementById('edit_form_keyword')) {
        new Vue({
            el:'#edit_form_keyword',
            mixins:[formTools],
            data() {
                return {
                    data:{},
                }
            },
            created() {
                this.data = form_data;
            },
            methods:{
                deleteItem() {
                    if (this.data.id)
                        usam_api('keyword/' + this.data.id, 'DELETE', this.afterDelete);
                },
                saveForm(add) {
                    usam_active_loader();
                    var data = structuredClone(this.data);
                    if (this.data.id)
                        usam_api('keyword/' + this.data.id, data, 'POST', (r) => {
                            if (add === true)
                                this.addNew();
                            usam_admin_notice(r);
                        });
                    else
                        usam_api('keyword', data, 'POST', (id) => {
                            if (add === true)
                                this.addNew();
                            else
                                this.afterAdding(id);
                        });
                },
            }
        })
    } else if (document.getElementById('edit_form_shipping')) {
        new Vue({
            el:'#edit_form_shipping',
            mixins:[formTools],
            data() {
                return {
                    data:{},
                    roles:[],
                    payers:[],
                    options:[],
                    bank_accounts:[],
                    storage:'',
                    thumbnail:'',
                    selfpickup:true
                }
            },
            created() {
                this.data = form_data;
                usam_api('roles', {fields:'code=>name'}, 'GET', (r) => this.roles = r.items);
                usam_api('types_payers', 'GET', (r) => this.payers = r.items);
                usam_api('accounts', {company_type:['own', 'partner'], fields:'id=>data', add_fields:['bank_account_name']}, 'POST', (r) => this.bank_accounts = r.items);
            },
            mounted() {
                this.$watch('data.handler', this.loadOptions);
                this.loadOptions();
            },
            methods:{
                loadOptions() {
                    this.options = [];
					usam_api('delivery/options/' + this.data.handler, 'GET', (r) => {
						for (let i in r.options)
							r.options[i].value = this.data[r.options[i].code] !== undefined ? this.data[r.options[i].code] :r.options[i].default;
						this.options = r.options;
						if( !this.data.handler )
							this.selfpickup = true;
						else
							this.selfpickup = r.selfpickup;
						this.data.delivery_option = this.selfpickup ? this.data.delivery_option :0
					});                    
                },
                deleteItem() {
                    if (this.data.id)
                        usam_api('delivery/' + this.data.id, 'DELETE', this.afterDelete);
                },
                saveForm() {
                    var data = structuredClone(this.data);
                    for (let i in this.options)
                        data[this.options[i].code] = this.options[i].value;
                    usam_active_loader();
                    if (this.data.id)
                        usam_api('delivery/' + this.data.id, data, 'POST', usam_admin_notice);
                    else
                        usam_api('delivery', data, 'POST', (id) => this.afterAdding(id));
                }
            }
        })
    } else if (document.getElementById('edit_form_plan')) {
        new Vue({
            el:'#edit_form_plan',
            data() {
                return {
                    data:{},
                }
            },
            created() {
                this.data = form_data;
            }
        })
    } else if (document.getElementById('edit_form_avito')) {
        new Vue({
            el:'#edit_form_avito',
            data() {
                return {
                    data:{},
                    attributes:[],
                    types:[]
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                this.upload_product_types();
                this.$watch('data.avito_category', this.change_avito_category);
                usam_api('product_attributes', {
                    orderby:'name',
                    order:'ASC',
                    count:0,
                    add_fields:'options'
                }, 'POST', (r) => {
                    for (let i in r.items)
                        if (r.items[i].parent)
                            Vue.set(this.attributes, i, {
                                id:r.items[i].slug,
                                name:r.items[i].name
                            });
                });
            },
            methods:{
                addAttribute(e) {
                    this.data.product_characteristics.push(e);
                },
                change_avito_category() {
                    this.types = [];
                    this.data.avito_product_type = '';
                    this.upload_product_types();
                    if (this.types.length)
                        Vue.set(this.data, 'avito_product_type', this.types[0]);
                },
                upload_product_types() {
                    for (let i in this.data.category_list[this.data.avito_group].sub) {
                        if (this.data.category_list[this.data.avito_group].sub[i].name === this.data.avito_category) {
                            if (this.data.category_list[this.data.avito_group].sub[i].types != undefined)
                                this.types = this.data.category_list[this.data.avito_group].sub[i].types;
                            break;
                        }
                    }
                }
            }
        })
    } else if (document.querySelector('.trading_platform')) {
        new Vue({
            el:'.trading_platform',
            data() {
                return {
                    data:{},
                    attributes:[],
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                usam_api('product_attributes', {
                    orderby:'name',
                    order:'ASC',
                    count:0,
                    add_fields:'options'
                }, 'POST', (r) => {
                    for (let i in r.items)
                        if (r.items[i].parent)
                            Vue.set(this.attributes, i, {
                                id:r.items[i].slug,
                                name:r.items[i].name
                            });
                });
            },
            methods:{
                addAttribute(e) {
                    this.data.product_characteristics.push(e);
                }
            }
        })
    } else if (document.getElementById('edit_form_department')) {
        new Vue({
            el:'#edit_form_department',
            mixins:[formTools],
            data() {
                return {
                    companies:[],
                    args_contacts:{
                        add_fields:['foto'],
                        user_id__not_in:0,
                        source:'employee'
                    }
                }
            },
            created() {
                this.data = form_data;
                usam_api('companies', {
                    type:'own',
                    orderby:'name',
                    count:1000
                }, 'POST', (r) => this.companies = r.items);
            },
            methods:{
                selectContact(item) {
                    this.data.chief = item.id;
                    this.manager = item;
                    this.sidebar('contacts')
                },
                deleteItem() {
                    if (this.data.id)
                        usam_api('department/' + this.data.id, 'DELETE', this.afterDelete);
                },
                saveForm() {
                    usam_active_loader();
                    var data = structuredClone(this.data);
                    if (this.data.id)
                        usam_api('department/' + this.data.id, data, 'POST', usam_admin_notice);
                    else
                        usam_api('department', data, 'POST', (id) => this.afterAdding(id));
                }
            }
        })
    } else if (document.querySelector('.affairs_grid_view')) {
        interface_filters = new Vue({
            el:'.affairs_grid_view',
            mixins:[grid_view, grid_view_event, data_filters],
            data() {
                return {
                    query_vars:{
                        affairs:1
                    },
                }
            }
        })
    } else if (document.querySelector('.tasks_grid_view')) {
        interface_filters = new Vue({
            el:'.tasks_grid_view',
            mixins:[grid_view, grid_view_event, data_filters],
            data() {
                return {
                    query_vars:{
                        type:'task'
                    },
                }
            }
        })
    } else if (document.getElementById('edit_form_seal')) {
        new Vue({
            el:'#edit_form_seal',
            mixins:[files],
            data() {
                return {
                    cFile:{
                        type:'seal'
                    }
                }
            },
            mounted() {
                this.fDownload();
            }
        })
    } else if (document.getElementById('edit_form_trigger')) {
        new Vue({
            el:'#edit_form_trigger',
            data() {
                return {
                    data:{},
                    actions_triggers:{},
                    triggers:{},
                    action:null,
                    type_prices:[],
                    newsletters:[]
                }
            },
            created() {
                this.data = form_data;
            },
            mounted() {
                this.triggers = form_args.triggers;
                this.actions_triggers = form_args.actions_triggers;
                usam_api('type_prices', 'GET', (r) => this.type_prices = r.items);
                usam_api('newsletters', {
                    class:'template'
                }, 'POST', (r) => this.newsletters = r.items);
            },
            methods:{
                sidebarOpen(type) {
                    this.$refs['modal' + type].show = true;
                },
                selectEvent(k) {
                    this.data.event = k;
                    this.$refs.modalevent.show = false;
                },
                editAction(k) {
                    this.action = k;
                    this.sidebarOpen('action');
                },
                addAction() {
                    this.action = null;
                    this.sidebarOpen('action');
                },
                selectAction(id) {
                    if (this.action === null) {
                        this.action = this.data.actions.push({
                            id:id,
                            settings:{}
                        });
                        this.action--;
                    }
                    if (id === 'creating_lead')
                        this.saveAction()
                },
                saveAction() {
                    this.action = null;
                    this.$refs.modalaction.show = false;
                },
                saveForm(e) {
                    e.preventDefault();
                    if (this.data.id)
                        usam_api('trigger/' + this.data.id, this.data, 'POST', usam_admin_notice);
                    else
                        usam_api('trigger', this.data, 'POST', (id) => {
                            this.data.id = id
                                let url = new URL(document.location.href);
                            url.searchParams.set('id', id);
                            window.location.replace(url.href);
                        });
                }
            }
        })
    } else if (document.getElementById('edit_form_parser_supplier')) {
        new Vue({
            el:'#edit_form_parser_supplier',
            mixins:[parser, formTools]
        })
    } else if (document.getElementById('edit_form_parser_competitor')) {
        new Vue({
            el:'#edit_form_parser_competitor',
            mixins:[parser, formTools]
        })
    } else if (document.querySelector('.form_country')) {
        new Vue({
            el:'.form_country',
            mixins:[formTools],
            created() {
                this.data = form_data;
            }
        })
    } else if (document.querySelector('.form_bonus_card')) {
        new Vue({
            el:'.form_bonus_card',
            mixins:[formTools],
            data() {
                return {
                    args_contacts:{
                        add_fields:['foto'],
                        user_id__not_in:0
                    }
                }
            },
            created() {
                this.data = form_data;
            },
            methods:{
                getSidebarSelected() {
                    if (this.data.user_id !== undefined)
                        return [this.data.user_id];
                    else
                        return [];
                },
                addContacts(item) {
                    this.data.user_id = item.user_id;
                    this.user = item;
                }
            }
        })
    } else if (document.querySelector('.form_phone')) {
        new Vue({
            el:'.form_phone',
            mixins:[formTools],
            data() {
                return {
                    select_location:false
                }
            },
            created() {
                this.data = form_data;
            }
        })
    } else if (document.querySelector('.import_form')) {
        new Vue({
            el:'.import_form',
            mixins:[formTools, exchangeRule],
            data() {
                return {
                    folder:{},
                    contractors:[],
                    columns_available:{},
                    column_conditions:{},
                    statuses:[],
                    prices:[],
                    groups:[],
                    roles:[],
                    taxonomies:[],
                    terms:{},
                }
            },
            computed:{
                priceColumns() {
                    for (let i in this.data.columns)
                        if (typeof this.data.columns[i].column === 'string' && this.data.columns[i].column.includes('price_'))
                            return true
                            return false;
                },
                priceColumns2() {
                    for (let i in this.data.columns)
                        if (typeof this.data.columns[i].column2 === 'string' && this.data.columns[i].column2.includes('price_'))
                            return true
                            return false;
                },
            },
            created() {
                if (this.data.type === 'product_export' || this.data.type === 'product_import' || this.data.type === 'pricelist') {
                    usam_api('type_prices', {
                        fields:'code=>title'
                    }, 'GET', (r) => this.prices = r.items);
                    usam_api('taxonomies', {
                        object_type:'usam-product'
                    }, 'POST', (r) => this.taxonomies = r);
                    usam_api('companies', {
                        type:'contractor',
                        fields:'id=>data',
                        orderby:'name',
                        count:1000
                    }, 'POST', (r) => this.contractors = r.items);
                    usam_api('terms', {
                        taxonomy_object:'usam-product',
                        hide_empty:0,
                        count:10000,
                        orderby:'sort',
                        order:'asc',
                        name_format:'hierarchy'
                    }, 'POST', (r) => {
                        var t = {};
                        for (let i in r.items) {
                            if (typeof t[r.items[i].taxonomy] === typeof undefined)
                                t[r.items[i].taxonomy] = []
                                t[r.items[i].taxonomy].push({
                                    id:r.items[i].term_id,
                                    name:r.items[i].name
                                })
                        }
                        this.terms = t
                    });
                    if (this.data.type === 'pricelist')
                        usam_api('roles', {
                            fields:'code=>name'
                        }, 'GET', (r) => this.roles = r.items);
                } else if (this.data.type === 'contact_export' || this.data.type === 'contact_import') {
                    usam_api('statuses', {
                        type:['contact'],
                        fields:'code=>name'
                    }, 'GET', (r) => this.statuses = r);
                    usam_api('groups', {
                        type:'contact',
                        count:1000
                    }, 'GET', (r) => this.groups = r.items);
                } else if (this.data.type === 'company_export' || this.data.type === 'company_import') {
                    usam_api('statuses', {
                        type:['company'],
                        fields:'code=>name'
                    }, 'GET', (r) => this.statuses = r);
                    usam_api('groups', {
                        type:'company',
                        count:1000
                    }, 'GET', (r) => this.groups = r.items);
                } else if (this.data.type === 'order_export' || this.data.type === 'order_import') {
                    usam_api('statuses', {
                        type:['order'],
                        fields:'code=>name'
                    }, 'GET', (r) => this.statuses = r);
                    usam_api('groups', {
                        type:'order',
                        count:1000
                    }, 'GET', (r) => this.groups = r.items);
                }
            },
            methods:{}
        })
    } else if (document.querySelector('.settings_form')) {
        new Vue({
            el:'.settings_form',
            mixins:[edit_properties],
            created() {
                this.properties = form_data;
            },
            methods:{
                save(e) {
                    var data = {
                        options:{}
                    };
                    for (let i in this.properties)
                        data.options[this.properties[i].code] = this.properties[i].value;
                    usam_api('theme', data, 'POST', usam_admin_notice);
                }
            }
        })
    } else if (document.getElementById('tab_presentation_content')) {
        new Vue({
            el:'#tab_presentation_content',
            mixins:[edit_properties],
            data() {
                return {
                    oldIndex:null,
                    tab:'',
                    blocks:[],
					home_blocks:[],				
                    settings:{},
                    tabs:[],
                    categories:[],				
                    default_category:'',
                    no_image_uploaded:{},
                    filters:{ active:0}
                };
            },
            created() {
				this.tab = home_blocks.length?'homeblocks':'productpage';
				this.blocks = htmlblocks;
				this.home_blocks = home_blocks;						
                this.settings = settings;
                this.tabs = tabs;
                this.no_image_uploaded = no_image_uploaded;
                this.default_category = default_category;
                usam_api('categories', {hide_empty:0, count:10000, orderby:'sort', order:'asc', name_format:'hierarchy'}, 'POST', (r) => {
                    this.categories.push({id:0, name:' - '})
                    for (let i in r.items)
                        this.categories.push({id:r.items[i].term_id, name:r.items[i].name})
                });				
            },
            methods:{
                sidebar(type) {
					this.$refs['modal' + type].show = !this.$refs['modal' + type].show;
				},
				fDrop(e) {
                    e.preventDefault();
                    e.currentTarget.classList.remove('over');
                    this.fUpload(e.dataTransfer.files[0]);
                },
                fAttach(e) {
                    let el = e.target.querySelector('input[type="file"]');
                    if (el)
                        el.click();
                    else if (e.currentTarget.nextElementSibling)
                        e.currentTarget.nextElementSibling.click();
                },
                aDrop(e) {
                    e.preventDefault();
                    e.currentTarget.classList.add('over');
                },
                fChange(e) {
                    if (!e.target.files[0])
                        return;
                    this.fUpload(e.target.files[0]);
                },
                fUpload(f) {
                    Vue.set(this.settings, 'no_image_uploaded', {name:'', title:f.name, percent:0, load:true, error:false});
                    var fData = new FormData();
                    fData.append('file', f);
                    usam_form_save(fData, (r) => {
                        if (r)
                            Object.assign(this.no_image_uploaded, {url:r, load:false});
                    }, (e) => this.no_image_uploaded.percent = e.loaded * 100 / e.total, 'no_image_uploaded');
                },
                allowDrop(e, k) {
                    e.preventDefault();
                    if (this.oldIndex != k) {
                        let v = structuredClone(this.home_blocks[this.oldIndex]);
                        this.home_blocks.splice(this.oldIndex, 1);
                        this.home_blocks.splice(k, 0, v);
                        this.oldIndex = k;
                    }
                },
                drag(e, k) {
                    this.oldIndex = k;
                    if (e.target.hasAttribute('draggable'))
                        e.currentTarget.classList.add('draggable');
                    else
                        e.preventDefault();
                },
                dragEnd(e, i) {
                    e.currentTarget.classList.remove('draggable');
                    for (i = 0; i < this.home_blocks.length; i++)
                        this.home_blocks[i].sort = i;
                },
                getProperties(b) {
                    var props = []
                    for (let i in this.settings)
                        if (this.settings[i].block == b)
                            props.push(this.settings[i]);
                    return props;
                },
                getProperty(c) {
                    for (let i in this.settings)
                        if (this.settings[i].code == c)
                            return this.settings[i];
                    return {};
                },				
                saveForm() {
                    var options = [{code:'default_category', value:this.default_category}]
					for (let i in this.settings)
                        if( !this.settings[i].option )
							options.push({code:this.settings[i].code, value:this.settings[i].value, set_option:this.settings[i].set_option});
					var v = {};					
					for (let i in this.settings)
					{
						if( this.settings[i].option )
						{
							if( v[this.settings[i].option] === undefined )
								v[this.settings[i].option] = {code:this.settings[i].option, value:{}, set_option:this.settings[i].set_option};
							v[this.settings[i].option].value[this.settings[i].code] = this.settings[i].value;
						}
					}	
					for (let i in v)
						options.push(v[i]);
                    var home_blocks = []
                    for (let i in this.home_blocks) {
                        let opts = structuredClone(this.home_blocks[i]);
                        let o = {};
                        for (let j in this.home_blocks[i].options)
                            o[this.home_blocks[i].options[j].code] = this.home_blocks[i].options[j].value;
                        opts.options = o						
                        home_blocks.push(opts);	
					}
                    usam_api('settings', {options:options, home_blocks:home_blocks}, 'POST', usam_admin_notice);
                }
            }
        })
	 } else if (document.getElementById('tab_html_blocks_content')) {
        new Vue({
			el:'#tab_html_blocks_content',
            mixins:[formTools],
			data() {
                return {               
                    data:[],
					registerBlocks:[], 	
					oldIndex:null,	
					nameJSONFile:'html-blocks'
                };
            },  
			created() {               
			//   for (let i in blocks)						
			//		this.data[blocks[i].code] = blocks[i];
				this.data = blocks;
				this.registerBlocks = registerBlocks;				
            },
            methods:{               
			   sidebar(type) {
					this.$refs['modal' + type].show = !this.$refs['modal' + type].show;
				},	
				loadDataJSON(e) {
					if (Object.keys(e).length) {
						this.data = [];
						this.dataTypeProcessing(e);
						for (let i in e) {						
							for (let k in this.registerBlocks) 
								if( this.registerBlocks[k].template == e[i].template )
								{
									for (const p of ['options', 'style', 'html', 'content_style'])
									{
										let o = structuredClone(this.registerBlocks[k][p]);
										for (let j in o)
											o[j].value = e[i][p][o[j].code];
										e[i][p] = o							
									}
									break;
								}						
						}	
						this.data = e;
					}
				},
				addBlock(item) {
					var block = structuredClone(item);
					var max = 0;
					for (let k in this.data)
						if( max < this.data[k].id )
							max = this.data[k].id;
					block.id = max+1;
					block.loading = "eager";
					this.data.push( block );
                },              
				getDataJSON(){
					var blocks = []
                    for (let i in this.data) {
                        let opts = structuredClone(this.data[i]);
                        for (const p of ['options', 'style', 'html', 'content_style'])
						{
							let o = {};
							for (let j in this.data[i][p])
								o[this.data[i][p][j].code] = this.data[i][p][j].value;
							opts[p] = o							
						}
                        blocks.push(opts);
                    }				
					return blocks;
				},
				saveForm() {                   			
					usam_api('settings', {htmlblocks:this.getDataJSON()}, 'POST', usam_admin_notice);
                }
            }
        })
    } else if (document.getElementById('edit_form_marking_code')) {
        new Vue({
            el:'#edit_form_marking_code',
            mixins:[formTools],
            data() {
                return {
                    data:{}
                }
            },
            created() {
                this.data = form_data;
            }
        })
    } else if (document.getElementById('edit_form_sms_newsletter')) {
        new Vue({
            el:'#edit_form_sms_newsletter',
            mixins:[formTools],
            data() {
                return {
                    data:{}
                }
            },
            created() {
                this.data = form_data;
            },
            methods:{
                saveForm(back) {
                    if (this.data.id)
                        usam_api('newsletter/' + this.data.id, this.data, 'POST', (r) => back === true ? this.backList() :usam_admin_notice(r));
                    else
                        usam_api('newsletter', this.data, 'POST', this.afterAdding);
                },
                changeStatus(status) {
                    this.data.status = status;
                    this.saveForm();
                },
                send(e) {
                    this.data.status = 5;
                    this.saveForm(true);
                },
                deleteItem() {
                    if (this.data.id)
                        usam_api('newsletter/' + this.data.id, 'DELETE', this.afterDelete);
                }
            }
        })
    }
    if ( document.getElementById('media-browser') ) {
        media_browser = new Vue({
            el:'#media-browser',
            mixins:[mediaBrowser],
            created() {
                make('.images img', 'click', this.init);
            },
            methods:{
                init(e) {
                    this.images = [];
                    var src = e.currentTarget.getAttribute('src')
					e.currentTarget.closest('.images').querySelectorAll('img').forEach((el) => {
						var imgUrl = el.getAttribute('src');
						if (imgUrl.indexOf('assets/sprite.svg#') === -1) {
							const url = new URL(imgUrl);
							const searchParams = url.searchParams;
							searchParams.delete("size");
							this.images.push({small:imgUrl, full:url.toString()});
							if (src == imgUrl)
								this.image_key = this.images.length - 1;
						}
					})
					if (this.images.length)
						this.open = true;
                }
            }
        })
    }
    if (document.getElementById('product_viewer')) {
        product_viewer = new Vue({
            el:'#product_viewer',
            mixins:[mediaBrowser, edit_properties, changeProductProperties],
            data() {
                return {
                    product:{
                        ID:0,
                        post_title:''
                    },
                    edit:false
                }
            },
            methods:{
                init() {
                    usam_active_loader();
                    this.images = [];
                    usam_api('product/' + this.product.ID, {
                        add_fields:['sku', 'views', 'comment', 'compare', 'desired', 'subscription', 'basket', 'purchased', 'category', 'images', 'price', 'edit_attributes', 'storages', 'not_limited', 'stock', 'reserve']
                    }, 'GET', (r) => {
                        this.open = true;
                        this.product = r;
                        this.images = r.images;
                        this.tab = this.images.length ? 'images' :'attribute';
                        this.processProduct(r);
                    });
                }
            }
        })
    }     
})

make('.js-product-viewer-open', 'click', (e) => {
    product_viewer.product = {
        ID:e.currentTarget.getAttribute('product_id')
    };
    product_viewer.init();
});

Vue.component('json-setting-upload', {
    data() {
        return {
            file:{},
        };
    },
    methods:{
        fDelete() {
            this.file = {};
        },
        fDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('over');
            this.fUpload(e.dataTransfer.files[0]);
        },
        fAttach(e) {
            let el = e.target.querySelector('input[type="file"]');
            if (el)
                el.click();
            else if (e.currentTarget.nextElementSibling)
                e.currentTarget.nextElementSibling.click();
        },
        aDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.add('over');
        },
        fChange(e) {
            if (!e.target.files[0])
                return;
            this.fUpload(e.target.files[0]);
        },
        fUpload(f) {
            const reader = new FileReader()
                reader.readAsText(f)
                reader.onload = () => this.$emit('result', JSON.parse(reader.result));
        }
    }
})

Vue.component('level-table', {
    template:'<div class="level_table"><slot name="head" :items="items"/><table class="widefat striped ready_options" :class="[fixed?`fixed`:``]"><thead><tr><slot name="thead" :items="items"/></tr></thead><tbody><tr v-for="(row, k) in items" @dragover="allowDrop($event, k)" @dragstart="drag($event, k)" @drop="drop($event, k)" @dragend="dragEnd($event, k)"><slot name="tbody" :items="items" :row="row" :k="k" :add="add" :del="del"/></tr></tbody></table><slot name="footer" :items="items"/></div>',
    props:{
        lists:{required:true, default:null},
        d:{required:false},
		fixed:{required:false, default:true},
    },
    data() {
        return {
            items:[]
        };
    },
    watch:{
        lists:{
            handler(val, oldVal) {
                if (val !== null)
                    this.items = this.lists;
            },
            deep:true
        }
    },
    mounted() {
        this.items = this.lists;
    },
    methods:{
        allowDrop(e, k) {
            e.preventDefault();
            let el = document.querySelector(".above_draggable")
                if (el)
                    el.classList.remove('above_draggable');
                e.currentTarget.closest('tr').classList.add('above_draggable');
        },
        drag(e, k) {
            e.dataTransfer.setData("old_k", k);
            e.currentTarget.closest('tr').classList.add('draggable');
        },
        dragEnd(e, k) {
            if (document.querySelector(".above_draggable"))
                document.querySelector(".above_draggable").classList.remove('above_draggable');
            e.currentTarget.closest('tr').classList.remove('draggable');
        },
        drop(e, k) {
            e.preventDefault();
            let old_k = e.dataTransfer.getData("old_k");
            if (old_k === '')
                return false;
            old_k = parseInt(old_k);
            let v = Object.assign({}, this.items[old_k]);
            this.items.splice(old_k, 1);
            this.items.splice(k, 0, v);

        },
        add(k) {
            var item = structuredClone(this.items[k]);
            for (let i in item)
                item[i] = typeof item[i] == 'string' || typeof item[i] == 'string' ? '' :[];
            this.items.splice(k + 1, 0, item);
            this.go(k + 1);
        },
        go(k) {
            setTimeout(() => {
                if (this.$refs['value' + k] && this.items.length >= k && k >= 0)
                    this.$refs['value' + k][0].focus();
            }, 100);
        },
        keyup(k, e) {
            if (e.which == 40 && e.ctrlKey)
                this.go(k + 1);
            else if (e.which == 38 && e.ctrlKey)
                this.go(k - 1);
        },
        del(k) {
            this.$emit('delete', this.items[k]);
            this.items.splice(k, 1);
        }
    }
});

Vue.component('file-upload', {
    props:{
        file:{required:true, default:null},
        params:{required:false, default:{ type:'loaded'} },
    },
    data() {
        return {
            file:[],
        };
    },
    methods:{
        fDelete() {
            this.file = {};
        },
        fDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('over');
            this.fUpload(e.dataTransfer.files[0]);
        },
        fAttach(e) {
            let el = e.target.querySelector('input[type="file"]');
            if (el)
                el.click();
            else if (e.currentTarget.nextElementSibling)
                e.currentTarget.nextElementSibling.click();
        },
        aDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.add('over');
        },
        fChange(e) {
            if (!e.target.files[0])
                return;
            this.fUpload(e.target.files[0]);
        },
        fUpload(f) {
            Vue.set(this, 'file', {
                name:'',
                title:f.name,
                size:formatFileSize(f.size),
                icon:'',
                percent:0,
                load:true,
                error:false
            });
            var fData = new FormData();
            fData.append('file', f[i]);
            for (let j in this.params)
                fData.append(j, this.params[j]);
            usam_form_save(fData, (r) => Vue.set(this, 'file', r), (e) => this.file.percent = e.loaded * 100 / e.total, 'upload');
        }
    }
})

Vue.component('selection-products', {
    props:{
        download:{
            required:false,
        default:
            true
        },
    },
    data() {
        return {
            page:1,
            count:0,
            orderby:'title',
            order:'asc',
            request:false,
            columns:{},
            items:[]
        };
    },
    watch:{
        page(val, oldVal) {
            this.requestData();
        },
        download(val, oldVal) {
            if (val)
                this.requestData();
        }
    },
    mixins:[data_filters],
    mounted() {
        if (this.download)
            this.requestData();
    },
    methods:{
        requestData(data) {
            if (data === undefined)
                data = {};
            data.paged = this.page;
            data.non_empty = true;
            data.add_fields = ['small_image', 'sku', 'price_currency', 'stock_units', 'storages_data'];
            this.request = true;
            usam_api('products', data, 'POST', (r) => {
                this.request = false;
                this.columns = {};
                for (let i in r.items) {
                    for (let j in r.items[i].storages_data) {
                        this.columns[j] = {
                            name:r.items[i].storages_data[j].title,
                            stock:r.items[i].storages_data[j].stock
                        };
                        if (Object.keys(this.columns).length > 10)
                            break;
                    }
                }
                this.items = r.items;
                this.count = r.count;
            });
        },
        sort(k) {
            this.order = k != this.orderby || this.order == 'desc' ? 'asc' :'desc';
            this.orderby = k;
            var data = {};
            data.order = this.order;
            data.orderby = this.orderby;
            this.requestData(data);
        },
        select(k) {
            this.$emit('change', this.items[k].ID);
        },
        viewer(k) {
            product_viewer.product = this.items[k];
            product_viewer.init();
        }
    }
})

Vue.component('ribbon', {
    props:{
        object_type:{
            type:String,
            required:true,
        default:
            ''
        },
        object_id:{
            type:Number,
            required:true,
        default:
            0
        },
        contact:{
            type:Object,
            required:false,
        default:
            () => {}
        },
    },
    data() {
        return {
            items:[],
            elKey:null,
            count:0,
            page:1,
            message:'',
            showAddElement:false,
            load:false,
            phone:{},
            tab:'comment',
            task:{
                type:'task',
                title:'',
                start:this.setDate(),
                end:this.setDate(),
                reminder_date:this.setDate(2)
            },
            meeting:{
                type:'meeting',
                title:'',
                start:this.setDate(),
                end:this.setDate(),
                reminder_date:this.setDate(2)
            },
            sms:{
                message:'',
                phone:0
            },
            letter:{
                message:'',
                email:''
            },
            messenger:{
                message:'',
                type:''
            },
            sidebarActive:0
        };
    },
    watch:{
        phone(v, old) {
            this.sms.phone = this.contact.phones;
        },
    },
    beforeMount() {
        this.scrollGrid();
    },
    mounted() {
        this.upload();
        var el = document.querySelector('.js-show-more');
        if (el) {
            const moreObserver = new IntersectionObserver((e, mObserver) => {
                e.forEach((entry) => {
                    if (entry.isIntersecting) {
                        if (this.items.length !== this.count) {
                            this.page++;
                            this.upload(true);
                        }
                    }
                })
            });
            moreObserver.observe(el);
        }
    },
    methods:{
        sidebar(type, k) {
            this.elKey = k;
            this.$refs['modal' + type].show = !this.$refs['modal' + type].show;
            this.sidebarActive = this.$refs['modal' + type].show ? type :false;
        },
        setDate(day) {
            day = day !== undefined ? day :3;
            const d = new Date();
            return local_date(d.setDate(d.getDate() + day), "Y-m-d H:i:s");
        },
        displayMessage(message) {
            return message.replace(/\n/g, "<br>");
        },
        scrollGrid() {
            setTimeout(() => {
                const slider = document.querySelector('.ribbon_buttons');
                let mouseDown = false;
                let startX,
                scrollLeft;
                let startDragging = (e) => {
                    mouseDown = true;
                    startX = e.type == 'touchstart' ? e.changedTouches[0].pageX - slider.offsetLeft :e.pageX - slider.offsetLeft;
                    scrollLeft = slider.scrollLeft;
                };
                let stopDragging = (e) => mouseDown = false;
                let moveDragging = (e) => {
                    if (mouseDown)
                        slider.scrollLeft = scrollLeft - ((e.type == 'touchmove' ? e.changedTouches[0].pageX - slider.offsetLeft :e.pageX - slider.offsetLeft) - startX);
                }
                slider.addEventListener('mousedown', startDragging, false);
                slider.addEventListener('touchstart', startDragging, false);
                slider.addEventListener('mousemove', moveDragging, false);
                slider.addEventListener('touchmove', moveDragging, false);
                slider.addEventListener('mouseup', stopDragging, false);
                slider.addEventListener('touchend', stopDragging, false);
                slider.addEventListener('mouseleave', stopDragging, false);
            }, 100);
        },
        upload(add) {
            if (!this.load && this.object_id > 0 && this.object_type !== '') {
                this.load = true;
                usam_api('livefeed', {
                    object_id:this.object_id,
                    object_type:this.object_type,
                    order:'DESC',
                    paged:this.page
                }, 'POST', (r) => {
                    this.load = false;
                    for (let k in r.items) {
                        r.items[k].editor = false;
                    }
                    if (add !== undefined && add)
                        for (let k in r.items)
                            this.items.push(r.items[k]);
                    else {
                        this.items = r.items;
                        this.count = r.count;
                    }
                });
            }
        },
        addComment(e) {
            e.preventDefault();
            if (this.message) {
                usam_api('comment', {
                    object_id:this.object_id,
                    object_type:this.object_type,
                    message:this.message,
                    ribbon:1
                }, 'POST', (r) => {
                    r.editor = false;
                    this.items.unshift(r);
                });
                this.message = '';
            }
        },
        openEdit(k) {
            this.items[k].editor = true;
            var f = (ev) => {
                let el = ev.target.closest('#comment_item_edit_' + this.items[k].id);
                if (el == null) {
                    this.items[k].editor = false;
                    document.removeEventListener("click", f);
                }
            };
            setTimeout(() => {
                this.$refs['messages'][k].focus();
                document.addEventListener("click", f);
            }, 100);
        },
        deleteComment(k) {
            usam_item_remove({
                data:{
                    comment:this.items[k]
                },
                'callback':(data) => {
                    this.items.splice(k, 0, data.comment);
                    this.updateComment(data.comment.id, {
                        status:0
                    })
                }
            });
            this.updateComment(this.items[k].id, {
                status:1
            });
            this.items.splice(k, 1);
        },
        clickCommentUpdate(k) {
            this.items[k].editor = false;
            this.updateComment(this.items[k].id, {
                message:this.items[k].message
            });
        },
        updateComment(id, data) {
            usam_api('comment/' + id, data, 'POST');
        },
        addTask() {
            if (this.task.title !== '') {
                this.addEvent(this.task);
                this.task.title = ''
            }
        },
        addMeeting() {
            if (this.meeting.title !== '') {
                this.addEvent(this.meeting);
                this.meeting.title = '';
            }
        },
        addEvent(data) {
            data.links = [{
                    object_id:this.object_id,
                    object_type:this.object_type
                }
            ];
            if (typeof this.contact.id !== typeof undefined)
                data.links.push({
                    object_id:this.contact.id,
                    object_type:'contact'
                });
            usam_api('event', data, 'POST', (r) => this.addItem(r, data.type));
        },
        updateTask(k, data) {
            usam_api('event/' + this.items[k].id, data, 'POST', (r) => {
                this.items[k] = Object.assign(this.items[k], r);
            });
        },
        addSMS() {
            usam_api('sms/send', Object.assign({
                    object_id:this.object_id,
                    object_type:this.object_type
                }, this.sms), 'POST', (r) => {
                if (r)
                    this.addItem(r, 'sms');
                else
                    usam_notifi({
                        'text':'Не отправлено'
                    });
            });
            this.sms.message = '';
        },
        addEmail(r) {
            this.sidebar('sendemail');
            this.addItem(r, 'email');
        },
        deleteEmail() {
            this.deleteItem(this.elKey);
            this.sidebar('email');
            //	this.elKey=null
        },
        addMessenger() {
            usam_api('/chat/message', Object.assign({
                    object_id:this.object_id,
                    object_type:this.object_type
                }, this.messenger), 'POST', (r) => {
                r.event_type = 'chat';
                r.editor = false;
                this.items.unshift(r);
            });
            this.messenger.message = '';
        },
        clickChatUpdate(k) {
            this.items[k].editor = false;
            usam_api('/chat/message/' + this.items[k].id, {
                message:this.items[k].message
            }, 'POST');
        },
        addItem(r, type) {
            r.event_type = type;
            this.items.unshift(r);
        },
        deleteItem(k) {
            this.items.splice(k, 1);
        },
        call(p) {
            this.phone = {
                number:p.hidden ? p.private :p.value,
                display:p.value
            };
        }
    }
})

Vue.component('comments', {
    props:{
        type:{
            type:String,
            required:true,
        default:
            ''
        },
        edit:{
            type:Boolean,
            required:false,
        default:
            true
        },
    },
    data() {
        return {
            id:0,
            items:[],
            count:0,
            page:1,
            message:'',
            showAddElement:false,
            loadMore:true,
        };
    },
    watch:{
        count(v, old) {
            this.$emit('count', v);
        },
    },
    mounted() {
        let url = new URL(document.location.href);
        this.id = url.searchParams.get('id');
        this.loadComments();
        var el = document.querySelector('.js-show-more');
        if (el) {
            const moreObserver = new IntersectionObserver((e, mObserver) => {
                e.forEach((entry) => {
                    if (entry.isIntersecting)
                        this.loadComments();
                })
            });
            moreObserver.observe(el);
        }
    },
    methods:{
        loadComments() {
            if (this.loadMore) {
                usam_api('comments', {
                    object_id:this.id,
                    object_type:this.type,
                    order:'DESC',
                    paged:this.page
                }, 'POST', (r) => {
                    if (!r.items.length)
                        this.loadMore = false;
                    else {
                        this.page++;
                        for (let k in r.items) {
                            r.items[k].editor = false;
                            r.items[k].message_html = r.items[k].message.replace(/\n/g, "<br>");
                        }
                        this.items = r.items;
                        this.count = r.count;
                    }
                });
            }
        },
        openEdit(k) {
            if (!this.edit)
                return false;
            this.items[k].editor = true;
            var f = (ev) => {
                let el = ev.target.closest('#comment_item_edit_' + this.items[k].id);
                if (el == null) {
                    this.items[k].editor = false;
                    document.removeEventListener("click", f);
                }
            };
            setTimeout(() => {
                this.$refs['messages'][k].focus();
                document.addEventListener("click", f);
            }, 100);
        },
        deleteComment(k) {
            if (!this.edit)
                return false;
            usam_item_remove({
                data:{
                    comment:this.items[k]
                },
                'callback':(data) => {
                    this.items.splice(k, 0, data.comment);
                    this.count++;
                    this.updateComment(data.comment.id, {
                        status:0
                    })
                }
            });
            this.updateComment(this.items[k].id, {
                status:1
            });
            this.items.splice(k, 1);
            this.count--;
        },
        addComment(e) {
            if (!this.edit)
                return false;
            e.preventDefault();
            if (this.message) {
                usam_api('comment', {
                    object_id:this.id,
                    object_type:this.type,
                    message:this.message,
                    ribbon:1
                }, 'POST', (r) => {
                    r.editor = false;
                    r.message_html = r.message.replace(/\n/g, "<br>");
                    this.items.unshift(r);
                    this.count++;
                });
                this.message = '';
            }
        },
        clickUpdate(k) {
            if (!this.edit)
                return false;
            this.items[k].editor = false;
            this.items[k].message_html = this.items[k].message.replace(/\n/g, "<br>");
            this.updateComment(this.items[k].id, {
                message:this.items[k].message
            });
        },
        updateComment(id, data) {
            usam_api('comment/' + id, data, 'POST');
        },
        localDate(date, format) {
            return local_date(date, format);
        }
    }
})

Vue.component('modal-panel', {
    template:'<div class ="modalSidebar" :class="[show?`show`:``]" :style="`width:`+size"><div class ="modalSidebar__head" ref="head"><slot name="title" :show="show"></slot><span class="dashicons dashicons-no-alt modalSidebar__close" @click="show=false"></span></div><div class ="modalSidebar__content" ref="content"><slot name="body" :show="show" :close="close"></slot></div></div>',
    props:{
        size:{required:false, default:'450px'},
        backdrop:{type:Boolean, required:false, default:true},
    },
    watch:{
        show(v, old) {
            if (v) {
                var height = window.innerHeight - this.$refs.head.clientHeight - parseInt(window.getComputedStyle(this.$refs.content, null).paddingTop) * 2;
                this.$refs.content.setAttribute("style", "max-height:" + height + "px; overflow-y:overlay; overflow-x:hidden;");
                setTimeout(() => document.addEventListener('click', this.close), 1);
            } else
                document.removeEventListener('click', this.close, false);
            if (this.backdrop) {
                if (v)
                    add_backdrop();
                else {
                    var b = document.querySelector('.usam_backdrop');
                    !b || b.remove();
                }
            }
            setTimeout(() => this.animation = v, 380);
        }
    },
    data() {
        return {
            show:false
        }
    },
    methods:{
        close(e) {
            if (e.target.closest('.modalSidebar') || e.target.classList.contains("modalSidebar"))
                return false;
            this.show = false;
            document.removeEventListener('click', this.close, false);
        },
    }
})

Vue.component('list-table', {
    template:'#list-table',
    props:{
        load:{required:false, default:true},
        args:{required:false, default:null},
        query:{required:false, default:''},		
        filter:{required:false, default:true},
        columns:{required:false, default:() => []},
    },
    data() {
        return {
            page:1,
            count:0,
            search:'',
            loading:false,
            items:null
        }
    },
    mounted() {
        if ( this.load )
            this.queryAPI();
		if( this.query )
		{
			this.$watch('search', this.queryAPI);
			this.$watch('page', this.queryAPI);
			this.$watch('args', this.queryAPI);
			this.$watch('load', () => {
				if (this.items === null)
					this.queryAPI();
			});
			this.$watch('query', () => {
				this.page = 1;
				this.search = '';
				this.queryAPI();
			});
		}
    },
    methods:{
        queryAPI() {
            this.loading = true
            var d = this.args === null ? {} :this.args;
            d.search = this.search;
            d.paged = this.page;
            usam_api(this.query, d, 'POST', (r) => {
				this.loading = false
				this.items = r.items
				this.count = r.count
				this.$emit('change', this.items);
            });
        },
        localDate(date, format) {
            return local_date(date, format);
        }
    }
})

Vue.component('usam-box', {
    template:'<div :id="id" class="postbox usam_box" :class="[show?``:`closed`]"><div v-if="handle" class="handlediv" title="Нажмите, чтобы переключить"></div><h3 class="usam_box__title ui-sortable-handle"><slot name="title" :edit="edit"><span v-html="title"></span><slot name="button" :edit="edit"></slot></slot></h3><div class="inside"><slot name="body" :edit="edit"></slot></div></div>',
    props:{
        id:{type:String, required:true, default:''},
        title:{type:String, required:false, default:''},
        handle:{type:Boolean, required:false, default:true},
    },
    data() {
        return {
            show:true,
            edit:false,
        }
    }
})

Vue.component('wp-media', {
    template:'<div class="wp_media"><slot name="body" :data="data"><a v-if="Object.keys(data).length&&data.url" class="image_container usam_thumbnail"><img :src="data.url" @click="addMedia"></a><div class="wp_media_buttons" v-if="Object.keys(data).length&&data.url"><div class="button" @click="deleteMedia">Удалить</div><div @click="addMedia" class="button">Изменить</div></div><div v-else @click="addMedia" class="button">Выбрать изображение</div></slot></div>',
    props:{
        value:{type:[Number,String], required:false, default:0},
		file:{type:Object, required:false, default:() => {}},		
        multiple:{type:[Boolean,Number], required:false, default:false},
		title:{type:String, required:false, default:''},	
    },
    data() {
        return {
            media:null, data:this.file
        }
    },
    methods:{
        addMedia() {
            if (this.media === null) {
                this.media = wp.media.frames.images_file_frame = wp.media({library:{type:'image'}, multiple:this.multiple, title:this.title});
                wp.media.frames.images_file_frame.on('open', () => {
                    var selection = wp.media.frames.images_file_frame.state().get('selection');
                    if (this.id > 0) {
                        attachment = wp.media.attachment(this.id);
                        attachment.fetch();
                        selection.add(attachment ? [attachment] :[]);
                    }
                });
                this.media.on('select', () => {
                    var m = this.multiple ? this.media.state().get('selection').toJSON() :this.media.state().get('selection').first().toJSON();
                    this.data = m;
					this.$emit('input', m.id);
					this.$emit('change', m);
                });
            }
            this.media.open();
        },
		deleteMedia() {
            this.data = {}
			this.$emit('input', 0);	
        }
    }
})

Vue.component('filter-search', {
    template:`<div class ="modal_panel_filter"><input class="modal_panel_filter__search" type="text" v-model="search" :placeholder="placeholder" autocomplete="off" ref="search" @keydown="keydown"><span class="dashicons dashicons-search modal_panel_filter__search_button" @click="$emit('change', search)"></span></div>`,
    props:{
        placeholder:{
            type:String,
            required:false,
        default:
            ''
        },
    },
    data() {
        return {
            search:''
        }
    },
    methods:{
        keydown(e) {
            if (e.code === 'Enter') {
                e.preventDefault();
                this.$emit('change', this.search);
            }
        }
    }
})

Vue.component('select-position', {
    template:`<div class="select_position"><div class="align_row" v-for="items in positions"><div class="cell_selector" v-for="item in items" @click="$emit('change', item)" :class="{'active':item==selected}"></div></div></div>`,
    props:{
        selected:{
            type:String,
            required:false,
        default:
            'center center'
        },
    },
    data() {
        return {
            positions:[['top left', 'top center', 'top right'], ['center left', 'center center', 'center right'], ['bottom left', 'bottom center', 'bottom right']]
        }
    }
})

Vue.component('usam-checked', {
    template:`<div class="usam_checked"><div class="usam_checked__item usam_checked-active" :class="{'checked':v}" @click="v=!v; $emit('input', v);"><div class="usam_checked_enable"><label>{{text}}</label></div></div></div>`,
    props:{
        value:{
            type:[Number, Boolean],
            required:true,
        default:
            0
        },
        text:{
            type:String,
            required:false,
        default:
            ''
        },
    },
    watch:{
        value(v, old) {
            this.v = v;
        }
    },
    data() {
        return {
            v:this.value
        }
    }
})

Vue.component('shipped-document', {
    props:{
        doc:{
            type:Object,
            required:true,
        default:{}
        },
        units:{
            type:Object,
            required:true,
        default:{}
        },
        user_columns:{
            required:true,
        default:
            []
        },
        abilityChange:{
            type:Boolean,
        default:
            true
        },
        products:{
            type:Array,
        default:
            []
        },
        statuses:{
            type:Array,
        default:
            []
        },
        storages:{
            type:Object,
        default:{}
        },
        couriers:{
            type:Object,
        default:{}
        },
        delivery_problems:{
            type:Object,
        default:{}
        },
        delivery:{
            type:Array
        },

    },
    mixins:[shipped_document],
    data() {
        return {
            edit:false,
            data:this.doc,
            pLoaded:true,
            toggle:false,
        };
    },
    watch:{
        doc(v, old) {
            this.data = this.doc;
        }
    },
    methods:{
        copyOrderOrder() {
            this.data.products = [];
            for (let j in this.products)
                this.addProduct(j);
        },
        sidebar(type, k) {
            this.$parent.sidebar(type, k);
        }
    }
})

Vue.component('meta-seo', {
    props:{
        data:{
            type:Object,
            required:true,
        default:{
                title:'',
                description:'',
                opengraph_title:'',
                opengraph_description:'',
                shortcode:'',
                noindex:0
            }
        },
        name:{
            type:String,
            required:false,
        default:
            'meta'
        },
    },
    data() {
        return {
            meta:this.data
        };
    },
    watch:{
        data(val, oldVal) {
            this.meta = this.data;
        }
    },
    methods:{
        blur(k) {
            var str = this.$refs[k].innerHTML;
            str = str.replace(/<[^>]+>/g, '');
            Vue.set(this.meta, k, str);
        },
        insert(text, key) {
            var s = window.getSelection();
            if (typeof s.baseNode.innerHTML !== typeof undefined) {
                if (!s.baseNode.classList.contains("shortcode_editor"))
                    return;
            } else if (!s.baseNode.parentNode.classList.contains("shortcode_editor"))
                return;
            var range = s.getRangeAt(0);
            range.deleteContents();
            range.insertNode(document.createTextNode(' %' + key + '% '));
        },
    }
})

Vue.component('usam-document', {
    props:{
        doc:{
            type:Object,
            required:true,
        default:{}
        },
    },
    data() {
        return {
            data:this.doc,
            edit:false,
            toggle:false,
        };
    },
    watch:{
        doc(val, oldVal) {
            this.data = this.doc;
        }
    }
})

Vue.component('order-contractor', {
    props:{
        doc:{
            type:Object,
            required:true,
        default:{}
        },
        units:{
            type:Object,
            required:true,
        default:{}
        },
        user_columns:{
            required:true,
        default:
            []
        },
        abilityChange:{
            type:Boolean,
        default:
            true
        },
        products:{
            type:Array,
        default:
            []
        },
        statuses:{
            type:Array,
        default:
            []
        },
    },
    data() {
        return {
            edit:false,
            data:this.doc,
            pLoaded:true,
            toggle:false,
        };
    },
    watch:{
        doc(val, oldVal) {
            this.data = this.doc;
        }
    },
    methods:{
        orderProduct(i, key) {
            for (let j in this.products)
                if (this.data.products[i].product_id == this.products[j].product_id && this.data.products[i].unit_measure == this.products[j].unit_measure)
                    return this.products[j][key];
            return '';
        },
        addProduct(j) {
            var ok = false;
            for (let i in this.data.products)
                if (this.data.products[i].product_id == this.products[j].product_id && this.data.products[i].unit_measure == this.products[j].unit_measure) {
                    ok = true;
                    if (this.data.products[i].quantity < this.products[j].quantity) {
                        this.data.products[i].quantity++;
                    }
                    break;
                }
            if (!ok) {
                var p = structuredClone(this.products[j]);
                this.data.products.push(p);
            }
        },
        statusStyle(d, type) {
            var style = '';
            for (let k in this.statuses)
                if (d.status == this.statuses[k].internalname && this.statuses[k].type == type) {
                    style = this.statuses[k].color ? 'background:' + this.statuses[k].color + ';' :'' + this.statuses[k].text_color ? 'color:' + this.statuses[k].text_color + ';' :'';
                    break;
                }
            return style;
        },
        statusName(d, type) {
            var name = '';
            for (let k in this.statuses)
                if (d.status == this.statuses[k].internalname && this.statuses[k].type == type) {
                    name = this.statuses[k].name
                        break;
                }
            return name;
        },
        localDate(date, format) {
            return local_date(date, format);
        },
        addOrderSupplier(f) {
            usam_api(f, this.data, 'POST', (r) => usam_notifi({
                    'text':r ? 'Отправлено' :'Не отправлено'
                }));
        },
        del() {
            this.data.status = 'delete';
        },
        sendTracking() {
            usam_api('shipped/tracking/' + this.data.id, 'GET', (r) => usam_notifi({
                    'text':r ? 'Отправлено' :'Не отправлено'
                }));
        }
    }
})

Vue.component('table-products', {
    template:'#table-products',
    mixins:[data_filters, importer],
    props:{
        items:{
            type:Array,
            required:true,
        default:
            () => []
        },
        columns:{
            type:Object,
            required:true,
        default:
            () => {}
        },
        edit:{
        default:
            true
        },
        show_button:{
        default:
            false
        },
        abilityChange:{
        default:
            true
        },
        recalculate:{
            type:Boolean,
        default:
            true
        },
        ability_change:{
        default:
            true
        },
        loaded:{
            type:Boolean,
        default:
            true
        },
        table_name:{
            type:String,
            required:true,
        default:
            ''
        },
        column_names:{type:Object, required:true, default:() => {}},
        taxes:{
            type:Array,
            required:false,
        default:
            () => []
        },
        additionalcolumns:{
            required:false,
        default:
            null
        },
        query:{
            type:Object,
            required:false,
        default:
            () => ({
                add_fields:'small_image,sku,price,old_price,unit_measure,discounts'
            })
        },
        type_price:{
            type:String,
            required:false,
        default:
            ''
        },
    },
    data() {
        return {
            columns_tools:false,
            products:this.items,
            product_discounts:'',
            add_sum:'',
            tableShow:false,
            importData:false,
            searchItem:'',
            product_id:'',
            rounding:2,
            discount:0,
            subtotal:0,
            taxtotal:0,
            totalprice:0,
            unwatch:null,
            user_columns:this.additionalcolumns,
            product_taxes:this.taxes
        };
    },
    watch:{
        user_columns(val, oldVal) {
            usam_api('table/columns', {type:this.table_name, columns:this.user_columns}, 'POST');
        },
        taxes(val, oldVal) {
            this.product_taxes = this.taxes;
        },
        items:{
            handler(val, oldVal) {
                if (this.unwatch !== null)
                    this.unwatch();
                this.products = this.items;
                this.recountProducts();
                this.unwatch = this.$watch('products', this.changeProducts, {deep:true});

            },
            deep:true
        },
        type_price(val, oldVal) {
            if (val && oldVal !== '') {
                let ids = [];
                for (let k in this.products)
                    ids[k] = this.products[k].product_id;
                usam_api('products', {
                    post__in:ids,
                    status:['publish', 'draft'],
                    add_fields:['price', 'old_price'],
                    type_price:val,
                    count:1000
                }, 'POST', (r) => {
                    for (let k in r.items) {
                        for (let i in this.products) {
                            if (r.items[k].ID == this.products[i].product_id) {
                                this.products[i].price = r.items[k].price;
                                this.products[i].old_price = r.items[k].old_price > 0 ? r.items[k].old_price :r.items[k].price;
                            }
                        }
                    }
                });
            }
        }
    },
    computed:{
        tableColumns() {
            let cols = [];
            for (let k in this.columns) {
                cols.push({
                    id:k,
                    name:this.columns[k]
                });
                if (k == 'title') {
                    for (let i in this.user_columns)
                        cols.push({
                            id:i,
                            name:this.column_names[this.user_columns[i]]
                        });
                }
                if (k == 'discount_price') {
                    var ids = [];
                    for (let i in this.product_taxes) {
                        if (!ids.includes(this.product_taxes[i].tax_id))
                            cols.push({
                                id:'tax_' + this.product_taxes[i].tax_id,
                                name:this.product_taxes[i].name
                            });
                        ids.push(this.product_taxes[i].tax_id);
                    }
                }
            }
            return cols;
        },
        total_product_taxes() {
            var p = {};
            for (let i in this.product_taxes) {
                if (typeof p[this.product_taxes[i].tax_id] === typeof undefined)
                    p[this.product_taxes[i].tax_id] = {
                        tax:this.product_taxes[i].tax,
                        name:this.product_taxes[i].name,
                        is_in_price:this.product_taxes[i].is_in_price
                    };
                else
                    p[this.product_taxes[i].tax_id].tax += this.product_taxes[i].tax;
            }
            return p;
        },
    },
    mounted() {
        if (this.user_columns === null)
            usam_api('table/columns', {type:this.table_name}, 'GET', (r) => this.user_columns = r);
        this.recountProducts();
        this.unwatch = this.$watch('products', this.changeProducts, {
            deep:true
        });
    },
    methods:{
        changeProducts() {
            if (this.unwatch !== null)
                this.unwatch();
            this.recountProducts();
            this.$emit('change', this.products);
            this.unwatch = this.$watch('products', this.changeProducts, {
                deep:true
            });
        },
        delElement(e, k) {
            e.preventDefault();
            this.products.splice(k, 1);
        },
        deleteElements() {
            this.products = [];
        },
        recountProducts() {
            if (!this.recalculate)
                return;
            this.subtotal = 0;
            this.discount = 0;
            this.taxtotal = 0;
            let n = 0;
            let d = 0;
            let taxes = 0;
            let sum = 0;
            let p = {};
            let items = [];
            for (let k in this.products) {
                p = structuredClone(this.products[k]);
                if (typeof p.price !== typeof undefined && typeof p.quantity !== typeof undefined) {
                    if (typeof p.old_price !== typeof undefined) {
                        if (typeof p.discount_type === typeof undefined)
                            p.discount_type = 'p';
                        n = p.old_price;
                        if (typeof n == 'string') {
                            if (n === '')
                                n = 0;
                            else
                                n = parseFloat(n.replace(/\s/g, ''));
                            p.old_price = n;
                        }
                        if (typeof p.discount === typeof undefined || p.discount === '') {
                            p.discount = p.old_price > p.price ? 100 - p.price * 100 / p.old_price :0;
                            p.discount_type = 'p';
                        } else {
                            d = parseFloat(p.discount);
                            if (d > 0) {
                                if (p.discount_type == 'p') {
                                    if (d > 100) {
                                        d = 100;
                                        p.discount = 100;
                                    }
                                    n = p.old_price - p.old_price * d / 100;
                                } else
                                    n = p.old_price - d;
                            }
                            p.discount = d - d.toFixed(2) > 0 ? d.toFixed(2) :d;
                            p.price = n;
                        }
                        this.discount += (p.old_price - p.price) * p.quantity;
                    }
                    p.taxes = {};
                    taxes = 0;
                    for (let i in this.product_taxes) {
                        if (p.taxes[this.product_taxes[i].tax_id] === undefined)
                            p.taxes[this.product_taxes[i].tax_id] = {};
                        if (this.product_taxes[i].product_id == p.product_id && this.product_taxes[i].unit_measure == p.unit_measure) {
                            if (this.product_taxes[i].is_in_price)
                                n = p.price * this.product_taxes[i].rate / (100 + this.product_taxes[i].rate);
                            else {
                                n = p.price * this.product_taxes[i].rate / 100;
                                taxes += n;
                            }
                            p.taxes[this.product_taxes[i].tax_id] = {
                                tax:this.formatted_number(n),
                                is_in_price:this.product_taxes[i].is_in_price
                            };
                        }
                    }
                    p.total = (p.price + taxes) * p.quantity;
                    this.subtotal += (p.old_price + taxes) * p.quantity;
                    p.total = parseFloat(p.total.toFixed(this.rounding));
                    sum += p.total;
                    this.taxtotal += taxes;
                } else if (typeof p.price !== typeof undefined && typeof p.old_price !== typeof undefined)
                    p.discount = p.old_price > 0 ? 100 - p.price * 100 / p.old_price :0;
                items.push(p);
            }
            this.products = items;
            this.calculate_totalprice(sum);
            this.discount = this.discount.toFixed(this.rounding);
        },
        calculate_totalprice(sum) {
            this.totalprice = sum;
        },
        formatted_number(number, r) {
            if (number === undefined)
                return '';
            r = r === undefined ? this.rounding :r;
            if (typeof number == 'string')
                number = Number(number);
            return number.toFixed(r).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        },
        clearSearch() {
            this.product_id = 0;
            this.searchItem = '';
        },
        selectElement(e) {
            if (this.checkProduct(e.id)) {
                this.product_id = e.id;
                this.searchItem = e.name;
            } else
                this.clearSearch();
        },
        addElement(e) {
            if (this.product_id) {
                this.getElement(this.product_id);
                this.clearSearch();
            }
        },
        selectionProducts(id) {
            if (this.checkProduct(id))
                this.getElement(id);
        },
        getElement(id) {
            usam_api('product/' + id, this.query, 'GET', this.formattingElement);
        },
        checkProduct(id) {
            if (!id)
                return false;
            let ok = true;
            for (let k in this.products) {
                if (this.products[k].product_id == id) {
                    if (typeof this.products[k].quantity !== typeof undefined)
                        this.products[k].quantity++;
                    ok = false;
                    break;
                }
            }
            return ok;
        },
        chargePrice(val) {
            val = parseFloat(val);
            if (!isNaN(val)) {
                var o = 0,
                s = 0;
                for (let k in this.products) {
                    s = val * (this.products[k].old_price * 100 / this.totalprice) / 100 + o;
                    m = Math.round(s);
                    this.products[k].old_price += m;
                    o = s - m;
                }
                this.recountProducts();
            }
        },
        changeDiscount(val) {
            val = parseFloat(val);
            if (!isNaN(val)) {
                for (let k in this.products)
                    this.products[k].discount = val;
                this.recountProducts();
            }
        },
        addBonuses(val) {
            val = parseFloat(val);
            var total = this.subtotal - this.discount - this.taxtotal;
            if (!isNaN(val) && val < total) {
                var prozent = val * 100 / total;
                var o = 0,
                s = 0,
                d = 0;
                for (let k in this.products) {
                    s = prozent * this.products[k].price / 100 + o;
                    this.products[k].used_bonuses = Math.round(s);
                    s = this.products[k].price - s;
                    d = Math.round(s);
                    o = s - d;
                    this.products[k].discount = 100 - (d / this.products[k].old_price * 100);
                }
                this.recountProducts();
            }
        },
        startImport() {
            usam_active_loader();
            usam_api('importer/file/data', {
                file:this.file.name,
                file_settings:this.file_settings
            }, 'POST', (r) => {
                let property = false;
                let properties = {};
                let values = [];
                for (let i in this.value_name) {
                    if (!property && (this.value_name[i] == 'sku' || this.value_name[i] == 'barcode')) {
                        property = this.value_name[i];
                        for (let j in r) {
                            if (typeof r[j][i] == 'string')
                                r[j][i] = r[j][i].trim();
                            if (r[j][i]) {
                                values.push(r[j][i]);
                                properties[r[j][i]] = {}
                                for (let k in this.value_name) {
                                    if (this.value_name[k]) {
                                        if ((this.value_name[k] == 'price' || this.value_name[k] == 'discount') && r[j][k]) {
                                            r[j][k] = String(r[j][k]).replace('-', '');
                                            r[j][k] = parseFloat(r[j][k].replace(',', '.'));
                                        }
                                        properties[r[j][i]][this.value_name[k]] = r[j][k];
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
                if (property) {
                    var vars = {productmeta:[{key:property, value:values, compare:'IN'}]};
                    args = Object.assign(this.query, vars);
                    if (args.fields)
                        args.fields.push(property);
                    else
                        args.fields = [property];
                    this.getProducts(args, (r) => {
                        for (let i in r.items) {
                            if (this.checkProduct(r.items[i].ID)) {
                                r.items[i] = Object.assign(r.items[i], properties[r.items[i][property]]);
                                this.formattingElement(r.items[i]);
                            }
                        }
                    });
                }
            });
        },
        formattingElement(item) {
            if (item.price === undefined)
                item.price = 0;
            if (item.old_price === undefined)
                item.old_price = item.price;
            else if (item.old_price == 0)
                item.old_price = item.price;
            if (item.quantity === undefined)
                item.quantity = 1;
            item.product_id = item.ID;
            item.name = item.post_title;
            delete item.ID;
            item.id = '+' + this.products.length;
            this.pushElement(item);
        },
        pushElement(item) {
            this.products.push(item);
        },
        getProducts(vars, handler) {
            vars.count = 10000;
            vars.status = 'any';
            usam_api('products', vars, 'POST', handler);
        },
        addToDocument(item) {
            item.name = item.post_title;
            item.discount_type = 'p';
            item.discounts = [];
            if (item.bonus === undefined)
                item.bonus = 0;
            item.formatted_bonus = 0;
            if (item.discount === undefined) {
                item.discount = item.old_price > 0 ? 100 - item.price * 100 / item.old_price :0;
                item.formatted_discount = this.formatted_number(item.discount);
                item.discount = item.discount.toFixed(2);
            }
            var add = true;
            for (let i in this.products) {
                if (this.products[i].product_id == item.product_id && this.products[i].unit_measure == item.unit_measure) {
                    this.products[i].quantity += 1;
                    this.recountProducts();
                    add = false;
                    break;
                }
            }
            delete item.ID;
            item.old_price = item.old_price || item.price;
            if (add) {
                this.products.push(item);
                this.recountProducts();
            }
        },
        viewer(k) {
            product_viewer.product = {
                ID:this.products[k].product_id === undefined ? this.products[k].ID :this.products[k].product_id
            };
            product_viewer.init();
        }
    }
})

Vue.component('contact-path', {
    props:{
        contact:{
            type:Object,
            required:true,
        default:
            () => {}
        },
    },
    data() {
        return {
            data:{},
            visits:{},
            visits_count:0
        };
    },
    mounted() {
        var ob = new IntersectionObserver((entries, Observer) => {
            entries.forEach((e) => {
                if (e.isIntersecting) {
                    this.load();
                    ob.unobserve(e.target);
                }
            })
        }, {
            rootMargin:'0px 0px 100px 0px'
        });
        ob.observe(this.$el);
    },
    methods:{
        load() {
            usam_api('contact/' + this.contact.id, 'GET', (r) => this.data = r);
            var args = {contact_id:this.contact.id, count:5, orderby:'date_insert', order:'DESC'}
            let url = new URL(document.location.href);
            if ('order' == url.searchParams.get('form_name')) {
                args.meta_query = [{key:'order_id', value:form_data.id, compare:'=', type:'NUMERIC'}];
                args.contact_id = form_data.contact_id;
            }
            args.number = 100;
            usam_api('visits', args, 'POST', (r) => {
                this.visits = r.items;
                this.visits_count = r.count;
            });
        }
    }
})
Vue.component('send-email', {
    mixins:[files],
    props:{
        emails:{required:false, default:''},
        attachments:{type:Array,required:false, default:() => []},
        object_type:{type:String, required:false, default:''},
        object_id:{type:Number, required:false, default: 0}
    },
    data() {
        return {
            tab:'',
            to:'',
            from:'',
            subject:'',
            signatures:[],
            signature:'',
            message:'',
            mailboxes:[]
        };
    },
    watch:{
        emails() {
            if (Object.keys(this.emails).length)
                this.from = Object.keys(this.emails).pop();
        },
        attachments(v, old) {
            this.files = v;
        }
    },
    mounted() {
        usam_api('mailboxes', {fields:['id', 'name', 'email'], user_id:'my'}, 'POST', (r) => {
            if (r.count) {
                this.mailboxes = r.items
                    this.to = r.items[0].id;
            }
        });
        usam_api('signatures', {
            manager_id:'my',
            orderby:'mailbox_id',
            order:'DESC'
        }, 'POST', (r) => this.signatures = r.items);
    },
    methods:{
        addSignature(i) {
            var signature = '<br>' + this.signatures[i].signature;
            if (this.signature && this.message.includes(this.signature))
                this.message = this.message.replace(this.signature, signature);
            else
                this.message += signature;
            this.signature = signature;
        },
        send() {
            var files = [];
            for (i = 0; i < this.files.length; i++)
                files.push(this.files[i].id)
                usam_api('email/send', {mailbox_id:this.to, email:this.from,subject:this.subject, message:this.message, object_id:this.object_id, object_type:this.object_type, files:files}, 'POST', (r) => {
                    if (r) {
                        this.message = '';
                        this.subject = '';
                        this.files = [];
                        this.$emit('add', r);
                    } else
                        usam_notifi({
                            'text':'Не отправлено'
                        });
                });
        }
    }
})

Vue.component('send-sms', {
    props:{
        phones:{
            required:true,
        default:
            ''
        },
        object_type:{
            type:String,
            required:false,
        default:
            ''
        },
        object_id:{
            type:Number,
            required:true,
        default:
            0
        }
    },
    data() {
        return {
            from:'',
            message:''
        };
    },
    watch:{
        phones() {
            if (Object.keys(this.phones).length)
                this.from = Object.keys(this.phones).pop();
        }
    },
    methods:{
        send() {
            usam_api('sms/send', {
                phone:this.from,
                message:this.message,
                object_id:this.object_id,
                object_type:this.object_type
            }, 'POST', (r) => {
                if (r) {
                    this.message = '';
                    this.$emit('add', r);
                } else
                    usam_notifi({
                        'text':'Не отправлено'
                    });
            });
        }
    }
})

Vue.component('form-email', {
    mixins:[files],
    props:{
        element:{
            type:Object,
            required:true,
        default:
            () => {}
        },
    },
    data() {
        return {
            tab:'',
            data:this.element
        };
    },
    watch:{
        element() {
            this.data = this.element;
        }
    },
    mounted() {
        setTimeout(() => this.$refs['iframe'].height = this.$refs['iframe'].contentWindow.document.body.scrollHeight + 30, 100);
    },
    methods:{
        reply() {},
        forward() {},
        send() {},
        addContact() {},
        download_all() {
            usam_api('email/' + this.data.id + '/files/download', 'GET');
        },
        edit() {},
        update(data) {
            this.data = Object.assign(this.data, data);
            this.$emit('input', this.data);
            usam_api('email/' + this.data.id, data, 'POST');
        },
        del() {
            usam_api('email/' + this.data.id, 'DELETE');
            this.$emit('delete', this.data);
        }
    }
})

Vue.component('tinymce', {
    template:'<div class="tinymce"><textarea ref="tinymce" v-model="text"></textarea></div>',
    props:{
        value:{
            type:String,
            required:false,
        default:
            ''
        },
    },
    data() {
        return {
            text:this.value,
        };
    },
    watch:{
        value(v, old) {
            if (v !== this.text)
                tinyMCE.get(this.$refs['tinymce'].id).setContent(v);
        }
    },
    mounted() {
        this.initTinyMCE();
    },
    unmounted() {
        tinymce.get(this.$refs['tinymce'].id).remove();
    },
    methods:{
        initTinyMCE() {
            tinyMCE.init({
                themes:'modern',
                skin:'lightgray',
                remove_script_host:false,
                relative_urls:false,
                branding:false,
                statusbar:false,
                height:'200px',
				quicktags:{
					buttons:'strong,em,link,ul,ol,li,code'
				},
                target:this.$refs['tinymce'],
                plugins:'textcolor lists tabfocus paste wordpress link image',
                table_toolbar:"tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol",
                menubar:false,
                theme:"modern",
                toolbar:'fontsizeselect fontselect | formats blockformats fontformats fontsizes align | bold italic underline strikethrough superscript subscript | backcolor forecolor | numlist bullist | link image | undo redo | removeformat',
                setup:(editor) => {
                    editor.on('change', () => {
                        this.text = editor.getContent();
                        this.$emit('input', this.text);
                    });
                }
            });
        }
    }
})

Vue.component('phone-call', {
    template:'<div class="call_control_panel" v-show="call"><div class="call_control_panel__body"><div class="call_control_panel__phone call_control_panel__text">{{phone.display}}</div><div class="cancel_call icon" @click="cancel"></div><div class="call_control_panel__line"></div><div class="call_control_panel__message call_control_panel__text">{{data.message}}</div><div class="call_customer icon"></div></div></div>',
    props:{
        phone:{
            type:Object,
            required:true,
        default:{
                number:'',
                display:''
            }
        },
        object_type:{
            type:String,
            required:false,
        default:
            ''
        },
        object_id:{
            type:Number,
            required:false,
        default:
            0
        }
    },
    data() {
        return {
            data:{
                id:0
            },
            call:false
        };
    },
    watch:{
        phone(v, old) {
            if (v.number)
                this.start();
        }
    },
    methods:{
        start() {
            if (this.phone.number) {
                this.call = true;
                usam_api('phone/call', {
                    phone:this.phone.number,
                    object_type:this.object_type,
                    object_id:this.object_id
                }, 'GET', (r) => this.data = r);
            }
        },
        cancel() {
            if (this.data.id) {
                this.call = false;
                usam_api('phone/cancel', this.call, 'GET');
            }
        }
    }
})

Vue.component('lists-input', {
    props:{
        selected:{
            required:false,
        default:
            () => []
        },
        lists:{
            type:[Array, Object],
            required:true,
        default:
            () => []
        },
    },
    watch:{
        show(v, old) {
            if (v)
                setTimeout(() => document.addEventListener('click', this.close), 1);
            else
                document.removeEventListener('click', this.close, false);
        },
        select(v, old) {
            this.$emit('change', v);
        }
    },
    computed:{
        items() {
            var r = {}
            if (this.select.length)
                for (let i in this.lists)
                    if (this.select.includes(i))
                        r[i] = this.lists[i]
                            return r;
        },
    },
    template:'<div class="lists_input"><div class="lists_input__items"><div class="lists_input__item" v-for="(name, k) in items"><span>{{name}}</span><span class="lists_input__delete" @click="remove(k)"></span></div><span v-show="!show" @click="show=true" class="lists_input__add_item">Добавить</span></div><check-list v-show="show" :selected="select" :lists="lists" @change="select=$event" ref="checklist"/></div>',
    data() {
        return {
            show:false,
            select:this.selected
        }
    },
    methods:{
        remove(k) {
            this.select.splice(k, 1);
        },
        close(e) {
            if (e.target.closest('.checklist') || e.target.classList.contains("checklist"))
                return false;
            this.show = false;
            document.removeEventListener('click', this.close, false);
        },
    }
})

Vue.component('locations', {
    props:{
        value:{
            required:false,
        default:
            () => []
        },
    },
    template:'<div class="locations"><div class="locations__selected"><div class="locations__selected_item" v-for="item in selectedItems">{{item.name}}<div class="locations__selected_item_delete" @click="del(item)"></div></div></div><div class="locations__selection" v-show="items.length"><locations-lists :lists="tree"/></div></div>',
    pinia:pinia,
    data() {
        return {
            items:[],
            tree:[]
        }
    },
    computed:{        
        selectedItems() {
            return this.items.filter(x => store().location_ids.includes(x.id));
        },
    },
    mounted() {
        this.load();
    },
    methods:{
        load() {
			store().setLoctions( this.value );			
            usam_api('locations', {count:10000}, 'GET', (r) => {
                this.items = r.items
                this.tree = this.buildTree(0)
            })
        },
        del(item) {
            store().setLoctions( item.id );
			this.treeTraversal(item.id, this.tree)
        },	
		treeTraversal(id, t) {
            for(k in t) {
                if( id == t[k].id ) 
				{
                    t[k].checked = false;
					break;						
                }
				this.treeTraversal(id, t[k].children)
            }
        },		
        buildTree(parent) {
            var results = [];
            for (k in this.items) {
                if (parent == this.items[k].parent) {
                    let item = structuredClone(this.items[k]);
                    item.checked = this.value !== null && this.value.includes(item.id);
                    item.open = this.items[k].open !== undefined ? this.items[k].open :false
                    item.children = this.buildTree(item.id);
                    results.push(structuredClone(item));
                }
            }
            return results;
        }
    }
})

Vue.component('locations-lists', {
    props:{
        lists:{type:[Array, Object], required:true, default:() => []},
    },
    template:'<div class="locations_lists"><div class="locations_lists__item" v-for="item in lists"><span class="locations_lists__item_name"><label><input type="checkbox" v-model="item.checked" @input="save(item)">{{item.name}}</label><span v-if="item.children.length" :class="[item.open?`minus`:`plus`]" @click="item.open=!item.open"></span></span><locations-lists :lists="item.children" :key="item.id" v-if="item.open && item.children.length"/></div></div>',
    methods:{
        save(item) {
            store().setLoctions( item.id );
            item.checked = store().location_ids.includes(item.id);
        }
    }
})

Vue.component('diplay-object', {
    props:{
        item:{type:Object, required:true, default:() => []},
    },
})

Vue.component('properties', {
    template:'<div class="edit_form"><slot name="head" :lists="lists"/><div class ="edit_form__item" v-for="(property, k) in lists"><div class="edit_form__item_name">{{property.name}}</div><div class="edit_form__item_option"><slot name="body" :property="property" :getProperty="getProperty"/></div></div><slot name="footer" :lists="lists"/></div>',
	props:{
        lists:{type:Array, Object, required:true, default:() => []},
    },
	methods:{
		getProperty( code ) { 
			for (k in this.lists)
				if( this.lists[k].code==code )
					return this.lists[k];
			return {};
		},
	}
})


Vue.component('html-blocks', {
    props:{
        lists:{type:[Array, Object], required:true, default:() => []},
    },   
	data() {
        return {
            blocks:[], hooks:{},
        }
    },
	watch:{
        lists(v, old) {
            this.load();
        },
    },
	mounted() {
        this.hooks = hooks;	
		this.load();
    },
	methods:{
		load() { 
			for (k in this.lists)
				Vue.set(this.lists[k], 'sectionTab', 'options');
			this.blocks = this.lists;
		},
		allowDrop(e, k) { 
			e.preventDefault();
			if (this.oldIndex != k) {
				let v = structuredClone(this.blocks[this.oldIndex]);
				this.blocks.splice(this.oldIndex, 1);
				this.blocks.splice(k, 0, v);
				this.oldIndex = k;
			}
		},
		drag(e, k) {
			this.oldIndex = k;
			if (e.target.hasAttribute('draggable'))
				e.currentTarget.classList.add('draggable');
			else
				e.preventDefault();
		},
		dragEnd(e, i) {
			e.currentTarget.classList.remove('draggable');
			for (i = 0; i < this.blocks.length; i++)
				this.blocks[i].sort = i;
		},
	}
})

