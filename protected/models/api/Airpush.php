<?php

class Airpush
{ 

	private $provider_id = 1;

	public function downloadInfo()
	{
		if ( isset( $_GET['date']) ) {
			$date = $_GET['date'];
		} else {
			$date = date('Y-m-d', strtotime('yesterday'));
		}

		// validate if info have't been dowloaded already.
		if ( DailyReport::model()->exists("providers_id=:providers AND DATE(date)=:date", array(":providers"=>$this->provider_id, ":date"=>$date)) ) {
			Yii::log("Information already downloaded.", 'warning', 'system.model.api.airpush');
			return 2;
		}

		// Get json from Airpush API.
		$network = Networks::model()->findbyPk($this->provider_id);
		$apikey = $network->token1;
		$apiurl = $network->url;
		$url = $apiurl . "?apikey=" . $apikey . "&startDate=" . $date . "&endDate=" . $date;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);
		$result = json_decode($result);
		if (!$result) {
			Yii::log("ERROR - decoding json", 'error', 'system.model.api.airpush');
			return 1;
		}
		curl_close($curl);
		
		// Save campaigns information 
		foreach ($result->advertiser_data as $campaign) {

			if ( $campaign->impression == 0 && $campaign->clicks == 0) { // if no impressions dismiss campaign
				continue;
			}

			$dailyReport = new DailyReport();
			
			// get campaign ID used in Server, from the campaign name use in the external provider
			$dailyReport->campaigns_id = Utilities::parseCampaignID($campaign->campaignname);

			if ( !$dailyReport->campaigns_id ) {
				Yii::log("Invalid external campaign name: '" . $campaign->campaignname, 'warning', 'system.model.api.airpush');
				continue;
			}

			$dailyReport->date = $date;
			$dailyReport->providers_id = $this->provider_id;
			$dailyReport->imp = $campaign->impression;
			$dailyReport->clics = $campaign->clicks;
			$dailyReport->conv_api = ConvLog::model()->count("campaign_id=:campaignid AND DATE(date)=:date", array(":campaignid"=>$dailyReport->campaigns_id, ":date"=>$date));
			//$dailyReport->conv_adv = 0;
			$dailyReport->spend = $campaign->Spent;
			$dailyReport->updateRevenue();
			$dailyReport->setNewFields();
			if ( !$dailyReport->save() ) {
				Yii::log("Can't save campaign: '" . $campaign->campaignname . "message error: " . json_encode($dailyReport->getErrors()), 'error', 'system.model.api.airpush');
				continue;
			}
		}
		Yii::log("SUCCESS - Daily info downloaded", 'info', 'system.model.api.airpush');
		return 0;
	}

}