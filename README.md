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
// 判断当前服务器操作系统, eg: 'Linux'或'Windows':
echo Helper::getOs();

// 获取当前微妙数, eg: 1512001416.3352:
echo Helper::microTimeFloat();

// 格式化文件大小, eg: '1.46 MB'.
echo Helper::byteFormat(1532684);

// 获取图片的宽高等属性, eg: ['width' => 1366, 'height' => 768, 'type' => 'PNG', 'mime' => 'image/png'].:
echo Helper::getImageInfo('uploads/img.png');

// 获取文件的扩展名, eg: 'jpg':
echo Helper::getExtension('uploads/img.jpg');

// 获取指定格式的文件路径, eg: 'uploads/image/201707/1512001416.jpg':
echo Helper::getFullName('img.jpg', 'uploads/image/{yyyy}{mm}/{time}');
```

调用上传类:
-----
```php
$config = [
    'maxSize' => 1*1024*1024,  // 上传大小限制, 单位B, 默认1MB
    'allowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],  // 上传图片格式显示
    'pathFormat' => 'uploads/image/{yyyy}{mm}/{yy}{mm}{dd}_{hh}{ii}{ss}_{rand:4}',  // 上传保存路径, 可以自定义保存路径和文件名格式
    //'thumb' => false,  // 不生成缩略图
    'thumb' => [  // 缩略图配置
        'width' => 300,  // 缩略图宽度
        'height' => 200, // 缩略图高度
        'mode' => 'outbound',  // 生成缩略图的模式, 可用值: 'inset'(补白), 'outbound'(裁剪, 默认值)
        'match' => ['image', 'thumb'],  // 缩略图路径的替换规则, 必须是两个元素的数组
    ]
];
$up = new Uploader('upfile', $config);
echo Json::encode([
    'url' => $up->fullName,
    'state' => $up->stateInfo
]);
```

> 提示: 缩略图配置中, `width`和`height`其中一个可以设置为`null`, 此时将按原图比例自动缩放图片. 但不能同时为`null`!

> 提示: 缩略图配置中的`match`参数, 当两个元素的值相同时, 将不会保存原图, 而仅保留缩略图.

开发计划:
-----
1. 文件/图片上传 --- 完成
2. 生成缩略图 --- 完成
3. 分片上传 --- 完成
4. 拉取远程图片
5. 处理base64编码图片 --- 完成
6. 保存上传信息到数据库
7. Image 的其它应用(水印图片, 文字, 边框, 裁剪, 旋转)
8. 可视化上传图片
