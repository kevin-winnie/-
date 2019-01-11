<?php $this->load->view('include/header'); ?>
<div class="container" style="margin-top:100px">
        <?php if(!empty($tips)){ ?>
        <div class="alert alert-danger" role="alert"><?php echo $tips;?></div>
        <?php } ?>
        <div>
    <form action="<?php echo base_url('admin/login')?>" method="post" role="form" class="well form-horizontal" style="width:500px;margin:0px auto;">
                <h3>代理商管理系统</h3>
                <div class="form-group">
                          <label for="name" class="col-sm-2 control-label">用户：</label>
                          <div class="col-sm-5">
                                 <input type="text" class="form-control" autocomplete="off" id="name" name="name" placeholder="请输入用户名">
                          </div>
                </div>
                <div class="form-group">
                          <label for="name" class="col-sm-2 control-label">密码：</label>
                          <div class="col-sm-5">
                                 <input type="password" class="form-control" id="pwd" name="pwd" placeholder="请输入密码">
                          </div>
                </div>
                <div class="form-group">
                          <div class="col-sm-offset-2 col-sm-10">
                                 <input type="hidden" name="submit" value="1">
                                 <button type="submit" class="btn btn-primary">Login</button>
                          </div>
                   </div>
        </form>
        </div>
 </div>



    
        <div style="margin-left: 43%;margin-top:20px;">
            <div class="span4 center" style="float:left;">
                <a target="_download_bowser" href="http://www.google.cn/intl/zh-CN/chrome/browser/"><img style="width:50px;height:50px;" src="/assets/image/chrome.png"></a>
            </div>
            <div class="span4 center" style="float:left;margin-left:15px;">
                <a target="_download_bowser" href="http://firefox.com.cn/download/"><img style="width:50px;height:50px;" src="/assets/image/firefox.png"></a>
            </div>
            <div class="span4 center" style="float:left;margin-left:15px;">
                <a target="_download_bowser" href="http://www.apple.com.cn/safari/"><img style="width:50px;height:50px;" src="/assets/image/safari.png"></a>
            </div>
        </div>
    


<?php $this->load->view('include/footer'); ?>
