<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/5
 * Time: 11:06
 */
namespace Back\Controller;
use Think\Page;

class GoodsController extends CommonController
{
    public function addAction()
    {
        if(IS_POST){
            $model = D('Goods');
            if($model->create()){
                $goods_id = $model->add();
                //增加商品相册数据
                $modelGallery = M('Gallery');
                $galleryList = [];
                foreach(I('post.galleries') as $value){
                    $value['goods_id'] = $goods_id;
                    $galleryList[] = $value;
                }
                //批量插入
                $modelGallery->addAll($galleryList);

                //商品属性信息
                $modelAttribute = M('Attribute');
                $modelAttributeType = M('AttributeType');
                $modelGoodsAttribute = M('GoodsAttribute');
                $modelGoodsAttributeOption = M('GoodsAttributeOption');
                foreach(I('post.attribute',[]) as $attribute_id=>$value){
                    //判断当前属性类型
                    $attribute_type_id = $modelAttribute->where(['attribute_id'=>$attribute_id])->getField('attribute_type_id');
                    $attributeType = $modelAttributeType->where(['attribute_type_id'=>$attribute_type_id])->getField('attribute_type_title');
                    switch($attributeType){
                        case 'text': //文本
                            $data = [
                                'goods_id'=>$goods_id,
                                'attribute_id'=>$attribute_id,
                                'value'=>$value,
                            ];
                            if($modelGoodsAttribute->create($data)){
                                $modelGoodsAttribute->add();
                            }
                            break;
                        case 'select': //单选
                            $data = [
                                'goods_id'=>$goods_id,
                                'attribute_id'=>$attribute_id,
                            ];
                            if($modelGoodsAttribute->create($data)){
                                $goods_Attribute_id = $modelGoodsAttribute->add();
                                //插入goods_attribute_option表数据
                                $data = [
                                    'goods_attribute_id'=>$goods_Attribute_id,
                                    'goods_option_id'=>$value,
                                ];
                                $modelGoodsAttributeOption->add($data);
                            }
                            break;
                        case 'select-multi'://多选
                            $data = [
                                'goods_id'=>$goods_id,
                                'attribute_id'=>$attribute_id,
                                //判断是是否在is_post数组中
                                'product_option'=>in_array('attribute_id',I('post.is_option',[])) ? 1 : 0,
                            ];
                            if($modelGoodsAttribute->create($data)) {
                                $goods_attribute_id = $modelGoodsAttribute->add();
                                //插入goods_attribute_option
                                $rows = array_map(function ($v) use($goods_attribute_id){
                                    return [
                                        'goods_attribute_id'=>$goods_attribute_id,
                                        'attribute_option_id'=>$v,
                                    ];
                                },$value);
                                $modelGoodsAttributeOption->addAll($rows);
                            };
                            break;
                    }
                }
                $this->redirect('list');
            }else{
                session('message', ['error'=>1, 'errorInfo'=>$model->getError()]);
                session('data', $_POST);
                $this->redirect('add');
            }
        }else{
            $this->assign('message', session('message'));
            session('message',null);
            $this->assign('data', session('data'));
            session('data', null);

            //获取对应数据
            $this->assign('sku_list', M('Sku')->select());
            $this->assign('tax_list', M('Tax')->select());
            $this->assign('stock_status_list', M('Stock_status')->select());
            $this->assign('length_unit_list', M('Length_unit')->select());
            $this->assign('weight_unit_list', M('Weight_unit')->select());
            $this->assign('brand_list', M('Brand')->select());
            $this->assign('category_list', D('Category')->getTreeList());
            $this->assign('type_list', M('Type')->select());

            $this->display('set');
        }
    }

    /**
     * 更新
     */
    public function edit()
    {
        $model = D('Goods');
        if(IS_POST){
            if($model->create()){
                $model->save;
                //新图像添加
                    //旧相册信息更新
                $modelGallery = M('Gallery');
                $newGalleryList = [];
                foreach(I('post.galleries') as $key=>$value){
                    if(isset($value['gallery_id'])){
                        $modelGallery->save();
                    }else{
                        $value['goods_id'] = I('post.goods_id');
                        $newGalleryList[] = $value;
                    }
                }
                $modelGallery->addAll($newGalleryList);

                //处理商品属相数据
                $goods_id = I('post.goods_id');
                $modelAttribute = M('Attribute');
                $modelAttributeType = M('Attribute_type');
                $modelGoodsAttribute = M('GoodsAttribute');
                $modelGoodsAttributeOption = M('GoodsAttributeOption');
                foreach(I('post.attribute',[]) as $goods_attribute_id=>$value){
                    $attributeType = $modelAttributeType
                        ->field('attribute_type_title')
                        ->join('left join __ATTRIBUTE__ a using(attribute_type_id)')
                        ->join('left join __GOODS_ATTRIBUTE__ ga using(attribute_id)')
                        ->where(['goods_attribute_id'=>$goods_attribute_list])
                        ->find();
                    $attributeType = $attributeType['attribute_type_title'];

                    switch ($attributeType){
                        case 'text':
                            $data = [
                                'goods_attribute_id'=>$goods_attribute_id,
                                'value'=>$value,
                            ];
                            if($modelGoodsAttribute->create()){
                                $modelAttribute->save();
                            }
                            break;
                        case 'select': // 单选
                            $modelGoodsAttributeOption->where([
                                'goods_attribute_id'  => $goods_attribute_id,
                            ])->save([
                                'attribute_option_id'  => $value,
                            ]);

                            break;
                        case 'select-multi':// 多选

                            // 插入goods_attribute_option表
                            $rows = array_map(function($v) use($goods_attribute_id) {
                                return [
                                    'goods_attribute_id'    =>$goods_attribute_id,
                                    'attribute_option_id'   => $v,
                                ];

                            }, $value);
                            $modelGoodsAttributeOption->add($row);

                            break;
                    }
                }
                $this->redirect('list');
            }else{
                session('message',['error'=>1, 'errorInfo'=>$model->getError()]);
                session('data', $_POST);
                $this->redirect('edit', ['goods_id'=>I('post.goods_id')]);
            }
        }else{
            $this->assign('message', sessoin('message'));
            session('message', null);
            $data = is_null(session('data')) ? $model->find(I('get.goods_id')) : session('data');
            $this->assign('data',$data);
            session('data', null);

            //获取相应的数据
            $this->assign('sku_list', M('Sku')->select());
            $this->assign('tax_list', M('Tax')->select());
            $this->assign('stock_status_list', M('StockStatus')->select());
            $this->assign('length_unit_list', M('LengthUnit')->select());
            $this->assign('weight_unit_list', M('WeightUnit')->select());
            $this->assign('brand_list', M('Brand')->select());
            $this->assign('category_list', D('Category')->getTreeList());
            //商品相册
            $this->assign('gallery_list', M('Gallery')->where(['goods_id'=>I('get.goods_id')])->select());
            $this->assign('type_list', M('Type')->select());
            //商品下的所有属性
            $attribute_list =  D('Attribute')
                ->alias('a')
                ->join('left join __ATTRIBUTE_TYPE__ at using(attribute_id)')
                ->relation(true)
                ->where(['type_id'=>$data['type_id']])
                ->select();
            //当前商品已选属性
            $goods_attribute_list = D('GoodsAttribute')
                ->alias('ga')
                ->relation(true)
                ->where(['goods_id'=>$data['goods_id']])
                ->select();
            //合并
            $attribute_merge_list = [];
            foreach($attribute_list as $attribute ) {
                foreach($goods_attribute_list as $goods_attribute) {
                    if ($attribute['attribute_id'] == $goods_attribute['attribute_id']) {
                        // 获取被选中的id的集合
                        $goods_attribute['checked_list'] = array_map(function($v) {
                            return $v['attribute_option_id'];
                        }, $goods_attribute['goodsOptionList']);
                        $attribute_merge_list[$attribute['attribute_id']] = array_merge($attribute, $goods_attribute);
                        break ;
                    }
                }
            }
            $this->assign('attribute_merge_list', $attribute_merge_list);
            $this->display('set');
        }
    }

    /**
     * 列表
     */
    public function listAction()
    {
        $model = M('Goods');
        $cond = [];
        //分页
        $page_size = 10;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $start = ($page-1) * $page_size;
        $rows = $model->where($cond)->count();
        $url = "?page={page}";
        $page_model = new Page($rows, $page_size, $page, $url, 2);
        $page_nav = $page_model->page_show();
        //排序
        $sort = [
            'field'=>I('get.sort_field', null),
            'type'=>I('get.sort_type','asc'),
        ];
        if(!is_null($sort['field'])){
            $sortString = $sort['field'] . ' ' . $sort['type'];
            $model->order($sortString);
        }
        $this->assign('sort', $sort);
        $list = $model->where($cond)->select();
        $this->assign('list', $list);
        $this->assign('page_nav', $page_nav);
        $this->display();

    }
    /**
     * 批量处理
     */
    public function multiAction()
    {
        $option = I('post.option',null);
        //先假设是批量删除
        $option = 'delete';
        switch ($option) {
            case 'delete':
                $model = M('Goods');
                $model->where(['goods_id'=>['in',I('post.selected')]])->delete();
                break;
        }
        $this->redirect('list');
    }
    /**
     * 接口开发
     */
    public function ajaxAction()
    {
        switch (I('request.operate','')) {
            case 'getAttrList' :
                $rows = D('Attribute')
                    ->alias('a')
                    ->join('left join __ATTRIBUTE_TYPE__ at using (attribute_type_id)')
                    ->relation(true)
                    ->where(['type_id'=>I('request.type_id')])
                    ->select();
                if($rows){
                    $this->ajaxReturn(['error'=>0, 'rows'=>$rows]);
                }else{
                    $this->ajaxReturn(['error'=>1]);
                }
                break;

        }
    }
}