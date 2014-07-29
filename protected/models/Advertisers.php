<?php

/**
 * This is the model class for table "advertisers".
 *
 * The followings are the available columns in table 'advertisers':
 * @property integer $id
 * @property string $name
 * @property string $cat
 * @property integer $commercial_id
 *
 * The followings are the available model relations:
 * @property Users $commercial
 * @property Ios[] $ioses
 */
class Advertisers extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'advertisers';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, cat', 'required'),
			array('commercial_id', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>128),
			array('cat', 'length', 'max'=>5),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, name, cat, commercial_id', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'commercial' => array(self::BELONGS_TO, 'Users', 'commercial_id'),
			'ioses' => array(self::HAS_MANY, 'Ios', 'advertisers_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'cat' => 'Cat',
			'commercial_id' => 'Commercial',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('cat',$this->cat,true);
		$criteria->compare('commercial_id',$this->commercial_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Advertisers the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
