<?php

namespace app\helpers;

use yii\helpers\FileHelper;
use app\helpers\ImageHandler;
use yii\helpers\Url;
use Yii;

class LoaderFH extends FileHelper{

    private static $sub_fld = 2;
    /*
     * get filename
     */
    public static function getRandomFileName($path, $extension='')
    {
        $extension = $extension ? '.' . $extension : '';

        if(!file_exists($path)) FileHelper::createDirectory($path);

        do {
            $name = md5(microtime() . rand(0, 9999));
            $file = $path . $name . $extension;
        } while (file_exists($file));

        return $name;
    }

    public static function getIMG($file,$folder ='',$default='',$style='')
    {
        $file_path = IMG_PATH.$folder.'/'.$file;

        if(!empty($file) && file_exists($file_path) && !empty($default))
            $path = \Yii::$app->params['img_url'].$folder.$file;
        else
            $path = \Yii::$app->params['img_url'].'/'.$default;

        return '<img  style="'.$style.'" src="'.$path.'">';
    }

    public static function loadIMG($file,$folder ='')
    {

        $flag=$folder;
        $extension = strtolower($file->extension);

        $sub_folder = (self::$sub_fld)? rand(1,self::$sub_fld).'/': '';

        if (!empty($folder) && $folder[0]!='/')$folder = '/'.$folder;
        $file_path=IMG_PATH.$folder.'/'.$sub_folder;

        $filename = LoaderFH::getRandomFileName($file_path, $extension);
        $filename = $filename . '.' . $extension;

        if(!$file->saveAs($file_path.$filename.'_')) return false;

        $ih = new ImageHandler();

        if($flag=='image'){
            $ih
                ->load($file_path.$filename.'_') //Загрузка оригинала картинки

                ->thumb('255', '170') //Создание превьюшки высотой 100px
                ->save($file_path.$filename,false,100); //Сохранение превьюшки в папку thumbs
        }else
        {
            $ih
                ->load($file_path.$filename.'_') //Загрузка оригинала картинки

                ->resizeCanvas('145','55', array(0,0,0))
                ->save($file_path.$filename,false,100); //Сохранение превьюшки в папку thumbs
        }
        unlink($file_path.$filename.'_');
//            ->reload()
//            ->resize('378', false)
//            ->save($file_path.'/max/'.$filename,false,100)
//        ;

        return '/'.$sub_folder.$filename;
    }

    public static function removeIMG($file,$folder='')
    {

        if (!empty($folder) && $folder[0]!='/')$folder = '/'.$folder;
        $file_path = IMG_PATH.$folder.'/'.$file;

        if(file_exists($file_path)){
            return unlink($file_path);
        }

        return false;
    }

    public static function getUrlData($file){

        $v= substr(md5(filemtime(BASE_PATH.'/htdocs/'.$file)), 0, 8);

        return Url::to(['/'.$file,'v'=>$v]);
    }

    public static function shareLink($soc,$url=''){

        $socLink = [
            'vk'=>'http://vk.com/share.php?noparse=false&url=%s',
            'ok'=>'http://www.odnoklassniki.ru/dk?st.cmd=addShare&st.s=1&st._surl=%s',
            'fb'=>'http://www.facebook.com/sharer.php?u=%s',
            'gp'=>'https://plus.google.com/share?url=%s',
        ];

        $url = urlencode($url);
        $link = sprintf($socLink[$soc],$url).urlencode(Url::base(true).'/?id=');

        return $link;
    }

    public static function metaTag($meta = false){

        //если есть точка
        if($meta){
            $tags['title'] = Yii::$app->params['meta']['title'];
            $tags['description'] = $meta['short_title'].', посети виртуальную выставку';
            $tags['image'] = Url::base(true).Yii::$app->params['meta']['image'];

            $tags['seo_title'] = $meta['short_title'].' - '.Yii::$app->params['meta']['title'];

            $patern="/([A-ZА-Я]+.+)[.!?]+[\s]+/sU";

            if(preg_match($patern, $meta['main_text'], $matches))
                $tags['seo_description'] = $matches[0];


        } else {
            $meta = Yii::$app->params['meta'];
            $tags['title'] = $meta['title'];
            $tags['description'] = $meta['description'];
            $tags['image'] = Url::base(true).$meta['image'];

            $tags['seo_title'] = $meta['title'];
            $tags['seo_description'] = $meta['description'];
        }

        return $tags;
    }
}