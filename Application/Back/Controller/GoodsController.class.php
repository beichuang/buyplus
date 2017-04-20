<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/5
 * Time: 11:06
 */

namespace Back\Controller;

use Think\Image;
use Think\Page;
use Think\Upload;

class GoodsController extends CommonController
{
    public function addAction()
    {
        if (IS_POST) {
            $model = D('Goods');
            if ($model->create()) {
                $goods_id = $model->add();
                //增加商品相册数据
                $modelGallery = M('Gallery');
                $galleryList = [];
                foreach (I('post.galleries') as $value) {
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
                foreach (I('post.attribute', []) as $attribute_id => $value) {
                    //判断当前属性类型
                    $attribute_type_id = $modelAttribute->where(['attribute_id' => $attribute_id])->getField('attribute_type_id');
                    $attributeType = $modelAttributeType->where(['attribute_type_id' => $attribute_type_id])->getField('attribute_type_title');
                    switch ($attributeType) {
                        case 'text': //文本
                            $data = [
                                'goods_id' => $goods_id,
                                'attribute_id' => $attribute_id,
                                'value' => $value,
                            ];
                            if ($modelGoodsAttribute->create($data)) {
                                $modelGoodsAttribute->add();
                            }
                            break;
                        case 'select': //单选
                            $data = [
                                'goods_id' => $goods_id,
                                'attribute_id' => $attribute_id,
                            ];
                            if ($modelGoodsAttribute->create($data)) {
                                $goods_Attribute_id = $modelGoodsAttribute->add();
                                //插入goods_attribute_option表数据
                                $data = [
                                    'goods_attribute_id' => $goods_Attribute_id,
                                    'goods_option_id' => $value,
                                ];
                                $modelGoodsAttributeOption->add($data);
                            }
                            break;
                        case 'select-multi'://多选
                            $data = [
                                'goods_id' => $goods_id,
                                'attribute_id' => $attribute_id,
                                //判断是是否在is_post数组中
                                'product_option' => in_array('attribute_id', I('post.is_option', [])) ? 1 : 0,
                            ];
                            if ($modelGoodsAttribute->create($data)) {
                                $goods_attribute_id = $modelGoodsAttribute->add();
                                //插入goods_attribute_option
                                $rows = array_map(function ($v) use ($goods_attribute_id) {
                                    return [
                                        'goods_attribute_id' => $goods_attribute_id,
                                        'attribute_option_id' => $v,
                                    ];
                                }, $value);
                                $modelGoodsAttributeOption->addAll($rows);
                            };
                            break;
                    }
                }
                $this->redirect('list');
            } else {
                session('message', ['error' => 1, 'errorInfo' => $model->getError()]);
                session('data', $_POST);
                $this->redirect('add');
            }
        } else {
            $this->assign('message', session('message'));
            session('message', null);
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
        if (IS_POST) {
            if ($model->create()) {
                $model->save;
                //新图像添加
                //旧相册信息更新
                $modelGallery = M('Gallery');
                $newGalleryList = [];
                foreach (I('post.galleries') as $key => $value) {
                    if (isset($value['gallery_id'])) {
                        $modelGallery->save();
                    } else {
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
                foreach (I('post.attribute', []) as $goods_attribute_id => $value) {
                    $attributeType = $modelAttributeType
                        ->field('attribute_type_title')
                        ->join('left join __ATTRIBUTE__ a using(attribute_type_id)')
                        ->join('left join __GOODS_ATTRIBUTE__ ga using(attribute_id)')
                        ->where(['goods_attribute_id' => $goods_attribute_id])
                        ->find();
                    $attributeType = $attributeType['attribute_type_title'];

                    switch ($attributeType) {
                        case 'text':
                            $data = [
                                'goods_attribute_id' => $goods_attribute_id,
                                'value' => $value,
                            ];
                            if ($modelGoodsAttribute->create()) {
                                $modelAttribute->save();
                            }
                            break;
                        case 'select': // 单选
                            $modelGoodsAttributeOption->where([
                                'goods_attribute_id' => $goods_attribute_id,
                            ])->save([
                                'attribute_option_id' => $value,
                            ]);

                            break;
                        case 'select-multi':// 多选

                            // 插入goods_attribute_option表
                            $rows = array_map(function ($v) use ($goods_attribute_id) {
                                return [
                                    'goods_attribute_id' => $goods_attribute_id,
                                    'attribute_option_id' => $v,
                                ];

                            }, $value);
                            $modelGoodsAttributeOption->add($rows);

                            break;
                    }
                }
                $this->redirect('list');
            } else {
                session('message', ['error' => 1, 'errorInfo' => $model->getError()]);
                session('data', $_POST);
                $this->redirect('edit', ['goods_id' => I('post.goods_id')]);
            }
        } else {
            $this->assign('message', sessoin('message'));
            session('message', null);
            $data = is_null(session('data')) ? $model->find(I('get.goods_id')) : session('data');
            $this->assign('data', $data);
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
            $this->assign('gallery_list', M('Gallery')->where(['goods_id' => I('get.goods_id')])->select());
            $this->assign('type_list', M('Type')->select());
            //商品下的所有属性
            $attribute_list = D('Attribute')
                ->alias('a')
                ->join('left join __ATTRIBUTE_TYPE__ at using(attribute_id)')
                ->relation(true)
                ->where(['type_id' => $data['type_id']])
                ->select();
            //当前商品已选属性
            $goods_attribute_list = D('GoodsAttribute')
                ->alias('ga')
                ->relation(true)
                ->where(['goods_id' => $data['goods_id']])
                ->select();
            //合并
            $attribute_merge_list = [];
            foreach ($attribute_list as $attribute) {
                foreach ($goods_attribute_list as $goods_attribute) {
                    if ($attribute['attribute_id'] == $goods_attribute['attribute_id']) {
                        // 获取被选中的id的集合
                        $goods_attribute['checked_list'] = array_map(function ($v) {
                            return $v['attribute_option_id'];
                        }, $goods_attribute['goodsOptionList']);
                        $attribute_merge_list[$attribute['attribute_id']] = array_merge($attribute, $goods_attribute);
                        break;
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
        $start = ($page - 1) * $page_size;
        $rows = $model->where($cond)->count();
        $url = "?page={page}";
        $page_model = new Page($rows, $page_size, $page, $url, 2);
        $page_nav = $page_model->page_show();
        //排序
        $sort = [
            'field' => I('get.sort_field', null),
            'type' => I('get.sort_type', 'asc'),
        ];
        if (!is_null($sort['field'])) {
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
        $option = I('post.option', null);
        //先假设是批量删除
        $option = 'delete';
        switch ($option) {
            case 'delete':
                $model = M('Goods');
                $model->where(['goods_id' => ['in', I('post.selected')]])->delete();
                break;
        }
        $this->redirect('list');
    }

    /**
     * 接口开发
     */
    public function ajaxAction()
    {
        switch (I('request.operate', '')) {
            case 'getAttrList' :
                $rows = D('Attribute')
                    ->alias('a')
                    ->join('left join __ATTRIBUTE_TYPE__ at using (attribute_type_id)')
                    ->relation(true)
                    ->where(['type_id' => I('request.type_id')])
                    ->select();
                if ($rows) {
                    $this->ajaxReturn(['error' => 0, 'rows' => $rows]);
                } else {
                    $this->ajaxReturn(['error' => 1]);
                }
                break;
            case 'imageUpload': //主图上传
                $toolUpload = new Upload();
                $toolUpload->exts = ['png', 'jpeg', 'jpg', 'gif'];
                $toolUpload->maxSize = 5 * 1024 * 1024;
                $toolUpload->rootPath = APP_PATH . 'Upload/';
                $toolUpload->savePath = 'Goods/';
                //执行上传
                $uploadInfo = $toolUpload->uploadOne($_FILES['imageAjax']);
                if ($uploadInfo) {
                    $image = $uploadInfo['savePath'] . $uploadInfo['saveName'];

                    //创建目录
                    $toolImage = new Image();
                    if (!is_dir('./Public/Thumb/' . $uploadInfo['savepath'])) {
                        mkdir('./Public/Thumb/' . $uploadInfo['savepath'], 0764, true);
                    }
                    $toolImage
                        ->open(APP_PATH . 'Upload/' . $image)
                        ->thumb(300, 340, Image::IMAGE_THUMB_FILLED)//缩略图
                        ->save('./Public/Thumb/' . $image);
                    $this->ajaxReturn(['error' => 0, 'imageAjax' => ['image' => $image, 'image_thumb' => $image, 'thumbUrl' => $image]]);
                }
                break;
            case 'galleriesUpload': //商品相册
                $toolUpload = new Upload();
                $toolUpload->exts = ['png', 'jpeg', 'jpg', 'gif'];// 允许类型
                $toolUpload->maxSize = 1 * 1024 * 1024;// 1M
                $toolUpload->rootPath = APP_PATH . 'Upload/';// 上传的根目录
                $toolUpload->savePath = 'Gallery/';// 保证目录存在
                // 执行上传
                $uploadInfo = $toolUpload->uploadOne($_FILES['galleriesAjax']);
                if ($uploadInfo) {
                    // 成功, 按照需要的格式进行响应
                    $image = $uploadInfo['savepath'] . $uploadInfo['savename'];
//                    创建public/Thumb/日期
                    $toolImage = new Image();
                    if (!is_dir('./Public/Thumb/' . $uploadInfo['savepath'])) {
                        mkdir('./Public/Thumb/' . $uploadInfo['savepath'], 0764, true);
                    }
                    // 创建大中小三种缩略图
                    $toolImage->open(APP_PATH . 'Upload/' . $image);
                    // 大缩略图 填充
                    $bigImage = $uploadInfo['savepath'] . 'big-' . $uploadInfo['savename'];
                    $toolImage->thumb(800, 800, Image::IMAGE_THUMB_FILLED)->save('./Public/Thumb/' . $bigImage);
                    // 中缩略图 填充
                    $mediumImage = $uploadInfo['savepath'] . 'medium-' . $uploadInfo['savename'];
                    $toolImage->thumb(300, 300, Image::IMAGE_THUMB_FILLED)->save('./Public/Thumb/' . $mediumImage);
                    // 小缩略图 填充
                    $smallImage = $uploadInfo['savepath'] . 'small-' . $uploadInfo['savename'];
                    $toolImage->thumb(60, 60, Image::IMAGE_THUMB_FILLED)->save('./Public/Thumb/' . $smallImage);
                    // 原始文件名, 缩略图文件名
                    // 制作响应数据
                    $this->ajaxReturn([
                        'error' => 0,
                        'image' => $image,
                        'image_small' => $smallImage,
                        'image_medium' => $mediumImage,
                        'image_big' => $bigImage,
                        'key' => strchr($uploadInfo['savename'], '.', true),
                        'savepath' => $uploadInfo['savepath'],
                        'ext' => strrchr($uploadInfo['savename'], '.'),
                    ]);
                }
                break;
            case 'galleryRemove':
                // 判断当前是否传输了gallery_id
                $gallery_id = I('request.gallery_id', null);
                if (is_null($gallery_id)) {
                    $image = I('request.key') . I('request.ext');
                    $savepath = I('request.savepath');
                } else {
                    // gallery_ID传递
                    $imageLong = M('Gallery')->where(['gallery_id' => $gallery_id])->getField('image');
                    $image = substr($imageLong, strrpos($imageLong, '/') + 1);
                    $savepath = substr($imageLong, 0, strrpos($imageLong, '/') + 1);

                    // 删除记录
                    M('Gallery')->delete($gallery_id);
                }
                //删图像文件
                @unlink(APP_PATH . 'Upload/' . $savepath . $image);// 上传的原图
                @unlink('./Public/Thumb/' . $savepath . 'big-' . $image);// 大图
                @unlink('./Public/Thumb/' . $savepath . 'medium-' . $image);// 中图
                @unlink('./Public/Thumb/' . $savepath . 'small-' . $image);// 小图

                $this->ajaxReturn(['error' => 0]);
                break;
            case 'addProduct':
                $modelProduct = M('Product');
                $product_id = $modelProduct->add(I('request.'));

                // 生成product_goods_attribute_option
                $modelPGAO = M('ProductGoodsAttributeOption');
                $rows = array_map(function ($goods_attribute_option_id) use ($product_id) {
                    return [
                        'product_id' => $product_id,
                        'goods_attribute_option_id' => $goods_attribute_option_id,
                    ];
                }, I('request.option', []));
                $modelPGAO->addAll($rows);

                $product['product_id'] = $product_id;
                $product = array_merge($product, I('request.'));
                // 通过所选的选项ID获取选项内容
                $modelGAO = M('GoodsAttributeOption');
                foreach ($product['option'] as $goods_attribute_option_id) {
                    $row = $modelGAO
                        ->join('left join __ATTRIBUTE_OPTION__ using(attribute_option_id)')
                        ->find($goods_attribute_option_id);
                    $product['optionList'][] = $row;
                }
                $this->assign('product', $product);

                $this->assign('priceDriftList', M('PriceDrift')->select());
                $this->display('product_row');
                break;
            default:
                $this->ajaxReturn(['error' => 1, 'errorInfo' => '操作错误']);
                break;

        }
    }

    /**
     * 货品操作
     */
    public function productAction()
    {
        // 获取当前商品的货品选项属性列表
        $goods_id = I('get.goods_id');
        $modelGoodsAttribute = M('GoodsAttribute');
        $optionList = $modelGoodsAttribute
            ->alias('ga')
            ->join('left join __ATTRIBUTE__ a using(attribute_id)')
            ->where(['goods_id'=>$goods_id, 'product_option'=>'1'])
            ->select();

        // 遍历当前的商品货品属性, 获取当前货品属性对应已选的属性选项值
        $modelGoodsAttributeOption = M('GoodsAttributeOption');
        foreach($optionList as $key=>$option) {
            $valueList = $modelGoodsAttributeOption
                ->alias('gao')
                ->join('left join __ATTRIBUTE_OPTION__ ao using(attribute_option_id)')
                ->where(['goods_attribute_id'=>$option['goods_attribute_id']])
                ->select();
            $optionList[$key]['valueList'] = $valueList;
        }
        $this->assign('optionList', $optionList);

        $this->assign('priceDriftList', M('PriceDrift')->select());

        $this->assign('goods_id', I('get.goods_id'));
        // 已有货品列表即可
        $productList = M('Product')
            ->where(['goods_id'=>I('get.goods_id')])
            ->select();
        foreach($productList as $key=>$product) {
            $rows = M('ProductGoodsAttributeOption')
                ->join('left join __GOODS_ATTRIBUTE_OPTION__ using(goods_attribute_option_id)')
                ->join('left join __ATTRIBUTE_OPTION__ using(attribute_option_id)')
                ->where(['product_id'=>$product['product_id']])
                ->select();
            $productList[$key]['optionList'] = $rows;
        }
        $this->assign('productList', $productList);


        $this->display();
    }

}

