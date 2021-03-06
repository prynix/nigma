<?php
/* @var $this AdvertisersController */
/* @var $model Advertisers */

$this->breadcrumbs=array(
	'Advertisers'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Advertisers', 'url'=>array('index')),
	array('label'=>'Create Advertisers', 'url'=>array('create')),
	array('label'=>'View Advertisers', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Advertisers', 'url'=>array('admin')),
);
?>

<h1>Update Advertisers <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>