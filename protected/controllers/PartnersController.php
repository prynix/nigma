<?php

class PartnersController extends Controller
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
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('affiliates'),
				'roles'=>array('admin','affiliate'),
			),
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('advertisers','excelReportAdvertisers'),
				'roles'=>array('admin','advertiser'),
			),
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('publishers','excelReportPublishers'),
				'roles'=>array('admin','publisher'),
			),
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('publishersCPM'),
				'roles'=>array('admin','publisherCPM'),
			),
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('previewAdvertisers', 'previewAffiliates', 'previewExcelReportAdvertisers'),
				'roles'=>array('admin'),
			),
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('previewPublishers', 'previewExcelReportPublishers'),
				'roles'=>array('admin','media_buyer_admin', 'operation_manager'),
			),
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('previewPublishersCPM'),
				'roles'=>array('admin','media_buyer_admin', 'operation_manager'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	// PUBLISHERS //

	public function actionAffiliates(){
		// $this->render('maintenance');
		$this->renderAffiliates(Yii::app()->user->id, false);
	}
	public function actionPreviewAffiliates($id){
		$this->renderAffiliates($id, true);
	}

	private function renderAffiliates($userId, $preview)
	{
		
		$dateStart = isset($_GET['dateStart']) ? $_GET['dateStart'] : '-7 days' ;
		$dateEnd   = isset($_GET['dateEnd']) ? $_GET['dateEnd'] : 'today';
		$sum       = isset($_GET['sum']) ? $_GET['sum'] : 0;
		
		$dateStart = date('Y-m-d', strtotime($dateStart));
		$dateEnd   = date('Y-m-d', strtotime($dateEnd));
		// $provider  = Affiliates::model()->findByUser($userId)->providers_id;

		$modelP = Providers::model()->findByAttributes(array('users_id'=>$userId));
		$model     = new Affiliates;
		$data = $model->getAffiliates($dateStart, $dateEnd, $modelP->id);

		$this->render('affiliates',array(
			'model'     => $model,
			'provider'  => $modelP,
			'dateStart' => $dateStart,
			'dateEnd'   => $dateEnd,
			'sum'       => $sum,
			'data'      => $data,
			'preview'   => $preview,
			'userId'    => $userId,
		));
	}

	// ADVERTISERS //

	public function actionExcelReportAdvertisers(){
		$this->renderExcelReportAdvertisers(Yii::app()->user->id, false);
	}
	public function actionPreviewExcelReportAdvertisers($id){
		$this->renderExcelReportAdvertisers($id, true);
	}

	public function renderExcelReportAdvertisers($userId, $preview){
		// $userId = Yii::app()->user->id;

		$model = new DailyReport;
		$advertiser = Advertisers::model()->findByUser($userId);

		$dateStart = isset($_POST['excel-dateStart']) ? $_POST['excel-dateStart'] : NULL;
		$dateEnd = isset($_POST['excel-dateEnd']) ? $_POST['excel-dateEnd'] : NULL;
		$sum = isset($_POST['sum']) ? $_POST['sum'] : 0;

		$dp = $model->advertiserSearch($advertiser->id, $dateStart, $dateEnd, $sum, false, false);
		$user_visibility = Visibility::model()->findByAttributes(array('users_id' => $userId));

		if( isset($_POST['excel-report-form']) ) {

			$csvData = array();
		

			foreach ( $dp->getData() as $data ) {

				$rowData = array();
				
				$rowData['Date'] = date("d-m-Y", strtotime($data->date));
				$rowData['Name'] = $data->campaigns->opportunities->product;


				if ( $user_visibility->country )
				{
					$rowData['Country'] = $data->campaigns->opportunities->regions->country->name;
				}

				if ( $user_visibility->carrier )
				{
					$rowData['Carrier'] = $data->campaigns->opportunities->carriers_id ? $data->campaigns->opportunities->carriers->mobile_brand : "All Carriers";
				}
						
				if ( $user_visibility->rate )
				{
					$rowData['Rate'] = "$ ".number_format($data->campaigns->opportunities->rate,2);
				}
			
				if ( $user_visibility->imp )
				{
					$rowData['Impressions'] = $data->imp;
				}
							
				if ( $user_visibility->clicks )
				{
					$rowData['Clicks'] = $data->clics;
				}						

				if ( $user_visibility->conv )
				{
					$rowData['Conversions'] = $data->conv_api;
				}	

				if ( $user_visibility->spend )
				{
					$rowData['Spend'] = $data->revenue;
				}					

				$csvData[] = $rowData;
			}
				
			$csv = new ECSVExport( $csvData );
			$csv->setEnclosure(chr(0));//replace enclosure with caracter
			$csv->setHeader( 'content-type', 'application/csv;charset=UTF-8' );
			$content = $csv->toCSV();  		

			if(isset($_REQUEST['v']))
				echo str_replace("\n", '<br/>', $content);
			else
				$filename = 'TheMediaLab-Advertiser_'.date("Y-m-d", strtotime($dateStart)).'.csv';
				Yii::app()->getRequest()->sendFile($filename, $content, "text/csv", false);

		}

		$dateStart = isset($_GET['dateStart']) ? $_GET['dateStart'] : NULL;
		$dateEnd = isset($_GET['dateEnd']) ? $_GET['dateEnd'] : NULL;
		$sum = isset($_GET['sum']) ? $_GET['sum'] : 0;
		$this->renderPartial('_excelReportAdvertisers', array('dateStart'=>$dateStart, 'dateEnd'=>$dateEnd, 'sum'=>$sum), false, true);
	}

	public function actionAdvertisers(){
		$this->renderAdvertisers(Yii::app()->user->id, false);
	}
	public function actionPreviewAdvertisers($id){
		$this->renderAdvertisers($id, true);
	}

	private function renderAdvertisers($userId, $preview){
		$model=new DailyReport('search');
		$model->unsetAttributes();  // clear any default values

		if(isset($_GET['DailyReport']))
			$model->attributes=$_GET['DailyReport'];

		// $providers = CHtml::listData(Providers::model()->findAll(), 'name', 'name');
		$advertiser   = Advertisers::model()->findByUser($userId);
		$user_visibility = Visibility::model()->findByAttributes(array('users_id' => $userId));

		if(!isset($user_visibility))
			die('User has no visibility set');
			
		$this->render('advertisers',array(
			'model'           => $model,
			'advertiser'	  => $advertiser,
			'advertiser_id'   => $advertiser->id,
			'user_visibility' => $user_visibility,
			'preview'         => $preview,
			'userId'          => $userId,
		));
	}


	// PUBLISHERS //

	public function actionPublishers(){
		// $this->render('maintenance');
		$this->renderPublishers(Yii::app()->user->id, false);
	}
	public function actionPreviewPublishers($id){
		$this->renderPublishers($id, true);
	}

	private function renderPublishers($userId, $preview){
		$model=new DailyPublishers('search');
		$model->unsetAttributes();  // clear any default values

		// $providers = CHtml::listData(Providers::model()->findAll(), 'name', 'name');
		// $user_visibility = Visibility::model()->findByAttributes(array('users_id' => $userId));

		// $publisher_id   = Publishers::model()->findByUser($userId);
		$publisher   = Providers::model()->findByUser($userId);
		if(!isset($publisher->id)) die('Publisher not allowed');

		$this->render('publishers',array(
			'model'           => $model,
			'publisher_id'    => $publisher->id,
			'publisher_name'    => $publisher->name,
			// 'user_visibility' => $user_visibility,
			'preview'         => $preview,
			'userId'          => $userId,
		));
	}

	// PUBLOSHERS CPM //
	
	
	public function actionPublishersCPM(){
		// $this->render('maintenance');
		$this->renderPublishersCPM(Yii::app()->user->id, false);
	}
	public function actionPreviewPublishersCPM($id){
		$this->renderPublishersCPM($id, true);
	}
	public function renderPublishersCPM($userId, $preview){

		KHtml::paginationController();
		
		$model = new FImpCompact('search');
		$model->unsetAttributes();
		
		$publisher   = Providers::model()->findByUser($userId);
		if(!isset($publisher->id)) die('Publisher not allowed');

		$this->render('//stats/impressions', 
			array(
				'model'          => $model,
				'publisher_id'   => $publisher->id,
				'publisher_name' => $publisher->name,
				));
	}


	// OLD //

	public function actionPublishersOld()
	{
		// if(Yii::app()->user->id)
		// {
			$dateStart = isset($_GET['dateStart']) ? $_GET['dateStart'] : '-1 week' ;
			$dateEnd   = isset($_GET['dateEnd']) ? $_GET['dateEnd'] : 'today';
			$sum       = isset($_GET['sum']) ? $_GET['sum'] : 0;
			
			$dateStart = date('Y-m-d', strtotime($dateStart));
			$dateEnd   = date('Y-m-d', strtotime($dateEnd));
			
			$model     = new Publishers;
			$provider  = Publishers::model()->findByUser(Yii::app()->user->id)->providers_id;
			
			$data = $model->getAffiliates($dateStart, $dateEnd, $provider);

			$this->render('publishers',array(
				'model'     =>$model,
				'provider'  =>$provider,
				'dateStart' =>$dateStart,
				'dateEnd'   =>$dateEnd,
				'sum'       =>$sum,
				'data'      =>$data
			));
		// }
		// else
		// {			
		// 	$this->redirect(Yii::app()->baseUrl);
		// }	
	}

	public function actionAdvertisersOld()
	{
		$year  = isset($_GET['year']) ? $_GET['year'] : date('Y', strtotime('today'));
		$month = isset($_GET['month']) ? $_GET['month'] : date('m', strtotime('today'));
		
		$advertiser = Advertisers::model()->findByUser(Yii::app()->user->id);
		
		$data = FinanceEntities::model()->getClients(
			$month, // month
			$year, // year
			null, // entity
			null, // io
			null, // account
			null, // opportunity
			null, // cat
			null, // status
			'profile', // group
			false, // close deal
			$advertiser->id
		);

		// add transaction count information
		$transactionCount =new TransactionCount;
		$consolidated     =array();
		$i                =0;
		$aux              =array();
		if($count=$transactionCount->getTotalsCarrier(NULL,$year.'-'.$month.'-01'))
		{
			foreach ($count as $value) {
				$found = false;
				foreach ($data['data'] as $key => $data) {
					if($data['country']==$value->getCountry() && $data['product']==$value->product && $data['carrier']==$value->carriers_id_carrier) {
						if($data['rate']==$value->rate) {
							$data['data'][$key]['conv']    +=$value->volume;
							$data['data'][$key]['revenue'] +=$value->total;
							$found = true;
							break;
						}
					}
				}
				if (!$found) {
					$aux[$i]            =$data;
					$aux[$i]['conv']    =$value->volume;
					$aux[$i]['revenue'] =$value->total;
					$aux[$i]['rate']    =$value->rate;				
					$i++;		
				}				
			}
			foreach ($aux as $value) {
				$consolidated[]=$value;
			}
		}
		foreach ($data['data'] as $value) {
			$consolidated[]=$value;
		}
		$totals['revenue']=0;
		$totals['conv']=0;
		foreach ($consolidated as $value) {
			$totals['revenue']+=$value['revenue'];
			$totals['conv']+=$value['conv'];
			
		}

		// Create dataProvider
		$dataProvider=new CArrayDataProvider($consolidated, array(
		    'id'=>'clients',
		    'sort'=>array(
		    	'defaultOrder'=>'country ASC',
		        'attributes'=>array(
		             'id', 'name', 'model', 'entity', 'currency', 'rate', 'conv','revenue', 'carrier','country','product','mobileBrand'
		        ),
		    ),
		    'pagination'   =>array(
		        'pageSize' =>30,
		    ),
		));

		$this->render('advertisers',array(
			// 'model'      =>$model,
			'advertiser'   =>$advertiser,
			'data'         =>$data,
			'dataProvider' =>$dataProvider,
			'totals'       =>$totals,
			'month'        =>$month,
			'year'         =>$year
		));
	}
}