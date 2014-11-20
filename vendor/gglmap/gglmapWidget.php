<?php

namespace app\vendor\gglmap;

use yii\base\Widget;
use yii\helpers\Html;

class gglmapWidget extends Widget
{
    public $params;
    public $model=null;
    public $conf=[
        'width'=>'100%',
        'height'=>'300px',
    ];

    public function init()
    {
        parent::init();
        if ($this->params !== null) {
            $this->conf = $this->params+$this->conf;
        }
    }

    public function run()
    {
        return $this->render('map',[
            'model'=>$this->model,
            'conf'=>$this->conf]);
    }
}