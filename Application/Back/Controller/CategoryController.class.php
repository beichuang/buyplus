<?php
namespace Back\Controller;

use Think\Controller;
class CategoryController extends Controller
{
    public function addAction()
    {

        if(IS_POST){
            $model = D('Category');
            if($model->create()){
                $model->add();
                $this->redirect('list');
            }else{
                session('message',['error'=>1,'errorInfo'=>$model->getError()]);
                session('data', $_POST);
                $this->redirect('add');
            }

        }else{
            $this->assign('message',session('message'));
            session('message',null);
            $this->assign('data',session('data'));
            session('data',null);
            //获取分类
            $modelCategory = D('Category');
            $list = $modelCategory->getTreeList();
            $this->assign('list', $list);

            $this->display('set');
        }
    }
    //更新
    public function editAction()
    {
        $model = D('Category');
        if(IS_POST){
            if($model->create()){
                $model->save();
                $this->redirect('list');
            }else{
                //错误信息存入session
                session('message', ['error'=>1, 'errorInfo'=>$model->getError()]);
                session('data', $_POST);
                $this->redirect('edit', ['category_id'=>I('post.category')]);
            }

        }else{
            $this->assign('message', session('message'));
            session('message',null);
            $this->assign('data', is_null(session('data')) ? $model->find(I('get.category_id')) : session('data'));
            session('data',null);
            //获取分类
            $modelCategory = D('Category');
            $this->assign('list', $modelCategory->getTreeList());

            $this->display('set');
        }
    }

    public function listAction()
    {
        $modelCategory = D('Category');
        $this->assign('list', $modelCategory->getTreeList());
        $this->display();
    }

    //批量处理
    public function multiAction()
    {
        $operate = I('post.operate', null);
        switch ($operate){
            case 'delete':
                $model = M('Category');
                $model->where(['category_id'=>['in',I('post.selected')]])->delete();
                break;
        }
        $this->redirect('list');
    }
}