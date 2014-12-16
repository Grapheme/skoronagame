<?php

namespace app\models;

use Yii;
use yii\db\Connection;

/**
 * This is the model class for table "sb_settings".
 *
 * @property integer $id
 * @property string $name
 * @property string $value
 * @property string $desc
 */
class Settings extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%settings}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'value', 'desc'], 'string', 'max' => 40]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'value' => 'Value',
            'desc' => 'Desc',
        ];
    }

    public static function getAllSettings(){

        $cache = Yii::$app->cache;
        $settings = $cache->get('settings');

        if ($settings === false) {

            $settings = Settings::find()->asArray()->all();
            $cache->set('settings', $settings);
        }

        return $settings;
    }
}
