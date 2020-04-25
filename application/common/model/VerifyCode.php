<?php

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
class  VerifyCode extends Model {

    public $page_info;

    /**
     * 获取验证码列表
     * @access public
     * @author csdeshang
     * @param type $condition
     * @param type $pagesize
     * @param type $order
     * @return type
     */
    public function getVerifyCodeList($condition, $pagesize = '', $order = 'verify_code_id desc') {
        if ($pagesize) {
            $result = db('VerifyCode')->where($condition)->order($order)->paginate($pagesize, false, ['query' => request()->param()]);
            $this->page_info = $result;
            return $result->items();
        } else {
            return db('VerifyCode')->where($condition)->order($order)->limit(10)->select();
        }
    }

    /**
     * 取得验证码信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @param string $order 排序
     * @return array
     */
    public function getVerifyCodeInfo($condition = array(), $fields = '*') {
        return db('VerifyCode')->where($condition)->field($fields)->order('verify_code_id desc')->find();
    }

    /**
     * 添加验证码信息
     * @access public
     * @author csdeshang  
     * @param array $data 参数数据
     * @return type
     */
    public function addVerifyCode($data) {
        return db('VerifyCode')->insertGetId($data);
    }

    /**
     * 编辑验证码信息
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editVerifyCode($data, $condition = array()) {
        return db('VerifyCode')->where($condition)->update($data);
    }

    /**
     * 获取验证码数量
     * @access public
     * @author csdeshang 
     * @param array $condition 条件
     * @return bool
     */
    public function getVerifyCodeCount($condition = array()) {
        return db('VerifyCode')->where($condition)->count();
    }

    /*
     * 发送频率
     * @param int $verify_code_type 验证码类型
     * @param int $verify_code_user_type 用户类型
     * @return array
     */

    public function isVerifyCodeFrequant($verify_code_type, $verify_code_user_type) {
        $ip = request()->ip();
        if ($this->getVerifyCodeCount(array('verify_code_ip' => $ip, 'verify_code_type' => $verify_code_type, 'verify_code_user_type' => $verify_code_user_type, 'verify_code_add_time' => array('>', TIMESTAMP - 60)))) {
            return ds_callback(false, '请60秒以后再发');
        }
        if ($this->getVerifyCodeCount(array('verify_code_ip' => $ip, 'verify_code_type' => $verify_code_type, 'verify_code_user_type' => $verify_code_user_type, 'verify_code_add_time' => array('>', strtotime(date('Y-m-d 0:0:0'))))) > 15) {
            return ds_callback(false, '今天验证码已超15条，不能再发送');
        }
        return ds_callback(true);
    }

    /*
     * 生成验证码
     * @param int $verify_code_type 验证码类型
     * @param int $verify_code_user_type 用户类型
     * @return array
     */

    public function genVerifyCode($verify_code_type, $verify_code_user_type) {
        $verify_code = str_pad(strval(rand(0, 999999)), 6, '0', STR_PAD_LEFT);
        $i = 0;
        while ($i < 100 && $this->getVerifyCodeCount(array('verify_code' => $verify_code, 'verify_code_type' => $verify_code_type, 'verify_code_user_type' => $verify_code_user_type, 'verify_code_add_time' => array('>', TIMESTAMP - VERIFY_CODE_INVALIDE_MINUTE * 60)))) {
            $verify_code = str_pad(strval(rand(0, 999999)), 6, '0', STR_PAD_LEFT);
            $i++;
        }
        if ($i < 100) {
            return $verify_code;
        }
        return false;
    }

}

?>
