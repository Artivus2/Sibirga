<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "siz_store".
 *
 * @property int $id Идентификатор таблицы(автоинкрементный)
 * @property int $plan_value Количество средств индивидуальной защиты на складе по плану
 * @property int $fact_value Количество средств индивидуальной защиты по факту
 * @property int $siz_id Внешний ключ к справочнику средств индивидуальной защиты
 * @property int $company_department_id Внешний ключ к справочнику подразделений
 *
 * @property CompanyDepartment $companyDepartment
 * @property Siz $siz
 */
class SizStore extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'siz_store';
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
            [['plan_value', 'fact_value', 'siz_id', 'company_department_id'], 'required'],
            [['plan_value', 'fact_value', 'siz_id', 'company_department_id'], 'integer'],
            [['siz_id', 'company_department_id'], 'unique', 'targetAttribute' => ['siz_id', 'company_department_id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['siz_id'], 'exist', 'skipOnError' => true, 'targetClass' => Siz::className(), 'targetAttribute' => ['siz_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы(автоинкрементный)',
            'plan_value' => 'Количество средств индивидуальной защиты на складе по плану',
            'fact_value' => 'Количество средств индивидуальной защиты по факту',
            'siz_id' => 'Внешний ключ к справочнику средств индивидуальной защиты',
            'company_department_id' => 'Внешний ключ к справочнику подразделений',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSiz()
    {
        return $this->hasOne(Siz::className(), ['id' => 'siz_id']);
    }
}
