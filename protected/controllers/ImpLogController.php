<?php

class ImpLogController extends Controller
{
	/**
	* @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	* using two-column layout. See 'protected/views/layouts/column2.php'.
	*/
	public $layout='//layouts/column1';

	/**
	* @return array action filters
	*/
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			);
	}

	/**
	* Specifies the access control rules.
	* This method is used by the 'accessControl' filter.
	* @return array access control rules
	*/
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','quickReport'),
				'roles'=>array('admin', 'media_buyer_manager', 'external'),
				),
			array('deny',  // deny all users
				'users'=>array('*'),
				),
			);
	}

	public function actionIndex()
	{
		KHtml::paginationController();
		
		$model = new ImpLog('search');
		
		// $model->unsetAttributes();
		// if(isset($_POST['ImpLog']))
		// 	$model->attributes=$_POST['ImpLog'];

		$this->render('index', 
			array(
				'model'=>$model
				));
	}

	public function actionQuickReport(){

		if($_POST){

			if($_POST['date'] && $_POST['tagid'] && $_POST['cpm']){

				$date = date("Y-m-d", strtotime($_POST['date']));
				$tagid = $_POST['tagid'];
				$cpm = $_POST['cpm'];
				$pubid = isset($_POST['pubid']) ? true : false;

				$tag = Tags::model()->findByPk($tagid);
				
				$select = array(
					// 'DATE(date) AS date', 
					'country', 
					'device_type', 
					'os', 
					'os_version', 
					'FORMAT( COUNT(id) ,0) AS imp',  
					'IF(
						country="'.$tag->country.'" AND 
						device_type="'.$tag->device_type.'" AND 
						os = "'.$tag->os.'" AND 
						os_version >= "'.$tag->os_version.'" , 
						FORMAT( COUNT(id)*'.$cpm.'/1000 ,2), 0 
					) AS revenue',   
					'FORMAT( COUNT(DISTINCT CONCAT_WS(" ",server_ip,user_agent)) ,0)
					AS unique_users',    
					'IF( 
						country="'.$tag->country.'" AND 
						device_type="'.$tag->device_type.'" AND 
						os = "'.$tag->os.'" AND 
						os_version >= "'.$tag->os_version.'" ,  
						FORMAT( COUNT(DISTINCT CONCAT_WS(" ",server_ip,user_agent) )*'.$cpm.'/1000 ,2) , 0
					) AS 1_24_revenue',
					); 
				if($pubid) $select[] = 'pubid';

				$where = array(
					'and',
					'tags_id = '.$tagid,
					'DATE(date) = "'.$date.'"',
					); 

				$group = array(
					'country', 
					'device_type', 
					'os', 
					'os_version',
					);
				if($pubid) $group[] = 'pubid';

				$data = Yii::app()->db->createCommand()->select($select)->from('imp_log')->where($where)->group($group)->queryAll();
				
				$select = array(
					'FORMAT( COUNT(id) ,0) AS imp',  
					'FORMAT( COUNT( DISTINCT CONCAT_WS(" ",server_ip,user_agent) ) ,0)
					AS unique_users', 
					);
				$where = array(
					'and',
					'tags_id = '.$tagid,
					'DATE(date) = "'.$date.'"',
					);
				$totalImps = Yii::app()->db->createCommand()->select($select)->from('imp_log')->where($where)->queryRow();

				$select = array(
					'FORMAT( COUNT(id) * '.$cpm.'/1000 ,2) AS revenue',   
					'FORMAT( COUNT( DISTINCT CONCAT_WS(" ",server_ip,user_agent) ) * '.$cpm.'/1000 ,2)
					AS 1_24_revenue',
					); 
				$where = array(
					'and',
					'tags_id = '.$tagid,
					'DATE(date) = "'.$date.'"',
					'country = "'.$tag->country.'"', 
					'device_type = "'.$tag->device_type.'"', 
					'os = "'.$tag->os.'"', 
					'os_version >= "'.$tag->os_version.'"',  
					);
				$totalRev = Yii::app()->db->createCommand()->select($select)->from('imp_log')->where($where)->queryRow();

				$totals = array(
					'',
					'',
					'',
					'',
					$totalImps['imp'],
					$totalRev['revenue'],
					$totalImps['unique_users'],
					$totalRev['1_24_revenue'],
					);

			}else{
				$data = 'Empty fields.';
			}				

		}else{
			$data = null;
		}
		
		$this->render('quickReport', 
			array(
				'data'=>$data,
				'totals'=>$totals,
				));
	
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