<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_vtb_ab".
 *
 * @property int $id
 * @property int $company_department_id
 * @property string $date_time_create
 * @property int $shift_id Внешний идентификатор смены
 * @property int $mine_id ключ шахтного поля
 *
 * @property OrderPlaceVtbAb[] $orderPlaceVtbAbs
 * @property CompanyDepartment $companyDepartment
 * @property Mine $mine
 * @property Shift $shift
 */
class OrderVtbAb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_vtb_ab';
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
            [['company_department_id', 'date_time_create', 'shift_id'], 'required'],
            [['company_department_id', 'shift_id', 'mine_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['mine_id', 'company_department_id', 'date_time_create', 'shift_id'], 'unique', 'targetAttribute' => ['mine_id', 'company_department_id', 'date_time_create', 'shift_id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_department_id' => 'Company Department ID',
            'date_time_create' => 'Date Time Create',
            'shift_id' => 'Shift ID',
            'mine_id' => 'Mine ID',
        ];
    }

    /**
     * Gets query for [[OrderPlaceVtbAbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaceVtbAbs()
    {
        return $this->hasMany(OrderPlaceVtbAb::className(), ['order_vtb_ab_id' => 'id']);
    }

    /**
     * Gets query for [[CompanyDepartment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * Gets query for [[Mine]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
    }

    /**
     * Gets query for [[Shift]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShift()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }
}
