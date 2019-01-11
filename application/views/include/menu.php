<!--nav class="navbar navbar-default navbar-fixed-top" role="navigation">
   <div class="container-fluid">
      <div class="navbar-header">
         <a class="navbar-brand" href="/">Fruitday</a>
      </div>
      <div>
         <ul class="nav navbar-nav">
   		 <?php foreach($menuArr as $key=>$val){ ?>
            <li class="dropdown">
               <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" style="padding-left:20px;">
                  <?php echo $val['name']; ?> 
                  <b class="caret"></b>
               </a>
               <ul class="dropdown-menu">
   				<?php foreach($val['child'] as $v){ ?>
   				<li><a href="<?php echo $v['url'];?>"><?php echo $v['name'];?></a></li>
   				<?php } ?>
               </ul>
            </li>
   		 <?php } ?>
         </ul>
      </div>
      <div>
         <p class="navbar-text navbar-right" style="padding:0 10px 0 0;">
            hello,<?php echo $adminname;?><a href="/admin/logout"> 退出</a>
         </p>
      </div>
   </div>
</nav-->
<div class="container-fluid">
