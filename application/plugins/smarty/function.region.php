<?php
function smarty_function_region($params)
{
    $CI = &get_instance();

    $province_id = $params['province_id'];
    $city_id = $params['city_id'];
    $area_id = $params['area_id'];

    $CI->load->model('region_model');
    $regions = $CI->region_model->getSonRegions(0);

    $province = '<option value=0>请选择</option>';
    foreach ($regions as $region) {
        if ($region['id'] == $province_id){
            $province .= "<option value={$region['id']} selected>{$region['name']}</option>";
        } else {
            $province .= "<option value={$region['id']}>{$region['name']}</option>";
        }
    }

    $city = '<option value=0>请选择</option>';
    if ($city_id) {
        $regions = $CI->region_model->getSonRegions($province_id);

        foreach ($regions as $region) {
            if ($region['id'] == $city_id) {
                $city .= "<option value={$region['id']} selected>{$region['name']}</option>";
            } else {
                $city .= "<option value={$region['id']}>{$region['name']}</option>";
            }
        }
    }

    $area = '<option value=0>请选择</option>';
    if ($area_id) {
        $regions = $CI->region_model->getSonRegions($city_id);

        foreach ($regions as $region) {
            if ($region['id'] == $area_id) {
                $area .= "<option value={$region['id']} selected>{$region['name']}</option>";
            } else {
                $area .= "<option value={$region['id']}>{$region['name']}</option>";
            }
        }
    }

    $domid = 'region-'.uniqid();

    $_region = <<<HTML

<div class="row" id="{$domid}">
    <div class="col-lg-3">
    <select class="form-control " name="province_id">
    {$province}
    </select> 
    </div>
    <div class="col-lg-3">
    <select class="form-control " name="city_id">
    {$city}
    </select> 
    </div>
    <div class="col-lg-3">
    <select class="form-control " name="area_id">
    {$area}
    </select> 
    </div>
</div>
<script type="text/javascript">
$(function(){
    $("#{$domid}").on("change","select",function(){
        var value = $(this).val();
        var name = $(this).attr('name');

        if (name == 'area_id') {return;};

        var options = '<option value="0">请选择</option>';
        if (value) {
            $.ajax('/region/regionTreeData?id='+value,{
                dataType:'json',
                type:'POST',
                async:false,
                success:function(resp){
                    $.each(resp,function(i,r){
                        options += '<option value='+r.id+'>'+r.text+'</option>';
                    });
                }
            });
        };

        if (name == 'province_id') {
            $("#{$domid}").find('select[name="city_id"]').html('<option value=0>请选择</option>');
             $("#{$domid}").find('select[name="area_id"]').html('<option value=0>请选择</option>');

            $("#{$domid}").find('select[name="city_id"]').html(options);
        }else if (name == 'city_id') {
            $("#{$domid}").find('select[name="area_id"]').html(options);
        }
    })
});
</script>
HTML;
    return $_region;
}
