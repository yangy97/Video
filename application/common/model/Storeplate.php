<?php
/**
 * 机构模型管理
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
class  Storeplate extends Model {
    public $page_info;
    
    /**
     * 版式列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @param int $pagesize 分页
     * @return array
     */
    public function getStoreplateList($condition, $field = '*', $pagesize = 0) {
        if($pagesize){
            $result = db('storeplate')->field($field)->where($condition)->paginate($pagesize,false,['query' => request()->param()]);
            $this->page_info = $result;
            return $result->items();
        }else{
            return db('storeplate')->field($field)->where($condition)->select();
        }
        
    }
    
    /**
     * 版式详细信息
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return array
     */
    public function getStoreplateInfo($condition) {
        return db('storeplate')->where($condition)->find();
    }
    
    public function getStoreplateInfoByID($storeplate_id) {
        $info = $this->_rStoreplateCache($storeplate_id);
        if (empty($info)) {
            $info = $this->getStoreplateInfo(array('storeplate_id' => $storeplate_id));
            $this->_wStoreplateCache($storeplate_id, $info);
        }
        return $info;
    }
    
    /**
     * 添加版式
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return boolean
     */
    public function addStoreplate($data) {
        return db('storeplate')->insertGetId($data);
    }
    
    /**
     * 更新版式
     * @access public
     * @author csdeshang
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return boolean
     */
    public function editStoreplate($update, $condition) {
        $list = $this->getStoreplateList($condition, 'storeplate_id');
        if (empty($list)) {
            return true;
        }
        $result = db('storeplate')->where($condition)->update($update);
        if ($result) {
            foreach ($list as $val) {
                $this->_dStoreplateCache($val['storeplate_id']);
            }
        }
        return $result;
    }
    
    /**
     * 删除版式
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return boolean
     */
    public function delStoreplate($condition) {
        $list = $this->getStoreplateList($condition, 'storeplate_id');
        if (empty($list)) {
            return true;
        }
        $result = db('storeplate')->where($condition)->delete();
        if ($result) {
            foreach ($list as $val) {
                $this->_dStoreplateCache($val['storeplate_id']);
            }
        }
        return $result;
    }
    
    /**
     * 读取机构关联板式缓存缓存
     * @access public
     * @author csdeshang
     * @param int $storeplate_id 机构关联版式id
     * @return array
     */
    private function _rStoreplateCache($storeplate_id) {
        return rcache($storeplate_id, 'store_plate');
    }
    
    /**
     * 写入机构关联板式缓存缓存
     * @access public
     * @author csdeshang
     * @param int $storeplate_id 机构关联版式id
     * @param array $info
     * @return boolean
     */
    private function _wStoreplateCache($storeplate_id, $info) {
        return wcache($storeplate_id, $info, 'store_plate');
    }
    
    /**
     * 删除机构关联板式缓存缓存
     * @access public
     * @author csdeshang
     * @param int $storeplate_id 机构关联版式id
     * @return boolean
     */
    private function _dStoreplateCache($storeplate_id) {
        return dcache($storeplate_id, 'store_plate');
    }
    
}

