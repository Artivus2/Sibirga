<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_employee_full".
 *
 * @property int $id
 * @property string $VORNA Имя
 * @property string $MIDNM Отчество
 * @property string $NACHN Фамилия
 * @property int $GESCH Пол
 * @property string $GBDAT
 * @property int $PERNR Табельный номер сотрудника
 * @property string $HIRE_DATE
 * @property string $FIRE_DATE
 * @property string $OSTEXT02 Компания
 * @property string $PLANS_TEXT Должность
 * @property string $CATEG
 * @property int $OBJID02
 * @property string $STELL
 */
class SapEmployeeFull extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_employee_full';
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
            [['GESCH', 'PERNR', 'OBJID02'], 'integer'],
            [['PERNR'], 'required'],
            [['VORNA'], 'string', 'max' => 40],
            [['MIDNM', 'NACHN', 'CATEG'], 'string', 'max' => 45],
            [['GBDAT', 'HIRE_DATE', 'FIRE_DATE', 'OSTEXT02'], 'string', 'max' => 100],
            [['PLANS_TEXT'], 'string', 'max' => 1024],
            [['STELL'], 'string', 'max' => 60],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'VORNA' => 'Имя',
            'MIDNM' => 'Отчество',
            'NACHN' => 'Фамилия',
            'GESCH' => 'Пол',
            'GBDAT' => 'Gbdat',
            'PERNR' => 'Табельный номер сотрудника',
            'HIRE_DATE' => 'Hire Date',
            'FIRE_DATE' => 'Fire Date',
            'OSTEXT02' => 'Компания',
            'PLANS_TEXT' => 'Должность',
            'CATEG' => 'Categ',
            'OBJID02' => 'Objid02',
            'STELL' => 'Stell',
        ];
    }
}
