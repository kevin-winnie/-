<?php
function smarty_function_o2oOrderfinder($params)
{   
    $CI = &get_instance();
    /*获取数据start*/
    $model = $params['model'];
    $url   = $params['ctl'];
    $filter = $params['filter'] ? json_encode(array('filter'=>json_decode($params['filter'],true))) : '{}';
    $detailsCtl = $params['details'] === false ? "false" : "true";
    $serverSide = $params['serverSide'] === false ? false : true;
    $lineTool = $params['lineTool'] === true ? "true" : "false";
    $displayLength = isset($params['displayLength']) ? $params['displayLength']:10;

    $pos = strrpos($model, '/');
    $object_name = false !== $pos ? substr($model, $pos+1) : $model;

    $CI->load->model($model);
    $sechma = $CI->{$object_name}->get_sechma();
    $extra_sechma = $CI->{$object_name}->get_extra_sechma();
    $sechma['columns'] = $sechma['columns']+(array) $extra_sechma['columns'];
    /*获取数据end*/

    //主键
    $idSrc = $sechma['columns']['index']['pkey'];

    /*title start*/
    $tr = '<tr>';
    $tr .= '<th class="dt-head-center"><input type="checkbox" id="checkAll"></th>';
    if ($detailsCtl=="true") $tr.="<th></th>";
    $i = 1;
    $filter_search_option = array();
    foreach ($sechma['columns'] as $key => $value) {
        if ($value['findercol']) {
            $tr .= '<th class="dt-head-center">'.$value['label'].'</th>';
        }
        if($value['selectfilter']){
            $filter_arr[] = $i;
            $filter_search_option[$i] = $value['options'];
        }
        $i++;
    }
    $filter_arr = json_encode($filter_arr);
    $filter_search_option = json_encode($filter_search_option);
    if($lineTool=="true")
    $tr .= '<th class="dt-head-center">操作</th></tr>';
    /*title end*/

    /*body start*/
    $domid = 'finder-'.uniqid();
    $columns = json_encode($sechma['columns']);
    $_finder = <<<HTML
<table id="{$domid}" class="display" cellspacing="0" width="100%">
    <thead>{$tr}</thead>
    <tfoot>{$tr}</tfoot>
    <tbody></tbody>
</table>

<script>
\$(function(){

    var editor;

    // $.fn.dataTable.Editor.fieldTypes.tree = $.extend( true, {}, $.fn.dataTable.Editor.models.fieldType, {
  
     
       
    // });
 

    var columns = $columns;
    var fields = [];

    /*title处理start*/
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
    /*title处理end*/

    /*editor处理start*/
    // editor = new \$.fn.dataTable.Editor( {
    //     ajaxUrl:"{$url}/finder",
    //     ajax: {
    //         create:{
    //             type:"POST",
    //             url:"{$url}/save"
    //         },
    //         remove:{
    //             type:"POST",
    //             url:"{$url}/remove"
    //         },
    //         edit:{
    //             type:"POST",
    //             url:"{$url}/save"
    //         }
    //     },
    //     table: "#{$domid}",
    //     fields: fields,
    //     i18n:{
    //         create:{
    //             title:"新增记录",
    //             submit:"保存"
    //         }
    //     }
    // });
    /*editor处理end*/

    /*翻页样式处理start*/
    $.extend( $.fn.DataTable.ext.classes, {
        "sPageButton":""
    });
    /*翻页样式处理end*/


    var options = {
        "dom":'<"toolbar"T><"top"f>rt<"bottom"ilp><"clear">',
        "processing": true,
        "serverSide": "$serverSide",
        "deferRender": true,
        "lengthChange":true,
        "lengthMenu":[10,20,30,40,50],
        "displayLength":"$displayLength",
        "ordering": false,
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
            
            /*展开详情start*/
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
            /*展开详情end*/


            //多选框事件
            if(\$("#allmap").length)
            \$(row).find('.checkList').on('click',function(e){
              addMark($(this).attr('longitude'),$(this).attr('latitude'),$(this).val());
              setSendNum();
            });
        },
        "columns":[],
        "language":{
            "url":'/assets/vendors/datatables/plugins/i18n/Chinese.lang'
        },
        tableTools:{
            sRowSelect: "os",
            aButtons: false
        },
        initComplete: function () {
            var api = this.api();
            var filter_arr = eval("("+"{$filter_arr}"+")");
            var filter_search_option = {$filter_search_option};
            api.columns().indexes().flatten().each( function ( i ) {
                var column = api.column( i );
                if ($.inArray(i, filter_arr)!=-1){
                    var select = $('<select id="foot_select_'+i+'" ><option value=""></option></select>')
                        .appendTo( $(column.footer()).empty() )
                        .on( 'change', function () {
                            var val = $.fn.dataTable.util.escapeRegex(
                                $(this).val()
                            );

                            // column.search( val ? '^'+val+'$' : '', true, false ).draw();
                            column.search( val ? val : '', true, false ).draw();
                        } );

                    var filter_search_option_obj = filter_search_option[i];
                    $.each(filter_search_option_obj, function(d,j) {     
                        select.append( '<option value="'+d+'">'+j+'</option>' )  
                    });  
                    // column.data().unique().sort().each( function ( d, j ) {
                    //     select.append( '<option value="'+d+'">'+filter_search_option[i][d]+'</option>' )
                    // } );
                }
            } );
        }
    };

    /*多选框start*/
        options.columns.push({
                "mDataProp": "id",
                class:"dt-body-center",
                "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
                    $(nTd).html("<input class='checkList' type='checkbox' name='checkList' value='" + sData + "' longitude='" + oData.longitude + "' latitude='" + oData.latitude + "'>");
 
                }
            });
    /*多选框end*/

    /*添加详情按钮start*/
    if("{$detailsCtl}" == "true")
        options.columns.push({
                searchable:     false,
                orderable:     false,
                data:           null,
                class:"details-control",
                defaultContent:""
        });
    /*添加详情按钮end*/

    

    /*列body start*/
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
    /*列body end*/

    /*行操作start*/
    if("{$lineTool}" == "true")
    options.columns.push({
        searchable:     false,
        orderable:     false,
        data:           null,
        class:"dt-body-center",
        defaultContent: '<button class="editor_edit btn btn-info btn-sm">完成</button>'
    });
    /*行操作end*/


    var table = \$("#{$domid}").DataTable(options);

    /*checkbox全选start*/
    $("#checkAll").on("click", function () {
        if ($(this).prop("checked") === true) {
            $("input[name='checkList']").prop("checked", $(this).prop("checked"));
            var checkList_obj = $("input[name='checkList']");
            var checkList_length = $("input[name='checkList']").length;
            for (var i = 0; i < checkList_length; i++) {
                addMark($(checkList_obj[i]).attr('longitude'),$(checkList_obj[i]).attr('latitude'),$(checkList_obj[i]).val());
            }
        } else {
            $("input[name='checkList']").prop("checked", false);
        }
        setSendNum();
    });
    /*checkbox全选end*/
});
</script>
HTML;
    return $_finder;
}
