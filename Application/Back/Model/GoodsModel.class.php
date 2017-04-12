<?php
namespace Back\Model;

use Think\Model;

class GoodsModel extends Model
{
    //验证规则
    protected $patchValidate = true;
    protected $_validate = [
        ['sku_id', 'chkSku', '请选择合理的库存单位', self::EXISTS_VALIDATE, 'callback', self::MODEL_BOTH],
        ['tax_id','chkTax','请选择合理的税类型',self::EXISTS_VALIDATE,'callback',self::MODEL_BOTH],
    ];
    //自动完成规则
    protected $_auto = [
        ['upc','mkUpc',self::MODEL_INSERT,'callback'],
        ['created_at', 'time', self::MODEL_INSERT, 'function'],
        ['updated_at','time',self::MODEL_INSERT,'function'],
        ['date_available','mkDateAvailable',self::MODEL_BOTH,'callback']
    ];

    protected function mkDateAvailable()
    {
        return date('Y-m-d');
    }

    protected function mkUpc($value)
    {
        //用户指定则使用用户的
        if($value != ''){
            return $value;
        }
        //否则使用自动生成的
        return time() . mt_rand(100,999) . mt_rand(100,999) . mt_rand(100,999); //伪随机数
    }

    protected function chkSku($value)
    {
        return (bool) M('Sku')->find($value);
    }
    protected function chkTax($value)
    {
        return (bool) M('Tax')->find($value);
    }

}