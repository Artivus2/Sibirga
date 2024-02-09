<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "template_order_vtb_ab".
 *
 * @property int $id идентификатор таблицы шаблон наряда АБ ВТБ
 * @property string $title Наименование шаблона наряда АБ ВТБ
 * @property string $order_json объект наряда в виде json
 * @property int $company_department_id Внешний идентификатор участка на который сохранён шаблон
 * @property int $shift_id Внешний идентификатор смены
 *
 * @property CompanyDepartment $companyDepartment
 * @property Shift $shift
 */
class TemplateOrderVtbAb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'template_order_vtb_ab';
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
            [['title', 'order_json', 'company_department_id', 'shift_id'], 'required'],
            [['order_json'], 'string'],
            [['company_department_id', 'shift_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'идентификатор таблицы шаблон наряда АБ ВТБ',
            'title' => 'Наименование шаблона наряда АБ ВТБ',
            'order_json' => 'объект наряда в виде json',
            'company_department_id' => 'Внешний идентификатор участка на который сохранён шаблон',
            'shift_id' => 'Внешний идентификатор смены',
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
    public function getShift()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }
}
