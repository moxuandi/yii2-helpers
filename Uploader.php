<?php
namespace moxuandi\helpers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use yii\imagine\Image;
use yii\web\UploadedFile;

/**
 * Class Uploader 通用上传类
 *
 * @author zhangmoxuan <1104984259@qq.com>
 * @link http://www.zhangmoxuan.com
 * @QQ 1104984259
 * @Date 2019-2-4
 *
 * 说明: 为兼容 UEditor编辑器(http://ueditor.baidu.com), `$config['allowFiles']`中的扩展名全都带有前缀'.', eg: ['.png', '.jpg', '.jpeg'].
 *
 * \yii\imagine\Image 的可用方法:
 * `Image::thumbnail()`: 生成缩略图
 * `Image::crop()`: 裁剪图片
 * `Image::frame()`: 给图片添加边框
 * `Image::watermark()`: 添加图片水印
 * `Image::text()`: 添加文字水印
 * `Image::resize()`: 调整图像大小
 * `Image::autorotate()`:
 */
class Uploader
{
    /**
     * @var array 上传配置信息
     * 可用的数组的键如下:
     * - `maxSize`: int 上传大小限制,  默认为: 1*1024*1024 (1M).
     * - `allowFiles`: array 允许上传的文件类型, 默认为: ['.png', '.jpg', '.jpeg']. 设置为空数组或`false`时, 将不验证文件类型!
     * - `pathFormat`: string 文件保存路径, 默认为: '/uploads/image/{time}'.
     * - `realName`: string 图片的原始名称, 处理 base64 编码的图片时有效.
     * - `thumb`: false|array 缩略图配置. 设置为`false`时不生成缩略图; 设置为数组时, 有以下可用值:
     *   * `width`: int 缩略图的宽度.
     *   * `height`: int 缩略图的高度.
     *   * `mode`: string 生成缩略图的模式, 可用值: 'inset'(补白), 'outbound'(裁剪, 默认值).
     *   * `match`: array 缩略图路径的替换规则, 必须是两个元素的数组, 默认为: ['image', 'thumb']. 注意, 当两个元素的值相同时, 将不会保存原图, 而仅保留缩略图.
     * - `crop`: false|array 裁剪图像配置. 设置为`false`时不生成裁剪图; 设置为数组时, 有以下可用值:
     *   * `width`: int 裁剪图的宽度.
     *   * `height`: int 裁剪图的高度.
     *   * `top`: int 裁剪图顶部的偏移, y轴起点, 默认为`0`.
     *   * `left`: int 裁剪图左侧的偏移, x轴起点, 默认为`0`.
     *   * `match`: array 裁剪图路径的替换规则, 必须是两个元素的数组, 默认为: ['image', 'crop']. 注意, 当两个元素的值相同时, 将不会保存原图, 而仅保留裁剪图.
     * - `frame`: false|array 添加边框的配置. 设置为`false`时不添加边框; 设置为数组时, 有以下可用值:
     *   * `margin`: int 边框的宽度, 默认为`20`.
     *   * `color`: string 边框的颜色, 十六进制颜色编码, 可以不带`#`, 默认为`666`.
     *   * `alpha`: int 边框的透明度, 可能仅`png`图片生效, 默认为`100`.
     *   * `match`: array 添加边框后保存路径的替换规则, 必须是两个元素的数组, 默认为: ['image', 'frame']. 注意, 当两个元素的值相同时, 将不会保存原图, 而仅保留添加边框后的图片.
     * - `watermark`: false|array 添加图片水印的配置. 设置为`false`时不添加图片水印; 设置为数组时, 有以下可用值:
     *   * `watermarkImage`: string 水印图片的绝对路径.
     *   * `top`: int 水印图片的顶部距离原图顶部的偏移, y轴起点, 默认为`0`.
     *   * `left`: int 水印图片的左侧距离原图左侧的偏移, x轴起点, 默认为`0`.
     *   * `match`: array 添加图片水印后保存路径的替换规则, 必须是两个元素的数组, 默认为: ['image', 'watermark']. 注意, 当两个元素的值相同时, 将不会保存原图, 而仅保添加图片水印后的图片.
     * - `text`: false|array 添加文字水印的配置. 设置为`false`时不添加文字水印; 设置为数组时, 有以下可用值:
     *   * `text`: string 水印文字的内容.
     *   * `fontFile`: string 字体文件, 可以是绝对路径或别名.
     *   * `top`: int 水印文字的顶部距离原图顶部的偏移, y轴起点, 默认为`0`.
     *   * `left`: int 水印文字的左侧距离原图左侧的偏移, x轴起点, 默认为`0`.
     *   * `fontOptions`: array 字体属性, 支持以下三个值:
     *     * `size`: int 字体的大小, 单位像素(`px`), 默认为`12`.
     *     * `color`: string 字体的颜色, 十六进制颜色编码, 可以不带`#`, 默认为`fff`.
     *     * `angle`: int 写入文本的角度, 默认为`0`.
     *   * `match`: array 添加文字水印后保存路径的替换规则, 必须是两个元素的数组, 默认为: ['image', 'text']. 注意, 当两个元素的值相同时, 将不会保存原图, 而仅保添加文字水印后的图片.
     * - `resize`: false|array 调整图片大小的配置. 设置为`false`时不调整大小; 设置为数组时, 有以下可用值:
     *   * `width`: int 图片调整后的宽度.
     *   * `height`: int 图片调整后的高度.
     *   * `keepAspectRatio`: bool 是否保持图片纵横比, 默认为`true`.
     *     * 如果设置为`true`, 图片将在不超过`width`和`height`的情况下, 等比例缩放;
     *   * `allowUpscaling`: bool 如果原图很小, 图片是否放大, 默认为`false`.
     *   * `match`: array 调整图片大小后保存路径的替换规则, 必须是两个元素的数组, 默认为: ['image', 'resize']. 注意, 当两个元素的值相同时, 将不会保存原图, 而仅保留调整大小的图片.
     */
    public $config = [];
    /**
     * @var UploadedFile|null 上传对象
     */
    public $file;
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
    public $thumbName;
    /**
     * @var string 完整的裁剪图文件名(带路径)
     */
    public $cropName;
    /**
     * @var string 添加边框后的完整的文件名(带路径)
     */
    public $frameName;
    /**
     * @var string 添加水印图片后的完整的文件名(带路径)
     */
    public $watermarkName;
    /**
     * @var string 添加文字图片后的完整的文件名(带路径)
     */
    public $textName;
    /**
     * @var string 调整图片大小后的完整的文件名(带路径)
     */
    public $resizeName;
    /**
     * @var int 文件大小, 单位:B
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
     * @var string 根目录绝对路径
     */
    public $rootPath;
    /**
     * @var string 根目录的URL
     */
    public $rootUrl;
    /**
     * @var string 分片暂存区
     */
    public $chunkPath = '/uploads/chunks';
    /**
     * @var string|null 上传状态信息
     */
    public $stateInfo;


    /**
     * Uploader constructor.
     * @param string $fileField 文件上传域名称, eg: 'upfile'.
     * @param array $config 上传配置信息.
     * @param string $type 上传类型, 可用值: 'remote'(拉取远程图片), 'base64'(处理base64编码的图片上传), 'upload'(普通上传, 默认值).
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    public function __construct($fileField, $config = [], $type = 'upload')
    {
        $_config = [
            'maxSize' => 1*1024*1024,
            'allowFiles' => ['.png', '.jpg', '.jpeg'],
            'pathFormat' => '/uploads/image/{time}',
            'realName' => 'scrawl.png',
            'thumb' => false,  // 缩略图
            'crop' => false,  // 裁剪图
            'frame' => false,  // 添加边框
            'watermark' => false,  // 添加图片水印
            'text' => false,  // 添加文字水印
            'resize' => false,  // 调整图片大小
        ];
        $this->rootPath = ArrayHelper::remove($config, 'rootPath', dirname(Yii::$app->request->scriptFile));
        $this->rootUrl = ArrayHelper::remove($config, 'rootUrl', Yii::$app->request->hostInfo);
        $this->config = array_merge($_config, $config);  // 不使用 ArrayHelper::merge() 方法, 是因为其会递归合并数组.
        $this->file = UploadedFile::getInstanceByName($fileField);  // 获取上传对象

        switch($type){
            case 'remote': $result = self::uploadFile(); break;
            case 'base64': $result = self::uploadBase64($fileField); break;
            case 'upload':
            default: $result = self::uploadHandle(); break;
        }
        if($result){
            $this->stateInfo = self::$stateMap[0];
            return true;
        }
        return false;
    }

    /**
     * 分离大文件分片上传与普通上传.
     * @return bool
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    private function uploadHandle()
    {
        return Yii::$app->request->post('chunks') ? self::uploadChunkFile() : self::uploadFile();
    }

    /**
     * 普通文件上传.
     * @return bool
     * @throws \yii\base\Exception
     */
    private function uploadFile()
    {
        // 检查上传对象是否为空
        if(empty($this->file)){
            $this->stateInfo = self::$stateMap[4];
            return false;
        }

        // 校验 $_FILES['upfile']['error'] 的错误
        if($this->file->error){
            $this->stateInfo = self::$stateMap[$this->file->error];
            return false;
        }

        // 检查临时文件是否存在
        if(!file_exists($this->file->tempName)){
            $this->stateInfo = self::$stateMap['ERROR_TMP_FILE_NOT_FOUND'];
            return false;
        }

        // 检查文件是否是通过 HTTP POST 上传的
        if(!is_uploaded_file($this->file->tempName)){
            $this->stateInfo = self::$stateMap['ERROR_HTTP_UPLOAD'];
            return false;
        }

        // 检查文件大小是否超出网站限制
        if($this->file->size > $this->config['maxSize']){
            $this->stateInfo = self::$stateMap['ERROR_SIZE_EXCEED'];
            return false;
        }

        $this->realName = $this->file->name;
        $this->fileSize = $this->file->size;
        $this->fileType = $this->file->type;
        $this->fileExt = $this->file->extension;

        // 检查文件类型(扩展名)是否符合网站要求
        if($this->config['allowFiles'] && !in_array('.' . $this->fileExt, $this->config['allowFiles'])){
            $this->stateInfo = self::$stateMap['ERROR_TYPE_NOT_ALLOWED'];
            return false;
        }

        $this->fullName = Helper::getFullName($this->realName, $this->config['pathFormat'], $this->fileExt);
        $this->fileName = StringHelper::basename($this->fullName);

        // 创建目录
        $fullPath = FileHelper::normalizePath($this->rootPath . $this->fullName);  // 文件在磁盘上的绝对路径
        if(!FileHelper::createDirectory(dirname($fullPath))){
            $this->stateInfo = self::$stateMap['ERROR_CREATE_DIR'];
            return false;
        }

        // 保存上传文件
        if($this->file->saveAs($fullPath)){
            if(!self::processImage($fullPath)){
                return false;
            }
            return true;
        }else{
            $this->stateInfo = self::$stateMap['ERROR_FILE_MOVE'];
            return false;
        }
    }

    /**
     * 大文件分片上传
     * @return bool
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    private function uploadChunkFile()
    {
        // 检查上传对象是否为空
        if(empty($this->file)){
            $this->stateInfo = self::$stateMap[4];
            return false;
        }

        // 校验 $_FILES['upfile']['error'] 的错误
        if($this->file->error){
            $this->stateInfo = self::$stateMap[$this->file->error];
            return false;
        }

        // 检查临时文件是否存在
        if(!file_exists($this->file->tempName)){
            $this->stateInfo = self::$stateMap['ERROR_TMP_FILE_NOT_FOUND'];
            return false;
        }

        // 检查文件是否是通过 HTTP POST 上传的
        if(!is_uploaded_file($this->file->tempName)){
            $this->stateInfo = self::$stateMap['ERROR_HTTP_UPLOAD'];
            return false;
        }

        $post = Yii::$app->request->post();  // 接收分片信息
        $this->realName = $post['name'];
        $this->fileSize = $post['size'];
        $this->fileType = $post['type'];
        $this->fileExt = $this->file->extension;

        // 检查文件大小是否超出网站限制
        if($this->fileSize > $this->config['maxSize']){
            $this->stateInfo = self::$stateMap['ERROR_SIZE_EXCEED'];
            return false;
        }

        // 检查文件类型(扩展名)是否符合网站要求
        if($this->config['allowFiles'] && !in_array('.' . $this->fileExt, $this->config['allowFiles'])){
            $this->stateInfo = self::$stateMap['ERROR_TYPE_NOT_ALLOWED'];
            return false;
        }

        // 保存分片
        $chunkPath = FileHelper::normalizePath($this->rootPath . $this->chunkPath . DIRECTORY_SEPARATOR . md5($this->realName));
        if(!FileHelper::createDirectory($chunkPath)){
            $this->stateInfo = self::$stateMap['ERROR_CREATE_DIR'];
            return false;
        }
        $this->file->saveAs($chunkPath . DIRECTORY_SEPARATOR . 'chunk_' . $post['chunk']);

        // 分片全部上传完成, 并且分片暂存区保存有所有分片
        if($post['chunk'] + 1 == $post['chunks'] && count(FileHelper::findFiles($chunkPath, ['recursive' => false])) == $post['chunks']){
            $this->fullName = Helper::getFullName($this->realName, $this->config['pathFormat'], $this->fileExt);
            $this->fileName = StringHelper::basename($this->fullName);

            // 创建目录
            $fullPath = FileHelper::normalizePath($this->rootPath . $this->fullName);  // 文件在磁盘上的绝对路径
            if(!FileHelper::createDirectory(dirname($fullPath))){
                $this->stateInfo = self::$stateMap['ERROR_CREATE_DIR'];
                return false;
            }

            // 合并分片
            $blob = '';
            for($i = 0; $i < $post['chunks']; $i++){
                $blob .= file_get_contents($chunkPath . DIRECTORY_SEPARATOR . 'chunk_' . $i);  // 依次读取所有分片内容
            }
            file_put_contents($fullPath, $blob);  // 保存分片内容到文件
            FileHelper::removeDirectory($chunkPath);  // 删除对应分片暂存区

            if(!self::processImage($fullPath)){
                return false;
            }

            //$this->file->size = $this->fileSize;  // 重置上传对象的大小, 可选
            //$this->file->type = $this->fileType;  // 重置上传对象的MIME类型, 可选
            return true;
        }else{
            $this->stateInfo = self::$stateMap['ERROR_CHUNK_DEFECT'];
            return false;
        }
    }

    /**
     * 处理 base64 编码的图片上传(主要是 UEditor 编辑器的涂鸦功能).
     * @param string $fileField 文件上传域名称, eg: 'upfile'.
     * @return bool
     * @throws \yii\base\Exception
     */
    private function uploadBase64($fileField)
    {
        $base64Data = Yii::$app->request->post($fileField);
        $baseImg = base64_decode($base64Data);  // 解码图片数据

        $this->realName = $this->config['realName'];
        $this->fileSize = strlen($baseImg);
        $this->fileType = 'image/png';
        $this->fileExt = Helper::getExtension($this->realName);
        $this->fullName = Helper::getFullName($this->realName, $this->config['pathFormat'], $this->fileExt);
        $this->fileName = StringHelper::basename($this->fullName);

        // 检查文件大小是否超出网站限制
        if($this->fileSize > $this->config['maxSize']){
            $this->stateInfo = self::$stateMap['ERROR_SIZE_EXCEED'];
            return false;
        }

        // 创建目录
        $fullPath = FileHelper::normalizePath($this->rootPath . $this->fullName);  // 文件在磁盘上的绝对路径
        if(!FileHelper::createDirectory(dirname($fullPath))){
            $this->stateInfo = self::$stateMap['ERROR_CREATE_DIR'];
            return false;
        }

        // 将图片数据写入文件, 并检查文件是否存在.
        if(file_put_contents($fullPath, $baseImg) && file_exists($fullPath)){
            return true;
        }else{
            $this->stateInfo = self::$stateMap['ERROR_WRITE_CONTENT'];
            return false;
        }
    }

    /**
     * 对图片进一步处理
     * @param string $tempName 图片的绝对路径, 或上传图片的临时文件的路径.
     * @return bool
     * @throws \yii\base\Exception
     */
    private function processImage($tempName)
    {
        if(in_array($this->fileExt, ['jpg', 'jpeg', 'png', 'gif'])){
            if($this->config['thumb']){  // 生成缩略图
                if(!self::makeThumb($tempName)){
                    $this->stateInfo = $this->stateInfo ? $this->stateInfo : self::$stateMap['ERROR_MAKE_THUMB'];
                    return false;
                }
            }
            if($this->config['crop']){  // 生成裁剪图
                if(!self::cropImage($tempName)){
                    $this->stateInfo = $this->stateInfo ? $this->stateInfo : self::$stateMap['ERROR_MAKE_CROP'];
                    return false;
                }
            }
            if($this->config['frame']){  // 添加边框
                if(!self::frameImage($tempName)){
                    $this->stateInfo = $this->stateInfo ? $this->stateInfo : self::$stateMap['ERROR_MAKE_FRAME'];
                    return false;
                }
            }
            if($this->config['watermark']){  // 添加图片水印
                if(!self::watermarkImage($tempName)){
                    $this->stateInfo = $this->stateInfo ? $this->stateInfo : self::$stateMap['ERROR_MAKE_WATERMARK'];
                    return false;
                }
            }
            if($this->config['text']){  // 添加文字水印
                if(!self::textImage($tempName)){
                    $this->stateInfo = $this->stateInfo ? $this->stateInfo : self::$stateMap['ERROR_MAKE_TEXT'];
                    return false;
                }
            }
            if($this->config['resize']){  // 调整图片大小
                if(!self::resizeImage($tempName)){
                    $this->stateInfo = $this->stateInfo ? $this->stateInfo : self::$stateMap['ERROR_MAKE_RESIZE'];
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 生成缩略图.
     * @param string $tempName 图片的绝对路径, 或上传图片的临时文件的路径.
     * @return bool 缩略图生成失败时, Image 会抛出异常.
     * @throws \yii\base\Exception
     */
    private function makeThumb($tempName)
    {
        $width = ArrayHelper::getValue($this->config['thumb'], 'width');
        $height = ArrayHelper::getValue($this->config['thumb'], 'height');
        $mode = ArrayHelper::getValue($this->config['thumb'], 'mode', 'outbound');
        list($imageStr, $thumbStr) = ArrayHelper::getValue($this->config['thumb'], 'match', ['image', 'thumb']);

        if(!$width && !$height){
            $this->stateInfo = self::$stateMap['ERROR_THUMB_WIDTH_HEIGHT'];
            return false;
        }

        $this->thumbName = Helper::getThumbName($this->fullName, $imageStr, $thumbStr);
        $thumbPath = FileHelper::normalizePath($this->rootPath . $this->thumbName);  // 文件在磁盘上的绝对路径

        // 创建目录
        if(!FileHelper::createDirectory(dirname($thumbPath))){
            $this->stateInfo = self::$stateMap['ERROR_CREATE_DIR'];
            return false;
        }

        // 生成并保存缩略图
        Image::thumbnail($tempName, $width, $height, $mode)->save($thumbPath);
        return true;
    }

    /**
     * 生成裁剪图
     * @param string $tempName 图片的绝对路径, 或上传图片的临时文件的路径.
     * @return bool 裁剪图生成失败时, Image 会抛出异常.
     * @throws \yii\base\Exception
     */
    private function cropImage($tempName)
    {
        $width = ArrayHelper::getValue($this->config['crop'], 'width');
        $height = ArrayHelper::getValue($this->config['crop'], 'height');
        $top = ArrayHelper::getValue($this->config['crop'], 'top', 0);
        $left = ArrayHelper::getValue($this->config['crop'], 'left', 0);
        list($imageStr, $cropStr) = ArrayHelper::getValue($this->config['crop'], 'match', ['image', 'crop']);

        $this->cropName = Helper::getThumbName($this->fullName, $imageStr, $cropStr);
        $cropPath = FileHelper::normalizePath($this->rootPath . $this->cropName);  // 文件在磁盘上的绝对路径

        // 创建目录
        if(!FileHelper::createDirectory(dirname($cropPath))){
            $this->stateInfo = self::$stateMap['ERROR_CREATE_DIR'];
            return false;
        }

        // 生成并保存裁剪图
        Image::crop($tempName, $width, $height, [$left, $top])->save($cropPath);
        return true;
    }

    /**
     * 给图片添加边框
     * @param string $tempName 图片的绝对路径, 或上传图片的临时文件的路径.
     * @return bool
     * @throws \yii\base\Exception
     */
    private function frameImage($tempName)
    {
        $margin = ArrayHelper::getValue($this->config['frame'], 'margin', 20);
        $color = ArrayHelper::getValue($this->config['frame'], 'color', '666');
        $alpha = ArrayHelper::getValue($this->config['frame'], 'alpha', 100);
        list($imageStr, $frameStr) = ArrayHelper::getValue($this->config['frame'], 'match', ['image', 'frame']);

        $this->frameName = Helper::getThumbName($this->fullName, $imageStr, $frameStr);
        $framePath = FileHelper::normalizePath($this->rootPath . $this->frameName);  // 文件在磁盘上的绝对路径

        // 创建目录
        if(!FileHelper::createDirectory(dirname($framePath))){
            $this->stateInfo = self::$stateMap['ERROR_CREATE_DIR'];
            return false;
        }

        // 生成并保存添加边框后的图片
        Image::frame($tempName, $margin, $color, $alpha)->save($framePath);
        return true;
    }

    /**
     * 给图片添加图片水印
     * @param string $tempName 图片的绝对路径, 或上传图片的临时文件的路径.
     * @return bool
     * @throws \yii\base\Exception
     */
    private function watermarkImage($tempName)
    {
        $watermarkImage = ArrayHelper::getValue($this->config['watermark'], 'watermarkImage');
        $top = ArrayHelper::getValue($this->config['watermark'], 'top', 0);
        $left = ArrayHelper::getValue($this->config['watermark'], 'left', 0);
        list($imageStr, $watermarkStr) = ArrayHelper::getValue($this->config['watermark'], 'match', ['image', 'watermark']);

        $this->watermarkName = Helper::getThumbName($this->fullName, $imageStr, $watermarkStr);
        $watermarkPath = FileHelper::normalizePath($this->rootPath . $this->watermarkName);  // 文件在磁盘上的绝对路径

        // 创建目录
        if(!FileHelper::createDirectory(dirname($watermarkPath))){
            $this->stateInfo = self::$stateMap['ERROR_CREATE_DIR'];
            return false;
        }

        // 生成并保存添加图片水印后的图片
        Image::watermark($tempName, $watermarkImage, [$left, $top])->save($watermarkPath);
        return true;
    }

    /**
     * 给图片添加文字水印
     * @param string $tempName 图片的绝对路径, 或上传图片的临时文件的路径.
     * @return bool
     * @throws \yii\base\Exception
     */
    private function textImage($tempName)
    {
        $text = ArrayHelper::getValue($this->config['text'], 'text');
        $fontFile = ArrayHelper::getValue($this->config['text'], 'fontFile');
        $top = ArrayHelper::getValue($this->config['text'], 'top', 0);
        $left = ArrayHelper::getValue($this->config['text'], 'left', 0);
        $fontOptions = ArrayHelper::getValue($this->config['text'], 'fontOptions', []);
        list($imageStr, $textStr) = ArrayHelper::getValue($this->config['text'], 'match', ['image', 'text']);

        $this->textName = Helper::getThumbName($this->fullName, $imageStr, $textStr);
        $textPath = FileHelper::normalizePath($this->rootPath . $this->textName);  // 文件在磁盘上的绝对路径

        // 创建目录
        if(!FileHelper::createDirectory(dirname($textPath))){
            $this->stateInfo = self::$stateMap['ERROR_CREATE_DIR'];
            return false;
        }

        // 生成并保存添加文字水印后的图片
        Image::text($tempName, $text, $fontFile, [$left, $top], $fontOptions)->save($textPath);
        return true;
    }

    /**
     * 调整图片大小
     * @param string $tempName 图片的绝对路径, 或上传图片的临时文件的路径.
     * @return bool
     * @throws \yii\base\Exception
     */
    private function resizeImage($tempName)
    {
        $width = ArrayHelper::getValue($this->config['resize'], 'width');
        $height = ArrayHelper::getValue($this->config['resize'], '$height');
        $keepAspectRatio = ArrayHelper::getValue($this->config['resize'], 'keepAspectRatio', true);
        $allowUpscaling = ArrayHelper::getValue($this->config['resize'], 'allowUpscaling', false);
        list($imageStr, $resizeStr) = ArrayHelper::getValue($this->config['resize'], 'match', ['image', 'resize']);

        $this->resizeName = Helper::getThumbName($this->fullName, $imageStr, $resizeStr);
        $resizePath = FileHelper::normalizePath($this->rootPath . $this->resizeName);  // 文件在磁盘上的绝对路径

        // 创建目录
        if(!FileHelper::createDirectory(dirname($resizePath))){
            $this->stateInfo = self::$stateMap['ERROR_CREATE_DIR'];
            return false;
        }

        // 生成并保存调整图片大小后的图片
        Image::resize($tempName, $width, $height, $keepAspectRatio, $allowUpscaling)->save($resizePath);
        return true;
    }


    /**
     * @var array 上传状态映射表, 国际化用户需考虑此处数据的国际化.
     * @see http://www.php.net/manual/en/features.file-upload.errors.php
     */
    static $stateMap = [
        0 => 'SUCCESS',  // UPLOAD_ERR_OK, 上传成功标记, 在`UEditor`中内不可改变, 否则`flash`判断会出错
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
        'ERROR_MAKE_CROP' => '创建裁剪图失败',
        'ERROR_MAKE_FRAME' => '添加边框失败',
        'ERROR_MAKE_WATERMARK' => '添加图片水印失败',
        'ERROR_MAKE_TEXT' => '添加文字水印失败',
        'ERROR_MAKE_RESIZE' => '调整图片大小失败',
        'ERROR_CHUNK_DEFECT' => '分片不完整',
        'ERROR_UNKNOWN' => '未知错误',
        //'ERROR_DEAD_LINK' => '链接不可用',
        //'ERROR_HTTP_LINK' => '链接不是http链接',
        //'ERROR_HTTP_CONTENTTYPE' => '链接contentType不正确',

        'ERROR_THUMB_WIDTH_HEIGHT' => '宽度和高度至少有一个值才能生成缩略图',
    ];
}
