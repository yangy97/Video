<?php

/**
 * 机构设置
 *
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
class  Store extends Model {

    public $page_info;
    
    /**
     * 自营机构的ID
     * @access protected
     * @author csdeshang
     * array(
     *   '机构ID(int)' => '是否绑定了全部商品类目(boolean)',
     *   // ..
     * )
     */
    protected $ownShopIds;

    /**
     * 删除缓存自营机构的ID
     * @access public
     * @author csdeshang
     */
    public function dropCachedOwnShopIds() {
        $this->ownShopIds = null;
        dkcache('own_shop_ids');
    }

    /**
     * 获取自营机构的ID
     * @access public
     * @author csdeshang
     * @param boolean $bind_all_gc = false 是否只获取绑定全部类目的自营店 默认否（即全部自营店）
     * @return int
     */
    public function getOwnShopIds($bind_all_gc = false) {

        $data = $this->ownShopIds;

        // 属性为空则取缓存
        if (!$data) {
            $data = rkcache('own_shop_ids');

            // 缓存为空则查库
            if (!$data) {
                $data = array();
                $all_own_shops = db('store')->field('store_id,bind_all_gc')->where(array('is_platform_store' => 1,))->select();
                foreach ((array) $all_own_shops as $v) {
                    $data[$v['store_id']] = (int) (bool) $v['bind_all_gc'];
                }

                // 写入缓存
                wkcache('own_shop_ids', $data);
            }

            // 写入属性
            $this->ownShopIds = $data;
        }

        return array_keys($bind_all_gc ? array_filter($data) : $data);
    }

    /**
     * 查询机构列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 字段
     * @param string $limit 限制条数
     * @return array
     */
    public function getStoreList($condition, $pagesize = null, $order = '', $field = '*', $limit = '') {
        if ($pagesize) {
            $result = db('store')->field($field)->where($condition)->order($order)->paginate($pagesize,false,['query' => request()->param()]);
            $this->page_info = $result;
            return $result->items();
        } else {
            $result = db('store')->field($field)->where($condition)->order($order)->limit($limit)->select();
            return $result;
        }
    }

    /**
     * 查询有效机构列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 字段
     * @return array
     */
    public function getStoreOnlineList($condition, $pagesize = null, $order = '', $field = '*') {
        $condition['store_state'] = 1;
        return $this->getStoreList($condition, $pagesize, $order, $field);
    }

    /**
     * 机构数量
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getStoreCount($condition) {
        return db('store')->where($condition)->count();
    }

    /**
     * 按机构编号查询机构的信息
     * @access public
     * @author csdeshang
     * @param type $storeid_array 机构ID编号
     * @param type $field 字段
     * @return type
     */
    public function getStoreMemberIDList($storeid_array, $field = 'store_id,member_id,store_name') {
        $store_list = db('store')->where(array('store_id' => array('in', $storeid_array)))->field($field)->select();
        $store_list = ds_change_arraykey($store_list, 'store_id');
        return $store_list;
    }

    /**
     * 查询机构信息
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array
     */
    public function getStoreInfo($condition) {
        $store_info = db('store')->where($condition)->find();
        if (!empty($store_info)) {
            if (!empty($store_info['store_presales']))
                $store_info['store_presales'] = unserialize($store_info['store_presales']);
            if (!empty($store_info['store_aftersales']))
                $store_info['store_aftersales'] = unserialize($store_info['store_aftersales']);

            //商品数
            $goods_model = model('goods');
            $store_info['goods_count'] = $goods_model->getGoodsOnlineCount(array('store_id' => $store_info['store_id']));

            //机构评价
            $evaluatestore_model = model('evaluatestore');
            $store_evaluate_info = $evaluatestore_model->getEvaluatestoreInfoByStoreID($store_info['store_id'], $store_info['storeclass_id']);

            $store_info = array_merge($store_info, $store_evaluate_info);
        }
        return $store_info;
    }

    /**
     * 通过机构编号查询机构信息
     * @access public
     * @author csdeshang
     * @param int $store_id 机构编号
     * @return array
     */
    public function getStoreInfoByID($store_id) {
        $prefix = 'store_info';

        $store_info = rcache($store_id, $prefix);
        if (empty($store_info)) {
            $store_info = $this->getStoreInfo(array('store_id' => $store_id));
            $cache = array();
            $cache['store_info'] = serialize($store_info);
            wcache($store_id, $cache, $prefix, 60 * 24);
        } else {
            $store_info = unserialize($store_info['store_info']);
        }

        return $store_info;
    }
    
    /**
     * 获取机构信息根据机构id
     * @access public
     * @author csdeshang
     * @param type $store_id 机构ID
     * @return type 
     */
    public function getStoreOnlineInfoByID($store_id) {
        $store_info = $this->getStoreInfoByID($store_id);
        if (empty($store_info) || $store_info['store_state'] == '0') {
            return array();
        } else {
            return $store_info;
        }
    }
    
    /**
     * 获取机构ID字符串
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return string
     */
    public function getStoreIDString($condition) {
        $condition['store_state'] = 1;
        $store_list = $this->getStoreList($condition);
        $store_id_string = '';
        foreach ($store_list as $value) {
            $store_id_string .= $value['store_id'] . ',';
        }
        return $store_id_string;
    }

    /**
     * 添加机构
     * @access public
     * @author csdeshang
     * @param type $data 机构数据
     * @return type
     */
    public function addStore($data) {
        return db('store')->insertGetId($data);
    }

    /**
     * 编辑机构
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editStore($update, $condition) {
        //清空缓存
        $store_list = $this->getStoreList($condition);
        foreach ($store_list as $value) {
            dcache($value['store_id'], 'store_info');
        }

        return db('store')->where($condition)->update($update);
    }

    /**
     * 删除机构
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delStore($condition) {
        $store_info = $this->getStoreInfo($condition);
        //删除机构相关图片
        @unlink(BASE_UPLOAD_PATH . DS . ATTACH_STORE . DS . $store_info['store_logo']);
        @unlink(BASE_UPLOAD_PATH . DS . ATTACH_STORE . DS . $store_info['store_banner']);
        if (isset($store_info['store_slide'])&&$store_info['store_slide'] != '') {
            foreach (explode(',', $store_info['store_slide']) as $val) {
                @unlink(BASE_UPLOAD_PATH . DS . ATTACH_SLIDE . DS . $val);
            }
        }

        //清空缓存
        dcache($store_info['store_id'], 'store_info');

        return db('store')->where($condition)->delete();
    }

    /**
     * 完全删除机构 包括店主账号、机构的管理员账号、机构相册、机构扩展
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     */
    public function delStoreEntirely($condition) {
        $this->delStore($condition);
        model('seller')->delSeller($condition);
        model('sellergroup')->delSellergroup($condition);
        model('album')->delAlbum($condition['store_id']);
        model('storegoodsclass')->delStoregoodsclass($condition);
        model('storemsg')->delStoremsg($condition);
        model('storenavigation')->delStorenavigation(array('storenav_store_id'=>$condition['store_id']));
        model('storeplate')->delStoreplate($condition);
        model('storereopen')->delStorereopen(array('storereopen_store_id'=>$condition['store_id']));
        model('storewatermark')->delStorewatermark($condition);
    }

    /**
     * 获取商品销售排行(每天更新一次)
     * @access public
     * @author csdeshang
     * @param int $store_id 机构编号
     * @param int $limit 限制数量
     * @return array
     */
    public function getHotSalesList($store_id, $limit = 5) {
        $prefix = 'store_hot_sales_list_' . $limit;
        $hot_sales_list = rcache($store_id, $prefix);
        if (empty($hot_sales_list)) {
            $goods_model = model('goods');
            $hot_sales_list = $goods_model->getGoodsOnlineList(array('store_id' => $store_id), '*', 0, 'goods_salenum desc', $limit);
            $cache = array();
            $cache['hot_sales'] = serialize($hot_sales_list);
            wcache($store_id, $cache, $prefix, 60 * 24);
        } else {
            $hot_sales_list = unserialize($hot_sales_list['hot_sales']);
        }
        return $hot_sales_list;
    }

    /**
     * 获取商品收藏排行(每天更新一次)
     * @access public
     * @author csdeshang
     * @param int $store_id 机构编号
     * @param int $limit 限制数量
     * @return array	商品信息
     */
    public function getHotCollectList($store_id, $limit = 5) {
        $prefix = 'store_collect_sales_list_' . $limit;
        $hot_collect_list = rcache($store_id, $prefix);
        if (empty($hot_collect_list)) {
            $goods_model = model('goods');
            $hot_collect_list = $goods_model->getGoodsOnlineList(array('store_id' => $store_id), '*', 0, 'goods_collect desc', $limit);
            $cache = array();
            $cache['collect_sales'] = serialize($hot_collect_list);
            wcache($store_id, $cache, $prefix, 60 * 24);
        } else {
            $hot_collect_list = unserialize($hot_collect_list['collect_sales']);
        }
        return $hot_collect_list;
    }

    /**
     * 获取机构列表页附加信息
     * @access public
     * @author csdeshang
     * @param array $store_array 机构数组
     * @return array 包含近期销量和8个推荐商品的机构数组
     */
    public function getStoreSearchList($store_array) {
        $store_array_new = array();
        if (!empty($store_array)) {
            $no_cache_store = array();
            foreach ($store_array as $value) {
                //$store_search_info = rcache($value['store_id']);
                //print_r($store_array);exit();
                //if($store_search_info !== FALSE) {
                //	$store_array_new[$value['store_id']] = $store_search_info;
                //} else {
                //	$no_cache_store[$value['store_id']] = $value;
                //}
                $no_cache_store[$value['store_id']] = $value;
            }
            if (!empty($no_cache_store)) {
                //获取机构商品数
                $no_cache_store = $this->getStoreInfoBasic($no_cache_store);
                //获取机构推荐商品
                $no_cache_store = $this->getGoodsListBySales($no_cache_store);
                //写入缓存
                foreach ($no_cache_store as $value) {
                    wcache($value['store_id'], $value, 'store_search_info');
                }
                $store_array_new = array_merge($store_array_new, $no_cache_store);
            }
        }
        return $store_array_new;
    }

    /**
     * 获得机构标志、信用、商品数量、机构评分等信息
     * @access public
     * @author csdeshang
     * @param type $list 机构数组
     * @param type $day  天数
     * @return type
     */
    public function getStoreInfoBasic($list, $day = 0) {
        $list_new = array();
        if (!empty($list) && is_array($list)) {
            foreach ($list as $key => $value) {
                if (!empty($value)) {
                    $value['store_logo'] = get_store_logo($value['store_logo']);
                    //机构评价
                    $evaluatestore_model = model('evaluatestore');
                    $store_evaluate_info = $evaluatestore_model->getEvaluatestoreInfoByStoreID($value['store_id'], $value['storeclass_id']);
                    $value = array_merge($value, $store_evaluate_info);

                    if (!empty($value['store_presales']))
                        $value['store_presales'] = unserialize($value['store_presales']);
                    if (!empty($value['store_aftersales']))
                        $value['store_aftersales'] = unserialize($value['store_aftersales']);
                    $list_new[$value['store_id']] = $value;
                    $list_new[$value['store_id']]['goods_count'] = 0;
                }
            }
            //全部商品数直接读取缓存
            if ($day > 0) {
                $store_id_string = implode(',', array_keys($list_new));
                //指定天数直接查询数据库
                $condition = array();
                $condition['goods_show'] = '1';
                $condition['store_id'] = array('in', $store_id_string);
                $condition['goods_addtime'] = array('gt', strtotime("-{$day} day"));
                $goods_count_array = db('goods')->field('store_id,count(*) as goods_count')->where($condition)->group('store_id')->select();
                if (!empty($goods_count_array)) {
                    foreach ($goods_count_array as $value) {
                        $list_new[$value['store_id']]['goods_count'] = $value['goods_count'];
                    }
                }
            } else {
                $list_new = $this->getGoodsCountByStoreArray($list_new);
            }
        }
        return $list_new;
    }

    /**
     * 获取机构商品数
     * @access public
     * @author csdeshang
     * @param type $store_array 机构数组
     * @return type
     */
    public function getGoodsCountByStoreArray($store_array) {
        $store_array_new = array();
        $no_cache_store = '';

        foreach ($store_array as $value) {
            $goods_count = rcache($value['store_id'], 'store_goods_count');

            if (!empty($goods_count) && $goods_count !== FALSE) {
                //有缓存的直接赋值
                $value['goods_count'] = $goods_count;
            } else {
                //没有缓存记录store_id，统计从数据库读取
                $no_cache_store .= $value['store_id'] . ',';
                $value['goods_count'] = '0';
            }
            $store_array_new[$value['store_id']] = $value;
        }

        if (!empty($no_cache_store)) {

            //从数据库读取机构商品数赋值并缓存
            $no_cache_store = rtrim($no_cache_store, ',');
            $condition = array();
            $condition['goods_state'] = '1';
            $condition['store_id'] = array('in', $no_cache_store);
            $goods_count_array = db('goods')->field('store_id,count(*) as goods_count')->where($condition)->group('store_id')->select();
            if (!empty($goods_count_array)) {
                foreach ($goods_count_array as $value) {
                    $store_array_new[$value['store_id']]['goods_count'] = $value['goods_count'];
                    wcache($value['store_id'], $value['goods_count'], 'store_goods_count');
                }
            }
        }
        return $store_array_new;
    }


    /**
     * 获取机构8个销量最高商品
     * @access public
     * @author csdeshang
     * @param type $store_array 机构数组
     * @return type
     */
    private function getGoodsListBySales($store_array) {
        $field = 'goods_id,store_id,goods_name,goods_image,goods_price,goods_salenum';
        foreach ($store_array as $value) {
            $store_array[$value['store_id']]['search_list_goods'] = db('goods')->field($field)->where(array('store_id' => $value['store_id'], 'goods_state' => 1))->order('goods_salenum desc')->limit(8)->select();
        }
        return $store_array;
    }
    /**
     * 编辑商品
     * @param type $condition
     * @param type $data
     * @return type
     */
    public function editGoods($condition,$data){
        return db('goods')->where($condition)->update($data);
    }
    /**
     * 获取单个机构
     * @param type $condition
     * @param type $field
     * @return type
     */
    public function getOneStore($condition,$field){
        return db('store')->field($field)->where($condition)->find();
    }

}
