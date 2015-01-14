<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "sb_log".
 *
 * @property integer $id
 * @property integer $LEVEL
 * @property string $category
 * @property integer $log_time
 * @property string $prefix
 * @property string $message
 */
class Log extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['LEVEL', 'log_time'], 'integer'],
            [['prefix', 'message'], 'string'],
            [['category'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'LEVEL' => 'Level',
            'category' => 'Category',
            'log_time' => 'Log Time',
            'prefix' => 'Prefix',
            'message' => 'Message',
        ];
    }
}
