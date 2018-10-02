<?php
namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%upload}}".
 *
 * @property integer $id
 * @property string $real_name
 * @property string $file_name
 * @property string $thumb_name
 * @property string $file_ext
 * @property string $file_mime
 * @property integer $file_size
 * @property string $md5
 * @property string $sha1
 * @property integer $down_hits
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 */
class Upload extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%upload}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            BlameableBehavior::className(),
        ];
    }

    public function rules()
    {
        return [
            [['real_name'], 'required'],
            [['file_size', 'down_hits', 'created_by', 'updated_by', 'created_at', 'updated_at'], 'integer'],
            [['file_size', 'down_hits'], 'default', 'value' => 0],
            [['md5', 'sha1'], 'string'],
            [['real_name', 'file_name', 'thumb_name', 'file_ext', 'file_mime'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'real_name' => '原始文件名称',
            'file_name' => '文件路径',
            'thumb_name' => '缩略图路径',
            'file_ext' => '扩展名',
            'file_mime' => 'MIME类型',
            'file_size' => '文件大小',
            'md5' => 'MD5',
            'sha1' => 'SHA1',
            'down_hits' => '下载次数',
            'created_by' => '创建者',
            'updated_by' => '更新者',
            'created_at' => '添加时间',
            'updated_at' => '更新时间',
        ];
    }
}
