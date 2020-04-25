<?php

/**
 * 机构入住模型
 */

namespace app\common\model;

use think\Model;
/**
 * ============================================================================
 *在线教育培训付费视频管理系统
 * ============================================================================
 * 版权所有 重庆师范大学计算机科学与技术杨玉印，并保留所有权利。
 * 网站地址: http://yyu.loveli.top

 * ============================================================================
 * 数据层模型
 */
class  Storejoinin extends Model {
    public $page_info;


    /**
     * 读取列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param int $pagesize 分页
     * @param string $order 排序
     * @param string $field 字段
     * @return array
     */
    public function getStorejoininList($condition, $pagesize = '', $order = '', $field = '*') {
        if($pagesize){
            $result = db('storejoinin')->field($field)->where($condition)->order($order)->paginate($pagesize,false,['query' => request()->param()]);
            $this->page_info = $result;
            return $result->items();
        }else{
            $result = db('storejoinin')->field($field)->where($condition)->order($order)->select();
            return $result;
        }
    }

    /**
     * 机构入住数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getStorejoininCount($condition) {
        return db('storejoinin')->where($condition)->count();
    }

    /**
     * 读取单条记录
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return array
     */
    public function getOneStorejoinin($condition) {
        $result = db('storejoinin')->where($condition)->find();
        return $result;
    }

    /**
     * 判断是否存在
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return boolean
     */
    public function isStorejoininExist($condition) {
        $result = $this->getOneStorejoinin($condition);
        if (empty($result)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param type $data 数据
     * @return type
     */
    public function addStorejoinin($data) {
        return db('storejoinin')->insert($data);
    }


    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param type $update 数据
     * @param type $condition 条件
     * @return type
     */
    public function editStorejoinin($update, $condition) {
        return db('storejoinin')->where($condition)->update($update);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function delStorejoinin($condition) {
        return db('storejoinin')->where($condition)->delete();
    }



}
