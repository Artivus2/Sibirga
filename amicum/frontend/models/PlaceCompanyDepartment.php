<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "place_company_department".
 *
 * @property int $id Идентификатор таблицы (автоинкрементный)
 * @property int $place_id Внешний ключ места за которомы закреплён ответственный
 * @property int $company_department_id Внешний ключ участка на котором есть ответственный (Начальник участка)
 *
 * @property Worker $companyDepartment
 * @property Place $place
 */
class PlaceCompanyDepartment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'place_company_department';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_amicum2');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['place_id', 'company_department_id'], 'required'],
            [['place_id', 'company_department_id'], 'integer'],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['company_department_id' => 'company_department_id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы (автоинкрементный)',
            'place_id' => 'Внешний ключ места за которомы закреплён ответственный',
            'company_department_id' => 'Внешний ключ участка на котором есть ответственный (Начальник участка)',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['company_department_id' => 'company_department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }
}
