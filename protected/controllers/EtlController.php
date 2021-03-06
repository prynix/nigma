<?php

set_time_limit(0);

spl_autoload_unregister(array('YiiBase', 'autoload'));
require_once(dirname(__FILE__).'/../external/vendor/autoload.php');
spl_autoload_register(array('YiiBase', 'autoload'));

use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;
use DeviceDetector\Parser\Client\ClientParserAbstract;

class EtlController extends Controller
{
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
		$actions = array(
			'index',
			'view',
			'supply',
			'demand',
			'useragent',
			'geolocation',
			'impressions',
			'bid',
			'piwik',
			'impcompact',
			'bidcompact',
			'compact',
			'compactData15'
			);

		return array(
			array('allow',
				'actions'=>$actions,
				'ips'=>array(Yii::app()->params['serverIP']),
			),
			array('allow', 
				'actions'=>$actions,
				'roles'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	public function actionIndex($id=1){

		die("deprecated");
		
		$start = time();

		$date = isset($_GET['date']) ? $_GET['date'] : null;

		if(isset($date)) echo 'Data from date: '.$date.'<hr/>';

		self::actionDemand();
		self::actionSupply();
		self::actionUseragent($id, $date);
		self::actionGeolocation($id, $date);
		self::actionImpressions($id, $date);
		self::actionBid($id, $date);

		Yii::app()->cache->flush();
		
		$elapsed = time() - $start;
		echo 'Total lapsed time: '.$elapsed.' seg.';

	}

	public function actionCompact(){
		
		$start = time();

		$date = isset($_GET['date']) ? $_GET['date'] : null;

		if(isset($date)) echo 'Data from date: '.$date.'<hr/>';

		self::actionDemand();
		self::actionSupply();
		self::actionUseragent(null, $date);
		self::actionGeolocation(null, $date);
		self::actionImpcompact($date);
		self::actionBidcompact($date);

		Yii::app()->cache->flush();
		
		$elapsed = time() - $start;
		echo 'Total lapsed time: '.$elapsed.' seg.';

	}

	public function actionView($id){
		self::actionIndex($id);
	}

	public function actionDemand(){

		$start = time();

		$query ='INSERT IGNORE INTO D_Demand (tag_id, advertiser, finance_entity, region, opportunity, campaign, tag, rate, freq_cap, country, connection_type, device_type, os_type, os_version) 
		SELECT t.id, 
			CONCAT(a.name," (",a.id,")"), 
			CONCAT(f.name," (",f.id,")"), 
			CONCAT(g.name," (",r.id,")"), 
			CONCAT(o.product," (",o.id,")"), 
			CONCAT(o.product," - ",c.name," (",c.id,")"), 
			CONCAT(t.name," (",t.id,")"), 
			o.rate, t.freq_cap, t.country, t.connection_type, t.device_type, t.os, t.os_version 
		FROM tags t 
		LEFT JOIN campaigns c        ON(t.campaigns_id        = c.id) 
		LEFT JOIN opportunities o    ON(c.opportunities_id    = o.id) 
		LEFT JOIN regions r          ON(o.regions_id          = r.id) 
		LEFT JOIN geo_location g     ON(r.country_id          = g.id_location) 
		LEFT JOIN finance_entities f ON(r.finance_entities_id = f.id) 
		LEFT JOIN advertisers a      ON(f.advertisers_id      = a.id)
		ON DUPLICATE KEY UPDATE
			advertiser     =CONCAT(a.name," (",a.id,")"), 
			finance_entity =CONCAT(f.name," (",f.id,")"), 
			region         =CONCAT(g.name," (",r.id,")"), 
			opportunity    =CONCAT(o.product," (",o.id,")"), 
			campaign       =CONCAT(o.product," - ",c.name," (",c.id,")"), 
			tag            =CONCAT(t.name," (",t.id,")"), 
			rate=o.rate, freq_cap=t.freq_cap, country=t.country, connection_type=t.connection_type, device_type=t.device_type, os_type=t.os, os_version=t.os_version
		';
		
		$return = Yii::app()->db->createCommand($query)->execute();

		$elapsed = time() - $start;

		echo 'ETL Demand: '.$return.' rows inserted - Elapsed time: '.$elapsed.' seg.<hr/>';
	}

	public function actionSupply(){
		
		$start = time();

		$query = 'INSERT IGNORE INTO D_Supply (placement_id, provider, site, placement, rate) 
		SELECT p.id, 
			CONCAT(o.name," (",o.id,")"), 
			CONCAT(s.name," (",s.id,")"), 
			CONCAT(p.name," (",p.id,")"), 
			p.rate 
		FROM placements p 
		LEFT JOIN sites s     ON(p.sites_id     = s.id) 
		LEFT JOIN providers o ON(s.providers_id = o.id)
		ON DUPLICATE KEY UPDATE
			provider  =CONCAT(o.name," (",o.id,")"), 
			site      =CONCAT(s.name," (",s.id,")"), 
			placement =CONCAT(p.name," (",p.id,")"), 
			rate=p.rate
		';
	
		$return = Yii::app()->db->createCommand($query)->execute();

		$elapsed = time() - $start;

		echo 'ETL Supply: '.$return.' rows inserted - Elapsed time: '.$elapsed.' seg.<hr/>';
	}

	public function actionUseragent($id=1, $date=null){
			
		$start = time();

		$query = 'INSERT IGNORE INTO D_UserAgent (user_agent) 
		SELECT DISTINCT user_agent 
		FROM imp_log i ';

		if(isset($date))
			$query .= 'WHERE DATE(i.date) = "'.date('Y-m-d', strtotime($date)).'"';
		else
			$query .= 'WHERE DATE(i.date) = CURDATE()';

		// $query .= 'WHERE i.date BETWEEN TIMESTAMP( DATE(NOW()) , SUBDATE( MAKETIME(HOUR(NOW()),0,0) , INTERVAL :h HOUR) ) AND TIMESTAMP( DATE(NOW()) , MAKETIME(HOUR(NOW()),0,0) ) ';

		$return = Yii::app()->db->createCommand($query)->bindParam('h',$id)->execute();

		$elapsed = time() - $start;

		echo 'ETL UserAgent: '.$return.' rows inserted - Elapsed time: '.$elapsed.' seg.<br/>';

		// fill data
		
		$start = time();

		$criteria = new CDbCriteria;
		$criteria->addCondition('device_type IS NULL');
		$ua_list = DUserAgent::model()->findAll($criteria);
		//var_dump($ua_list);
		//$wurfl = WurflManager::loadWurfl();
		$dd = new DeviceDetector();

		$filled = 0;
		foreach ($ua_list as $ua) {

			if ( !$ua->user_agent )
			{
				continue;
			}

			$dd->setUserAgent( $ua->user_agent );
			$dd->parse();
			$br = $dd->getClient();
			$os = $dd->getOs();

			//$device = $wurfl->getDeviceForUserAgent($ua->user_agent);
			// if($device = $wurfl->getDeviceForUserAgent($ua->user_agent))
			// 	echo $device->getCapability('brand_name').' - ';
			// else
			// 	echo '- ';

			/*
			$ua->device_brand    = $device->getCapability('brand_name');
			$ua->device_model    = $device->getCapability('marketing_name');
			$ua->os_type         = $device->getCapability('device_os');
			$ua->os_version      = $device->getCapability('device_os_version');
			$ua->browser_type    = $device->getVirtualCapability('advertised_browser');
			$ua->browser_version = $device->getVirtualCapability('advertised_browser_version');
			*/
			$ua->device_brand    = $dd->getBrandName();
			$ua->device_model    = $dd->getModel();
			$ua->os_type         = isset($os['name']) ? $os['name'] : null;
			$ua->os_version      = isset($os['version']) ? $os['version'] : null;
			$ua->browser_type    = isset($br['name']) ? $br['name'] : null;
			$ua->browser_version = isset($br['version']) ? $br['version'] : null;
			$ua->device_type 	 = $dd->getDeviceName() ? ucfirst($dd->getDeviceName()) : 'other';
			if($ua->device_type == 'Phablet' || $ua->device_type == 'Smartphone')
				$ua->device_type 	 = 'Mobile';

			/*
			if ($device->getCapability('is_tablet') == 'true')
				$ua->device_type = 'Tablet';
			else if ($device->getCapability('is_wireless_device') == 'true')
				$ua->device_type = 'Mobile';
			else
				$ua->device_type = 'Desktop';
			*/

			if($ua->save())
				$filled++;
			else
				echo 'ERROR: '.json_encode($ua->getErrors()) .'<br>';

		}

		$elapsed = time() - $start;

		echo 'ETL UserAgent: '.$filled.'/'.count($ua_list).' rows filled - Elapsed time: '.$elapsed.' seg.<hr/>';
	}

	public function actionGeolocation($id=1, $date=null){
		
		$start = time();

		$filled = 0;
		$query = 'INSERT IGNORE INTO D_GeoLocation (server_ip) 
		SELECT DISTINCT server_ip 
		FROM imp_log i ';

		if(isset($date))
			$query .= 'WHERE DATE(i.date) = "'.date('Y-m-d', strtotime($date)).'"';
		else
			$query .= 'WHERE DATE(i.date) = CURDATE()';

		// $query .= 'WHERE i.date BETWEEN TIMESTAMP( DATE(NOW()) , SUBDATE( MAKETIME(HOUR(NOW()),0,0) , INTERVAL :h HOUR) ) AND TIMESTAMP( DATE(NOW()) , MAKETIME(HOUR(NOW()),0,0) ) ';
	
		$return = Yii::app()->db->createCommand($query)->bindParam('h',$id)->execute();

		$elapsed = time() - $start;

		echo 'ETL GeoLocation: '.$return.' rows inserted - Elapsed time: '.$elapsed.' seg.<br/>';

		// fill data
		
		$start = time();

		$ip_list  = DGeoLocation::model()->findAllByAttributes(array('connection_type'=>null));
		$binPath  = Yii::app()->params['ipDbFile'];
		$location = new IP2Location($binPath, IP2Location::FILE_IO);

		foreach ($ip_list as $ip) {
			$ipData   = $location->lookup($ip->server_ip, IP2Location::ALL);

			$ip->country = $ipData->countryCode;
			$ip->carrier = $ipData->mobileCarrierName;

			if($ipData->mobileCarrierName == '-')
				$ip->connection_type = 'WIFI';
			else
				$ip->connection_type = '3G';

			if($ip->save())
				$filled++;
			else
				echo 'ERROR: '.json_encode($ip->getErrors()) .'<br>';
		}

		$elapsed = time() - $start;

		echo 'ETL GeoLocation: '.$filled.'/'.count($ip_list).' rows filled - Elapsed time: '.$elapsed.' seg.<hr/>';
	}

	public function actionImpressions($id=1, $date=null){

		die("deprecated");

		$start = time();

		$query = 'INSERT IGNORE INTO F_Imp (id, D_Demand_id, D_Supply_id, date_time, D_UserAgent_id, D_GeoLocation_id, unique_id, pubid, ip_forwarded, referer_url, referer_app) 
		SELECT i.id, i.tags_id, i.placements_id, i.date, u.id, g.id, SHA(CONCAT(i.server_ip,i.user_agent)), i.pubid, i.ip_forwarded, i.referer, i.app 
		FROM imp_log i 
		INNER JOIN D_UserAgent u   ON(i.user_agent = u.user_agent) 
		INNER JOIN D_GeoLocation g ON(i.server_ip  = g.server_ip) ';

		if(isset($date))
			$query .= 'WHERE DATE(i.date) = "'.date('Y-m-d', strtotime($date)).'" ';
		else
			$query .= 'WHERE DATE(i.date) = CURDATE()';
		
		// $query .= 'WHERE i.date BETWEEN TIMESTAMP( DATE(NOW()) , SUBDATE( MAKETIME(HOUR(NOW()),0,0) , INTERVAL :h HOUR) ) AND TIMESTAMP( DATE(NOW()) , MAKETIME(HOUR(NOW()),0,0) ) ';

		$query .= 'AND i.placements_id IS NOT NULL AND i.tags_id IS NOT NULL AND i.user_agent IS NOT NULL AND i.server_ip IS NOT NULL';

		$return = Yii::app()->db->createCommand($query)->bindParam('h',$id)->execute();

		$elapsed = time() - $start;

		echo 'ETL Impressions: '.$return.' rows inserted - Elapsed time: '.$elapsed.' seg.<hr/>';
	}

	public function actionImpcompact($date=null){

		$start = time();

		$query = 'INSERT IGNORE INTO F_Imp_Compact ( 
			D_Demand_id, 
			D_Supply_id, 
			date_time, 
			D_UserAgent_id, 
			D_GeoLocation_id, 
			unique_id, 
			pubid, 
			ip_forwarded, 
			referer_url, 
			referer_app, 
			imps, 
			ad_req,
			device_type, 
			device_brand, 
			device_model, 
			os_type, 
			os_version, 
			browser_type, 
			browser_version, 
			country, 
			carrier, 
			connection_type,
			ad_server_id
			) 
		SELECT 
			i.tags_id, 
			i.placements_id, 
			i.date, 
			u.id, 
			g.id, 
			MD5(CONCAT(i.server_ip,i.user_agent,date(i.date))), 
			i.pubid, 
			i.ip_forwarded, 
			i.referer, 
			i.app, 
			count(MD5(CONCAT(i.server_ip,i.user_agent,date(i.date)))), 
			count(MD5(CONCAT(i.server_ip,i.user_agent,date(i.date)))), 
			u.device_type, 
			u.device_brand, 
			u.device_model, 
			u.os_type, 
			u.os_version, 
			u.browser_type, 
			u.browser_version, 
			g.country, 
			g.carrier, 
			g.connection_type, 
			1
		FROM imp_log i 
		INNER JOIN D_UserAgent u   ON(i.user_agent = u.user_agent) 
		INNER JOIN D_GeoLocation g ON(i.server_ip  = g.server_ip) ';

		if(isset($date))
			$query .= 'WHERE DATE(i.date) = "'.date('Y-m-d', strtotime($date)).'" ';
		else
			$query .= 'WHERE DATE(i.date) = CURDATE()';

		$query .= 'AND i.placements_id IS NOT NULL AND i.tags_id IS NOT NULL AND i.user_agent IS NOT NULL AND i.server_ip IS NOT NULL ';

		$query .= 'GROUP BY MD5(CONCAT(i.server_ip,i.user_agent,date(i.date))) ';

		$return = Yii::app()->db->createCommand($query)->bindParam('h',$id)->execute();

		$elapsed = time() - $start;

		echo 'ETL Impressions: '.$return.' rows inserted - Elapsed time: '.$elapsed.' seg.<hr/>';
	}


	public function actionBidcompact($date=null){

		$inicialStart = time();

		if(isset($date))
			$dateCondition = 'AND DATE(i.date_time) = "'.date('Y-m-d', strtotime($date)).'" ';
		else
			$dateCondition = 'AND DATE(i.date_time) = CURDATE()';

		$start = time();

		$query = 'UPDATE F_Imp_Compact i 
		LEFT JOIN D_Demand d      ON(i.D_Demand_id      = d.tag_id) 
		LEFT JOIN D_Supply s      ON(i.D_Supply_id      = s.placement_id) 
		LEFT JOIN D_UserAgent u   ON(i.D_UserAgent_id   = u.id) 
		LEFT JOIN D_GeoLocation g ON(i.D_GeoLocation_id = g.id) 
		SET 
		i.revenue = CASE
        WHEN d.freq_cap IS NULL  THEN d.rate/1000 * i.imps
        WHEN d.freq_cap > i.imps THEN d.rate/1000 * i.imps
        ELSE d.rate/1000 * d.freq_cap
        END,
		i.cost = CASE
        WHEN d.freq_cap IS NULL  THEN s.rate/1000 * i.imps
        WHEN d.freq_cap > i.imps THEN s.rate/1000 * i.imps
        ELSE s.rate/1000 * d.freq_cap
        END
		WHERE 
		(g.connection_type = d.connection_type OR d.connection_type IS NULL OR d.connection_type = "") 
		AND 
		(g.country         = d.country         OR d.country         IS NULL OR d.country         = "") 
		AND 
		(u.os_type         = d.os_type         OR d.os_type         IS NULL OR d.os_type         = "") 
		AND 
		(CONVERT(u.os_version, DECIMAL(5,2)) >= CONVERT(d.os_version, DECIMAL(5,2)) OR d.os_version IS NULL OR d.os_version = "") 
		AND 
		ad_server_id = 1
		';
		$query.= $dateCondition;

		// echo $query;
		$return = Yii::app()->db->createCommand($query)->execute();

		$elapsed = time() - $start;

		echo 'ETL Bid - Open Freq. Cap: '.$return.' rows inserted - Elapsed time: '.$elapsed.' seg.<br/>';

	}

	public function actionBid($id=2, $date=null){

		die("deprecated");

		$inicialStart = time();
		$total = 0;

		if(isset($date))
			$dateCondition = 'AND DATE(i.date_time) = "'.date('Y-m-d', strtotime($date)).'"';
		else
			$dateCondition = 'WHERE DATE(i.date) = CURDATE()';

		// $dateCondition = 'AND i.date_time BETWEEN TIMESTAMP( DATE(NOW()) , SUBDATE( MAKETIME(HOUR(NOW()),0,0) , INTERVAL '.$id.' HOUR) ) AND TIMESTAMP( DATE(NOW()) , MAKETIME(HOUR(NOW()),0,0) ) ';	

		$return = Yii::app()->db->createCommand()
		->select('MAX(freq_cap) AS fc')
		->from('D_Demand')
		->queryRow();
		$fc = $return['fc'];

		// open freq_cap 

		$start = time();

		$query = 'INSERT IGNORE INTO D_Bid (F_Impressions_id, revenue, cost, profit) 
		SELECT i.id, d.rate/1000, s.rate/1000, d.rate/1000 - s.rate/1000 
		FROM F_Imp i  
		LEFT JOIN D_Bid b         ON(i.id               = b.F_Impressions_id) 
		LEFT JOIN D_Demand d      ON(i.D_Demand_id      = d.tag_id) 
		LEFT JOIN D_Supply s      ON(i.D_Supply_id      = s.placement_id) 
		LEFT JOIN D_UserAgent u   ON(i.D_UserAgent_id   = u.id) 
		LEFT JOIN D_GeoLocation g ON(i.D_GeoLocation_id = g.id) 
		WHERE b.F_Impressions_id IS NULL 
		AND d.freq_cap IS NULL 
		AND (g.connection_type = d.connection_type OR d.connection_type IS NULL OR d.connection_type = "") 
		AND (g.country         = d.country         OR d.country         IS NULL OR d.country         = "") 
		AND (g.carrier         = d.carrier         OR d.carrier         IS NULL OR d.carrier         = "") 
		AND (u.device_type     = d.device_type     OR d.device_type     IS NULL OR d.device_type     = "") 
		AND (u.device_brand    = d.device_brand    OR d.device_brand    IS NULL OR d.device_brand    = "") 
		AND (u.device_model    = d.device_model    OR d.device_model    IS NULL OR d.device_model    = "") 
		AND (u.os_type         = d.os_type         OR d.os_type         IS NULL OR d.os_type         = "") 
		AND (CONVERT(u.os_version, DECIMAL(5,2)) >= CONVERT(d.os_version, DECIMAL(5,2)) OR d.os_version IS NULL OR d.os_version = "") 
		';
		$query .= $dateCondition;

		$return = Yii::app()->db->createCommand($query)->execute();
		$total += $return;

		$elapsed = time() - $start;

		echo 'ETL Bid - Open Freq. Cap: '.$return.' rows inserted - Elapsed time: '.$elapsed.' seg.<br/>';
	
		// freq_cap

		for($i=1; $i<=$fc; $i++){
	
			$start = time();	

			$query = 'INSERT IGNORE INTO D_Bid (F_Impressions_id, revenue, cost, profit) 
			SELECT i.id, d.rate/1000, s.rate/1000, d.rate/1000 - s.rate/1000  
			FROM F_Imp i  
			LEFT JOIN D_Bid b    ON(i.id          = b.F_Impressions_id) 
			LEFT JOIN D_Demand d ON(i.D_Demand_id = d.tag_id) 
			LEFT JOIN D_Supply s ON(i.D_Supply_id = s.placement_id) 
			LEFT JOIN D_UserAgent u   ON(i.D_UserAgent_id   = u.id) 
			LEFT JOIN D_GeoLocation g ON(i.D_GeoLocation_id = g.id) 
			WHERE b.F_Impressions_id IS NULL 
			AND d.freq_cap >= :fc 
			AND (g.connection_type = d.connection_type OR d.connection_type IS NULL OR d.connection_type = "") 
			AND (g.country         = d.country         OR d.country         IS NULL OR d.country         = "") 
			AND (g.carrier         = d.carrier         OR d.carrier         IS NULL OR d.carrier         = "") 
			AND (u.device_type     = d.device_type     OR d.device_type     IS NULL OR d.device_type     = "") 
			AND (u.device_brand    = d.device_brand    OR d.device_brand    IS NULL OR d.device_brand    = "") 
			AND (u.device_model    = d.device_model    OR d.device_model    IS NULL OR d.device_model    = "") 
			AND (u.os_type         = d.os_type         OR d.os_type         IS NULL OR d.os_type         = "") 
			AND (CONVERT(u.os_version, DECIMAL(5,2)) >= CONVERT(d.os_version, DECIMAL(5,2)) OR d.os_version IS NULL OR d.os_version = "") 
			'.$dateCondition.' 
			GROUP BY i.unique_id
			';

			$return = Yii::app()->db->createCommand($query)->bindParam('fc',$i)->execute();
			$total += $return;

			$elapsed = time() - $start;

			echo 'ETL Bid - Freq. Cap '.$i.'/24: '.$return.' rows inserted - Elapsed time: '.$elapsed.' seg.<br/>';

		}

		// non targeted values
		
		$start = time();

		$query = 'INSERT IGNORE INTO D_Bid (F_Impressions_id) 
		SELECT i.id 
		FROM F_Imp i  
		LEFT JOIN D_Bid b ON(i.id = b.F_Impressions_id) 
		WHERE b.F_Impressions_id IS NULL  
		';
		$query .= $dateCondition;


		$return = Yii::app()->db->createCommand($query)->execute();
		$total += $return;

		$elapsed = time() - $start;

		echo 'ETL Bid - Non targeted: '.$return.' rows inserted - Elapsed time: '.$elapsed.' seg.<br/>';

		//

		$elapsed = time() - $inicialStart;

		echo 'ETL Bid: '.$total.' rows inserted - Total elapsed time: '.$elapsed.' seg.<hr/>';


	}

	public function actionPiwik ( )
	{
		$dd = new DeviceDetector();
		$ua = $_GET['ua'];
		$dd->setUserAgent( $ua );
		$dd->parse();
		$br = $dd->getClient();
		$os = $dd->getOs();

		$deviceType 	 = $dd->getDeviceName() ? ucfirst($dd->getDeviceName()) : 'other';
		if($deviceType == 'Phablet' || $deviceType == 'Smartphone')
			$deviceType 	 = 'Mobile';


		$data = [
			'user_agent' => [
				'name'	  		  => $ua,
				'device_type'  	  => $deviceType,
				'device_brand' 	  => $dd->getBrandName(),
				'device_model' 	  => $dd->getModel(),
				'os_type'	   	  => isset($os['name']) ? $os['name'] : null,
				'os_version'   	  => isset($os['version']) ? $os['version'] : null,
				'browser_type' 	  => isset($br['name']) ? $br['name'] : null,
				'browser_version' => isset($br['version']) ? $br['version'] : null
			]
		];

		header('Content-Type: text/json');
		echo json_encode($data, \JSON_PRETTY_PRINT);
	}

	// public function actionBid(){

	// 	$inicialStart = time();
	// 	$total = 0;
	

    function actionCompactData15(){

    	$daysago = isset($_GET['daysago']) ? $_GET['daysago'] : 15;

		$start = time();

		$insertQuery = 'INSERT INTO F_Imp_Compact
			(
				D_Demand_id,
				D_Supply_id,
				date_time,
				unique_imps,
				ad_req,
				imps,
		        revenue,
		        cost,
		        country,
		        connection_type,
		        device_type,
		        os_type,
		        os_version
		    )

		SELECT
			D_Demand_id AS D_Demand_id, 
			D_Supply_id AS D_Supply_id,
		    date_time AS date_time,
			SUM(id) AS imps,
			SUM(id) AS imps,
		    SUM(distinct unique_id) AS unique_imps,
		    SUM(revenue) AS revenue,
		    SUM(cost) AS cost,
		    country,
	        connection_type,
	        device_type,
	        os_type,
	        os_version

		FROM F_Imp_Compact i

		WHERE date(date_time) = SUBDATE(CURDATE(), :daysago) 

		GROUP BY 
			D_Demand_id, 
			D_Supply_id, 
			country,
	        connection_type,
	        device_type,
	        os_type,
	        os_version
		';

		// WHERE date(date_time) BETWEEN SUBDATE(CURDATE(), 30) AND SUBDATE(CURDATE(), 15) 

		// $return = Yii::app()->db->createCommand($insertQuery)->bindParam('daysago',$daysago)->execute();


		$elapsed = time() - $start;

        echo 'Rows affected: '.' in '.$elapsed.' sec.';
    }

}