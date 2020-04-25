<?php
/**
 *    代金券
 */
namespace app\home\controller;
use think\Lang;/**
 * ============================================================================
 *在线教育培训付费视频管理系统
 * ============================================================================
 * 版权所有 重庆师范大学计算机科学与技术杨玉印，并保留所有权利。
 * 网站地址: http://yyu.loveli.top

 * ============================================================================
 * 控制器
 */
class  Membervoucher extends BaseMember
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH.'home/lang/'.config('default_lang').'/member_voucher.lang.php');
        //判断系统是否开启代金券功能
        if (intval(config('voucher_allow')) !== 1){
            $this->error(lang('voucher_unavailable'));
        }
    }
    /*
	 * 默认显示代金券模版列表
	 */
    public function index() {
        $voucher_model = model('voucher');
        $voucher_list = $voucher_model->getMemberVoucherList(session('member_id'), input('param.select_detail_state'), 10);

        //取已经使用过并且未有voucher_order_id的代金券的订单ID
        $used_voucher_code = array();
        $voucher_order = array();
        if (!empty($voucher_list)) {
            foreach ($voucher_list as $v) {
                if ($v['voucher_state'] == 2 && empty($v['voucher_order_id'])) {
                    $used_voucher_code[] = $v['voucher_code'];
                }
            }
        }

        $this->assign('voucher_list', $voucher_list);
        $this->assign('voucherstate_arr', $voucher_model->getVoucherStateArray());
        $this->assign('show_page',$voucher_model->page_info->render()) ;
        $this->setMemberCurItem('voucher_list');
        $this->setMemberCurMenu('member_voucher');
        return $this->fetch($this->template_dir.'member_voucher_list');
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string	$menu_type	导航类型
     * @param string 	$menu_key	当前导航的menu_key
     * @param array 	$array		附加菜单
     * @return
     */
    protected function getMemberItemList()
    {
       $menu_array=array(
           array(
               'name'=>'voucher_list','text'=>lang('ds_myvoucher'),'url'=>url('Membervoucher/index')
           )
       );
       return $menu_array;
    }


}