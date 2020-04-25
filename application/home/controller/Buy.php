<?php

namespace app\home\controller;

use think\Lang;
/**
 * ============================================================================
 *在线教育培训付费视频管理系统
 * ============================================================================
 * 版权所有 重庆师范大学计算机科学与技术杨玉印，并保留所有权利。
 * 网站地址: http://yyu.loveli.top

 * ============================================================================
 * 控制器
 */
class  Buy extends BaseMember {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/buy.lang.php');
        //验证该会员是否禁止购买
        if(!session('is_buy')){
            $this->error(lang('cart_buy_noallow'));
        }
        if(config('member_auth') && $this->member_info['member_auth_state']!=3){
            $this->error(lang('cart_buy_noauth'),url('MemberAuth/index'));
        }
    }

    public function buy_step1() {
        if(empty(input('post.'))){
            $this->error(lang('param_error'));
        }
        //虚拟商品购买分流
        $this->_buy_branch(input('post.'));
    }

    /**
     * 得到所购买的id和数量
     *
     */
    private function _parseItems($cart_id) {
        //存放所购商品ID和数量组成的键值对
        $buy_items = array();
        if (is_array($cart_id)) {
            foreach ($cart_id as $value) {
                if (preg_match_all('/^(\d{1,10})\|(\d{1,6})$/', $value, $match)) {
                    $buy_items[$match[1][0]] = $match[2][0];
                }
            }
        }
        return $buy_items;
    }
    /**
     * 购买分流
     */
    private function _buy_branch($post) {
        if (!isset($post['ifcart'])) {
            //取得购买商品信息
            $buy_items = $this->_parseItems($post['cart_id']);
            $goods_id = key($buy_items);
            $quantity = current($buy_items);

            $goods_info = model('goods')->getGoodsOnlineInfoAndPromotionById($goods_id);
                $this->redirect('Buyvirtual/buy_step1',['goods_id'=>$goods_id,'quantity'=>$quantity]);
        }
    }
    
    
    /**
     * 预存款充值下单时支付页面
     */
    public function pd_pay() {
        $pay_sn = input('param.pay_sn');
        if (!preg_match('/^\d{20}$/', $pay_sn)) {
            $this->error(lang('param_error'), url('Predeposit/index'));
        }

        //查询支付单信息
        $predeposit_model = model('predeposit');
        $pd_info = $predeposit_model->getPdRechargeInfo(array('pdr_sn' => $pay_sn, 'pdr_member_id' => session('member_id')));
      
        // dump($pd_info);
        // die;
        if (empty($pd_info)) {
            $this->error(lang('param_error'));
        }
        if (intval($pd_info['pdr_payment_state'])) {
            $this->error(lang('not_repeat_payment'), url('Predeposit/index'));
        }
        $this->assign('pdr_info', $pd_info);

        //显示支付接口列表
        $payment_model = model('payment');
        $condition = array();
        $condition['payment_code'] = array('not in', array('offline', 'predeposit'));
        $condition['payment_state'] = 1;
        $condition['payment_platform'] = 'pc';
        $payment_list = $payment_model->getPaymentList($condition);
        // dump($payment_list);
        // dump(123);
        // die;
       
        if (empty($payment_list)) {
            $this->error(lang('appropriate_payment_method'), url('Predeposit/index'));
        }
        $this->assign('payment_list', $payment_list);
//         dump($payment_list);
//         dump(123);
// die;
        //标识 购买流程执行第几步
        $this->assign('buy_step', 'step3');
    //  dump( $this->assign('buy_step', 'step3'));
    //  die;
        return $this->fetch($this->template_dir.'predeposit_pay');
    }

}