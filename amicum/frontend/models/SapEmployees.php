<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_employees".
 *
 * @property int $PERNR Идентификатор и табельный номер
 * @property string $BEGDA Дата начало работы 
 * @property string $ENDDA Дата окончания(увольнения) работы
 * @property int $OBJID идентификатор(id) подразделения
 * @property int $STELL идентификатор(id) должности
 * @property string $NACHN фамилия
 * @property string $VORNA имя
 * @property string $MIDNM отчество
 */
class SapEmployees extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_employees';
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
            [['PERNR', 'BEGDA', 'ENDDA', 'OBJID', 'STELL', 'NACHN', 'VORNA'], 'required'],
            [['PERNR', 'OBJID', 'STELL'], 'integer'],
            [['BEGDA', 'ENDDA'], 'safe'],
            [['NACHN', 'VORNA', 'MIDNM'], 'string', 'max' => 50],
            [['PERNR', 'BEGDA', 'ENDDA'], 'unique', 'targetAttribute' => ['PERNR', 'BEGDA', 'ENDDA']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'PERNR' => 'Идентификатор и табельный номер',
            'BEGDA' => 'Дата начало работы ',
            'ENDDA' => 'Дата окончания(увольнения) работы',
            'OBJID' => 'идентификатор(id) подразделения',
            'STELL' => 'идентификатор(id) должности',
            'NACHN' => 'фамилия',
            'VORNA' => 'имя',
            'MIDNM' => 'отчество',
        ];
    }
}
