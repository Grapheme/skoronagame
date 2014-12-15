<?php

namespace app\models;

use app\helpers\LoaderFH;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\FileHelper;
use yii\db\Connection;

/**
 * This is the model class for table "sb_questions".
 *
 * @property integer $id
 * @property string $id_game
 * @property integer $lvl
 * @property string $quest
 * @property string $answer
 * @property string $variants
 * @property string $pl_answer
 * @property string $points
 */
class Levels extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%levels}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lvl','region'], 'integer'],
            [['id_game'], 'string', 'max' => 40],
            [['date'], 'safe'],
            [['quest', 'answer', 'variants', 'type'], 'string', 'max' => 100],
            [['pl_answer'], 'string', 'max' => 500]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_game' => 'Id Game',
            'lvl' => 'Lvl',
            'quest' => 'Quest',
            'answer' => 'Answer',
            'variants' => 'Variants',
            'pl_answer' => 'Pl Answer',
            'type' => 'type',
            'region' => 'region',
            'date' => 'date',
        ];
    }

    public static function getGameLevels($game) {
        return Levels::find()->where('id_game = "'.$game.'"')->asArray()->all();
    }
}
