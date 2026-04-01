<?php
/**
 * 应用公共函数库
 *
 * 提供供所有模块直接调用的公共方法
 * 注意：必须兼容 PHP 5.6
 */

// ===========================================
// 时间处理相关
// ===========================================

/**
 * 解析时间范围参数
 *
 * 参数格式: startDate_endDate
 * 示例: 2026-01-01_2026-01-31
 * 处理逻辑:
 * - 起始时间戳为当天 0:0:0
 * - 结束时间戳为当天 23:59:59
 * - 如果起始时间不合逻辑，默认设置为 startTime = endTime = 0，即不用时间筛选
 *
 * @param string $timeRange 时间范围字符串，如 "2026-01-01_2026-01-31"
 * @return array 包含 startTime 和 endTime 的数组
 */
function parse_time_range($timeRange)
{
    $result = array('startTime' => 0, 'endTime' => 0);

    if (empty($timeRange)) {
        return $result;
    }

    $parts = explode('_', $timeRange);
    if (count($parts) !== 2) {
        return $result;
    }

    $startDate = trim($parts[0]);
    $endDate = trim($parts[1]);

    // 验证日期格式
    $startTimestamp = strtotime($startDate);
    $endTimestamp = strtotime($endDate);

    if ($startTimestamp && $endTimestamp) {
        $result['startTime'] = $startTimestamp;
        $result['endTime'] = strtotime($endDate . ' 23:59:59');
    }

    return $result;
}

// ===========================================
// 数值计算相关（高精度运算）
// ===========================================

/**
 * 高精度加法
 *
 * @param string $number_a 被加数
 * @param string $number_b 加数
 * @param int $scale 保留几位小数
 * @return string
 */
function number_bcadd($number_a, $number_b, $scale = 0)
{
    $number_a = strval($number_a);
    $number_b = strval($number_b);
    return bcadd($number_a, $number_b, $scale);
}

/**
 * 高精度减法
 *
 * @param string $number_a 被减数
 * @param string $number_b 减数
 * @param int $scale 保留几位小数
 * @return string
 */
function number_bcsub($number_a, $number_b, $scale = 0)
{
    $number_a = strval($number_a);
    $number_b = strval($number_b);
    return bcsub($number_a, $number_b, $scale);
}

/**
 * 高精度比较大小
 *
 * @param string $number_a
 * @param string $number_b
 * @param int $scale 保留几位小数
 * @return int -1: $number_a < $number_b, 0: 相等, 1: $number_a > $number_b
 */
function number_bccomp($number_a, $number_b, $scale = 0)
{
    $number_a = strval($number_a);
    $number_b = strval($number_b);
    return bccomp($number_a, $number_b, $scale);
}

// ===========================================
// IP 地址相关
// ===========================================

/**
 * 获取客户端IP地址
 *
 * @param int $string 返回格式：1=字符串IP，0=整型IP
 * @return string|int
 */
function get_ip($string = 1)
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $cip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $cip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $cip = $_SERVER['REMOTE_ADDR'];
    } else {
        $cip = '';
    }
    preg_match("/[\d\.]{7,15}/", $cip, $cips);
    $cip = isset($cips[0]) ? $cips[0] : '';
    unset($cips);
    if ($string == 1) {
        return $cip;
    } else {
        return $cip ? ip2long($cip) : 0;
    }
}

// ===========================================
// cURL 相关
// ===========================================

/**
 * 初始化 cURL 请求
 *
 * @param string $url 请求URL
 * @param string $method 请求方法：get/post
 * @param array $data POST 数据
 * @return mixed
 */
function curl_init_request($url, $method = 'get', $data = array())
{
    $curl = curl_init();
    if (stripos($url, "https://") !== false) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    // 强制设置IPV4
    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

    if (strtolower($method) == "post") {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }

    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

// ===========================================
// 日志相关
// ===========================================

/**
 * 记录日志到文件
 *
 * @param mixed $logtxt 日志内容
 * @param string $path 日志文件路径
 * @return void
 */
function log_result($logtxt, $path = './w.txt')
{
    if (is_array($logtxt)) {
        $logtxt = json_encode($logtxt);
    }
    $fp = fopen($path, "a");
    flock($fp, LOCK_EX);
    fwrite($fp, "time:" . date("Y-m-d H:i:s", time()) . "  " . $logtxt . "\r\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}

// ===========================================
// 验证相关
// ===========================================

/**
 * 检查是否为数字
 *
 * @param mixed $str
 * @return boolean
 */
function is_number($str)
{
    return is_numeric($str);
}

/**
 * 检查是否为手机号
 *
 * @param string $str
 * @return boolean
 */
function is_mobile($str)
{
    if (!is_number($str)) {
        return false;
    }
    $regex = '/^13[0-9]{9}$|14[0-9]{9}$|15[0-9]{9}$|16[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$|19[0-9]{9}$/';
    if (preg_match($regex, $str) == 1) {
        return true;
    }
    return false;
}

// ===========================================
// 格式化相关
// ===========================================

/**
 * 格式化金额（保留2位小数）
 *
 * @param float|string $str
 * @return string
 */
function format_money($str)
{
    return number_format($str, 2, ".", "");
}

/**
 * 格式化数字
 *
 * @param float|string $str
 * @param int $decimals 保留小数位数
 * @return string
 */
function format_number($str, $decimals = 2)
{
    return number_format($str, $decimals, ".", "");
}

/**
 * 生成随机字符串
 *
 * @param int $length 字符串长度
 * @return string
 */
function random_str($length)
{
    $arr = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
    $str = '';
    $arr_len = count($arr);
    for ($i = 0; $i < $length; $i++) {
        $rand = mt_rand(0, $arr_len - 1);
        $str .= $arr[$rand];
    }
    return $str;
}

// ===========================================
// 加密解密相关
// ===========================================

/**
 * 系统加密方法
 *
 * @param string $data 要加密的字符串
 * @param int $expire 过期时间，单位秒
 * @param string $key 加密密钥
 * @return string
 */
function think_encrypt($data, $expire = 0, $key = '')
{
    $key = md5(empty($key) ? 'userEncryPt' : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }
    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

/**
 * 系统解密方法
 *
 * @param string $data 要解密的字符串（必须是think_encrypt方法加密的字符串）
 * @param string $key 加密密钥
 * @return string
 */
function think_decrypt($data, $key = '')
{
    $key = md5(empty($key) ? 'userEncryPt' : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}
