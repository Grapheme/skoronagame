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
 * @property string $question
 * @property string $variants
 * @property integer $answer
 * @property string $type
 */
class Questions extends \yii\db\ActiveRecord
{
    public $variant;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%questions}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['variants'], 'string', 'length'=>[10,200]],
            [['variant'], 'validateVariant'],

            [['answer'], 'integer'],
            [['answer'], 'required', 'message'=>'Правильный ответ должен быть отмечен'],

            [['question'], 'string', 'max' => 200],
            [['type'], 'string', 'max' => 10]
        ];
    }

    public function validateVariant($attribute, $params)
    {
        if (count($this->$attribute) < 2)
            $this->addError($attribute, 'Вопрос должен иметь минимум 2 варианта ответа".');

        foreach($this->$attribute as $variant){
            if(strlen($variant)<3)
                $this->addError($attribute, 'Минимум 3 символа в ответе".');
        }
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'question' => 'Вопрос',
            'variants' => 'Варианты ответа',
            'variant' => 'Вариант ответа',
            'answer' => 'Верный ответ',
            'type' => 'Type',
        ];
    }

    public static function questToJson($variant, $answer){

        $answer = $variant[$answer];
        $variant = array_values($variant);
        $answer = array_search($answer, $variant);

        $rez = ['answer' => $answer, 'variant' => json_encode($variant, JSON_UNESCAPED_UNICODE)];
        return $rez;
    }

    public function addQuest(){

        $model = Questions::questToJson($this->variant, $this->answer);
        $this->variants = $model['variant'];
        $this->answer   = $model['answer'];
        $this->type     = 'quest';

        return $this->save();
    }

    public function addQuiz(){
        $this->type     = 'quiz';
        return $this->save();
    }

    public function search($params)
    {
        $query = Questions::find();
        $dataProvider=new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);


        if (!($this->load($params))) {
            return $dataProvider;
        }

        $query
            ->andFilterWhere(['or',
                ['and',['type' => 'quiz'],['like', 'answer', $this->answer]],
                ['and',['type' => 'quest'],['like', 'variants', $this->answer]]]
            )
            ->andFilterWhere(['type' => $this->type])
            ->andFilterWhere(['like', 'question', $this->question]);

        return $dataProvider;
    }

    public static function exportQuestions($type = 'all'){

        $rez = [];
        $cond = ($type != 'all')? ['type' => $type]:'';

        $model = Questions::find()->where($cond)->asArray()->all();

        foreach ($model as &$val) {

            if($val['type'] == 'quest') {

                $variants = json_decode($val['variants']);

                $val['variants'] = implode(PHP_EOL, (Array)$variants);

                $val['answer'] = $variants[$val['answer']];
            }
        }

        $dp = new ArrayDataProvider();
        $dp->setModels($model);
        return $dp;
    }

    public static function getQuiz(){

        $model = Questions::find()
            ->select(['question', 'answer', 'id'])
            ->where(['type' => 'quiz'])
            ->orderBy('RAND()')
            ->asArray()
            ->one();

        return $model;
    }

    public static function getQuest(){

        $model = Questions::find()
            ->select(['question', 'answer', 'variants', 'id'])
            ->where(['type' => 'quest'])
            ->orderBy('RAND()')
            ->asArray()
            ->one();

        return $model;
    }

}
