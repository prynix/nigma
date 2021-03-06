<?php
$this->breadcrumbs=array(
	'Traffic Sources'=>array('providers/admin'),
	'Publishers'=>array('providers/admin/publishers'),
	'Sites',
);
?>

<?php 
/*
$this->menu=array(
	array('label'=>'List Sites','url'=>array('index')),
	array('label'=>'Create Sites','url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
	$('.search-button').click(function(){
		$('.search-form').toggle();
		return false;
	});
	$('.search-form form').submit(function(){
		$.fn.yiiGridView.update('sites-grid', {
			data: $(this).serialize()
		});
		return false;
	});
");

echo '
<h1>Manage Sites</h1>

<p>
	You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>
		&lt;&gt;</b>
	or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>
'

echo CHtml::link('Advanced Search','#',array('class'=>'search-button btn')); ?>
<div class="search-form" style="display:none">
	<?php $this->renderPartial('_search',array(
	'model'=>$model,
)); 
echo '</div><!-- search-form -->'
*/
?>

<?php BuildGridView::createButton($this, array('sites/create'), 'modalSites', 'sites-grid', 'Create Site'); ?>

<br>

<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm', array(
        'id'=>'date-filter-form',
        'type'=>'search',
        'htmlOptions'=>array('class'=>'well'),
        // to enable ajax validation
        'enableAjaxValidation'=>true,
        'action' => Yii::app()->getBaseUrl() . '/' . Yii::app()->controller->getId().'/'.Yii::app()->controller->getAction()->getId(),
        'method' => 'GET',
        'clientOptions'=>array('validateOnSubmit'=>true, 'validateOnChange'=>true),
    )); ?> 

<fieldset>

	<?php echo KHtml::filterProviders($publisher); ?>
    <?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=>'submit', 'label'=>'Filter', 'htmlOptions' => array('class' => 'showLoading'))); ?>

</fieldset>
<?php $this->endWidget(); ?>




<?php $this->widget('application.components.NiExtendedGridView',array(
	'id'=>'sites-grid',
	'dataProvider' => $model->search($publisher),
	'filter'       => $model,
	'type'                     => 'striped condensed',
	'rowHtmlOptionsExpression' => 'array(
		"data-row-id" => $data->id, 
		"class" => "deepLink",
		"onclick" => "deepLink(\"'.Yii::app()->createUrl('placements/admin').'?site=\"+$data->id)",
		)',
	'template'                 => '{items} {pagerExt} {summary}',
	'columns'    => array(
		array(
			'name'              =>'id',
			'headerHtmlOptions' => array('style'=>'width:100px'),
		),
		'name',
		array( 
			'name'  => 'publishers_name',
			'value' => '$data->providers->name',
		),
		'model',
		'publisher_percentage',
		'rate',
		BuildGridView::buttonColumn('modalSites', 'sites-grid', 'Update Site'),
	),
)); ?>

<?php BuildGridView::printModal($this, 'modalSites', 'Sites'); ?>

