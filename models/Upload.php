<?php
namespace moxuandi\helpers\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%upload}}".
 *
 * @property int $id
 * @property string $real_name 原始文件名
 * @property string $file_name 新文件名
 * @property string $full_name 完整的文件名(带路径)
 * @property string $process_name 经过处理后的文件名(带路径)
 * @property int $file_size 文件大小(单位:B)
 * @property string $file_type 文件的 MIME 类型
 * @property string $file_ext 文件扩展名
 * @property string $file_md5 MD5
 * @property string $file_sha1 SHA1
 * @property int $status 状态
 * @property int $is_delete 删除
 * @property int $created_at 添加时间
 * @property int $updated_at 更新时间
 */
class Upload extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%upload}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['real_name', 'file_name', 'full_name', 'process_name', 'file_size', 'file_type', 'file_ext', 'file_md5', 'file_sha1'], 'trim'],
            [['file_size', 'status', 'is_delete'], 'integer'],
            [['real_name', 'file_name', 'full_name', 'process_name', 'file_type', 'file_ext', 'file_md5', 'file_sha1'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'real_name' => '原始文件名',
            'file_name' => '新文件名',
            'full_name' => '完整的文件名(带路径)',
            'process_name' => '经过处理后的文件名(带路径)',
            'file_size' => '文件大小(单位:B)',
            'file_type' => '文件的 MIME 类型',
            'file_ext' => '文件扩展名',
            'file_md5' => 'MD5',
            'file_sha1' => 'SHA1',
            'status' => '状态',
            'is_delete' => '删除',
            'created_at' => '添加时间',
            'updated_at' => '更新时间',
        ];
    }

    // 通用状态
    const STATUS_ACTIVE = 10;  // 启用
    const STATUS_DISABLE = 0;  // 禁用
    const STATUS_YES = 1;
    const STATUS_NO = 0;

    /**
     * 启用
     * @return bool
     */
    public function enable()
    {
        return static::updateAttributes(['status' => self::STATUS_ACTIVE]) !== false;
    }

    /**
     * 禁用
     * @return bool
     */
    public function disable()
    {
        return static::updateAttributes(['status' => self::STATUS_DISABLE]) !== false;
    }

    /**
     * 回收站/软删除
     * @param bool $real 物理删除还是逻辑删除
     * @return bool|false|int
     * @throws \Throwable
     * @throws yii\db\StaleObjectException
     */
    public function delete($real = false)
    {
        return $real ? parent::delete() : static::updateAttributes(['is_delete' => self::STATUS_YES]) !== false;
    }
}
