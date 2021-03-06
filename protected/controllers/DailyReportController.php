<?php

class DailyReportController extends Controller
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
			'postOnly + delete', // we only allow deletion via POST request
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
				'actions'=>array('index','view','create','update', 'response', 'updateAjax','redirectAjax','admin','delete', 'graphic', 'updateColumn', 'excelReport', 'multiRate', 'createByProvider', 'updateConvs2s', 'updateEditable'),
				'roles'=>array('admin', 'media', 'media_manager', 'business','affiliates_manager', 'account_manager','account_manager_admin','operation_manager'),
			),
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','viewAjax','redirectAjax','admin', 'excelReport', 'multiRate'),
				'roles'=>array('commercial', 'finance', 'sem'),
			),
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('setRevenue', 'setNewFields','setAllNewFields', 'updateSpendAffiliates','opportunityMA'),
				'roles'=>array('admin'),
			),
			// array('allow', // allow authenticated user to perform 'create' and 'update' actions
			// 	'actions'=>array('create','update'),
			// 	'users'=>array('@'),
			// ),
			// array('allow', // allow admin user to perform 'admin' and 'delete' actions
			// 	'actions'=>array('admin','delete'),
			// 	'users'=>array('admin'),
			// ),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	public function actionResponse($id){
		
		$action = isset($_GET['action']) ? $_GET['action'] : 'created';
		$this->layout='//layouts/modalIframe';
		$this->render('//layouts/mainResponse',array(
			'entity' => 'Daily Report',
			'action' => $action,
			'id'    => $id,
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new DailyReport;

		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation($model);

		if(isset($_POST['DailyReport']))
		{
			$model->attributes  = $_POST['DailyReport'];
			
			$modelCampaign      = Campaigns::model()->findByPk($model->campaigns_id);
			$model->providers_id = $modelCampaign->providers_id;
			$model->conv_api    = ConvLog::model()->count("campaigns_id=:campaignid AND DATE(date)=:date", array(":campaignid"=>$model->campaigns_id, ":date"=>$model->date));
			
			if ( !isset( $_POST['DailyReport']['revenue']) )
				$model->updateRevenue();

			$model->setNewFields();
			if($model->save())

				$this->redirect(array('response', 'id'=>$model->id, 'action'=>'created'));
		}

		$this->renderFormAjax($model);
	}

	public function actionCreateByProvider()
	{
		$date = date('Y-m-d', strtotime('yesterday'));
		$currentProvider = NULL;

		// If date and provider are submitted then get values
		if ( isset($_GET['providersSubmit']) ) {
			$date            = $_GET['date'];
			$currentProvider = $_GET['providers'];
		}

		if ( isset($_POST['saveSubmit']) ) {
			$tmp = Providers::model()->findByPk($_POST['DailyReport']['providers_id']);

			if ( $tmp->isNetwork() && $tmp->networks->use_vectors ) { // Is entry vectors

				$attr = $_POST['DailyReport'];
				$vector_id = substr($attr['campaigns_id'], 1);
				$vectorModel = Vectors::model()->findByPk($vector_id);

				$return = $vectorModel->explodeVector($attr);
				echo json_encode($return);

				/*
				// DEPRECATED
				$campaigns = VectorsHasCampaigns::model()->findAll('vectors_id=:vid', array(':vid' => $vector_id));

				if (empty($campaigns)) { // if vectors hasn't associated campaigns then exit
					$r         = new stdClass();
					$r->result = 'OK';
					$r->c_id   = $vector_id;
					echo json_encode($r);
					Yii::app()->end();
				}

				$porc = count($campaigns);
				$attr['imp']     = round($attr['imp']   / $porc, 0);
				$attr['imp_adv'] = round($attr['imp_adv']   / $porc, 0);
				$attr['clics']   = round($attr['clics'] / $porc, 0);
				$attr['spend']   = round($attr['spend'] / $porc, 2);

				foreach ($campaigns as $campaign) {
					$model=new DailyReport;
					$model->attributes = $attr;
					$model->campaigns_id = $campaign->campaigns_id;
					$r = $model->createByProvider();
					$r->c_id = $vector_id;
					if ($r->result == 'ERROR') {
						echo json_encode($r);
						Yii::app()->end();
					}
				}
				echo json_encode($r);
				 */
				
			} else { // Is entry campaigns
				$model=new DailyReport;
				$model->attributes = $_POST['DailyReport'];
				echo json_encode($model->createByProvider());
			}
			Yii::app()->end();
		}
		
		$criteria        = new CDbCriteria;
		$criteria->join  = 'LEFT JOIN networks ON t.id=networks.providers_id';
		$criteria->order = 'name';
		$criteria->compare('networks.has_api',0);
		$criteria->compare('t.status','Active');
		$criteria->compare('t.prospect',10);
		$providers = CHtml::listData(Providers::model()->findAll($criteria), 'id', 'name');

		$campaign = new Campaigns('search');
		$campaign->unsetAttributes();  // clear any default values

		$vector = new Vectors('search');
		$vector->unsetAttributes();  // clear any default values

		$daily = new DailyReport('search');
		$daily->unsetAttributes();  // clear any default values

		$this->render('createByProvider', array(
			'model'           => $daily,
			'campaign'        => $campaign,
			'vector'          => $vector,
			'providers'       => $providers,
			'date'            => $date,
			'currentProvider' => $currentProvider,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation($model);

		if(isset($_POST['DailyReport']))
		{
			$model->attributes=$_POST['DailyReport'];
			$model->conv_api = ConvLog::model()->count("campaigns_id=:campaignid AND DATE(date)=:date", array(":campaignid"=>$model->campaigns_id, ":date"=>$model->date));

			// allways update revenue
			// if(!isset($_POST['DailyReport']['revenue']))
			$model->updateRevenue();
			
			$model->setNewFields();
			if($model->save())
				$this->redirect(array('response', 'id'=>$model->id, 'action'=>'updated'));
		}

		$this->renderFormAjax($model);
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$this->loadModel($id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$dataProvider=new CActiveDataProvider('DailyReport');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		KHtml::paginationController();
		
		$model=new DailyReport('search');
		$model->unsetAttributes();  // clear any default values
		if ( isset($_REQUEST['download']) )
		{
			$this->_sendCsvFile( $model );
		}

		$this->render('admin',array(
			'model'=>$model,
		));			
		/*
		KHtml::paginationController();
		
		$model=new DailyReport('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['DailyReport']))
			$model->attributes=$_GET['DailyReport'];

		$criteria       = new CDbCriteria;
		// $criteria->join = 'RIGHT JOIN networks ON t.id = networks.providers_id';
		// $criteria->join = 'RIGHT JOIN affiliates ON t.id = affiliates.providers_id';
		$criteria->with = array('networks','affiliates');
		$criteria->compare('networks.providers_id','<>NULL', false, 'OR');
		$criteria->compare('affiliates.providers_id','<>NULL', false, 'OR');
		$criteria->compare('t.status','Active', false, 'AND');
		$criteria->order = 'name';
		$providers = CHtml::listData( Providers::model()->findAll($criteria) , 'name', 'name');

		$this->render('admin',array(
			'model'=>$model,
			'providers_names' => $providers,
		));
		*/
	}

	protected function _sendCsvFile ( $model )
	{
		$csvData = array();
			
		$dateStart = isset($_REQUEST['dateStart']) ? $_REQUEST['dateStart'] : date("Y-m-d", strtotime("yesterday"));
		$dateEnd = isset($_REQUEST['dateEnd']) ? $_REQUEST['dateEnd'] : date("Y-m-d", strtotime("today"));

		$sum = isset($_REQUEST['sum']) ? $_REQUEST['sum'] : array();
		$filters                    = array();
		$filters['provider']        = null; 
		$filters['advertiser']      = null;
		$filters['country']         = null;
		$filters['campaign']        = null;
		$filters['vector']          = null;
		$filters['opportunity']     = null;
		$filters['account_manager'] = null;
		$filters['category']        = null;
		$filters['carrier']         = null;

		if ( isset($_REQUEST['filter']) )
		{
			foreach ( $_REQUEST['filter'] as $f => $v )
			{
				$filters[$f] = $v;
			}
		}

		$group = isset($_REQUEST['group']) ? $_REQUEST['group'] : array();

		$dp = $model->cache(3600)->csvReport( $dateStart, $dateEnd, $group, $filters );

		foreach ($dp->getData() as $data) {
			$row = array();

			if ( $group['ID'] )
				$row['ID'] 				     	= $data->id;	

			if ( $group['AccountManager'] )
				$row['Account Manager']      	= $data->account_manager;		

			if ( $group['Date'] )
				$row['Date']      				= $data->date;		


			if ( $group['TrafficSource'] == 1 )
			{
				$row['Traffic Source']  		= $data->providers_name . ' (' . $data->providers_id . ')';
			}

			if ( $group['Advertiser'] == 1 )
				$row['Advertiser']      		= $data->advertisers_name;


			if ( $group['Category'] == 1 )
				$row['Category']	      		= $data->advertiser_cat;


			if ( $group['Campaign'] == 1 )
			{
				if ( $data->campaigns_id )
					$row['Campaign']     		= $data->campaign_name . ' ('.$data->campaigns_id.')';
				else
					$row['Campaign']			= null;
			}

			if ( $group['Vector'] == 1 )
			{
				$row['Vector']     			= $data->vectors_name;
				$row['Vector ID']     		= $data->vector;
			}

			if ( $group['Opportunity'])
			{

				$row['Opportunity'] = $data->opportunity;
			}

			if ( $group['Country'] )
				$row['Country']      			= $data->country;		


			if ( $group['Carrier'])
			{
				$row['Carrier'] = $data->carrier;
			}

			if ( $sum['Imp'] == 1 )
				$row['Imp']	     				= $data->imp;	



			if ( $sum['Clicks'] )
			{
				$row['Clicks']       			= $data->clics;			
			}

			if ( $sum['CTR'] == 1 )
				$row['CTR']	     				= $data->click_through_rate;	

			if ( $sum['Conv'] == 1 )
				$row['Conversions']	     		= $data->conv_api;	

			if ( $sum['CR'] == 1 )
				$row['CR']	     				= $data->conversion_rate;	


			if ( $sum['Revenue'] == 1 )
				$row['Revenue']    				= $data->getRevenueUSD();
			
			if ( $sum['Spend'] == 1 )
				$row['Spend']    				= $data->getSpendUSD();			


			if ( $sum['Profit'] == 1 )
			{
				$row['Profit']    				= $data->getRevenueUSD()-$data->getSpendUSD();
				$row['Profit %']    		= $data->profit_percent;			
			}

			if ( $sum['eCPM'] == 1 )
				$row['eCPM']						= $data->eCPM;

			if ( $sum['eCPC'] == 1 )
				$row['eCPC']						= $data->eCPC;

			if ( $sum['eCPA'] == 1 )
				$row['eCPA']						= $data->eCPA;							

			$csvData[] = $row;
		}

		$csv = new ECSVExport( $csvData );
		$csv->setEnclosure(chr(0));//replace enclosure with caracter
		$csv->setHeader( 'content-type', 'application/csv;charset=UTF-8' );
		$content = $csv->toCSV();   

		if(isset($_REQUEST['v']))
			echo str_replace("\n", '<br/>', $content);
		else
		{
			$filename = 'DailyReport_'.date("Y-m-d", strtotime($dateStart)).'.csv';
			Yii::app()->getRequest()->sendFile($filename, $content, "text/csv", true);		
		}		
	}

	/**
	 * Get data for Graphic
	 */
	public function actionGraphic() {
		if ( isset($_POST['c_id']) && isset($_POST['net_id']) ) {
			$c_id = $_POST['c_id'];
			$net_id = $_POST['net_id'];
		} else {
			// echo json_encode("ERROR c_id or net_id missing");
			Yii::app()->end();
		}

		if ( isset($_POST['endDate']) ) {
			$endDate = new DateTime( $_POST['endDate'] );
		} else {
			$endDate = new DateTime("NOW");
		}

		
		if ( isset($_POST['startDate']) ) {
			$startDate = new DateTime( $_POST['startDate'] );
		} else {
			$startDate = new DateTime( $endDate->format("Y-m-d") );
			$startDate = $startDate->sub( DateInterval::createFromDateString('7 days') );
		}

		$model = new DailyReport();
		$response = $model->getGraphicDateRangeInfo( $c_id, $net_id, $startDate->format("Y-m-d"), $endDate->format("Y-m-d") );
		echo json_encode($response, JSON_NUMERIC_CHECK);
		Yii::app()->end();
	}

	public function actionUpdateEditable(){
		$req = Yii::app()->getRequest();

		$model = DailyReport::model()->findByPk($req->getParam('pk'));
		$model[$req->getParam('name')] = $req->getParam('value');

		$model->updateRevenue();
		$model->setNewFields();
		$model->save();
		Yii::app()->end();

	}

	public function actionUpdateColumn() {

		if ( isset($_POST["id"]) && isset($_POST["newValue"]) && isset($_POST["col"]) ) {
			$keyvalue   = $_POST["id"];
	        $newValue  = $_POST["newValue"];
	        $col = $_POST["col"];
		} else {
			// echo json_encode("ERROR missing params.");
			Yii::app()->end();
		}

		$model = DailyReport::model()->findByPk($keyvalue);
		$model[$col] = $newValue;
		$model->updateRevenue();
		$model->setNewFields();

		if ( ! $model->save() ) {
			// echo json_encode("ERROR updating daily report");
		}

		Yii::app()->end();
	}

	public function actionExcelReport()
	{
		if( isset($_POST['excel-report-daily']) ) {
			set_time_limit(1000);
			//$csvData = array();
			$model = new DailyReport();

			$dateStart      = isset($_POST['excel-dateStart']) ? $_POST['excel-dateStart'] : 'yesterday' ;
			$dateEnd        = isset($_POST['excel-dateEnd']) ? $_POST['excel-dateEnd'] : 'yesterday';
			$accountManager = isset($_POST['excel-accountManager']) ? $_POST['excel-accountManager'] : NULL;
			$opportunities  = isset($_POST['excel-opportunities']) ? $_POST['excel-opportunities'] : NULL;
			$providers      = isset($_POST['excel-providers']) ? $_POST['excel-providers'] : NULL;
			$sum            = isset($_POST['sum']) ? $_POST['sum'] : 0;
			$adv_categories = isset($_POST['excel-advertisers-cat']) ? $_POST['excel-advertisers-cat'] : NULL;

			$dp = $model->excel(
					$dateStart,
					$dateEnd,
					$accountManager,
					$opportunities,
					$providers,
					$sum,
					$adv_categories
			);

			//  Traer clicks desde ClicksLog para comparar
			/*
				$criteria=new CDbCriteria;
				$criteria->select                        ='count(*) as clics';
				$criteria->addCondition("DATE(date) = '".$this->date."' AND campaigns_id=".$this->campaigns_id);
				$clicksLogs                              = ClicksLog::model()->find($criteria)->clics;
			*/

			foreach ( $dp->getData() as $data ) {

				$csvData[] = array(
					'Account Manager'		=> $data->campaigns->opportunities->accountManager->lastname . " " . $data->campaigns->opportunities->accountManager->name,
					'Commercial Name'		=> $data->campaigns->opportunities->regions->financeEntities->commercial->lastname . " " .$data->campaigns->opportunities->regions->financeEntities->commercial->name,
					'Campaign'			=> Campaigns::model()->getExternalName($data->campaigns_id), // REVISAR LAZY LOAD
					'Format'				=> $data->campaigns->formats->name,
					'Oportunity'			=> $data->campaigns->opportunities->getVirtualName(),
					'Finance Entity'				=> $data->campaigns->opportunities->regions->financeEntities->name,
					'Country'				=> $data->campaigns->opportunities->regions->country->name,
					'Category'			=> $data->campaigns->opportunities->regions->financeEntities->advertisers->cat,
					'Provider'			=> $data->providers->name, 
					'Rate'					=> $data->campaigns->opportunities->getRate($data->date), 
			        'Impressions'					=> $data->imp,
			        //'imp_adv'				=> $data->imp_adv,
			        'Clicks'				=> $data->clics, 
			        //'Clicks Redirect'		=> $clicksLogs, // Activar cuando se traen clicks desde ClicksLog
			        'Conversions'				=> $data->conv_api,
			        //'conv_adv'				=> $data->conv_adv,
			        'Consolidated'			=> $data->getConv(),
			        'Spend'					=> $data->spend,
			        'Revenue'				=> $data->revenue,
			        'Profit'				=> $data->profit,
			        'Profit Percent'		=> $data->profit_percent * 100,
			        'Click Through Rate'	=> $data->click_through_rate * 100,
			        'Conversion Rate'		=> $data->conversion_rate * 100,
			        'eCPM'					=> $data->eCPM,
			        'eCPC'					=> $data->eCPC,
			        'eCPA'					=> $data->eCPA,
			        'Date'					=> date("d-m-Y", strtotime($data->date)),
			        'CAP'					=> $data->getCapStatus() ? "Exceeded" : "", // REVISAR LAZY LOAD
				);
			}
				
			$csv = new ECSVExport( $csvData );
			$csv->setEnclosure(chr(0));//replace enclosure with caracter
			$csv->setHeader( 'content-type', 'application/csv;charset=UTF-8' );
			$content = $csv->toCSV();  		

			if(isset($_REQUEST['v']))
				echo str_replace("\n", '<br/>', $content);
			else
				$filename = 'TML-DailyReport_'.date("Y-m-d", strtotime($dateStart)).'.csv';
				Yii::app()->getRequest()->sendFile($filename, $content, "text/csv", false);
		}

		$this->renderPartial('_excelReport', array(), false, true);	
	}

	public function actionMultiRate($id)
	{

		$model = $this->loadModel($id);

		// 
		// Resolve multi rates submitted
		// 
		if ( isset($_POST['multiRate-submit']) ) {

			// walk through all MultiRate records submitted
			$i = 1;
			$model->conv_adv = 0;
			$model->revenue  = 0;
			while ( isset($_POST['MultiRate' . $i]) ) {

				$tmp_id = $_POST['MultiRate' . $i]['id'];
				if ($tmp_id != '') { // if MultiRate record already exists, load it.
					$modelMultiRate = MultiRate::model()->findByPk($tmp_id);
				} else {
					$modelMultiRate = new MultiRate;
				}
				$modelMultiRate->attributes=$_POST['MultiRate' . $i];
				// ignore records in blank
				if ( $modelMultiRate->rate == 0 && $modelMultiRate->conv == 0 && $tmp_id == '') {
					$i++;
					continue;
				}
				$model->conv_adv += $modelMultiRate->conv;
				// $model->revenue += ($modelMultiRate->conv * $modelMultiRate->rate);

				if ( !$modelMultiRate->save() ) {
					print "ERROR - " .  json_encode($modelMultiRate->getErrors()) . "<br>";
				}

				$i++;
			}

			$model->updateRevenue();
			$model->setNewFields();
			if ( $model->save() ){
				$urlArray = array_merge(array('admin'), json_decode($_POST['query_string'], true));
				$this->redirect($urlArray);
			}else{
				var_dump($model->getErrors());
			}
		}


		//
		// Render modal for multi rates
		//
		if ( !$model->campaigns->opportunities->country_id ) {
			print "ERROR - country_id NULL";
			Yii::app()->end();
		}

		$carriers = Carriers::model()->findAll( array('order'=>'mobile_brand', 'condition'=>'id_country=:cid', 'params'=>array(':cid'=>$model->campaigns->opportunities->country_id)) ); // FIXME que pasa si country_id == NULL ???

		$multi_rates = MultiRate::model()->findAll(array('order'=>'daily_report_id', 'condition'=>'daily_report_id=:id', 'params'=>array(':id'=>$id)));

		// populate info into carriers list
		foreach ($carriers as $carrier) {
			$found = false;
			// search every carrier in MultiRate, if carrier is not include then add to list with zero values
			foreach ($multi_rates as $multi_rate) {
				if ($multi_rate->carriers_id_carrier == $carrier->id_carrier) {
					$found = true;
					break;
				}
			}
			if ( !$found ) {
				$new = new MultiRate;
				$new->daily_report_id = $id;
				$new->carriers_id_carrier = $carrier->id_carrier;
				$multi_rates[] = $new;
			}
		}

		$this->renderPartial('_multiRate', array(
			'model'       => $model,
			'multi_rates' => $multi_rates,
			'currency'    => $model->campaigns->opportunities->regions->financeEntities->currency,
		), false, false);
	}	

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return DailyReport the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=DailyReport::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param DailyReport $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='daily-report-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function renderFormAjax($model)
	{
		$this->layout='//layouts/modalIframe';
	
		//$providers = CHtml::listData(Providers::model()->findAll(array('order'=>'name')), 'id', 'name');
		$criteria       = new CDbCriteria;
		$criteria->with = array('providers', 'opportunities.regions','opportunities.regions.financeEntities','opportunities.regions.financeEntities.advertisers');
		
		$criteria->join = 'LEFT JOIN networks ON t.providers_id=networks.providers_id';
		$criteria->compare('networks.has_api', 0);
		$criteria->compare('t.editable', 1, false, 'OR');
		$criteria->compare('t.status', 'Active', false, 'AND');

		if( UserManager::model()->isUserAssignToRole('account_manager_admin') || UserManager::model()->isUserAssignToRole('account_manager_admin') )
			$criteria->compare('advertisers.cat', array('VAS','Affiliates','App Owners'), 'AND');

		if( UserManager::model()->isUserAssignToRole('operation_manager') )
			$criteria->compare('advertisers.cat', array('Networks','Incent'));		

		$criteria->order = 'financeEntities.name';
		$campaigns = CHtml::listData(Campaigns::model()->findAll($criteria), 'id',
			function($camp) { return $camp->getExternalName($camp->id); } );

		if ( $model->isNewRecord )
			$model->is_from_api = 0;

		$this->render('_form', array(
			'model'     => $model,
			//'providers'  => $providers,
			'campaigns' => $campaigns, 
		));
	}

	public function actionOpportunityMA($id){
        $model = Campaigns::model()->findByPk($id);
        echo $model->opportunities->model_adv;
	}

	public function actionSetRevenue($id){
		$rate = isset($_GET['rate']) ? $_GET['rate'] : null;
		if($model = DailyReport::model()->findByPk($id)){
			$msj = $model->updateRevenue($rate);
			$model->setNewFields();
			$model->save();
			echo $id . " - updated: ".$msj;
			echo '<hr>imp: '.$model->imp.' - revenue: '.$model->revenue;
		}else{
			echo $id . "- not exists";
		}
	}

	public function actionSetNewFields($id){

		$new_rate = isset($_GET['newRate']) ? $_GET['newRate'] : NULL;

		if($model = DailyReport::model()->findByPk($id)){
			if ( $new_rate )
				$model->updateRevenue($new_rate);

			$model->setNewFields();
			$model->save();
			echo $id . " - updated";
		}else{
			echo $id . "- not exists";
		}

	}
	public function actionSetAllNewFields(){

		set_time_limit(100000);

		$opp_id   = isset($_GET['opp']) ? $_GET['opp'] : NULL;
		if ($opp_id) {
			$new_rate = isset($_GET['newRate']) ? $_GET['newRate'] : NULL;
		} else { // not allow update rate if opp_id isn't specified.
			echo "newRate value is present but no opportunity id is specified.";
			return;
		}

		if( isset($_GET['dateStart']) && isset($_GET['dateEnd']) ){
			$criteria = new CDbCriteria;
			$criteria->with = array('campaigns.opportunities');
			$criteria->compare('date', '>=' . $_GET['dateStart']);
			$criteria->compare('date', '<=' . $_GET['dateEnd']);
			$criteria->compare('opportunities.id', $opp_id);

			$list = DailyReport::model()->findAll( $criteria );
			foreach ($list as $model) {
				$model->updateRevenue($new_rate);
				$model->setNewFields();
				$model->save();
				echo $model->id . " - updated<br/>";
			}
		}else{
			echo "no date setted";
		}
	}

	public function actionUpdateSpendAffiliates()
	{
		$date      = isset($_GET['date']) ? $_GET['date'] : date('d-m-Y', strtotime('today'));
		$affiliate = isset($_GET['affiliate']) ? $_GET['affiliate'] : NULL;
		$newRate   = isset($_GET['newRate']) ? $_GET['newRate'] : NULL;

		$criteria = new CDbCriteria;
		$criteria->compare('date', $date);
		if ($affiliate) { // update one affiliate
			$criteria->compare('providers_id', $affiliate);
		} else { // update all affiliate
			$q = Yii::app()->db->createCommand()
			    ->select('providers_id')
			    ->from('affiliates')
			    ->queryAll(false);

			foreach ($q as $nid)
	        	$affiliates[] = $nid[0];

			$criteria->addInCondition('providers_id', $affiliates);
		}

		$list = DailyReport::model()->findAll( $criteria );
		foreach ($list as $model) {
			$model->updateSpendAffiliates($newRate);
			$model->setNewFields();
			$model->save();
			echo $model->id . " - updated<br/>";
		}
	}

	public function actionUpdateConvs2s()
	{
		set_time_limit(100000);
		if(isset($_GET['date'])) {
			$list = DailyReport::model()->findAll(array('condition'=>'date(date)="'.$_GET['date'].'"'));
			foreach ($list as $model) {
				if ($model->conv_api == 0) {
					$model->conv_api    = ConvLog::model()->count("campaigns_id=:campaignid AND DATE(date)=:date", array(":campaignid"=>$model->campaigns_id, ":date"=>$model->date));
					$model->updateRevenue();
					$model->setNewFields();
					$model->save();
					echo $model->id . " - updated<br/>";
				}
			}
		}else{
			echo "no date seted";
		}
	}

}
