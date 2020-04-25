<?php

namespace app\admin\controller;

use think\Controller;
/**
 * ============================================================================
 *在线教育培训付费视频管理系统
 * ============================================================================
 * 版权所有 重庆师范大学计算机科学与技术杨玉印，并保留所有权利。
 * 网站地址: http://yyu.loveli.top

 * ============================================================================
 * 控制器
 */
class  AdminControl extends Controller {

    /**
     * 管理员资料 name id group
     */
    protected $admin_info;
    protected $permission;

    public function _initialize() {
        $this->admin_info = $this->systemLogin();
        $config_list = rkcache('config', true);
        config($config_list);
        if ($this->admin_info['admin_id'] != 1) {
            // 验证权限
            $this->checkPermission();
        }
        $this->setMenuList();
    }

    /**
     * 取得当前管理员信息
     *
     * @param
     * @return 数组类型的返回结果
     */
    protected final function getAdminInfo() {
        return $this->admin_info;
    }

    /**
     * 系统后台登录验证
     *
     * @param
     * @return array 数组类型的返回结果
     */
    protected final function systemLogin() {
        $admin_info = array(
            'admin_id' => session('admin_id'),
            'admin_name' => session('admin_name'),
            'admin_gid' => session('admin_gid'),
            'admin_is_super' => session('admin_is_super'),
        );
        if (empty($admin_info['admin_id']) || empty($admin_info['admin_name']) || !isset($admin_info['admin_gid']) || !isset($admin_info['admin_is_super'])) {
            session(null);
            $this->redirect('Admin/Login/index');
        }

        return $admin_info;
    }

    public function setMenuList() {
        $menu_list = $this->menuList();

        $menu_list = $this->parseMenu($menu_list);
        $this->assign('menu_list', $menu_list);
    }

    /**
     * 验证当前管理员权限是否可以进行操作
     *
     * @param string $link_nav
     * @return
     */
    protected final function checkPermission($link_nav = null) {
        if ($this->admin_info['admin_is_super'] == 1)
            return true;

        $controller = request()->controller();
        $action = request()->action();
        if (empty($this->permission)) {

            $admin_model = model('admin');
            $gadmin = $admin_model->getOneGadmin(array('gid' => $this->admin_info['admin_gid']));

            $permission = ds_decrypt($gadmin['glimits'], MD5_KEY . md5($gadmin['gname']));
            $this->permission = $permission = explode('|', $permission);
        } else {
            $permission = $this->permission;
        }
        //显示隐藏小导航，成功与否都直接返回
        if (is_array($link_nav)) {
            if (!in_array("{$link_nav['controller']}.{$link_nav['action']}", $permission) && !in_array($link_nav['controller'], $permission)) {
                return false;
            } else {
                return true;
            }
        }
        //以下几项不需要验证
        $tmp = array('Index', 'Dashboard', 'Login');
        if (in_array($controller, $tmp)) {
            return true;
        }
        if (in_array($controller, $permission) || in_array("$controller.$action", $permission)) {
            return true;
        } else {
            $extlimit = array('ajax', 'export_step1');
            if (in_array($action, $extlimit) && (in_array($controller, $permission) || strpos(serialize($permission), '"' . $controller . '.'))) {
                return true;
            }
            //带前缀的都通过
            foreach ($permission as $v) {
                if (!empty($v) && strpos("$controller.$action", $v . '_') !== false) {
                    return true;
                    break;
                }
            }
        }
        $this->error(lang('ds_assign_right'), 'Dashboard/welcome');
    }

    /**
     * 过滤掉无权查看的菜单
     *
     * @param array $menu
     * @return array
     */
    private final function parseMenu($menu = array()) {
        if ($this->admin_info['admin_is_super'] == 1) {
            return $menu;
        }
        foreach ($menu as $k => $v) {
            foreach ($v['children'] as $ck => $cv) {
                $tmp = explode(',', $cv['args']);
                //以下几项不需要验证
                $except = array('Index', 'Dashboard', 'Login');
                if (in_array($tmp[1], $except))
                    continue;
                if (!in_array($tmp[1], array_values($this->permission)) && !in_array($tmp[1].'.'.$tmp[0], array_values($this->permission))) {
                    unset($menu[$k]['children'][$ck]);
                }
            }
            if (empty($menu[$k]['children'])) {
                unset($menu[$k]);
                unset($menu[$k]['children']);
            }
        }
        return $menu;
    }

    /**
     * 记录系统日志
     *
     * @param $lang 日志语言包
     * @param $state 1成功0失败null不出现成功失败提示
     * @param $admin_name
     * @param $admin_id
     */
    protected final function log($lang = '', $state = 1, $admin_name = '', $admin_id = 0) {
        if ($admin_name == '') {
            $admin_name = session('admin_name');
            $admin_id = session('admin_id');
        }
        $data = array();
        if (is_null($state)) {
            $state = null;
        } else {
            $state = $state ? '' : lang('ds_fail');
        }
        $data['adminlog_content'] = $lang . $state;
        $data['adminlog_time'] = TIMESTAMP;
        $data['admin_name'] = $admin_name;
        $data['admin_id'] = $admin_id;
        $data['adminlog_ip'] = request()->ip();
        $data['adminlog_url'] = request()->controller() . '&' . request()->action();

        $adminlog_model = model('adminlog');
        return $adminlog_model->addAdminlog($data);
    }

    /**
     * 添加到任务队列
     *
     * @param array $goods_array
     * @param boolean $ifdel 是否删除以原记录
     */
    protected function addcron($data = array(), $ifdel = false) {
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
     * 当前选中的栏目
     */
    protected function setAdminCurItem($curitem = '') {
        $this->assign('admin_item', $this->getAdminItemList());
        $this->assign('curitem', $curitem);
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        return array();
    }

    /*
     * 侧边栏列表
     */

    function menuList() {
        return array(
            'dashboard' => array(
                'name' => 'dashboard',
                'text' => lang('ds_dashboard'),
                'show' => TRUE,
                'children' => array(
                    'welcome' => array(
                        'ico' => "&#xe70b;",
                        'text' => lang('ds_welcome'),
                        'args' => 'welcome,Dashboard,dashboard',
                    ),
                    
                    // 'config' => array(
                    //     'ico' => '&#xe6e0;',
                    //     'text' => lang('ds_base'),
                    //     'args' => 'base,Config,dashboard',
                    // ),
                    // 'member' => array(
                    //     'ico' => '&#xe667;',
                    //     'text' => lang('ds_member_manage'),
                    //     'args' => 'member,Member,dashboard',
                    // ),
                    'aboutus' => array(
                        'text' => lang('ds_aboutus'),
                        'args' => 'aboutus,dashboard,dashboard',
                        ),
                ),
            ),
            'setting' => array(
                'name' => 'setting',
                'text' => lang('ds_setting'),
                'show' => TRUE,
                'children' => array(
                                
                    'message' => array(
                        'ico' => '&#xe71b;',
                        'text' => lang('ds_message'),
                        'args' => 'seller_tpl,Message,setting',
                    ),
                   
                    'config' => array(
                        'ico' => '&#xe6e0;',
                        'text' => lang('ds_base'),
                        'args' => 'base,Config,setting',
                    ), 
                   
                    'admin' => array(
                        'ico' => '&#xe67b;',
                        'text' => lang('ds_admin'),
                        'args' => 'admin,Admin,setting',
                    ),
              
                    'payment' => array(
                        'ico' => '&#xe74d;',
                        'text' => lang('ds_payment'),
                        'args' => 'index,Payment,setting',
                    ),
                    'admin_log' => array(
                        'ico' => '&#xe71f;',
                        'text' => lang('ds_adminlog'),
                        'args' => 'loglist,Adminlog,setting',
                    ),
                ),
            ),
            'member' => array(
                'name' => 'member',
                'text' => lang('ds_member'),
                'show' => TRUE,
                'children' => array(
                    'member' => array(
                        'ico' => '&#xe667;',
                        'text' => lang('ds_member_manage'),
                        'args' => 'member,Member,member',
                    ),
                    // 'member_auth' => array(
                    //     'ico'=>'&#xe6ea;',
                    //     'text' => lang('member_auth'),
                    //     'args' => 'index,member_auth,member',
                    // ),
                    'membergrade' => array(
                        'ico' => '&#xe6a3;',
                        'text' => lang('ds_membergrade'),
                        'args' => 'index,Membergrade,member',
                    ),
                    'exppoints' => array(
                        'ico' => '&#xe727;',
                        'text' => lang('ds_exppoints'),
                        'args' => 'index,Exppoints,member',
                    ),
                    'notice' => array(
                        'ico' => '&#xe71b;',
                        'text' => lang('ds_notice'),
                        'args' => 'index,Notice,member',
                    ),
                    'points' => array(
                        'ico' => '&#xe6f5;',
                        'text' => lang('ds_points'),
                        'args' => 'index,Points,member',
                    ),
                    'predeposit' => array(
                        'ico' => '&#xe6e2;',
                        'text' => lang('ds_predeposit'),
                        'args' => 'pdrecharge_list,Predeposit,member',
                    ),
                 
                ),
            ),
            'goods' => array(
                'name' => 'goods',
                'text' => lang('ds_goods'),
                'show' => TRUE,
                'children' => array(
                    'Goods' => array(
                        'ico' => '&#xe732;',
                        'text' => lang('ds_goods_manage'),
                        'args' => 'index,Goods,goods',
                    ),
                    'goodsclass' => array(
                        'ico' => '&#xe652;',
                        'text' => lang('ds_goodsclass'),
                        'args' => 'goods_class,Goodsclass,goods',
                    ),
                   
                    'album' => array(
                        'ico' => '&#xe72a;',
                        'text' => lang('ds_album'),
                        'args' => 'index,Goodsalbum,goods',
                    ),
                ),
            ),
            'store' => array(
                'name' => 'store',
                'text' => lang('ds_store'),
                'show' => TRUE,
                'children' => array(
                    'Store' => array(
                        'ico' => '&#xe6ec;',
                        'text' => lang('ds_store_manage'),
                        'args' => 'store,Store,store',
                    ),
                
                    'Storedeposit' => array(
                        'ico' => '&#xe72b;',
                        'text' => lang('ds_store_deposit'),
                        'args' => 'index,Storedeposit,store',
                    ),
                    'Storegrade' => array(
                        'ico' => '&#xe6a3;',
                        'text' => lang('ds_storegrade'),
                        'args' => 'index,Storegrade,store',
                    ),
                    'Storeclass' => array(
                        'ico' => '&#xe652;',
                        'text' => lang('ds_storeclass'),
                        'args' => 'store_class,Storeclass,store',
                    ),
                    'Storehelp' => array(
                        'ico' => '&#xe6b4;',
                        'text' => lang('ds_Storehelp'),
                        'args' => 'index,Storehelp,store',
                    ),
                    // 'Storejoin' => array(
                    //     'ico' => '&#xe6ff;',
                    //     'text' => lang('ds_storejoin'),
                    //     'args' => 'index,Storejoin,store',
                    // ),
                    'Ownshop' => array(
                        'ico' => '&#xe6ec;',
                        'text' => lang('ds_ownshop'),
                        'args' => 'index,Ownshop,store',
                    ),
                ),
            ),
            'trade' => array(
                'name' => 'trade',
                'text' => lang('ds_trade'),
                'show' => TRUE,
                'children' => array(
                    'vrorder' => array(
                        'ico' => '&#xe71f;',
                        'text' => lang('ds_vrorder'),
                        'args' => 'index,Vrorder,trade',
                    ),
                    // 'vrrefund' => array(
                    //     'ico' => '&#xe6f3;',
                    //     'text' => lang('ds_vrrefund'),
                    //     'args' => 'refund_manage,Vrrefund,trade',
                    // ),
                    // 'Bill' => array(
                    //     'ico' => '&#xe69c;',
                    //     'text' => lang('ds_bill_manage'),
                    //     'args' => 'show_statis,Bill,trade',
                    // ),
                    'consulting' => array(
                        'ico' => '&#xe71c;',
                        'text' => lang('ds_consulting'),
                        'args' => 'Consulting,Consulting,trade',
                    ),
                    'inform' => array(
                        'ico' => '&#xe70c;',
                        'text' => lang('ds_inform'),
                        'args' => 'inform_list,Inform,trade',
                    ),
                    'evaluate' => array(
                        'ico' => '&#xe6f2;',
                        'text' => lang('ds_evaluate'),
                        'args' => 'evalgoods_list,Evaluate,trade',
                    ),
                ),
            ),
            'website' => array(
                'name' => 'website',
                'text' => lang('ds_website'),
                'show' => TRUE,
                'children' => array(
                    'Articleclass' => array(
                        'ico' => '&#xe652;',
                        'text' => lang('ds_articleclass'),
                        'args' => 'index,Articleclass,website',
                    ),
                    'Article' => array(
                        'ico' => '&#xe72a;',
                        'text' => lang('ds_article'),
                        'args' => 'index,Article,website',
                    ),
                    'Document' => array(
                        'text' => lang('ds_document'),
                        'args' => 'index,Document,website',
                    ),
                    'Navigation' => array(
                        'text' => lang('ds_navigation'),
                        'args' => 'index,Navigation,website',
                    ),
                    'Adv' => array(
                        'text' => lang('ds_adv'),
                        'args' => 'ap_manage,Adv,website',
                    ),
                
                    'Mallconsult' => array(
                        'ico' => '&#xe750;',
                        'text' => lang('ds_mall_consult'),
                        'args' => 'index,Mallconsult,website',
                    ),
                    'Feedback' => array(
                        'ico'=>'&#xe672;',
                        'text' => lang('ds_feedback'),
                        'args' => 'flist,feedback,website',
                    ),
                ),
            ),
            // 'operation' => array(
            //     'name' => 'operation',
            //     'text' => lang('ds_operation'),
            //     'show' => TRUE,
            //     'children' => array(
            //         'Operation' => array(
            //             'ico' => '&#xe734;',
            //             'text' => lang('ds_operation_set'),
            //             'args' => 'index,Operation,operation',
            //         ),
            //     ),
            // ),
            // 'mobile' => array(
            //     'name' => 'mobile',
            //     'text' => lang('mobile'),
            //     'show' => TRUE,
            //     'children' => array(
            //         'app_appadv' => array(
            //             'text' => lang('appadv'),
            //             'args' => 'index,Appadv,mobile',
            //         ),
            //     ),
            // ),
            // 'wechat' => array(
            //     'name' => 'wechat',
            //     'text' => lang('wechat'),
            //     'show' => TRUE,
            //     'children' => array(
            //         'wechat_setting' => array(
            //             'ico' => '&#xe6e0;',
            //             'text' => lang('wechat_setting'),
            //             'args' => 'setting,Wechat,wechat',
            //         ),
            //         'wechat_menu' => array(
            //             'ico' => '&#xe679;',
            //             'text' => lang('wechat_menu'),
            //             'args' => 'menu,Wechat,wechat',
            //         ),
            //         'wechat_keywords' => array(
            //             'ico'=>'&#xe672;',
            //             'text' => lang('wechat_keywords'),
            //             'args' => 'k_text,Wechat,wechat',
            //         ),
            //         'wechat_member' => array(
            //             'ico' => '&#xe729;',
            //             'text' => lang('wechat_member'),
            //             'args' => 'member,Wechat,wechat',
            //         ),
            //         'wechat_push' => array(
            //             'ico' => '&#xe71b;',
            //             'text' => lang('wechat_push'),
            //             'args' => 'SendList,Wechat,wechat',
            //         ),
            //     ),
            // ),
            // 'exam' => array(
            //     'name' => 'exam',
            //     'text' => lang('ds_exam'),
            //     'show' => TRUE,
            //     'children' => array(
            //         'examclass' => array(
            //             'ico' => '&#xe71f;',
            //             'text' => lang('ds_examclass'),
            //             'args' => 'index,Examclass,exam',
            //         ),
            //         'exambank' => array(
            //             'ico' => '&#xe71f;',
            //             'text' => lang('ds_exambank'),
            //             'args' => 'index,Exambank,exam',
            //         ),
            //         'exampaper' => array(
            //             'ico' => '&#xe71f;',
            //             'text' => lang('ds_exampaper'),
            //             'args' => 'index,Exampaper,exam',
            //         ),
            //     ),
            // ),
            // 'live' => array(
            //     'name' => 'live',
            //     'text' => lang('ds_live'),
            //     'show' => TRUE,
            //     'children' => array(
            //         'live_setting' => array(
            //             'ico' => '&#xe71f;',
            //             'text' => lang('live_setting'),
            //             'args' => 'index,live_setting,live',
            //         ),
            //         'live_apply' => array(
            //             'ico' => '&#xe71f;',
            //             'text' => lang('live_apply'),
            //             'args' => 'index,live_apply,live',
            //         ),
            //         'instant_message' => array(
            //             'ico' => '&#xe71f;',
            //             'text' => lang('instant_message'),
            //             'args' => 'index,instant_message,live',
            //         ),
            //     ),
            // ),
        );
    }

    /*
     * 权限选择列表
     */

    function limitList() {
        $_limit = array(
            array('name' => lang('ds_setting'), 'child' => array(
                    array('name' => lang('ds_base'), 'action' => null, 'controller' => 'Config'),
                    array('name' => lang('ds_upload_set'), 'action' => null, 'controller' => 'Upload'),
                    array('name' => lang('ds_payment'), 'action' => null, 'controller' => 'Payment'),
                    array('name' => lang('ds_message'), 'action' => null, 'controller' => 'Message'),
                    array('name' => lang('ds_admin'), 'action' => null, 'controller' => 'Admin'),
                    array('name' => lang('ds_region'), 'action' => null, 'controller' => 'Region'),
                    array('name' => lang('ds_adminlog'), 'action' => null, 'controller' => 'Adminlog'),
                )),
            array('name' => lang('ds_goods'), 'child' => array(
                    array('name' => lang('ds_goods_manage'), 'action' => null, 'controller' => 'Goods'),
                    array('name' => lang('ds_goodsclass'), 'action' => null, 'controller' => 'Goodsclass'),
                    array('name' => lang('ds_album'), 'action' => null, 'controller' => 'Goodsalbum'),
                )),
            array('name' => lang('ds_store'), 'child' => array(
                    array('name' => lang('ds_store_manage'), 'action' => null, 'controller' => 'Store'),
                    array('name' => lang('ds_store_money'), 'action' => null, 'controller' => 'Storemoney'),
                    array('name' => lang('ds_store_deposit'), 'action' => null, 'controller' => 'Storedeposit'),
                    array('name' => lang('ds_storegrade'), 'action' => null, 'controller' => 'Storegrade'),
                    array('name' => lang('ds_storeclass'), 'action' => null, 'controller' => 'Storeclass'),
                    array('name' => lang('ds_Storehelp'), 'action' => null, 'controller' => 'Storehelp'),
                    array('name' => lang('ds_storejoin'), 'action' => null, 'controller' => 'Storejoin'),
                    array('name' => lang('ds_ownshop'), 'action' => null, 'controller' => 'Ownshop'),
                )),
            array('name' => lang('ds_member'), 'child' => array(
                    array('name' => lang('ds_member_manage'), 'action' => null, 'controller' => 'Member'),
                    // array('name' => lang('member_auth'), 'action' => null, 'controller' => 'MemberAuth'),
                    array('name' => lang('ds_membergrade'), 'action' => null, 'controller' => 'Membergrade'),
                    array('name' => lang('ds_exppoints'), 'action' => null, 'controller' => 'Exppoints'),
                    array('name' => lang('ds_notice'), 'action' => null, 'controller' => 'Notice'),
                    array('name' => lang('ds_points'), 'action' => null, 'controller' => 'Points'),
                    array('name' => lang('ds_snsmalbum'), 'action' => null, 'controller' => 'Snsmalbum'),
                    array('name' => lang('ds_predeposit'), 'action' => null, 'controller' => 'Predeposit'),
                )),
            array('name' => lang('ds_trade'), 'child' => array(
                    array('name' => lang('ds_vrorder'), 'action' => null, 'controller' => 'Vrorder'),
                    // array('name' => lang('ds_vrrefund'), 'action' => null, 'controller' => 'Vrrefund'),
                    // array('name' => lang('ds_bill_manage'), 'action' => null, 'controller' => 'Bill'),
                    array('name' => lang('ds_consulting'), 'action' => null, 'controller' => 'Consulting'),
                    array('name' => lang('ds_inform'), 'action' => null, 'controller' => 'Inform'),
                    array('name' => lang('ds_evaluate'), 'action' => null, 'controller' => 'Evaluate'),
                )),
            array('name' => lang('ds_website'), 'child' => array(
                    array('name' => lang('ds_articleclass'), 'action' => null, 'controller' => 'Articleclass'),
                    array('name' => lang('ds_article'), 'action' => null, 'controller' => 'Article'),
                    array('name' => lang('ds_document'), 'action' => null, 'controller' => 'Document'),
                    array('name' => lang('ds_navigation'), 'action' => null, 'controller' => 'Navigation'),
                    array('name' => lang('ds_adv'), 'action' => null, 'controller' => 'Adv'),
                    array('name' => lang('ds_friendlink'), 'action' => null, 'controller' => 'Link'),
                    array('name' => lang('ds_mall_consult'), 'action' => null, 'controller' => 'Mallconsult'),
                    array('name' => lang('ds_feedback'), 'action' => null, 'controller' => 'Feedback'),
                )),
            // array('name' => lang('ds_operation'), 'child' => array(
            //         array('name' => lang('ds_operation_set'), 'action' => null, 'controller' => 'Operation|Voucher|Inviter|Bonus|Marketmanage|Pointprod|Pointorder|Rechargecard'),
            //     )),
            // array('name' => lang('mobile'), 'child' => array(
            //         array('name' => lang('appadv'), 'action' => null, 'controller' => 'Appadv'),
            //     )),
            // array('name' => lang('wechat'), 'child' => array(
            //         array('name' => lang('wechat_setting'), 'action' => 'setting', 'controller' => 'Wechat'),
            //         array('name' => lang('wechat_template_message'), 'action' => 'template_message', 'controller' => 'Wechat'),
            //         array('name' => lang('wechat_menu'), 'action' => 'menu', 'controller' => 'Wechat'),
            //         array('name' => lang('wechat_keywords'), 'action' => 'k_text', 'controller' => 'Wechat'),
            //         array('name' => lang('wechat_member'), 'action' => 'member', 'controller' => 'Wechat'),
            //         array('name' => lang('wechat_push'), 'action' => 'SendList', 'controller' => 'Wechat'),
            //     )),
        );

        return $_limit;
    }

}

?>
