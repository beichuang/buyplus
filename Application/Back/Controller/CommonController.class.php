<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/5
 * Time: 11:08
 */
namespace Back\Controller;
use Org\Util\Rbac;
use Think\Controller;

class CommonController extends Controller
{
//    public function _initialize()
//    {
//        C('RBAC_ROLE_TABLE', C('DB_PREFIX') . C('RBAC_ROLE_TABLE'));
//        C('RBAC_USER_TABLE', C('DB_PREFIX') . C('RBAC_USER_TABLE'));
//        C('RBAC_ACCESS_TABLE', C('DB_PREFIX') . C('RBAC_ACCESS_TABLE'));
//        C('RBAC_NODE_TABLE', C('DB_PREFIX') . C('RBAC_NODE_TABLE'));
//
//        //检验登录
//        Rbac::checkLogin();
//        //检验权限
//        if( ! Rbac::AccessDecision()){
//            $this->error('没有权限',U('Admin/login'));
//        }
//        $this->assign('', Rbac::getAccessList(session(C('USER_AUTH_KEY'))));
//    }
}