<?php
namespace Home\Controller;
use Think\Controller;
class UserController extends Controller {

    public function user(){

    	$User = M('Wx_user');
		// 和用法 $User = new \Think\Model('User'); 等效
		// 执行其他的数据操作
		$data = $User->select();
		var_dump($data);exit;
        
    }


}