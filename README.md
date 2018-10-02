yii2-helpers: 助手类,通用上传类
==================

安装:
------------
使用 [composer](http://getcomposer.org/download/) 下载:
```
# 2.x(yii >= 2.0.16):
composer require moxuandi/yii2-helpers:"~2.0"

# 1.x(非重要Bug, 不再更新):
composer require moxuandi/yii2-helpers:"~1.0"

# 旧版归档(不再更新):
composer require moxuandi/yii2-helpers:"~0.1"

# 开发版:
composer require moxuandi/yii2-helpers:"dev-master"
```


用法示例:
-----
```php
// 判断当前服务器操作系统, eg: 'Linux'或'Windows'.
echo Helper::getOs();

// 获取当前微妙数, eg: 1512001416.3352.
echo Helper::microtime_float();

// 遍历文件夹.
echo Helper::scanfDir('uploads/image');

// 格式化文件大小, eg: '1.46 MB'.
echo Helper::byteFormat(1532684);

// 获取文件的扩展名, eg: 'jpg'.
echo Helper::getExtension('uploads/img.jpg');

// 获取图片的宽高值, eg: ['width'=>1366, 'height'=>768].
echo Helper::getImageInfo('uploads/img.jpg');

// 创建目录.
echo Helper::createDir('uploads/image');

// 修正路径, eg: 'uploads\img.jpg'(Windows 环境下).
echo Helper::trimPath('uploads/img.jpg');

// 获取客户端操作系统, eg: 'Windows 7'.
echo Helper::get_os($userAgent);

// 获取客户端浏览器版本, eg: ['Firefox', '57.0'].
echo Helper::getBrowser($userAgent);

// 获取指定IP的地区和网络接入商信息, eg: ['ip'=>'182.123.156.241', 'address'=>'中国-河南省-周口市', 'isp'=>'联通'].
echo Helper::getAddress('182.123.156.241');
```

调用上传类:
```php
$config = [
    'maxSize' => 5*1024*1024,  // 上传大小限制, 单位B, 默认5MB
    'allowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],  // 上传图片格式显示
    'thumbStatus' => false,  // 是否生成缩略图
    'thumbWidth' => 300,  // 缩略图宽度
    'thumbHeight' => 200,  // 缩略图高度
    'thumbCut' => 1,  // 生成缩略图的方式, 0:留白, 1:裁剪
    'pathFormat' => 'uploads/image/{yyyy}{mm}/{yy}{mm}{dd}_{hh}{ii}{ss}_{rand:4}',
      // 上传保存路径, 可以自定义保存路径和文件名格式
];
$up = new Uploader('upfile', $config);
echo Json::encode([
    'url' => $up->fullName,
    'state' => $up->stateInfo
]);
```
