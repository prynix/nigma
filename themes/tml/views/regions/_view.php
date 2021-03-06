<?php
/* @var $this IosController */
/* @var $model Ios */
?>

<div class="modal-header">
    <a class="close" data-dismiss="modal">&times;</a>
    <h4>Region <?php echo "#".$model->id ?></h4>
</div>

<div class="modal-body">
	<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	    'type'=>'striped bordered condensed',
		'data'=>$model,
		'attributes'=>array(
			'id',
			'region',
			array(
				'label' =>$model->getAttributeLabel('country_name'),
				'name'  =>'country.name'
			),
			array(
				'label' =>$model->getAttributeLabel('finance_entities_name'),
				'name'  =>'financeEntities.name'
			),
		),
	)); ?>

	<h5>Opportunities</h5>
	<?php /*
	$opportunitie=new Opportunities;
	$this->widget('bootstrap.widgets.TbGridView', array(
    'id'           =>'conversions-grid',
    'type'         =>'striped condensed',
    'dataProvider' =>$opportunitie->findByIo($model->id),
    'template'     =>'{items} {summary} {pager}',
    'columns'=>array(
        array(
			'name'              =>'id',
			'headerHtmlOptions' => array('style'=>'width: 50px'),
		),
		array(
			'name'              =>'advertiser_name',
			'value'             =>'$data->ios->advertisers->name',
			'headerHtmlOptions' => array('style'=>'width: 90px'),
		),
		// array( 
		// 	'name'              =>'ios_name',
		// 	'value'             =>'$data->ios->name',
		// 	'headerHtmlOptions' => array('style'=>'width: 90px'),
		// ),
		array(
			'name'              =>'country_name',
			'value'             =>'$data->country ? $data->country->ISO2 : ""',
			'headerHtmlOptions' => array('style'=>'width: 50px'),
		),
		array(
			'name'              =>'carrier_mobile_brand',
			'value'             =>'$data->carriers ? $data->carriers->mobile_brand : ""',
			'headerHtmlOptions' => array('style'=>'width: 90px'),
		),
		'product',
		array(
			'name'              => 'model_adv',
			'headerHtmlOptions' => array('style'=>'width: 30px'),
		),
		array(
			'name'              => 'currency',
			'value'             =>'$data->ios->currency',
			'headerHtmlOptions' => array('style'=>'width: 30px'),
		),
		array(
			'name'              => 'rate',
			'headerHtmlOptions' => array('style'=>'width: 60px'),
		),
		// array(
		// 	'name'  =>'account_manager_lastname',
		// 	'value' =>'$data->account_manager_id ? $data->accountManager->lastname . " " . $data->accountManager->name : ""',
		// ),
		// 'comment',
		// array(
		// 	'name'  =>'country_name',
		// 	'value' =>'$data->country_id ? $data->country->name : ""',
		// ),
		// array( 
		// 	'name'  =>'wifi',
		// 	'value' =>'$data->wifi ? "Habilitado" : "Inhabilitado"',
		// ),
		array(
			'name' => 'budget',
			'headerHtmlOptions' => array('style'=>'width: 80px'),
		),
		// 'server_to_server',
		// array( 
		// 	'name'  =>'startDate',
		// 	'value' =>'$data->startDate !== "0000-00-00 00:00:00" ? date("d-m-Y", strtotime($data->startDate)) : ""',
		// 	'headerHtmlOptions' => array('style'=>'width: 80px'),
		// 	'filter' => false,
		// ),
		// array( 
		// 	'name'  =>'endDate',
		// 	'value' =>'$data->endDate !== "0000-00-00 00:00:00" ? date("d-m-Y", strtotime($data->endDate)) : ""',
		// 	'headerHtmlOptions' => array('style'=>'width: 80px'),
		// 	'filter' => false,
		// ),
    ),
));*/
?>
</div>

<div class="modal-footer">
    Io detail view.
</div>