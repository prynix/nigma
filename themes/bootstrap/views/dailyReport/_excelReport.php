<?php 
/* @var $this DailyReportController */
/* @var $form CActiveForm */
?>

<div class="modal-header">
    <a class="close" data-dismiss="modal">&times;</a>
    <h4>Excel Report</h4>
</div>


<div class="modal-body">

    <?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm', array(
        'id'=>'excel-report-daily-form',
        'type'=>'horizontal',
        'htmlOptions'=>array('class'=>'well'),
        // to enable ajax validation
        'enableAjaxValidation'=>true,
        'clientOptions'=>array('validateOnSubmit'=>true, 'validateOnChange'=>true),
    )); ?>
    <fieldset>

        <div class="input-append">
            <?php echo CHtml::label("From:", 'excel-dateStart', array('class'=>'control-label')); ?>

            <div class="controls">
                <?php $this->widget('bootstrap.widgets.TbDatePicker',array(
                    'name'  => 'excel-dateStart',
                    'value' => date('d-m-Y', strtotime('yesterday')),
                    'htmlOptions' => array(
                        'style' => 'width: 80px',
                    ),
                    'options' => array(
                        'todayBtn'       => true,
                        'autoclose'      => true,
                        'todayHighlight' => true,
                        'format'         => 'dd-mm-yyyy',
                        'viewformat'     => 'dd-mm-yyyy',
                        'placement'      => 'right',
                ))); ?>
                <span class="add-on"><i class="icon-calendar"></i></span>
            </div>
        <br/>
        </div>
            
        <div class="input-append">
            <?php echo CHtml::label("To:", 'excel-dateEnd', array('class'=>'control-label')); ?>
            
            <div class="controls">
                <?php $this->widget('bootstrap.widgets.TbDatePicker',array(
                    'name'  => 'excel-dateEnd',
                    'value' => date('d-m-Y', strtotime('yesterday')),
                    'htmlOptions' => array(
                        'style' => 'width: 80px',
                    ),
                    'options' => array(
                        'todayBtn'       => true,
                        'autoclose'      => true,
                        'todayHighlight' => true,
                        'format'         => 'dd-mm-yyyy',
                        'viewformat'     => 'dd-mm-yyyy',
                        'placement'      => 'right',
                ))); ?>
                <span class="add-on"><i class="icon-calendar"></i></span>
            </div>
        <br/>
        </div>

    <div class="form-actions">
        <?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=>'submit', 'type'=>'success', 'label'=>'Download', 'htmlOptions' => array('name' => 'excel-report-daily'))); ?>
    </div>
    </fieldset>

    <?php $this->endWidget(); ?>


</div>

<div class="modal-footer">
    Excel Report Daily Report. Search by <span class="required">date</span>.
</div>