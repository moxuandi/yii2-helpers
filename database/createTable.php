<?php
namespace app\controllers;

use Yii;

class createTable
{
    // 添加 upload 表
    public function actionUpload()
    {
        $mg = new \yii\db\Migration();
        $mg->createTable('upload', [
            'id' => $mg->primaryKey(),
            'real_name' => $mg->string()->notNull()->comment('原始文件名称'),
            'file_name' => $mg->string()->comment('文件路径'),
            'thumb_name' => $mg->string()->comment('缩略图路径'),
            'file_ext' => $mg->string()->comment('扩展名'),
            'file_mime' => $mg->string()->comment('MIME类型'),
            'file_size' => $mg->integer()->defaultValue(0)->comment('文件大小'),
            'md5' => $mg->text()->comment('MD5'),
            'sha1' => $mg->text()->comment('SHA1'),
            'down_hits' => $mg->integer()->defaultValue(0)->comment('下载次数'),
            'created_by' => $mg->integer()->notNull()->comment('创建者'),
            'updated_by' => $mg->integer()->notNull()->comment('更新者'),
            'created_at' => $mg->integer()->notNull()->comment('添加时间'),
            'updated_at' => $mg->integer()->notNull()->comment('更新时间'),
        ], 'ENGINE = INNODB');
    }
}
