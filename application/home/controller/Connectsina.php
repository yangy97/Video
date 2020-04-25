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
class  Connectsina extends BaseMall
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH.'home/lang/'.config('default_lang').'/connectsina.lang.php');
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/login-register.lang.php');
        /**
         * 判断新浪微博登录功能是否开启
         */
        if (config('sina_isuse') != 1) {
            $this->error(lang('home_sconnect_unavailable'));
        }
        if (!session('slast_key')) {
            $this->error(lang('home_sconnect_error'));
        }
        $this->assign('hidden_nctoolbar', 1);
    }

    /**
     * 首页
     */
    public function index()
    {
        /**
         * 检查登录状态
         */
        if (session('is_login') == '1') {
            $this->bindsina();
        }
        else {
            $this->autologin();
            $this->register();
        }
    }

    /**
     * 新浪微博账号绑定新用户
     */
    public function register() {
        //实例化模型
        $member_model = model('member');
        //检查登录状态
        $member_model->checkloginMember();
        //获取新浪微博账号信息
            require_once(PLUGINS_PATH . '/login/sina/saetv2.ex.class.php');
            $c = new \SaeTClientV2(config('sina_wb_akey'), config('sina_wb_skey'), session('slast_key.access_token'));
            $sinauser_info = $c->show_user_by_id(session('slast_key.uid'));//根据ID获取用户等基本信息
        if (request()->isPost()) {
            $type=input('param.type');
            $user=input('param.user');
            $email=input('param.email');
            $password=input('param.password');
            $password2=input('param.password2');
                $reg_info = array(
                    'member_sinaopenid' => session('slast_key.uid'),
                    'nickname' => isset($sinauser_info['screen_name'])?$sinauser_info['screen_name']:'',
                    'headimgurl' => isset($sinauser_info['avatar_large'])?$sinauser_info['avatar_large']:'',
                );
                $data=array(
                    'member_name'=>$user,
                    'member_password'=>$password,
                    'member_email'=>$email,
                    'member_sinaopenid' => $reg_info['member_sinaopenid'],
                    'member_sinainfo' =>  serialize($reg_info),
                    'member_nickname'=>$reg_info['nickname'],
                );
            if($type==1){//注册


                $login_validate = validate('member');
                if (!$login_validate->scene('register')->check($data)) {
                    $this->error($login_validate->getError());
                }
                $member_info = $member_model->register($data);
                if (!isset($member_info['error'])) {
                    $member_model->createSession($member_info, true);
                    $headimgurl = $reg_info['headimgurl'];
                    $avatar = @copy($headimgurl, BASE_UPLOAD_PATH . '/' . ATTACH_AVATAR . "/avatar_".$member_info['member_id'].".jpg");
                    if ($avatar) {
                        $member_model->editMember(array('member_id' => $member_info['member_id']), array('member_avatar' => "avatar_".$member_info['member_id'].".jpg"));
                    }
                } else {
                    $this->error($member_info['error']);
                }
            }else{//绑定
       
                $login_validate = validate('member');
                if (!$login_validate->scene('login')->check($data)) {
                    ds_json_encode(10001, $login_validate->getError());
                }
                $map = array(
                    'member_name' => $data['member_name'],
                    'member_password' => md5($data['member_password']),
                );
                $member_info = $member_model->getMemberInfo($map);
                if ($member_info) {
                    $member_model->editMember(array('member_id' => $member_info['member_id']), array('member_sinaopenid' => $data['member_sinaopenid'],'member_sinainfo' => $data['member_sinainfo']));
                }else{
                    $this->error(lang('login_register_bind_fail'));
                }
                $member_model->createSession($member_info, true);
            }
            
            
            $this->success(lang('ds_common_save_succ'), HOME_SITE_URL);
        } else {
            
            

            if(config('auto_register')){//如果开启了自动注册
                $logic_connect_api = model('connectapi', 'logic');
                //注册会员信息 返回会员信息
                $reg_info = array(
                    'member_sinaopenid' => session('slast_key.uid'),
                    'nickname' => isset($sinauser_info['screen_name'])?$sinauser_info['screen_name']:'',
                    'headimgurl' => isset($sinauser_info['avatar_large'])?$sinauser_info['avatar_large']:'',
                );
                $wx_member = $logic_connect_api->wx_register($reg_info, 'sina');
                if ($wx_member) {
                    if (!$wx_member['member_state']) {
                        $this->error(lang('login_index_account_stop'), 'Index/index');
                    }
                    $member_model->createSession($wx_member, true);
                    $success_message = lang('login_index_login_success');
                    $this->success($success_message, HOME_SITE_URL);
                } else {
                    $this->error(lang('login_usersave_regist_fail'), 'login/register'); //"会员注册失败"
                }
            }else{
                $this->assign('sinauser_info', $sinauser_info);
                $this->assign('user_passwd', '');
                echo $this->fetch($this->template_dir . 'connect_register');
            }

        }
    }

    /**
     * 绑定新浪微博账号后自动登录
     */
    public function autologin()
    {
        //查询是否已经绑定该新浪微博账号,已经绑定则直接跳转
        $member_model = model('member');
        $array = array();
        $array['member_sinaopenid'] = session('slast_key.uid');
        $member_info = $member_model->getMemberInfo($array);
        if (is_array($member_info) && count($member_info) > 0) {
            if (!$member_info['member_state']) {//1为启用 0 为禁用
                $this->error(lang('login_index_account_stop'));
            }
            $member_model->createSession($member_info);
            $success_message = lang('login_index_login_success');
            $this->success($success_message, 'index/index');
        }
    }

    /**
     * 已有用户绑定新浪微博账号
     */
    public function bindsina()
    {
        $member_model = model('member');
        //验证新浪账号用户是否已经存在
        $array = array();
        $array['member_sinaopenid'] = session('slast_key.uid');
        $member_info = $member_model->getMemberInfo($array);
        if (is_array($member_info) && count($member_info) > 0) {
            session('slast_key.uid',null);
            $this->error(lang('home_sconnect_binding_exist'), 'memberconnect/sinabind');
        }
        //处理sina账号信息
        require_once(PLUGINS_PATH . '/login/sina/saetv2.ex.class.php');
        $c = new \SaeTClientV2(config('sina_wb_akey'), config('sina_wb_skey'), session('slast_key.access_token'));
        $sinauser_info = $c->show_user_by_id(session('slast_key.uid'));//根据ID获取用户等基本信息
        $sina_arr = array();
        $sina_arr['nickname'] = $sinauser_info['name'];
        $sina_str = '';
        $sina_str = serialize($sina_arr);
        $edit_state = $member_model->editMember(array('member_id' => session('member_id')), array(
            'member_sinaopenid' => session('slast_key.uid'), 'member_sinainfo' => $sina_str
        ));
        if ($edit_state) {
            $this->success(lang('home_sconnect_binding_success'), 'memberconnect/sinabind');
        }
        else {
            $this->error(lang('home_sconnect_binding_fail'), 'memberconnect/sinabind');
        }
    }

    /**
     * 更换绑定新浪微博账号
     */
    public function changesina()
    {
        //如果用户已经登录，进入此链接则显示错误
        if (session('is_login') == '1') {
            $this->error(lang('home_sconnect_error'));
        }
        session('slast_key',null);
        $this->redirect('api/oa_sina');
        exit;
    }
}