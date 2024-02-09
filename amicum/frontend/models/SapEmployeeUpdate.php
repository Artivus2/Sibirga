<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_employee_update".
 *
 * @property int $id
 * @property string $VORNA
 * @property string $MIDNM
 * @property string $NACHN
 * @property string $GESCH
 * @property string $GBDAT
 * @property int $PERNR
 * @property string $HIRE_DATE
 * @property string $FIRE_DATE
 * @property string $OSTEXT02
 * @property string $PLANS_TEXT
 * @property string $CATEG
 * @property int $OBJID02
 * @property int $OBJID
 * @property string $STELL
 * @property string $date_modified
 * @property int $num_sync
 * @property int $status
 */
class SapEmployeeUpdate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_employee_update';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['PERNR'], 'required'],
            [['PERNR', 'OBJID02', 'OBJID', 'num_sync', 'status'], 'integer'],
            [['date_modified'], 'safe'],
            [['VORNA', 'MIDNM', 'NACHN', 'GBDAT', 'HIRE_DATE', 'FIRE_DATE', 'CATEG', 'STELL'], 'string', 'max' => 45],
            [['GESCH'], 'string', 'max' => 5],
            [['OSTEXT02'], 'string', 'max' => 100],
            [['PLANS_TEXT'], 'string', 'max' => 1024],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'VORNA' => 'Vorna',
            'MIDNM' => 'Midnm',
            'NACHN' => 'Nachn',
            'GESCH' => 'Gesch',
            'GBDAT' => 'Gbdat',
            'PERNR' => 'Pernr',
            'HIRE_DATE' => 'Hire Date',
            'FIRE_DATE' => 'Fire Date',
            'OSTEXT02' => 'Ostext02',
            'PLANS_TEXT' => 'Plans Text',
            'CATEG' => 'Categ',
            'OBJID02' => 'Objid02',
            'OBJID' => 'Objid',
            'STELL' => 'Stell',
            'date_modified' => 'Date Modified',
            'num_sync' => 'Num Sync',
            'status' => 'Status',
        ];
    }
}
