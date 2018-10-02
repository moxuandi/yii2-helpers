<?php
namespace moxuandi\helpers;

use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use common\models\Upload;

/**
 * Class Uploader
 * 通用上传类
 *
 * @author  zhangmoxuan <1104984259@qq.com>
 * @link  http://www.zhangmoxuan.com
 * @QQ  1104984259
 * @Date  2017/7/12
 *
 * 说明: 为兼容 UEditor编辑器(http://ueditor.baidu.com), `$config['allowFiles'])`中的扩展名全都带有前缀'.', eg: ['.png', '.jpg', '.jpeg'].
 *
 * 示例:
 *  $config = [
 *      'maxSize' => 5*1024*1024,  // 上传大小限制, 单位B, 默认5MB, 注意修改服务器的大小限制
 *      'allowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],  // 上传图片格式显示
 *      'thumbStatus' => false,  // 是否生成缩略图
 *      'thumbWidth' => 300,  // 缩略图宽度
 *      'thumbHeight' => 200,  // 缩略图高度
 *      'thumbCut' => 1,  // 生成缩略图的方式, 0:留白, 1:裁剪
 *      'pathFormat' => 'uploads/image/{yyyy}{mm}/{yy}{mm}{dd}_{hh}{ii}{ss}_{rand:4}',  // 上传保存路径, 可以自定义保存路径和文件名格式
 *  ];
 *  $up = new Uploader('upfile', $config);
 *  echo Json::encode([
 *      'url' => $up->fullName,
 *      'state' => $up->stateInfo
 *  ]);
 */
class Uploader
{
    public $fileField;           // 文件域名
    public $file;                 // 上传对象
    public $config;               // 上传配置信息
    public $realName;             // 原始文件名
    public $fileSize;             // 文件大小
    public $fileMime;             // 文件的 MIME 类型
    public $fileType;             // 文件扩展名
    public $fileName;             // 新文件名
    public $fullName;             // 完整的文件名,
    public $thumbName = '';       // 完整的缩略图文件名
    public $chunkPath = 'uploads/chunks';   //分片暂存区
    public $stateInfo;            // 上传状态信息
    public $stateMap = [          // 上传状态映射表, 国际化用户需考虑此处数据的国际化
        0 => 'SUCCESS',             // 上传成功标记, 在UEditor中内不可改变, 否则flash判断会出错
        1 => '文件大小在 php.ini 超出 upload_max_filesize 限制' ,
        2 => '文件大小超出 HTML 表单指定的 MAX_FILE_SIZE 限制' ,
        3 => '文件未被完整上传' ,
        4 => '没有文件被上传' ,
        6 => '找不到临时文件' ,
        7 => '临时文件写入磁盘失败',
        8 => '因php扩展停止文件上传',
        'ERROR_SIZE_EXCEED' => '文件大小超出网站限制',
        'ERROR_TYPE_NOT_ALLOWED' => '不允许的文件类型',
        'ERROR_CREATE_DIR' => '目录创建失败',
        'ERROR_DIR_NOT_WRITEABLE' => '目录没有写入的权限',
        'ERROR_FILE_MOVE' => '文件保存时出错',
        'ERROR_WRITE_CONTENT' => '写入文件内容错误',
        'ERROR_THUMB' => '缩略图创建失败',
        'ERROR_DEAD_LINK' => '链接不可用',
        'ERROR_HTTP_LINK' => '链接不是http链接',
        'ERROR_HTTP_CONTENTTYPE' => '链接contentType不正确',
        'INVALID_URL' => '非法 URL',
        'INVALID_IP' => '非法 IP',
        'ERROR_UPLOAD' => '非法上传',  // 文件不是通过 HTTP POST 上传的
        'ERROR_UNKNOWN' => '未知错误',
        'ERROR_DATABASE' => '文件上传成功，但在保存到数据库时失败！',
    ];

    public $saveDatabase;  // 保存上传信息到数据库, 使用前请导入'database'文件夹中的数据表'upload'和模型类'Upload'


    /**
     * Uploader constructor.
     * @param string $fileField 表单名称
     * @param array $config 上传配置信息
     * @param string $type 是否解析base64编码, 可省略. 若开启, 则$fileField代表的是base64编码的字符串表单名称
     * @param bool $saveDatabase 保存上传信息到数据库, 使用前请导入'database'文件夹中的数据表'upload'和模型类'Upload'
     * @throws \yii\base\ErrorException
     */
    public function __construct($fileField, $config, $type='upload', $saveDatabase=false)
    {
        // 默认的上传配置信息
        $_config = [
            'maxSize' => 5*1024*1024,  // 上传大小限制, 单位B, 默认5MB, 注意修改服务器的大小限制
            'allowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],  // 上传图片格式显示
            'thumbStatus' => false,  // 是否生成缩略图
            'thumbWidth' => 300,  // 缩略图宽度
            'thumbHeight' => 200,  // 缩略图高度
            'thumbCut' => 1,  // 生成缩略图的方式, 0:留白, 1:裁剪
            'pathFormat' => 'uploads/image/{yyyy}{mm}/{yy}{mm}{dd}_{hh}{ii}{ss}_{rand:4}',  // 上传保存路径, 可以自定义保存路径和文件名格式
        ];

        $this->fileField = $fileField;
        $this->saveDatabase = $saveDatabase;
        $this->config = array_merge($_config, $config);
        // 判断上传类型
        switch($type){
            // 拉取远程图片
            case 'remote': self::saveRemote(); break;
            // 处理base64编码的图片上传
            case 'base64': self::upBase64(); break;
            // 默认文件上传
            //case 'upload':
            default: self::uploadHandle(); break;
        }
    }

    /**
     * 分离大文件分片上传与普通上传
     * @throws \yii\base\ErrorException
     */
    private function uploadHandle()
    {
        if(Yii::$app->request->post('chunks')){
            self::chunkFile();
        }else{
            self::uploadFile();
        }
    }

    /**
     * 上传文件的主处理方法
     * @return bool
     */
    private function uploadFile()
    {
        $this->file = UploadedFile::getInstanceByName($this->fileField);  // 调用 yii\web\UploadedFile:getInstanceByName() 方法接收上传文件

        // 检查上传对象是否为空
        if(empty($this->file)){
            $this->stateInfo = $this->stateMap[1];
            return false;
        }

        // 校验 $_FILES['upfile']['error'] 的错误
        if($this->file->error){
            $this->stateInfo = $this->stateMap[$this->file->error];
            return false;
        }

        // 检查临时文件是否存在
        if(!file_exists($this->file->tempName)){
            $this->stateInfo = $this->stateMap[6];
            return false;
        }

        // 检查文件是否是通过 HTTP POST 上传的
        if(!is_uploaded_file($this->file->tempName)){
            $this->stateInfo = $this->stateMap['ERROR_UPLOAD'];
            return false;
        }

        // 检查文件大小是否超出网站限制
        if($this->file->size > $this->config['maxSize']){
            $this->stateInfo = $this->stateMap['ERROR_SIZE_EXCEED'];
            return false;
        }

        $this->realName = $this->file->name;
        $this->fileSize = $this->file->size;
        $this->fileMime = $this->file->type;
        $this->fileType = self::getFileType($this->realName);

        // 检查文件类型(扩展名)是否符合网站要求
        if(!in_array($this->fileType, $this->config['allowFiles'])){
            $this->stateInfo = $this->stateMap['ERROR_TYPE_NOT_ALLOWED'];
            return false;
        }

        $this->fullName = self::getFullName($this->realName, $this->config['pathFormat'], $this->fileType);
        $this->fileName = self::getFileName($this->fullName);

        // 生成缩略图
        if($this->config['thumbStatus'] && in_array($this->fileType, ['.jpg', '.jpeg', '.png', '.gif'])){
            if(!self::makeThumb($this->file->tempName)){
                $this->stateInfo = $this->stateInfo ? $this->stateInfo : $this->stateMap['ERROR_THUMB'];
                return false;
            }
        }

        // 创建目录
        if(($info = Helper::createDir(dirname($this->fullName))) !== true){
            $this->stateInfo = $info;
            return false;
        }

        // 调用 yii\web\UploadedFile:saveAs() 方法保存上传文件, 并删除临时文件
        if(!$this->file->saveAs($this->fullName)){
            $this->stateInfo = $this->stateMap['ERROR_FILE_MOVE'];
            return false;
        }elseif($this->saveDatabase){
            if(self::saveDatabase()){
                $this->stateInfo = $this->stateMap[0];
                return true;
            }else{
                $this->stateInfo = $this->stateMap['ERROR_DATABASE'];
                return false;
            }
        }else{
            $this->stateInfo = $this->stateMap[0];
            return true;
        }
    }

    /**
     * 大文件分片上传
     * @return bool
     * @throws \yii\base\ErrorException
     */
    private function chunkFile()
    {
        $post = Yii::$app->request->post();  // 接收分片信息

        $this->realName = $post['name'];
        $this->fileSize = $post['size'];
        $this->fileMime = $post['type'];
        $this->fileType = self::getFileType($this->realName);

        // 检查文件大小是否超出网站限制
        if($this->fileSize > $this->config['maxSize']){
            $this->stateInfo = $this->stateMap['ERROR_SIZE_EXCEED'];
            return false;
        }

        // 检查文件类型(扩展名)是否符合网站要求
        if(!in_array($this->fileType, $this->config['allowFiles'])){
            $this->stateInfo = $this->stateMap['ERROR_TYPE_NOT_ALLOWED'];
            return false;
        }

        $this->file = UploadedFile::getInstanceByName($this->fileField);  // 调用 yii\web\UploadedFile:getInstanceByName() 方法接收上传的分片文件

        // 检查上传对象是否为空
        if(empty($this->file)){
            $this->stateInfo = $this->stateMap[1];
            return false;
        }

        // 校验 $_FILES['upfile']['error'] 的错误
        if($this->file->error){
            $this->stateInfo = $this->stateMap[$this->file->error];
            return false;
        }

        // 检查临时文件是否存在
        if(!file_exists($this->file->tempName)){
            $this->stateInfo = $this->stateMap[6];
            return false;
        }

        // 检查文件是否是通过 HTTP POST 上传的
        if(!is_uploaded_file($this->file->tempName)){
            $this->stateInfo = $this->stateMap['ERROR_UPLOAD'];
            return false;
        }

        $chunkPath = $this->chunkPath . '/' . $post['id'];
        if(($info = Helper::createDir($chunkPath)) !== true){
            $this->stateInfo = $info;
            return false;
        }
        $this->file->saveAs($chunkPath . '/chunk_' . $post['chunk']);  // 保存分片

        // 分片全部上传完成, 并且分片暂存区保存有所有分片
        if($post['chunk'] + 1 == $post['chunks'] && count(FileHelper::findFiles($chunkPath, ['recursive'=>false])) == $post['chunks']){
            $this->fullName = self::getFullName($this->realName, $this->config['pathFormat'], $this->fileType);
            $this->fileName = self::getFileName($this->fullName);

            // 创建目录
            if(($info = Helper::createDir(dirname($this->fullName))) !== true){
                $this->stateInfo = $info;
                return false;
            }

            // 合并分片
            $blob = '';
            for($i=0; $i< $post['chunks']; $i++){
                $blob .= file_get_contents($chunkPath . '/chunk_' . $i);  // 依次读取所有分片内容
            }
            file_put_contents($this->fullName, $blob);  // 保存分片内容到文件
            FileHelper::removeDirectory($chunkPath);  // 删除对应分片暂存区

            // 生成缩略图
            if($this->config['thumbStatus'] && in_array($this->fileType, ['.jpg', '.jpeg', '.png', '.gif'])){
                if(!self::makeThumb($this->fullName)){
                    $this->stateInfo = $this->stateInfo ? $this->stateInfo : $this->stateMap['ERROR_THUMB'];
                    return false;
                }
            }

            //$this->file->size = $this->fileSize;  // 重置上传对象的大小, 可选
            //$this->file->type = $this->fileMime;  // 重置上传对象的MIME类型, 可选

            if($this->saveDatabase){
                if(self::saveDatabase()){
                    $this->stateInfo = $this->stateMap[0];
                    return true;
                }else{
                    $this->stateInfo = $this->stateMap['ERROR_DATABASE'];
                    return false;
                }
            }else{
                $this->stateInfo = $this->stateMap[0];
                return true;
            }
        }else{
            $this->stateInfo = '分片不完整';
            return false;
        }
    }

    /**
     * 拉取远程图片(待测试)
     * @return bool
     */
    private function saveRemote()
    {
        $imgUrl = htmlspecialchars($this->fileField);
        $imgUrl = str_replace('&amp;', '&', $imgUrl);

        // http开头验证
        if(strpos($imgUrl, 'http') !== 0){
            $this->stateInfo = $this->stateMap['ERROR_HTTP_LINK'];
            return false;
        }

        preg_match('/(^https*:\/\/[^:\/]+)/', $imgUrl, $matches);
        $host_with_protocol = count($matches) > 1 ? $matches[1] : '';

        // 判断是否是合法 url
        if(!filter_var($host_with_protocol, FILTER_VALIDATE_URL)){
            $this->stateInfo = $this->stateMap['INVALID_URL'];
            return false;
        }

        preg_match('/^https*:\/\/(.+)/', $host_with_protocol, $matches);
        $host_without_protocol = count($matches) > 1 ? $matches[1] : '';

        // 此时提取出来的可能是 ip 也有可能是域名, 先获取 ip
        $ip = gethostbyname($host_without_protocol);
        // 判断是否是私有 ip
        if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)){
            $this->stateInfo = $this->stateMap['INVALID_IP'];
            return false;
        }

        // 获取请求头并检测死链
        $heads = get_headers($imgUrl, 1);
        if(!(stristr($heads[0], '200') && stristr($heads[0], 'OK'))){
            $this->stateInfo = $this->stateMap['ERROR_DEAD_LINK'];
            return false;
        }

        // 格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower(strrchr($imgUrl, '.'));
        if(!in_array($fileType, $this->config['allowFiles']) || !isset($heads['Content-Type']) || !stristr($heads['Content-Type'], 'image')){
            $this->stateInfo = $this->stateMap['ERROR_HTTP_CONTENTTYPE'];
            return false;
        }

        // 打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create([
            'http' => ['follow_location'=>false]  // don't follow redirects
        ]);
        readfile($imgUrl, false, $context);
        $img = ob_get_contents();
        ob_end_clean();
        preg_match('/[\/]([^\/]*)[\.]?[^\.\/]*$/', $imgUrl, $m);

        $this->realName = $m ? $m[1] : '';
        $this->fileSize = strlen($img);
        $this->fileType = self::getFileType($this->realName);
        $this->fullName = self::getFullName($this->realName, $this->config['pathFormat'], $this->fileType);
        $this->fileName = self::getFileName($this->fullName);

        // 检查文件大小是否超出网站限制
        if($this->fileSize > $this->config['maxSize']){
            $this->stateInfo = $this->stateMap['ERROR_SIZE_EXCEED'];
            return false;
        }

        // 创建目录
        if(($info = Helper::createDir(dirname($this->fullName))) !== true){
            $this->stateInfo = $info;
            return false;
        }

        // 移动文件
        if(!(file_put_contents($this->fullName, $img) && file_exists($this->fullName))){  // 移动失败
            $this->stateInfo = $this->stateMap['ERROR_WRITE_CONTENT'];
            return false;
        }else{  // 移动成功
            $this->stateInfo = $this->stateMap[0];
            return true;
        }
    }

    /**
     * 处理base64编码的图片上传
     * @return bool
     */
    private function upBase64()
    {
        $base64Data = Yii::$app->request->post($this->fileField);
        $img = base64_decode($base64Data);

        $this->realName = $this->config['realName'];
        $this->fileSize = strlen($img);
        $this->fileMime = 'image/png';  // png
        $this->fileType = self::getFileType($this->realName);
        $this->fullName = self::getFullName($this->realName, $this->config['pathFormat'], $this->fileType);
        $this->fileName = self::getFileName($this->fullName);

        // 检查文件大小是否超出网站限制
        if($this->fileSize > $this->config['maxSize']){
            $this->stateInfo = $this->stateMap['ERROR_SIZE_EXCEED'];
            return false;
        }

        // 创建目录
        if(($info = Helper::createDir(dirname($this->fullName))) !== true){
            $this->stateInfo = $info;
            return false;
        }

        // 移动文件
        if(!(file_put_contents($this->fullName, $img) && file_exists($this->fullName))){  // 移动失败
            $this->stateInfo = $this->stateMap['ERROR_WRITE_CONTENT'];
            return false;
        }elseif($this->saveDatabase){
            if(self::saveDatabase()){
                $this->stateInfo = $this->stateMap[0];
                return true;
            }else{
                $this->stateInfo = $this->stateMap['ERROR_DATABASE'];
                return false;
            }
        }else{
            $this->stateInfo = $this->stateMap[0];
            return true;
        }
    }

    /**
     * 生成缩略图
     * @param string $tempName  // 临时文件(路径)
     * @return bool
     */
    private function makeThumb($tempName)
    {
        // 获取上传图片的宽高值
        if(!($imgInfo = Helper::getImageInfo($tempName))){
            return false;
        }
        $width = $this->config['thumbWidth'];   // 缩略图的宽度
        $height = $this->config['thumbHeight'];   // 缩略图的高度
        $bgimg = imagecreatetruecolor($width, $height);  // 新建一个真彩色图像
        $white = imagecolorallocate($bgimg, 255, 255, 255);  // 为一幅图像分配颜色
        imagefill($bgimg, 0, 0, $white);  // 图形着色
        switch($this->fileMime){
            case 'image/gif':
                $im = @imagecreatefromgif($tempName);
                $outfun = 'imagegif';
                break;
            case 'image/png':
                $im = @imagecreatefrompng($tempName);
                $outfun = 'imagepng';
                break;
            case 'image/jpeg':
                $im = @imagecreatefromjpeg($tempName);
                $outfun = 'imagejpeg';
                break;
            default: return false;
        }

        $copy = false;  // 是否直接复制图片到背景图上
        if($imgInfo['width'] / $width >= $imgInfo['height'] / $height){  // 宽度较大时
            if($imgInfo['width'] > $width){  // 图片宽度大于缩略图宽度
                if($this->config['thumbCut']){  // 左右两端裁掉
                    $new_height = $height;
                    $new_width = ($height * $imgInfo['width']) / $imgInfo['height'];
                    $bg_x = ($width - $new_width) / 2;
                    imagecopyresampled($bgimg, $im, $bg_x, 0, 0, 0, $new_width, $new_height, $imgInfo['width'], $imgInfo['height']);
                }else{  // 上下两端留白
                    $new_width = $width;
                    $new_height = ($width * $imgInfo['height']) / $imgInfo['width'];
                    $bg_y = ceil(abs(($height - $new_height) / 2));  // 取绝对值并进一法取整
                    imagecopyresampled($bgimg, $im, 0, $bg_y, 0, 0, $new_width, $new_height, $imgInfo['width'], $imgInfo['height']);
                }
            }else{
                $copy = true;
            }
        }else{  // 高度较大时
            if($imgInfo['height'] > $height){  // 图片高度大于缩略图高度
                if($this->config['thumbCut']){  // 上下两端裁掉
                    $new_width = $width;
                    $new_height = ($width * $imgInfo['height']) / $imgInfo['width'];
                    $bg_y = ($height - $new_height) / 2;
                    imagecopyresampled($bgimg, $im, 0, $bg_y, 0, 0, $new_width, $new_height, $imgInfo['width'], $imgInfo['height']);
                }else{  // 左右两端留白
                    $new_height = $height;
                    $new_width = ($height * $imgInfo['width']) / $imgInfo['height'];
                    $bg_x = ceil(abs(($width - $new_width) / 2));  // 取绝对值并进一法取整
                    imagecopyresampled($bgimg, $im, $bg_x, 0, 0, 0, $new_width, $new_height, $imgInfo['width'], $imgInfo['height']);
                }
            }else{
                $copy = true;
            }
        }
        if($copy){  // 直接复制图片到背景图上
            $bg_x = ceil(($width - $imgInfo['width']) / 2);
            $bg_y = ceil(($height - $imgInfo['height']) / 2);
            imagecopy($bgimg, $im, $bg_x, $bg_y, 0, 0, $imgInfo['width'], $imgInfo['height']);
        }

        $this->thumbName = self::getThumb($this->fullName);  // 获取缩略图文件名

        // 创建目录
        if(($info = Helper::createDir(dirname($this->thumbName))) !== true){
            $this->stateInfo = $info;
            return false;
        }

        $outfun($bgimg, $this->thumbName);  // 输出保存缩略图
        imagedestroy($bgimg);  // 销毁背景图
        return true;
    }

    /**
     * 返回完整的文件名
     * @param string $fileName
     * @param string $format  eg:'uploads/image/{yyyy}{mm}/{yy}{mm}{dd}_{hh}{ii}{ss}_{rand:4}'
     * @param string $fileType  eg:'jpg'
     * @return string  eg:'/uploads/image/201707/170722_110145_7367.jpg'
     * $format 可用变量:
     * {filename} 会替换成原文件名[要注意中文文件乱码问题]
     * {rand:6} 会替换成随机数, 后面的数字是随机数的位数
     * {time} 会替换成时间戳
     * {yyyy} 会替换成四位年份
     * {yy} 会替换成两位年份
     * {mm} 会替换成两位月份
     * {dd} 会替换成两位日期
     * {hh} 会替换成两位小时
     * {ii} 会替换成两位分钟
     * {ss} 会替换成两位秒
     * 非法字符 \ : * ? " < > |
     * 具请体看线上文档: http://fex.baidu.com/ueditor/#server-path #3.1
     */
    public static function getFullName($fileName, $format, $fileType)
    {
        //替换日期事件
        $t = time();
        $d = explode('-', date('Y-y-m-d-H-i-s'));
        //$format = $this->config['pathFormat'];
        $format = str_replace('{yyyy}', $d[0], $format);
        $format = str_replace('{yy}', $d[1], $format);
        $format = str_replace('{mm}', $d[2], $format);
        $format = str_replace('{dd}', $d[3], $format);
        $format = str_replace('{hh}', $d[4], $format);
        $format = str_replace('{ii}', $d[5], $format);
        $format = str_replace('{ss}', $d[6], $format);
        $format = str_replace('{time}', $t, $format);

        //过滤文件名的非法字符, 并替换文件名
        $realName = substr($fileName, 0, strrpos($fileName, '.'));
        $realName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $realName);
        $format = str_replace('{filename}', $realName, $format);

        //替换随机字符串
        $randNum = rand(1, 10000000000) . rand(1, 10000000000);
        if(preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)){
            $format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
        }

        return $format . $fileType;
    }

    /**
     * 截取文件名, 返回完整文件名的文件名部分
     * @param string $fullName
     * @return bool|string  eg:'170722_110145_7367.jpg'
     */
    public static function getFileName($fullName)
    {
        return substr($fullName, strrpos($fullName, '/') + 1);
    }

    /**
     * 返回文件扩展名
     * @param string $fileName
     * @return string  eg:'.jpg'
     */
    public static function getFileType($fileName)
    {
        return strtolower(strrchr($fileName, '.'));
    }

    /**
     * 保存上传信息到数据库
     * @return bool
     */
    private function saveDatabase()
    {
        $model = new Upload([
            'real_name' => $this->realName,
            'file_name' => $this->fullName,
            'thumb_name' => $this->thumbName,
            'file_ext' => $this->fileType,
            'file_mime' => $this->fileMime,
            'file_size' => $this->fileSize,
            'md5' => md5_file($this->fullName),
            'sha1' => sha1_file($this->fullName),
        ]);
        return $model->save();
    }

    /**
     * 获取缩略图文件名
     * @param string $url  // 文件url路径
     * @return mixed
     */
    public static function getThumb($url)
    {
        return str_replace('image', 'thumb', $url);
    }
}
