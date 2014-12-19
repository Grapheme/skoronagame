<?php

namespace app\models;

use app\helpers\LoaderFH;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\FileHelper;
use yii\db\Connection;

/**
 * This is the model class for table "sb_questions"
 *
 * @property integer $id
 * @property string $game
 * @property integer $player
 * @property string $date_start
 * @property string $date_stop
 */
class Games extends \yii\db\ActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%games}}';
    }

    public function getGame()
    {
        return $this->hasMany(Games::className(), ['game' => 'game']);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['player','points', 'raiting', 'place'], 'integer'],
            [['date_start','date_stop'], 'safe'],
            [['game', 'color', 'type'], 'string', 'max' => 40],
            [['nickname'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'game' => 'Game',
            'player' => 'Player',
            'date_start' => 'Date',
            'date_stop' => 'Date',
            'color' => 'Color',
            'nickname' => 'nickname',
            'type' => 'type',
            'points' => 'points',
            'raiting' => 'raiting',
            'place' => 'place',
        ];
    }

    public static function getLastGame($id) {

        $game = Games::find()
            ->select('game, max(date_start) as mdate')
            ->where('player = '.$id)
            ->orderBy("mdate DESC")
            ->groupBy('game')
            ->with('game')
            ->asArray()
            ->one();

        return $game;
    }

}
