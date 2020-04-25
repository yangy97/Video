<?php

/**
 * 商品下的课程
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
class  Goodscourses extends Model {

    /**
     * 插入数据
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return boolean
     */
    public function addGoodscourses($data) {
        $result = db('goodscourses')->insertGetId($data);
        if ($result) {
            if ($data['goods_id']){
                $this->_dGoodscoursesCache($data['goods_id']);
            }
        }
        return $result;
    }

    public function getGoodscoursesList($condition) {
        return db('goodscourses')->where($condition)->select();
    }
    
    public function getOneGoodscourses($condition){
        return db('goodscourses')->where($condition)->find();
    }
    
    public function editGoodscourses($condition,$data){
        return db('goodscourses')->where($condition)->update($data);
    }

    public function delGoodscourses($condition) {
        $list = $this->getGoodscoursesList($condition, 'goods_id');
        if (empty($list)) {
            return true;
        }
        $result = db('goodscourses')->where($condition)->delete();
        if ($result) {
            foreach ($list as $v) {
                $this->_dGoodscoursesCache($v['goods_id']);
            }
        }
        return $result;
    }

    /**
     * 读取商品课程缓存
     * @access public
     * @author csdeshang
     * @param int $goods_id 商品id
     * @return array
     */
    private function _rGoodscoursesCache($goods_id) {
        return rcache($goods_id, 'goods_courses');
    }

    /**
     * 写入商品课程缓存
     * @access public
     * @author csdeshang
     * @param int $goods_id 商品ID
     * @param array $array 数组内容
     * @return boolean
     */
    private function _wGoodscoursesCache($goods_id, $array) {
        return wcache($goods_id, $array, 'goods_courses', 60);
    }

    /**
     * 删除商品课程缓存
     * @access public
     * @author csdeshang
     * @param int $goods_id 商品第
     * @return boolean
     */
    private function _dGoodscoursesCache($goods_id) {
        return dcache($goods_id, 'goods_courses');
    }

}
