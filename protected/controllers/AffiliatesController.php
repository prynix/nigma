<?php

class AffiliatesController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column1', meaning
	 * using two-column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout='//layouts/column1';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
		);
	}
	
	public function accessRules()
	{
		return array(
			array('allow',
				'actions' =>array('admin','view','create','update'),
				'roles'   =>array('admin','media_manager','affiliates_manager'),
			),
			array('allow',
				'actions' =>array('admin','view'),
				'roles'   =>array('media','finance'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new Affiliates('search');
		$model->unsetAttributes();  // clear any default values
		// $model->providers->status = 'Active';
		if(isset($_GET['Affiliates']))
			$model->attributes=$_GET['Affiliates'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}


	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$modelAffi = $this->loadModel($id);
		$modelProv = Providers::model()->findByPk($modelAffi->providers_id);

		$this->renderPartial('_view', array( 
			'modelAffi'=>$modelAffi,
			'modelProv'=>$modelProv,
		), false, true);
	}


	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$modelAffi=new Affiliates;
		$modelProv=new Providers;

		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation($modelAffi, $modelProv);

		if(isset($_POST['Affiliates']) && isset($_POST['Providers']))
		{
			$modelProv->attributes=$_POST['Providers'];
			if ($modelProv->save()) {
				$modelAffi->attributes=$_POST['Affiliates'];
				$modelAffi->providers_id = $modelProv->id;
				if ($modelAffi->save())
					$this->redirect(array('admin'));
			}
		}

		$this->renderFormAjax($modelAffi, $modelProv);
	}


	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$modelAffi=$this->loadModel($id);
		$modelProv=Providers::model()->findByPk($modelAffi->providers_id);

		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation($modelAffi, $modelProv);

		if(isset($_POST['Affiliates']) && isset($_POST['Providers']))
		{
			$modelAffi->attributes=$_POST['Affiliates'];
			$modelProv->attributes=$_POST['Providers'];
			if($modelAffi->save() && $modelProv->save())
				$this->redirect(array('admin'));
		}

		$this->renderFormAjax($modelAffi, $modelProv);
	}


	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Affiliates the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=Affiliates::model()->with('providers')->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}


	/**
	 * Performs the AJAX validation.
	 * @param Affiliates $model the model to be validated
	 */
	protected function performAjaxValidation($modelAffi, $modelProv)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='affiliates-form')
		{
			echo CActiveForm::validate(array($modelAffi, $modelProv));
			Yii::app()->end();
		}
	}

	/**
	 * [renderFormAjax description]
	 * @param  [type] $modelAffi [description]
	 * @param  [type] $modelProv [description]
	 * @return [type]        	 [description]
	 */
	private function renderFormAjax($modelAffi, $modelProv)
	{
		$users    = CHtml::listData(Users::model()->findAll(array('condition'=>'status="Active"','order'=>'username')), 'id', 'username');

		$this->renderPartial('_form',array(
			'modelAffi' =>$modelAffi,
			'modelProv' =>$modelProv,
			'users'     =>$users,
		), false, true);
	}

	// Uncomment the following methods and override them if needed
	/*
	public function filters()
	{
		// return the filter configuration for this controller, e.g.:
		return array(
			'inlineFilterName',
			array(
				'class'=>'path.to.FilterClass',
				'propertyName'=>'propertyValue',
			),
		);
	}

	public function actions()
	{
		// return external action classes, e.g.:
		return array(
			'action1'=>'path.to.ActionClass',
			'action2'=>array(
				'class'=>'path.to.AnotherActionClass',
				'propertyName'=>'propertyValue',
			),
		);
	}
	*/
}