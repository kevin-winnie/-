/**
* 全选
* 
* items 复选框的name
*/
function allCkb(items){
	$('[name='+items+']:checkbox').prop("checked", true);
}

/**
* 全不选
* 
*/
function unAllCkb(){
	$('[type=checkbox]:checkbox').prop('checked', false);
}

function loadind(){
	$('.loading-bg').css({'display':'block'});
       $('.loading-content').css({'display':'block'});
}
function unloading(){
      $('.loading-bg').css({'display':'none'});
       $('.loading-content').css({'display':'none'});
}

var MessageBox = {};
MessageBox.error = function(){
    var msg = (typeof(arguments[0]) != 'undefined') ? arguments[0] : '操作失败';
    var layout = (typeof(arguments[1]) != 'undefined') ? arguments[1] : 'center';

    var n = noty({
      text        : msg,
      type        : 'error',
      layout      : layout,
      maxVisible  : 1,
      killer      : true,
      animation   : {
          open  : 'animated flipInX',
          close : 'animated flipOutX',
          easing: 'swing',
          speed : 500
      },
      timeout     : 3000,
    });
};


MessageBox.success = function(){
    var msg = (typeof(arguments[0]) != 'undefined') ? arguments[0] : '操作成功';
    var layout = (typeof(arguments[1]) != 'undefined') ? arguments[1] : 'center';

    var n = noty({
      text        : msg,
      type        : 'success',
      layout      : layout,
      maxVisible  : 1,
      killer      : true,
      animation   : {
          open  : 'animated flipInX',
          close : 'animated flipOutX',
          easing: 'swing',
          speed : 500
      },
      timeout     : 3000,
    });
};

MessageBox.alert = function(){
   var msg = (typeof(arguments[0]) != 'undefined') ? arguments[0] : '操作成功';
    var layout = (typeof(arguments[1]) != 'undefined') ? arguments[1] : 'center';

    var n = noty({
      text        : msg,
      type        : 'alert',
      layout      : layout,
      maxVisible  : 1,
      killer      : true,
      animation   : {
          open  : 'animated flipInX',
          close : 'animated flipOutX',
          easing: 'swing',
          speed : 500
      },
    });
};

MessageBox.warning = function(){
    var msg = (typeof(arguments[0]) != 'undefined') ? arguments[0] : '操作成功';
    var layout = (typeof(arguments[1]) != 'undefined') ? arguments[1] : 'center';

    var n = noty({
      text        : msg,
      type        : 'warning',
      layout      : layout,
      maxVisible  : 1,
      killer      : true,
      animation   : {
          open  : 'animated flipInX',
          close : 'animated flipOutX',
          easing: 'swing',
          speed : 500
      },
      timeout     : 3000,
    });
};

MessageBox.notification=function(){

};

MessageBox.information=function(){

};

var TTGY = {};

TTGY.DEFAULTS = {
  modal:{
    count:0
  }
};

TTGY.modal = function(option){
   var $this = $(this);
   var id = $this.data('ttgy.modal');
   if (!id) {
    TTGY.DEFAULTS.modal.count++;
    var id  = 'custom-modal-'+TTGY.DEFAULTS.modal.count;
    var tmpl = '<div class="modal fade" id="'+id+'">' +
                  '<div class="modal-dialog">' +
                    '<div class="modal-content">' +
                      '<div class="modal-header">' +
                        '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<h4 class="modal-title"></h4>' +
                      '</div>' +
                      '<div class="modal-body"></div>' +
                      '<div class="modal-footer">' +
                      '<button type="button" class="btn btn-primary">保存</button>' +
                      '<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>' +
                    '</div>' +
                  '</div>' +
                '</div>' +
              '</div>';
    $(document.body).append(tmpl);
    $this.data('ttgy.modal',id);
  };

  if (option.show != false)  $('#'+id).modal(option);

  return $('#'+id);
}

/*左侧菜单栏隐藏*/
$(function(){
  $(".sidebar-toggle").click(function(){
      if ($('.sidebar').hasClass('sidehide')) {
        $('.sidebar').show('fast',function(){
           $('.main').removeClass('col-md-12').addClass('col-md-10 col-md-offset-2');
           $(this).removeClass('sidehide');
        });
      } else {
        $('.sidebar').hide('fast',function(){
          $('.main').removeClass('col-md-10 col-md-offset-2').addClass('col-md-12');
          $(this).addClass('sidehide');
        });
      }
  });
});



$(function(){
	//省市区联动查询
	if ($('#select_province').length > 0){
		//获取省份
		$.post("/regions/search",{pid:0},function(respData){
            if(respData.status=='success'){
            	if (respData.region_list){
            		var province_list = '<option value="-1">省份</option>';
            		$(respData.region_list).each(function(i,val){
            			province_list = province_list + '<option value="'+val.AREAIDS+'">'+val.AREANAME+'</option>';
            		});
            		$('#select_province').html(province_list);
            		$('#select_province').change(function(){
            			$('#select_area').html('<option value="-1">地区</option>');
            			$.post("/regions/search",{pid:$(this).val()},function(respData){
				            if(respData.status=='success'){
				            	if (respData.region_list){
				            		var city_list = '<option value="-1">市区</option>';
				            		$(respData.region_list).each(function(i,val){
				            			city_list = city_list + '<option value="'+val.AREAIDS+'">'+val.AREANAME+'</option>';
				            		});
				            		$('#select_city').html(city_list);
				            		$('#select_city').change(function(){
				            			$.post("/regions/search",{pid:$(this).val()},function(respData){
								            if(respData.status=='success'){
								            	if (respData.region_list){
								            		var area_list = '<option value="-1">地区</option>';
								            		$(respData.region_list).each(function(i,val){
								            			area_list = area_list + '<option value="'+val.AREAIDS+'">'+val.AREANAME+'</option>';
								            		});
								            		$('#select_area').html(area_list);
								            	}
								            }
								        },'json');
				            		})
				            	}
				            }
				        },'json');
            		})
            	}
            }
        },'json');
	}
	
	//商品ajax搜索
	if ($('.search_product').length > 0){
		$('.search_product').attr('autocomplete','off');
		if ($('#product_ul').length == 0){
			$('<div id="product_ul"></div>').insertAfter('.search_product');
		}
		$('.search_product').keyup(function(){
			var search_val = $(this).val();
			if(!search_val){
	            $("#product_ul").html('');
	            return;
	        }
	        $.ajax({
	            type: "post",
	            url: "/products/ajax_search_proudct_by_name",
	            data: {
	                name:search_val
	            },
	            success: function (data, status) {
	                data = JSON.parse(data);
	                if(data != ""){
	                    var str = '<ul class="list-group">';
	                    for(var i=0;i<data.length;i++){
	                        str +='<li pid='+data[i]['id']+' onclick="sel_product('+data[i]['id']+')" class="search_product_li list-group-item" >'+data[i]['product_name']+'，价格：￥'+data[i]['price']+'【id='+data[i]['id']+'】</li>';
	                    }
	                    str += '</ul>';
	                    $("#product_ul").html(str);
	                }else{
	                    $("#product_ul").html('');
	                }
	            },
	            error: function () {
	                $("#product_ul").html('');
	            },
	            complete: function () {
	
	            }
	        });
		});
	}
});

function showSearchProduct(){
	$('.search_product').attr('autocomplete','off');
	if ($('#product_ul').length == 0){
		$('<div id="product_ul"></div>').insertAfter('.search_product');
	}
	$('.search_product').keyup(function(){
		var search_val = $('.search_product').val();
		if(!search_val){
            $("#product_ul").html('');
            return;
        }
        $.ajax({
            type: "post",
            url: "/products/ajax_search_proudct_by_name",
            data: {
                name:search_val
            },
            success: function (data, status) {
                data = JSON.parse(data);
                if(data != ""){
                    var str = '<ul class="list-group">';
                    for(var i=0;i<data.length;i++){
                        str +='<li pid='+data[i]['id']+' onclick="sel_product('+data[i]['id']+',\''+data[i]['product_name']+'\')" class="search_product_li list-group-item" >'+data[i]['product_name']+'，价格：￥'+data[i]['price']+'【id='+data[i]['id']+'】</li>';
                    }
                    str += '</ul>';
                    $("#product_ul").html(str);
                }else{
                    $("#product_ul").html('');
                }
            },
            error: function () {
                $("#product_ul").html('');
            },
            complete: function () {

            }
        });
	});
}

function sel_product(id,name) {
    $(".search_product").val(id);
    $('#product_ul').html('');
    if (name){
    	$('#search_product_name').html(name);
    }
}



