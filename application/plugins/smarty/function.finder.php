<?php
function smarty_function_finder($params)
{
    $CI = &get_instance();

    $model = $params['model'];
    $url   = $params['ctl'];
    $filter = $params['filter'] ? json_encode(array('filter'=>json_decode($params['filter'],true))) : '{}';
    $detailsCtl = $params['details'] === false ? "false" : "true";
    $serverSide = $params['serverSide'] === false ? false : true;

    $pos = strrpos($model, '/');
    $object_name = false !== $pos ? substr($model, $pos+1) : $model;

    $CI->load->model($model);
    $sechma = $CI->{$object_name}->get_sechma();
    $extra_sechma = $CI->{$object_name}->get_extra_sechma();
    
    $sechma['columns'] = $sechma['columns']+(array) $extra_sechma['columns'];

    $idSrc = $sechma['columns']['index']['pkey'];

    $tr = '<tr>'; if ($detailsCtl=="true") $tr.="<th></th>";
    foreach ($sechma['columns'] as $key => $value) {
        if ($value['findercol']) {
            $tr .= '<th class="dt-head-center">'.$value['label'].'</th>';
        }
    }

    $tr .= '<th class="dt-head-center">操作</th></tr>';

    $domid = 'finder-'.uniqid();

    $columns = json_encode($sechma['columns']);
    $_finder = <<<HTML
<table id="{$domid}" class="display" cellspacing="0" width="100%">
    <thead>{$tr}</thead>
    <tbody></tbody>
</table>

<script>
\$(function(){

    var editor;

    $.fn.dataTable.Editor.fieldTypes.tree = $.extend( true, {}, $.fn.dataTable.Editor.models.fieldType, {
        "create": function ( conf ) {
            var that = this;
     
            conf._enabled = true;

            // Create the elements to use for the input
            conf._input = \$(
    '<div class="input-group">' + 
      '<input type="text" class="form-control" placeholder="请选择..." disabled>' +
      '<input type="hidden" name="region">' +
      '<span class="input-group-btn">' +
        '<button class="btn btn-default tree-selector" type="button">请选择</button>' + 
      '</span>'+
    '</div>');
    \$('button.tree-selector', conf._input).click(function(){
        var modalDom = TTGY.modal.call(this,{show:false});

        modalDom.off('show.bs.modal').on('show.bs.modal',function(e){
            \$('.modal-body',modalDom).jstree({
              'core' : {
                   'data' : {
                       'url' : conf.treeurl,
                       'data' : function (node) {
                           return { id : node.id};
                       },
                       "dataType" : "json"
                   },
                   'multiple':false
                },
                'plugins':['types','state'],
                'types':{
                  'default':{'icon':false}
                },
                'state':{
                    'key':"regiontree-$domid"
                } 
            });

            modalDom.on('click','button.btn-primary',function(){
                var jstree_instance = \$('.modal-body',modalDom).jstree(true);

                var selectedNode = jstree_instance.get_selected(true);
                if (selectedNode.length == 0 || selectedNode[0].state.type != 'area') {return MessageBox.error('请选择省市区');};

                var region = {}; 
                region.label = selectedNode[0].text;
                region.value = selectedNode[0].id;

                $.each(selectedNode[0].parents,function(i,n){
                    if (n != '#') {
                        var node = jstree_instance.get_node(n);

                        region.label = node.text + '-' + region.label;
                        region.value = node.id + ',' + region.value;
                    };
                });
                that.set(conf.name,region);

                modalDom.modal('hide');
            });
        });
        modalDom.modal({
            backdrop:false
        });
    });
     return conf._input;

        },
     
        "get": function ( conf ) {
            return $('input[name="region"]', conf._input).attr('value');
        },
     
        "set": function ( conf, val ) {
            $('.form-control',conf._input).attr('value',val.label);
            $('input[name="region"]',conf._input).attr('value',val.value);
        }
    });
 

    var columns = $columns;
    var fields = [];

    $.each(columns,function(c,v){
        if (v.finderform) {
            var f = {
                label:v.label,
                name:c,
                type:v.formtype ? v.formtype : 'text',
                options:[]
            };

            if (v.default != undefined) f.def = v.default;
            if (v.formtype == 'tree') f.treeurl = v.treeurl;

            if (v.formtype == 'radio' || v.formtype == 'select') {
                $.each(v.options,function(value,label){
                    f.options.push({
                        label:label,
                        value:value
                    });
                });
            };
            fields.push(f);
        };
    });

    editor = new \$.fn.dataTable.Editor( {
        ajaxUrl:"{$url}/finder",
        ajax: {
            create:{
                type:"POST",
                url:"{$url}/save"
            },
            remove:{
                type:"POST",
                url:"{$url}/remove"
            },
            edit:{
                type:"POST",
                url:"{$url}/save"
            }
        },
        table: "#{$domid}",
        fields: fields,
        i18n:{
            create:{
                title:"新增记录",
                submit:"保存"
            }
        }
    });

    $.extend( $.fn.DataTable.ext.classes, {
        "sPageButton":""
    });

    var options = {
        "dom":'<"toolbar"T><"top"f>rt<"bottom"ilp><"clear">',
        "processing": true,
        "serverSide": "$serverSide",
        "deferRender": true,
        "lengthChange":false,
        "ajax":{
            "url": "{$url}/finder",
            "type": "POST"
        },
        "rowCallback":function(row,data){
            \$(row).find('button.editor_remove').on('click',function(e){
                e.preventDefault();
                editor
                    .title( '删除记录' )
                    .message( "你确定删除该记录吗?" )
                    .buttons( { "label": "确定", "fn": function () { editor.submit() } } )
                    .remove( \$(this).closest('tr') );
            });

            \$(row).find('button.editor_edit').on('click',function(e){
               e.preventDefault();
         
                editor
                    .title( '编辑' )
                    .buttons( { "label": "保存", "fn": function () { editor.submit() } } )
                    .edit( \$(this).closest('tr') );  
            });
            
            if (\$(row).find('.details-control').length)
            \$(row).find('.details-control').on('click',function(e){
                var tr = \$(this).closest('tr');
                var row = table.row( tr );

                if ( row.child.isShown() ) {
                    // This row is already open - close it
                    row.child.hide();
                    tr.removeClass('details');
                }
                else {
                    // Open this row
                    $.ajax({
                        url:"{$url}/details",
                        type:'POST',
                        data:{
                            id:data.DT_RowId
                        },
                        success:function(resp){
                            row.child( resp ).show();
                        }
                    });
                    
                    tr.addClass('details');
                }
            });
        },
        "columns":[],
        "language":{
            "url":'/assets/vendors/datatables/plugins/i18n/Chinese.lang'
        },
        tableTools:{
            sRowSelect: "os",
            aButtons: [
                { 
                    sExtends: "editor_create",
                    sButtonClass:"btn btn-success btn-sm", 
                    sButtonText:"添加",
                    editor: editor
                }
            ]
        }
    };
    
    if("{$detailsCtl}" == "true")
        options.columns.push({
                searchable:     false,
                orderable:     false,
                data:           null,
                class:"details-control",
                defaultContent:""
        });

    $.each(columns, function(c,v){
        if (v.findercol) {
            var p = {
                name:c,
                data:c.dname ? c.dname : c,
                searchable:v.searchable ? v.searchable : false,
                orderable:false,
                class:"dt-body-center",
                defaultContent:""
            };

           if (v.formtype == "select" || v.formtype == "radio") {
                p.render = function(val, type, row){
                    var op = v.options;
                    return op[val];
                };
            };
            options.columns.push(p);
        };
    })

    options.columns.push({
        searchable:     false,
        orderable:     false,
        data:           null,
        class:"dt-body-center",
        defaultContent: '<button class="editor_edit btn btn-info btn-sm">编辑</button> <button class="editor_remove btn btn-danger btn-sm">删除</button>'
    });

    var table = \$("#{$domid}").DataTable(options);

});
</script>
HTML;

    return $_finder;
}
