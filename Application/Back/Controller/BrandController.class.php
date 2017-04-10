<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/28
 * Time: 13:37
 */
namespace Back\Controller;

use Think\Controller;
use Think\Image;
use Think\Page;
use Think\Upload;

class BrandController extends Controller
{
    //添加
    public function addAction()
    {
        if(IS_POST){
            $model = D('Brand');
            if($model->create()) {
                // 完成文件上传
                $toolUpload = new Upload();// use Think\Upload;
                $toolUpload->exts = ['png', 'jpeg', 'jpg', 'gif'];// 允许类型
                $toolUpload->maxSize = 1 * 1024 * 1024;// 1M
                $toolUpload->rootPath = APP_PATH . 'Upload/';// 上传的根目录
                $toolUpload->savePath = 'Brand/';
                // 执行上传
                $uploadInfo = $toolUpload->uploadOne($_FILES['logo']);
                if ($uploadInfo) {
                    // 上传成功
                    $model->logo = $uploadInfo['savepath'] . $uploadInfo['savename'];//Brand/2016-12-23/585c82bd6245c.png
                    // 处理好的图像, 移动到Public目录
                    $toolImage = new Image();
//                    创建public/Thumb/日期
                    if (! is_dir ('./Public/Thumb/' . $uploadInfo['savepath'])) {
                        mkdir ('./Public/Thumb/' . $uploadInfo['savepath'], 0764, true);
                    }
                    $toolImage
                        ->open(APP_PATH . 'Upload/' . $model->logo)
                        // 出现对图像的操作(裁剪, 缩略图, 水纹)
                        ->save('./Public/Thumb/' . $model->logo);
                } else {
                    // 上传失败, 考虑是否需要处理
                }
                $model->add();
                $this->redirect('list');
            }else{
                //错误信息存在session中
                session('message',['error'=>1, 'errorInfo'=>$model->getError()]);
                session('data',$_POST);
                $this->redirect('add');
            }
        }else{
            //错误信息展示
            $this->assign('message', session('message'));
            session('message',null);
            $this->assign('data',session('data'));
            session('data',null);
            $this->display('set');
        }
    }
    public function editAction()
    {
        $model = D('Brand');
        if(IS_POST){
            if($model->create()){
                // 完成文件上传
                $toolUpload = new Upload();// use Think\Upload;
                $toolUpload->exts = ['png', 'jpeg', 'jpg', 'gif'];// 允许类型
                $toolUpload->maxSize = 1 * 1024 * 1024;// 1M
                $toolUpload->rootPath = APP_PATH . 'Upload/';// 上传的根目录
                $toolUpload->savePath = 'Brand/';
                // 执行上传
                $uploadInfo = $toolUpload->uploadOne($_FILES['logo']);
                if ($uploadInfo) {
                    // 上传成功, 新logo替换旧logo
                    $model->logo = $uploadInfo['savepath'] . $uploadInfo['savename'];//Brand/2016-12-23/585c82bd6245c.png
                    // 处理好的图像, 移动到Public目录
                    $toolImage = new Image();
//                    创建public/Thumb/日期
                    if (! is_dir ('./Public/Thumb/' . $uploadInfo['savepath'])) {
                        mkdir ('./Public/Thumb/' . $uploadInfo['savepath'], 0764, true);
                    }
                    $toolImage
                        ->open(APP_PATH . 'Upload/' . $model->logo)
                        // 出现对图像的操作(裁剪, 缩略图, 水纹)
                        ->save('./Public/Thumb/' . $model->logo);

                    // 删除旧文件
                    // 找到旧的
                    $oldLogo = $model->where(['brand_id'=>I('post.brand_id')])->getField('logo');
                    @unlink(APP_PATH . 'Upload/' . $oldLogo);
                    @unlink('./Public/Thumb/' . $oldLogo);

                } else {
                    // 上传失败, 考虑是否需要处理
                }

                $model->save();
                $this->redirect('list');
            }else{
                //错误信息存入session中
                session('message', ['error'=>1, 'errorInfo'=>$model->getError()]);
                session('data', $_POST);
                $this->redirect('edit', ['brand_id'=>I('post.brand_id')]);
            }
        }else{
            $this->assign('message', session('message'));
            session('message',null);
            $this->assign('data', is_null(session('data')) ? $model->find(I('get.brand_id')):session('data'));
            session('data',null);
            $this->display('set');
        }
    }
    //列表
    public function listAction()
    {
        $model = M('Brand');
        // 一: 查询条件
        $cond = [];// 初始化查询条件
        $filter = []; // 初始化一个用于记录查询查询的数组, 分配到视图模板中
        if(null !== $title=I('get.filter_title', null, 'trim')) {
            // 在请求数据中存在filter_title, 需要设置条件
            $cond['title'] = ['like', $title . '%'];
            $filter['filter_title'] = $title;
        }
        // 继续判断其他的字段, 入$cond和$filter数组即可
        // 所有检索结束, 分配搜索条件
        $this->assign('filter', $filter);

        //二, 分页
        $page_size = 5;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $start = ($page-1) * $page_size;
        $rows = $model->where($cond)->count();
        $url = "?page={page}";
        if ($rows > $page_size)
        {//总记录数大于每页显示数，显示分页
            $page_model = new Page($rows, $page_size, $page, $url, 2);
            $page_nav = $page_model->page_show();
        }else{
            $page_nav = '';
        }

        // 三: 考虑排序
        $sort = [
            'field' => I('get.sort_field', 'sort_number'),
            'type' => I('get.sort_type', 'asc'),
        ];// 默认的排序方式
//        确定排序字符串
        if (! empty($sort)) {
            $sortString = $sort['field'] . ' ' . $sort['type'];
            $model->order($sortString);
        }
//        将当前的排序方式, 分配到模板中
        $this->assign('sort', $sort);
        $list = $model->where($cond)->limit("$start,$page_size")->select();
        $this->assign('list', $list);
        $this->assign('page_nav', $page_nav);

        $this->display();
    }
    //ajax处理
    public function ajaxAction()
    {
        $operate = I('request.operate', null);
        if(is_null($operate)){
            $this->ajaxReturn(['error'=>1, 'errorInfo'=>'没有确定的操作']);
        }
        switch ($operate){
            case 'titleUnique':
                $model = M('Brand');
                if($row = $model->getByTitle(I('request.title', ''))){
                    if($row['brand_id'] == I('request.brand_id')){
                        echo 'true';
                    }else{
                        echo 'false';
                    }
                }else{
                    echo 'true';
                }
                break;
        }
    }

    //批量处理
    public function multiAction()
    {
        $operate = I('post.operate', null);
        // 先处理删除
        $operate = 'delete';
        switch ($operate) {
            case 'delete':
                $model = M('Brand');
                // 先删除图像logo
                foreach($model->where(['brand_id'=>['in', I('post.selected')]])->getField('logo', true) as $logo) {
                    @unlink(APP_PATH . 'Upload/' . $logo);
                    @unlink('./Public/Thumb/' . $logo);
                };
                // 删除记录
                $model->where(['brand_id'=>['in', I('post.selected')]])->delete();
                break;
        }
        $this->redirect('list');
    }

}