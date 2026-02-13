<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用配置文件
return [
    // 应用命名空间
    'app_namespace' => 'app',
    // 应用调试模式
    'app_debug' => true,
    // 应用Trace
    'app_trace' => false,
    // 应用模式状态
    'app_status' => '',
    // 是否支持多模块
    'app_multi_module' => true,
    // 入口自动绑定模块
    'auto_bind_module' => false,
    // 注册的根命名空间
    'root_namespace' => [],
    // 默认模块
    'default_module' => 'index',
    // 默认控制器
    'default_controller' => 'Index',
    // 默认操作
    'default_action' => 'index',
    // 默认验证器
    'default_validate' => '',
    // 默认的空控制器名
    'empty_controller' => 'Error',
    // 默认的空模块名
    'empty_module' => '',
    // 是否自动转换URL参数
    'url_param_type' => 0,
    // 是否开启请求缓存 true自动缓存 false关闭缓存
    'request_cache' => false,
    // 请求缓存有效期
    'request_cache_expire' => 3600,
    // 全局请求缓存排除规则
    'request_cache_except' => [],
    // 是否开启路由
    'url_route_must' => false,
    // 路由配置文件
    'route_config_file' => ['route'],
    // 兼容PATH_INFO获取
    'pathinfo_fetch' => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr' => '/',
    // URL伪静态后缀
    'url_html_suffix' => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param' => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type' => 0,
    // 是否开启路由延迟解析
    'url_lazy_route' => false,
    // 是否强制使用路由
    'url_route_must' => false,
    // 合并路由规则
    'route_rule_merge' => false,
    // 路由是否完全匹配
    'route_complete_match' => false,
    // 是否去除路由规则中的空格
    'route_option_suffix' => false,
    // 使用注解路由
    'route_annotation' => false,
    // 域名部署
    'url_domain_deploy' => false,
    // 域名根，如thinkphp.cn
    'url_domain_root' => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert' => true,
    // 默认的访问控制器层
    'url_controller_layer' => 'controller',
    // 表单请求类型伪装变量
    'var_method' => '_method',
    // 表单ajax伪装变量
    'var_ajax' => '_ajax',
    // 表单pjax伪装变量
    'var_pjax' => '_pjax',
    // 是否开启请求缓存 true自动缓存 false关闭缓存
    'request_cache' => false,
    // 请求缓存有效期
    'request_cache_expire' => 3600,
    // 全局请求缓存排除规则
    'request_cache_except' => [],
    // 是否开启路由
    'url_route_must' => false,
    // 路由配置文件
    'route_config_file' => ['route'],
    // 应用类库后缀
    'class_suffix' => false,
    // 控制器类后缀
    'controller_suffix' => false,
    // 默认输出类型
    'default_return_type' => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return' => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler' => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler' => 'callback',
    // 响应输出类型
    'response_return' => false,
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl' => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl' => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    // 异常页面的模板文件
    'exception_tmpl' => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',
    // 错误显示信息,非调试模式有效
    'error_message' => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg' => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle' => '',
    // cache缓存配置
    'cache' =>[
        // 选择缓存驱动类型 - 临时使用文件缓存 (Story 2.4: 原 Redis 配置，待 PHP Redis 扩展安装后切回)
        'type'   => 'File',
        // 缓存前缀
        'prefix' => 'bms:',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
        // 缓存标签前缀
        'tag_prefix' => 'tag:',
        // 序列化机制 例如 ['serialize', 'unserialize']
        'serialize' => array(),

        // Redis 配置
        'host'         => '127.0.0.1', // Redis 服务器地址
        'port'         => 6379,        // Redis 端口
        'password'     => '',           // Redis 密码（如果有）
        'select'       => 0,           // 选择的 Redis 数据库
        'timeout'      => 0,           // 超时时间
        'persistent'   => false,       // 是否使用长连接
        // 适用于集群的配置
        // 'cluster'      => [
        //     'host'         => ['127.0.0.1:6379', '127.0.0.1:6380'],
        //     'options'      => [],
        // ],
    ]
];
