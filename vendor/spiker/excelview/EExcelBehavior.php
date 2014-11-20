<?php
/**
 * Behavior to extend
* @author Spiker
* @license MIT
* @version 0.1
 */
namespace eview;

use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;

class EExcelBehavior extends Behavior
{

	/**
	 * Attached action to export a data provider into excel or other formats
	 * Usage:
	 * 		In your controller, create a method
	 *			public function behaviours()
	 *			{
	 *				return array(
	 *					'eexcelview'=>array(
	 *						'class'=>'ext.eexcelview.EExcelBehavior',
	 *					),
	 *				);
	 *			}
	 *		Then, to use
	 *			public function actionToCSV()
	 * 			{
	 *				$model = Sala::model()->findAll();
	 *
	 *	 			$columns = array(
	 *					'id',
	 *					'nombre',
	 *					array(
	 *						'name' => 'relatedModel.name',
	 *						'header' => 'Related Model Name',
	 *						'footer'=>'Total Balance: 10', 
	 *					),
	 *				);
	 *				
	 *	 			$this->actionExcel($model, $columns, 'MyTitle', array(), 'CSV');
	 *			}
	 *	Of course, this is only a wrapper so you can edit in many ways to allow more specialization
	 * @param null string|CDataProvider|array $model the data provider for the grid.
	 * @param null array $columns array with specif columns and its format. Defaults to all attributes.
	 * @param null string $title title for the file
	 * @param null array $documentDetails details of the document
	 * @param null string $exportType format to export (Excel5,Excel2007, PDF, HTML, CSV). Defaults to Excel2007 (xlsx)
	 */
	public function toExcel($models=array(), $columns_=array(), $title=null, $documentDetails=array(), $exportType='Excel2007')
	{

//		// Be sure to be attached to an instance of CController
//		if(! $this->owner instanceof CController && !$this->owner  instanceof CConsoleCommand ){
//			Yii::log('EExcelBehavior can only be attached to an instance of CControler', 'error', 'system.base.CBehavior');
//			return;
//		}



        $dp=array();
        $clm=array();
        $num = 0;
        //перебор всех датапровайдеров
        foreach($models as $name=>$model) {
            if(!isset($model))
            {
                // Get the controller name as the model
                $model = ucfirst($this->owner->id);
                $dataProvider = new ActiveDataProvider($model);
            }
            // Next, check if is string
            elseif(is_string($model))
            {
                // We fetch all records
                $dataProvider = new ActiveDataProvider($model);
            }
            // Next, check if is an array
            elseif(is_array($model))
            {
                // If is an array, we assume is an array of Model instance
                // so let's add that data to the CDataprovider
                if(empty($model)) {
                    // If we got an empty array as model... we can't do much with that information
                    Yii::error('Empty array passed as data, nothing to do *here*', 'info');
                    return;
                } else {

                    // At this point we should do type checking on every entry of the array againts a model
                    // But as we don't know what model is, we must try and catch an exception

                    // Get model name
                    $className = get_class($model[0]); // get the class from the first element

                    try {
                        // Set an CActiveDataProvider for this model
                        $dataProvider = new ActiveDataProvider($className);

                        // And add the data
                        $dataProvider->setModels([$model]);

                    } catch(Exception $e) {
                        // Something went wrong
                        Yii::error($e->getMessage(), 'info');
                        return;
                    }
                }

            }
            // Or if it's an subclass of CDataProvider
            elseif($model instanceof ActiveDataProvider)
            {
                // We use the provided data set
                $dataProvider = $model;
            }
            elseif($model instanceof ArrayDataProvider)
            {
                // We use the provided data set
                $dataProvider = $model;
            }
            else {
                // We have no valid data set
                Yii::error('Invalid data set provided', 'error');
                return;
            }

            //массив датапровайдеров
            $dp[$name] = $dataProvider;

            //шапка
            if(isset($columns_[$num])) $clm[$num]=$columns_[$num];

            if(!isset($columns_[$num]) || !is_array($columns_[$num]))
                $clm[] = array();

            $num++;
        }

        /**
		 * Next, we need to check if user passed an array for columns, if not, we define an empty one meaning we want
		 * all columns by default
		 */


        /**
		 * Finally we export the contents specified
		 */
		$config = array(
			'title'=>$title,
			'exportType' => $exportType,

			'dataProviders'=> $dp,
			'columns_all' => $clm,
		);

		$arg = array_merge($config, $documentDetails);

            $r= new EExcelViewConsole($arg);
            $r->run();

//        if($this->owner  instanceof CConsoleCommand){
//            Yii::import('ext.eexcelview.EExcelViewConsole');
//            $r= new EExcelViewConsole($arg,true);
//            $r->run();
//        }
//        else
//            $this->owner->widget('eview.EExcelViewConsole', $arg);

//
		//$this->owner->widget('ext.eexcelview.EExcelView', $arg);

		Yii::app()->end();
	}
}