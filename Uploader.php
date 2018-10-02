<?php
namespace moxuandi\helpers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\imagine\Image;
use yii\web\UploadedFile;

/**
 * Class Uploader 通用上传类
 *
 * @author  zhangmoxuan <1104984259@qq.com>
 * @link  http://www.zhangmoxuan.com
 * @QQ  1104984259
 * @Date  2017/12/1
 *
 * 说明: 为兼容 UEditor编辑器(http://ueditor.baidu.com), `$config['allowFiles'])`中的扩展名全都带有前缀'.', eg: ['.png', '.jpg', '.jpeg'].
 */
class Uploader
{
    /**
     * @var string 根目录绝对路径
     */
    public $rootPath;
    /**
     * @var string 原始文件名
     */
    public $realName;
    /**
     * @var string 新文件名
     */
    public $fileName;
    /**
     * @var string 完整的文件名(带路径)
     */
    public $fullName;
    /**
     * @var string 完整的缩略图文件名(带路径)
     */
    public $thumbName = '';
    /**
     * @var string 文件大小
     */
    public $fileSize;
    /**
     * @var string 文件的 MIME 类型
     */
    public $fileType;
    /**
     * @var string 文件扩展名
     */
    public $fileExt;
    /**
     * @var null|UploadedFile 上传对象
     */
    public $file;
    /**
     * @var array 上传配置信息
     * 可用的数组的键如下:
     * - maxSize: int 上传大小限制,  默认为: 1*1024*1024 (1M).
     * - allowFiles: array 允许上传的文件类型, 默认为: ['.png', '.jpg', '.jpeg'].
     * - pathFormat: string 文件保存路径, 默认为: '/uploads/image/{time}'.
     * - thumbStatus: bool 是否生成缩略图, 默认为: false.
     * - thumbWidth: int 缩略图的宽度, 默认为: 300.
     * - thumbHeight: int 缩略图的高度, 默认为: 200.
     * - thumbMode: string 生成缩略图的模式, 可用值: 'inset'(补白), 'outbound'(裁剪, 默认值).
     * - realName: string 图片的原始名称, 处理 base64 编码的图片时有效.
     */
    public $config;
    /**
     * @var string 分片暂存区
     */
    public $chunkPath = '/uploads/chunks';
    /**
     * @var string 上传状态信息
     */
    public $stateInfo;
    /**
     * @var array 上传状态映射表, 国际化用户需考虑此处数据的国际化.
     * @see http://www.php.net/manual/en/features.file-upload.errors.php
     */
    private $stateMap = [
        0 => 'SUCCESS',  // UPLOAD_ERR_OK, 上传成功标记, 在UEditor中内不可改变, 否则flash判断会出错
        1 => '文件大小超出 php.ini 中的 upload_max_filesize 限制' ,  // UPLOAD_ERR_INI_SIZE
        2 => '文件大小超出 HTML 表单中的 MAX_FILE_SIZE 限制' ,  // UPLOAD_ERR_FORM_SIZE
        3 => '文件未被完整上传' ,  // UPLOAD_ERR_PARTIAL
        4 => '没有文件被上传' ,  // UPLOAD_ERR_NO_FILE
        6 => '临时文件夹不存在' ,  // UPLOAD_ERR_NO_TMP_DIR
        7 => '无法将文件写入磁盘',  // UPLOAD_ERR_CANT_WRITE
        8 => '因 php 扩展停止文件上传',  // UPLOAD_ERR_EXTENSION
        //'ERROR_TMP_FILE' => '临时文件错误',
        'ERROR_TMP_FILE_NOT_FOUND' => '找不到临时文件',
        'ERROR_SIZE_EXCEED' => '文件大小超出网站限制',
        'ERROR_TYPE_NOT_ALLOWED' => '文件类型不允许',
        'ERROR_CREATE_DIR' => '目录创建失败',
        'ERROR_DIR_NOT_WRITEABLE' => '目录没有写入权限',
        'ERROR_FILE_MOVE' => '文件保存时出错',
        //'ERROR_FILE_NOT_FOUND' => '找不到上传文件',
        'ERROR_WRITE_CONTENT' => '写入文件内容错误',
        'ERROR_HTTP_UPLOAD' => '非法上传',
        'ERROR_MAKE_THUMB' => '创建缩略图失败',
        'ERROR_CHUNK_DEFECT' => '分片不完整',
        'ERROR_UNKNOWN' => '未知错误',
        //'ERROR_DEAD_LINK' => '链接不可用',
        //'ERROR_HTTP_LINK' => '链接不是http链接',
        //'ERROR_HTTP_CONTENTTYPE' => '链接contentType不正确',
    ];


    /**
     * Uploader constructor.
     * @param string $fileField 文件上传域名称, eg: 'upfile'.
     * @param array $config 上传配置信息.
     * @param string $type 上传类型, 可用值: 'remote'(拉取远程图片), 'base64'(处理base64编码的图片上传), 'upload'(普通上传, 默认值).
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    public function __construct($fileField, $config=[], $type='upload')
    {
        $_config = [
            'maxSize' => 1*1024*1024,
            'allowFiles' => ['.png', '.jpg', '.jpeg'],
            'pathFormat' => '/uploads/image/{time}',
            'thumbStatus' => false,
            'thumbWidth' => 300,
            'thumbHeight' => 200,
            'thumbMode' => 'outbound'
        ];

        $this->rootPath = ArrayHelper::remove($config, 'rootPath', dirname(Yii::$app->request->scriptFile));
        $this->config = array_merge($_config, $config);  // 不使用 ArrayHelper::merge() 方法, 是因为其会递归合并数组.
        $this->file = UploadedFile::getInstanceByName($fileField);  // 获取上传对象

        switch($type){
            case 'remote': $return = self::uploadFile(); break;
            case 'base64': $return = self::uploadBase64($fileField); break;
            //case 'upload':
            default: $return = self::uploadHandle(); break;
        }

        if($return){
            $this->stateInfo = $this->stateMap[0];
            return true;
        }else{
            return false;
        }
    }

    /**
     * 分离大文件分片上传与普通上传.
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    private function uploadHandle()
    {
        return Yii::$app->request->post('chunks') ? self::uploadChunkFile() : self::uploadFile();
    }

    /**
     * 普通文件上传.
     * @return bool 上传成功返回 true, 否则返回 false.
     * @throws \yii\base\Exception
     */
    private function uploadFile()
    {
        // 检查上传对象是否为空
        if(empty($this->file)){
            $this->stateInfo = $this->stateMap[4];
            return false;
        }

        // 校验 $_FILES['upfile']['error'] 的错误
        if($this->file->error){
            $this->stateInfo = $this->stateMap[$this->file->error];
            return false;
        }

        // 检查临时文件是否存在
        if(!file_exists($this->file->tempName)){
            $this->stateInfo = $this->stateMap['ERROR_TMP_FILE_NOT_FOUND'];
            return false;
        }

        // 检查文件是否是通过 HTTP POST 上传的
        if(!is_uploaded_file($this->file->tempName)){
            $this->stateInfo = $this->stateMap['ERROR_HTTP_UPLOAD'];
            return false;
        }

        // 检查文件大小是否超出网站限制
        if($this->file->size > $this->config['maxSize']){
            $this->stateInfo = $this->stateMap['ERROR_SIZE_EXCEED'];
            return false;
        }

        $this->realName = $this->file->name;
        $this->fileSize = $this->file->size;
        $this->fileType = $this->file->type;
        $this->fileExt = $this->file->extension;
        //$this->fileExt = Helper::getExtension($this->realName);

        // 检查文件类型(扩展名)是否符合网站要求
        if(!in_array('.' . $this->fileExt, $this->config['allowFiles'])){
            $this->stateInfo = $this->stateMap['ERROR_TYPE_NOT_ALLOWED'];
            return false;
        }

        $this->fullName = Helper::getFullName($this->realName, $this->config['pathFormat'], $this->fileExt);
        $this->fileName = Helper::getFileName($this->fullName);

        // 判断是否生成缩略图
        if($this->config['thumbStatus'] && in_array($this->fileExt, ['jpg', 'jpeg', 'png'])){
            if(!self::makeThumb($this->file->tempName)){
                $this->stateInfo = $this->stateInfo ? $this->stateInfo : $this->stateMap['ERROR_MAKE_THUMB'];
                return false;
            }
        }

        // 创建目录
        $fullPath = FileHelper::normalizePath($this->rootPath . $this->fullName);  // 文件在磁盘上的绝对路径
        if(!FileHelper::createDirectory(dirname($fullPath))){
            $this->stateInfo = $this->stateMap['ERROR_CREATE_DIR'];
            return false;
        }

        // 保存上传文件
        if(!$this->file->saveAs($fullPath)){
            $this->stateInfo = $this->stateMap['ERROR_FILE_MOVE'];
            return false;
        }else{
            return true;
        }
    }

    /**
     * 大文件分片上传.
     * @return bool 上传成功返回 true, 否则返回 false.
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    private function uploadChunkFile()
    {
        // 检查上传对象是否为空
        if(empty($this->file)){
            $this->stateInfo = $this->stateMap[4];
            return false;
        }

        // 校验 $_FILES['upfile']['error'] 的错误
        if($this->file->error){
            $this->stateInfo = $this->stateMap[$this->file->error];
            return false;
        }

        // 检查临时文件是否存在
        if(!file_exists($this->file->tempName)){
            $this->stateInfo = $this->stateMap['ERROR_TMP_FILE_NOT_FOUND'];
            return false;
        }

        // 检查文件是否是通过 HTTP POST 上传的
        if(!is_uploaded_file($this->file->tempName)){
            $this->stateInfo = $this->stateMap['ERROR_HTTP_UPLOAD'];
            return false;
        }

        $post = Yii::$app->request->post();  // 接收分片信息
        $this->realName = $post['name'];
        $this->fileSize = $post['size'];
        $this->fileType = $post['type'];
        $this->fileExt = $this->file->extension;
        //$this->fileExt = Helper::getExtension($this->realName);

        // 检查文件大小是否超出网站限制
        if($this->fileSize > $this->config['maxSize']){
            $this->stateInfo = $this->stateMap['ERROR_SIZE_EXCEED'];
            return false;
        }

        // 检查文件类型(扩展名)是否符合网站要求
        if(!in_array('.' . $this->fileExt, $this->config['allowFiles'])){
            $this->stateInfo = $this->stateMap['ERROR_TYPE_NOT_ALLOWED'];
            return false;
        }

        // 保存分片
        $chunkPath = FileHelper::normalizePath($this->rootPath . $this->chunkPath . DIRECTORY_SEPARATOR . md5($this->realName));
        if(!FileHelper::createDirectory($chunkPath)){
            $this->stateInfo = $this->stateMap['ERROR_CREATE_DIR'];
            return false;
        }
        $this->file->saveAs($chunkPath . DIRECTORY_SEPARATOR . 'chunk_' . $post['chunk']);

        // 分片全部上传完成, 并且分片暂存区保存有所有分片
        if($post['chunk'] + 1 == $post['chunks'] && count(FileHelper::findFiles($chunkPath, ['recursive'=>false])) == $post['chunks']){
            $this->fullName = Helper::getFullName($this->realName, $this->config['pathFormat'], $this->fileExt);
            $this->fileName = Helper::getFileName($this->fullName);

            // 创建目录
            $fullPath = FileHelper::normalizePath($this->rootPath . $this->fullName);  // 文件在磁盘上的绝对路径
            if(!FileHelper::createDirectory(dirname($fullPath))){
                $this->stateInfo = $this->stateMap['ERROR_CREATE_DIR'];
                return false;
            }

            // 合并分片
            $blob = '';
            for($i=0; $i< $post['chunks']; $i++){
                $blob .= file_get_contents($chunkPath . DIRECTORY_SEPARATOR . 'chunk_' . $i);  // 依次读取所有分片内容
            }
            file_put_contents($fullPath, $blob);  // 保存分片内容到文件
            FileHelper::removeDirectory($chunkPath);  // 删除对应分片暂存区

            // 判断是否生成缩略图
            if($this->config['thumbStatus'] && in_array($this->fileExt, ['jpg', 'jpeg', 'png'])){
                if(!self::makeThumb($fullPath)){
                    $this->stateInfo = $this->stateInfo ? $this->stateInfo : $this->stateMap['ERROR_MAKE_THUMB'];
                    return false;
                }
            }

            //$this->file->size = $this->fileSize;  // 重置上传对象的大小, 可选
            //$this->file->type = $this->fileType;  // 重置上传对象的MIME类型, 可选
            return true;
        }else{
            $this->stateInfo = $this->stateMap['ERROR_CHUNK_DEFECT'];
            return false;
        }
    }

    /**
     * 处理 base64 编码的图片上传(主要是 UEditor 编辑器的涂鸦功能).
     * @param string $fileField 文件上传域名称, eg: 'upfile'.
     * @return bool 上传成功返回 true, 否则返回 false.
     * @throws \yii\base\Exception
     */
    private function uploadBase64($fileField)
    {
        $base64Data = Yii::$app->request->post($fileField);
        $baseImg = base64_decode($base64Data);  // 解码图片数据

        $this->realName = $this->config['realName'];
        $this->fileSize = strlen($baseImg);
        $this->fileType = 'image/png';  // png
        $this->fileExt = Helper::getExtension($this->realName);
        $this->fullName = Helper::getFullName($this->realName, $this->config['pathFormat'], $this->fileExt);
        $this->fileName = Helper::getFileName($this->fullName);

        // 检查文件大小是否超出网站限制
        if($this->fileSize > $this->config['maxSize']){
            $this->stateInfo = $this->stateMap['ERROR_SIZE_EXCEED'];
            return false;
        }

        // 创建目录
        $fullPath = FileHelper::normalizePath($this->rootPath . $this->fullName);  // 文件在磁盘上的绝对路径
        if(!FileHelper::createDirectory(dirname($fullPath))){
            $this->stateInfo = $this->stateMap['ERROR_CREATE_DIR'];
            return false;
        }

        // 将图片数据写入文件, 并检查文件是否存在.
        if(!(file_put_contents($fullPath, $baseImg) && file_exists($fullPath))){
            $this->stateInfo = $this->stateMap['ERROR_WRITE_CONTENT'];
            return false;
        }else{
            return true;
        }
    }

    /**
     * 生成缩略图.
     * @param string $tempName 图片的路径, 或上传图片的临时文件的路径. eg: '/uploads/image/20170722_110145_7367.jpg'.
     * @return bool 缩略图生成失败时, Image 会抛出异常.
     * @throws \yii\base\Exception
     */
    private function makeThumb($tempName)
    {
        $this->thumbName = Helper::getThumb($this->fullName);

        // 创建目录
        $fullPath = FileHelper::normalizePath($this->rootPath . $this->thumbName);  // 文件在磁盘上的绝对路径
        if(!FileHelper::createDirectory(dirname($fullPath))){
            $this->stateInfo = $this->stateMap['ERROR_CREATE_DIR'];
            return false;
        }
        // 生成并保存缩略图
        Image::thumbnail($tempName, $this->config['thumbWidth'], $this->config['thumbHeight'], $this->config['thumbMode'])->save($fullPath);
        return true;
    }
}
