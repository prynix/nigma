<?php
$this->breadcrumbs=array(
	'Providers',
);

?>

<?php $this->widget('bootstrap.widgets.TbGridView',array(
	'id'                       =>'providers-grid',
	'dataProvider'             =>$model->search(),
	'filter'                   =>$model,
	'type'                     => 'striped condensed',
	'rowHtmlOptionsExpression' => 'array("data-row-id" => $data->id)',
	'template'                 => '{items} {pager} {summary}',
	'columns'                  =>array(
		'id',
		'type',
		'prefix',
		'name',
		'status',
		/*
		'currency',
		'country_id',
		'model',
		'net_payment',
		'deal',
		'post_payment_amount',
		'start_date',
		'end_date',
		'daily_cap',
		'sizes',
		'has_s2s',
		'callback',
		'placeholder',
		'has_token',
		'commercial_name',
		'state',
		'zip_code',
		'address',
		'contact_com',
		'email_com',
		'contact_adm',
		'email_adm',
		'entity',
		'tax_id',
		'prospect',
		'pdf_name',
		'pdf_agreement',
		'phone',
		'foundation_date',
		'foundation_place',
		'bank_account_name',
		'bank_account_number',
		'branch',
		'bank_name',
		'swift_code',
		'percent_off',
		'url',
		'use_alternative_convention_name',
		'has_api',
		'use_vectors',
		'query_string',
		'token1',
		'token2',
		'token3',
		'publisher_percentage',
		'rate',
		'users_id',
		'account_manager_id',
		*/
	// array(
	// 	'class'=>'bootstrap.widgets.TbButtonColumn',
	// ),
	BuildGridView::buttonColumn('modalProviders'),

),
)); ?>

<?php BuildGridView::printModal($this, 'modalProviders', 'Providers'); ?>