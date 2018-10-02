<?php
namespace moxuandi\helpers;

use yii\helpers\Json;
use yii\web\NotFoundHttpException;

class Helper
{
    /**
     * 判断当前服务器操作系统.
     * @return string 'Linux' 或 'Windows'.
     */
    public static function getOS()
    {
        return PATH_SEPARATOR == ':' ? 'Linux' : 'Windows';
    }

    /**
     * 获取当前微妙数.
     * @return float eg: 1512001416.3352.
     */
    public static function microtime_float()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * 遍历文件夹.
     * @param string $dir eg:'uploads/image'.
     * @param bool $all true 表示递归遍历
     * @param array $ret
     * @return array
     */
    public static function scanfDir($dir='', $all=false, &$ret=[])
    {
        if($handle = opendir($dir)){
            while(($file = readdir($handle)) !== false){
                if(!in_array($file, ['.', '..', '.git', '.gitignore', '.svn', '.buildpath', '.project'])){
                    $cur_path = $dir . '/' . $file;
                    if(is_dir($cur_path)){
                        $ret['dirs'][] = $cur_path;
                        $all && self::scanfDir($cur_path, $all, $ret);
                    }else{
                        $ret['files'][] = $cur_path;
                    }
                }
            }
            closedir($handle);
        }
        return $ret;
    }

    /**
     * 格式化文件大小的单位.
     * @param integer $size 文件大小, eg: 1532684.
     * @param integer $dec 小数位数, eg: 2.
     * @return string 格式化后的文件大小, eg: '1.46 MB'.
     */
    public static function byteFormat($size, $dec=2)
    {
        $byte = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB', 'BB', 'NB', 'DB', 'CB'];
        $pos = 0;
        while($size >= 1024){
            $size /= 1024;
            $pos ++;
        }
        return round($size, $dec) . ' ' . $byte[$pos];
    }

    /**
     * 获取文件的扩展名
     * @param string $fileName 文件名, eg: 'uploads/img.jpg'.
     * @return string 文件扩展名, eg: 'jpg'.
     */
    public static function getExtension($fileName)
    {
        //$pathinfo = pathinfo($file);
        //return strtolower($pathinfo['extension']);
        //return strtolower(strrchr($fileName, '.'));  // return '.jpg'
        //return strtolower(substr(strrchr($fileName, '.'), 1));  // return 'jpg'
        return strtolower(substr($fileName, strrpos($fileName, '.')+1));  // return 'jpg'
    }

    /**
     * 获取图片的宽高值.
     * @param string $img 图片路径, eg: 'uploads/img.jpg'.
     * @return array|bool 图片宽高值组成的数组, eg: ['width'=>1366, 'height'=>768].
     */
    public static function getImageInfo($img)
    {
        if($imgInfo = getimagesize($img)){
            return [
                'width' => $imgInfo[0],
                'height' => $imgInfo[1]
            ];
        }else{
            return false;
        }
    }

    /**
     * 创建目录并检查目录是否可写.
     * @param string $path 目录路径, eg: 'uploads/image'.
     * @return bool|string 目录创建成功返回 true, 失败则返回错误信息.
     */
    public static function createDir($path)
    {
        if(!file_exists($path) && !mkdir($path, 0777, true)){
            return '目录创建失败';
        }elseif(!is_writable($path)){
            return '目录没有写入权限';
        }
        return true;
    }

    /**
     * 将路径修正为适合当前操作系统的形式.
     * @param string $path 目录或文件路径, eg: 'uploads/img.jpg'.
     * @return mixed eg: 'uploads\img.jpg'(Windows 环境下).
     */
    public static function trimPath($path)
    {
        return str_replace(['/', '\\', '//', '\\\\'], DIRECTORY_SEPARATOR, $path);
    }


    /**
     * 获取客户端的操作系统信息.
     * @param string $agent User-Agent 头信息, eg: 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0'.
     * @return string 客户端操作系统, eg: 'Windows 7'.
     */
    public static function get_os($agent)
    {
        //$agent = Yii::$app->request->userAgent;
        if(preg_match('/win/i', $agent) && strpos($agent, '95')){
            $os = 'Windows 95';
        }elseif(preg_match('/win 9x/i', $agent) && strpos($agent, '4.90')){
            $os = 'Windows ME';
        }elseif(preg_match('/win/i', $agent) && preg_match('/98/i', $agent)){
            $os = 'Windows 98';
        }elseif(preg_match('/win/i', $agent) && preg_match('/nt 5.1/i', $agent)){
            $os = 'Windows XP';
        }elseif(preg_match('/win/i', $agent) && preg_match('/nt 5/i', $agent)){
            $os = 'Windows 2000';
        }elseif(preg_match('/win/i', $agent) && preg_match('/nt 6.0/i', $agent)){
            $os = 'Windows Vista';
        }elseif(preg_match('/win/i', $agent) && preg_match('/nt 6.1/i', $agent)){
            $os = 'Windows 7';
        }elseif(preg_match('/win/i', $agent) && preg_match('/nt 6.2/i', $agent)){
            $os = 'Windows 8';
        }elseif(preg_match('/win/i', $agent) && preg_match('/nt 10.0/i', $agent)){
            $os = 'Windows 10';
        }elseif(preg_match('/win/i', $agent) && preg_match('/nt/i', $agent)){
            $os = 'Windows NT';
        }elseif(preg_match('/win/i', $agent) && preg_match('/32/i', $agent)){
            $os = 'Windows 32';
        }elseif(preg_match('/linux/i', $agent)){
            $os = 'Linux';
        }elseif(preg_match('/unix/i', $agent)){
            $os = 'Unix';
        }elseif(preg_match('/sun/i', $agent) && preg_match('/os/i', $agent)){
            $os = 'SunOS';
        }elseif(preg_match('/ibm/i', $agent) && preg_match('/os/i', $agent)){
            $os = 'IBM OS/2';
        }elseif(preg_match('/Mac/i', $agent) && preg_match('/PC/i', $agent)){
            $os = 'Macintosh';
        }elseif(preg_match('/PowerPC/i', $agent)){
            $os = 'PowerPC';
        }elseif(preg_match('/AIX/i', $agent)){
            $os = 'AIX';
        }elseif(preg_match('/HPUX/i', $agent)){
            $os = 'HPUX';
        }elseif(preg_match('/NetBSD/i', $agent)){
            $os = 'NetBSD';
        }elseif(preg_match('/BSD/i', $agent)){
            $os = 'BSD';
        }elseif(preg_match('/OSF1/i', $agent)){
            $os = 'OSF1';
        }elseif(preg_match('/IRIX/i', $agent)){
            $os = 'IRIX';
        }elseif(preg_match('/FreeBSD/i', $agent)){
            $os = 'FreeBSD';
        }elseif(preg_match('/teleport/i', $agent)){
            $os = 'teleport';
        }elseif(preg_match('/flashget/i', $agent)){
            $os = 'flashget';
        }elseif(preg_match('/webzip/i', $agent)){
            $os = 'webzip';
        }elseif(preg_match('/offline/i', $agent)){
            $os = 'offline';
        }else{
            $os = 'Other';
        }
        return $os;
    }

    /**
     * 获取客户端浏览器的类型和版本号.
     * @param string $sys User-Agent 头信息, eg: 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0'.
     * @return array 客户端浏览器的类型和版本号, eg: ['Firefox', '57.0'].
     */
    public static function getBrowser($sys)
    {
        //$sys = Yii::$app->request->userAgent;
        if(stripos($sys, "Firefox/") > 0){
            preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
            $exp[] = "Firefox";
            $exp[] = $b[1];
        }elseif(stripos($sys, "Maxthon") > 0){
            preg_match("/Maxthon\/([\d\.]+)/", $sys, $aoyou);
            $exp[] = "傲游";
            $exp[] = $aoyou[1];
        }elseif(stripos($sys, "MSIE") > 0){
            preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
            $exp[] = "IE";
            $exp[] = $ie[1];
        }elseif(stripos($sys, "OPR") > 0){
            preg_match("/OPR\/([\d\.]+)/", $sys, $opera);
            $exp[] = "Opera";
            $exp[] = $opera[1];
        }elseif(stripos($sys, "Edge") > 0){
            preg_match("/Edge\/([\d\.]+)/", $sys, $edge);
            $exp[] = "Edge";
            $exp[] = $edge[1];
        }elseif(stripos($sys, "Chrome") > 0){
            preg_match("/Chrome\/([\d\.]+)/", $sys, $chrome);
            $exp[] = "Chrome";
            $exp[] = $chrome[1];
        }elseif(stripos($sys,'rv:')>0 && stripos($sys,'Gecko')>0){
            preg_match("/rv:([\d\.]+)/", $sys, $ie);
            $exp[] = "IE";
            $exp[] = $ie[1];
        }
        /*elseif(stripos($sys, "Version") > 0){
            preg_match("/Version\/([\d\.]+)/", $sys, $b);
            $exp[] = "Safari";
            $exp[] = $b[1];
        }*/
        else{
            $exp[] = "Other";
            $exp[] = '';
        }
        return $exp;
    }

    /**
     * 获取指定IP的地区和网络接入商信息.
     * @param string $ip IPv4, eg: '182.123.156.241'.
     * @return array IP的地区和网络接入商数组, eg: ['ip'=>'182.123.156.241', 'address'=>'中国-河南省-周口市', 'isp'=>'联通'].
     * @throws NotFoundHttpException API 返回错误.
     */
    public static function getAddress($ip)
    {
        // 淘宝API: http://ip.taobao.com/service/getIpInfo.php?ip=182.123.156.241; 仅支持中国
        // http://int.dpool.sina.com.cn/iplookup/iplookup.php?ip=182.123.156.241
        $string = Json::decode(file_get_contents('http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip));
        if($string['code']){
            throw new NotFoundHttpException($string['data']);
        }
        $data = $string['data'];
        $return['ip'] = $data['ip'];
        if($data['country_id'] === 'CN'){
            $return['address'] = implode('-', [$data['country'], $data['region'], $data['city']]);
            $return['isp'] = $data['isp'];
        }else{
            $return['address'] = $data['country'];
            $return['isp'] = $data['isp'];
        }
        return $return;
    }
}
