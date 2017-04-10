<?php
namespace Back\Model;

use Think\Model;

class CategoryModel extends Model
{
    protected $patchValidate = true;
    protected $_validate = [
        ['parent_id', 'nonChild', '不能设置后代分类为上级分类', self::EXISTS_VALIDATE, 'call_back', self::MODEL_UPDATE],
    ];
    protected function nonChild($parent_id)
    {
        if($parent_id == 0){
            return true;
        }
        $category_id = I('post.category');
        if($parent_id == $category_id){
            return false;
        }
        //向上递归查找
        return $this->checkParents($parent_id,$category_id);
    }
    protected function checkParents($parent_id,$category_id)
    {
        if($parent_id == 0){
            return true;
        }
        $parent_id = $this->where(['category_id'=>$parent_id])->getField('parent_id');
        if($parent_id == $category_id){
            return false;
        }else{
            return $this->checkParents($parent_id, $category_id);
        }
    }
    //获取树状列表
    public function getTreeList()
    {
        $list =  $this->order('sort_number')->select();
        $tree = $this->tree($list);
        return $tree;
    }
    protected function tree($list, $category_id=0, $level=0)
    {
        static $tree = [];
        foreach ($list as $row){
            if($row['parent_id'] == $category_id){
                $row['level'] = $level;
                $tree[] = $row;
                $this->tree($list, $row['category_id'], $level+1);
            }
        }
        return $tree;
    }

}