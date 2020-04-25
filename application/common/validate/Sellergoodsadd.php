<?php

namespace app\common\validate;


use think\Validate;
/**
 * ============================================================================
 *在线教育培训付费视频管理系统
 * ============================================================================
 * 版权所有 重庆师范大学计算机科学与技术杨玉印，并保留所有权利。
 * 网站地址: http://yyu.loveli.top

 * ============================================================================
 * 验证器
 */
class  Sellergoodsadd extends Validate
{
    protected $rule = [
        ['goods_name', 'require', '课程名称不能为空'],
        ['goods_price', 'require', '课程价格不能为空'],
        ['goods_type', 'in:0,1', '课程类型错误'],
    ];

    protected $scene = [
        'save_goods' => ['goods_name', 'goods_price','goods_type'],
    ];
}