<?php

/*
 * 卖家相关控制中心
 */

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
class  BaseSeller extends BaseMall
{

    //机构信息
    protected $store_info = array();

    public function _initialize()
    {
        parent::_initialize();
        Lang::load(APP_PATH . 'home/lang/' . config('default_lang') . '/basemember.lang.php');
        Lang::load(APP_PATH . 'home/lang/' . config('default_lang') . '/baseseller.lang.php');
        //卖家中心模板路径
        $this->template_dir = 'default/seller/' . strtolower(request()->controller()) . '/';
        if (request()->controller() != 'Sellerlogin') {
            if (!session('member_id')) {
                $this->redirect('Home/Sellerlogin/login');
            }
            if (!session('seller_id')) {
                $this->redirect('Home/Sellerlogin/login');
            }

            // 验证机构是否存在
            $store_model = model('store');
            $this->store_info = $store_model->getStoreInfoByID(session('store_id'));
            if (empty($this->store_info)) {
                $this->redirect('Home/Sellerlogin/login');
            }

            // 机构关闭标志
            if (intval($this->store_info['store_state']) === 0) {
                $this->assign('store_closed', true);
                $this->assign('store_close_info', $this->store_info['store_close_info']);
            }

            // 机构等级
            if (session('is_platform_store')) {
                $this->store_grade = array(
                    'storegrade_id' => '0',
                    'storegrade_name' => lang('exclusive_grade_stores'),
                    'storegrade_goods_limit' => '0',
                    'storegrade_album_limit' => '0',
                    'storegrade_space_limit' => '999999999',
                    'storegrade_template_number' => '6',
                    // 'storegrade_template' => 'default|style1|style2|style3|style4|style5',
                    'storegrade_price' => '0.00',
                    'storegrade_description' => '',
                    'storegrade_function' => 'editor_multimedia',
                    'storegrade_sort' => '255',
                );
            } else {
                $store_grade = rkcache('storegrade', true);
                $this->store_grade = @$store_grade[$this->store_info['grade_id']];
            }
            if (session('seller_is_admin') !== 1 && request()->controller() !== 'Seller' && request()->controller() !== 'Sellerlogin') {
                if (!in_array(request()->controller(), session('seller_limits'))) {
                    $this->error(lang('have_no_legalpower'), 'Seller/index');
                }
            }
        }
    }

    /**
     * 记录卖家日志
     *
     * @param $content 日志内容
     * @param $state 1成功 0失败
     */
    protected function recordSellerlog($content = '', $state = 1)
    {
        $seller_info = array();
        $seller_info['sellerlog_content'] = $content;
        $seller_info['sellerlog_time'] = TIMESTAMP;
        $seller_info['sellerlog_seller_id'] = session('seller_id');
        $seller_info['sellerlog_seller_name'] = session('seller_name');
        $seller_info['sellerlog_store_id'] = session('store_id');
        $seller_info['sellerlog_seller_ip'] = request()->ip();
        $seller_info['sellerlog_url'] = request()->module() . '/' . request()->controller() . '/' . request()->action();
        $seller_info['sellerlog_state'] = $state;
        $sellerlog_model = model('sellerlog');
        $sellerlog_model->addSellerlog($seller_info);
    }

    /**
     * 记录机构费用
     *
     * @param $storecost_price 费用金额
     * @param $storecost_remark 费用备注
     */
    protected function recordStorecost($storecost_price, $storecost_remark)
    {
        // 平台机构不记录机构费用
        if (check_platform_store()) {
            return false;
        }
        $storecost_model = model('storecost');
        $param = array();
        $param['storecost_store_id'] = session('store_id');
        $param['storecost_seller_id'] = session('seller_id');
        $param['storecost_price'] = $storecost_price;
        $param['storecost_remark'] = $storecost_remark;
        $param['storecost_state'] = 0;
        $param['storecost_time'] = TIMESTAMP;
        $storecost_model->addStorecost($param);

        // 发送机构消息
        $param = array();
        $param['code'] = 'store_cost';
        $param['store_id'] = session('store_id');
        $param['param'] = array(
            'price' => $storecost_price,
            'seller_name' => session('seller_name'),
            'remark' => $storecost_remark
        );
        //微信模板消息
                $param['weixin_param'] = array(
                    'url' => config('h5_site_url').'/seller/cost_list',
                    'data'=>array(
                        "keyword1" => array(
                            "value" => $storecost_price,
                            "color" => "#333"
                        ),
                        "keyword2" => array(
                            "value" => date('Y-m-d H:i'),
                            "color" => "#333"
                        )
                    ),
                );
        \mall\queue\QueueClient::push('sendStoremsg', $param);
    }

    /**
     * 添加到任务队列
     *
     * @param array $goods_array
     * @param boolean $ifdel 是否删除以原记录
     */
    protected function addcron($data = array(), $ifdel = false)
    {
        $cron_model = model('cron');
        if (isset($data[0])) { // 批量插入
            $where = array();
            foreach ($data as $k => $v) {
                if (isset($v['content'])) {
                    $data[$k]['content'] = serialize($v['content']);
                }
                // 删除原纪录条件
                if ($ifdel) {
                    $where[] = '(type = ' . $data['type'] . ' and exeid = ' . $data['exeid'] . ')';
                }
            }
            // 删除原纪录
            if ($ifdel) {
                $cron_model->delCron(implode(',', $where));
            }
            $cron_model->addCronAll($data);
        } else { // 单条插入
            if (isset($data['content'])) {
                $data['content'] = serialize($data['content']);
            }
            // 删除原纪录
            if ($ifdel) {
                $cron_model->delCron(array('type' => $data['type'], 'exeid' => $data['exeid']));
            }
            $cron_model->addCron($data);
        }
    }

    /**
     *    当前选中的栏目
     */
    protected function setSellerCurItem($curitem = '')
    {
        $this->assign('seller_item', $this->getSellerItemList());
        $this->assign('curitem', $curitem);
    }

    /**
     *    当前选中的子菜单
     */
    protected function setSellerCurMenu($cursubmenu = '')
    {
        $seller_menu = $this->getSellerMenuList();
        $this->assign('seller_menu', $seller_menu);
        $curmenu = '';
        foreach ($seller_menu as $key => $menu) {
            foreach ($menu['submenu'] as $subkey => $submenu) {
                if ($submenu['name'] == $cursubmenu) {
                    $curmenu = $menu['name'];
                }
            }
        }
        //当前一级菜单
        $this->assign('curmenu', $curmenu);
        //当前二级菜单
        $this->assign('cursubmenu', $cursubmenu);
    }

    /*
     * 获取卖家栏目列表,针对控制器下的栏目
     */

    protected function getSellerItemList()
    {
        return array();
    }

    /*
     * 获取卖家菜单列表
     */

    private function getSellerMenuList()
    {
        //controller  注意第一个字母要大写
        $menu_list = array(
            'sellergoods' =>
                array(
                    'ico'=>'&#xe732;',
                    'name' => 'sellergoods',
                    'text' => lang('site_search_goods'),
                    'url' => url('Sellergoodsonline/index'),
                    'submenu' => array(
                        array('name' => 'sellergoodsadd', 'text' => lang('goods_released'), 'controller' => 'Sellergoodsadd', 'url' => url('Sellergoodsadd/index'),),
                        array('name' => 'sellergoodsonline', 'text' => lang('goods_on_sale'), 'controller' => 'Sellergoodsonline', 'url' => url('Sellergoodsonline/index'),),
                        array('name' => 'sellergoodsoffline', 'text' => lang('warehouse_goods'), 'controller' => 'Sellergoodsoffline', 'url' => url('Sellergoodsoffline/index'),),
                        // array('name' => 'sellerplate', 'text' => lang('associated_format'), 'controller' => 'Sellerplate', 'url' => url('Sellerplate/index'),),
                        // array('name' => 'selleralbum', 'text' => lang('image_space'), 'controller' => 'Selleralbum', 'url' => url('Selleralbum/index'),),
                        array('name' => 'sellervideo', 'text' => lang('sellervideo'), 'controller' => 'Sellervideo', 'url' => url('sellervideo/index'),),
                    )
                ),
            'sellervrorder' =>
                array(
                    'ico'=>'&#xe71f;',
                    'name' => 'sellervrorder',
                    'text' => lang('pointsorderdesc_1'),
                    'url' => url('sellervrorder/index'),
                    'submenu' => array(
                        array('name' => 'sellervrorder', 'text' => lang('code_order'), 'controller' => 'Sellervrorder', 'url' => url('Sellervrorder/index'),),
                        array('name' => 'sellerevaluate', 'text' => lang('evaluation_management'), 'controller' => 'Sellerevaluate', 'url' => url('Sellerevaluate/index'),),
                        // array('name' => 'Sellerbill', 'text' => lang('physical_settlement'), 'controller' => 'Sellerbill', 'url' => url('Sellerbill/index'),),
                    )
                ),
            'seller' =>
                array(
                    'ico'=>'&#xe663;',
                    'name' => 'seller',
                    'text' => lang('site_search_store'),
                    'url' => url('Seller/index'),
                    'submenu' => array(
                        array('name' => 'seller_index', 'text' => lang('store_overview'), 'controller' => 'Seller', 'url' => url('Seller/index'),),
                        array('name' => 'seller_setting', 'text' => lang('store_setup'), 'controller' => 'Sellersetting', 'url' => url('Sellersetting/setting'),),
                        // array('name' => 'seller_navigation', 'text' => lang('store_navigation'), 'controller' => 'Sellernavigation', 'url' => url('Sellernavigation/index'),),
                        array('name' => 'sellerinfo', 'text' => lang('store_information'), 'controller' => 'Sellerinfo', 'url' => url('Sellerinfo/index'),),
                        array('name' => 'sellergoodsclass', 'text' => lang('store_classification'), 'controller' => 'Sellergoodsclass', 'url' => url('Sellergoodsclass/index'),),
                    )
                ),
            'sellerconsult' =>
                array(
                    'ico'=>'&#xe6ab;',
                    'name' => 'sellerconsult',
                    'text' => lang('after_sales_service'),
                    'url' => url('Sellerconsult/index'),
                    'submenu' => array(
                        array('name' => 'seller_consult', 'text' => lang('consulting_management'), 'controller' => 'Sellerconsult', 'url' => url('Sellerconsult/index'),),
                    )
                ),
                'sellercallcenter' =>
                array(
                    'ico'=>'&#xe61c;',
                    'name' => 'sellercallcenter',
                    'text' => lang('news_service'),
                    'url' => url('Sellercallcenter/index'),
                    'submenu' => array(
                        array('name' => 'Sellercallcenter', 'text' => lang('setting_service'), 'controller' => 'Sellercallcenter', 'url' => url('Sellercallcenter/index'),),
                        array('name' => 'Sellermsg', 'text' => lang('system_message'), 'controller' => 'Sellermsg', 'url' => url('Sellermsg/index'),),
                    )
                ),
        );
        if(!$this->store_info['is_platform_store']){
            $menu_list['seller']['submenu']=array_merge(array(array('name' => 'seller_money', 'text' => lang('store_money'), 'controller' => 'Sellermoney', 'url' => url('Sellermoney/index'),),array('name' => 'seller_deposit', 'text' => lang('store_deposit'), 'controller' => 'Sellerdeposit', 'url' => url('Sellerdeposit/index'),)),$menu_list['seller']['submenu']);
        }
        return $menu_list;
    }
}

?>
