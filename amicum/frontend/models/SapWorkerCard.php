<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_worker_card".
 *
 * @property int $ID
 * @property int $FTABN_SAP табельный номер сотрудника
 * @property string $FNCARD карта сотрудника
 * @property int $FPEOPLEGID
 * @property int $FBDATE
 * @property int $FTYPE
 * @property string $FBDTIME
 * @property string $FEDTIME
 * @property string $FEDITOR
 * @property int $FORG
 * @property int $FNHELP
 * @property int $FDEPGID
 * @property int $FTYPESYSTEM
 * @property string $LAST_CHANGE
 * @property string $FTYPECARD
 * @property int $FEDATE
 */
class SapWorkerCard extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_worker_card';
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
            [['ID'], 'required'],
            [['ID', 'FTABN_SAP', 'FPEOPLEGID', 'FBDATE', 'FTYPE', 'FORG', 'FNHELP', 'FDEPGID', 'FTYPESYSTEM', 'FEDATE'], 'integer'],
            [['FBDTIME', 'FEDTIME', 'LAST_CHANGE'], 'safe'],
            [['FNCARD', 'FEDITOR', 'FTYPECARD'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ID' => 'ID',
            'FTABN_SAP' => 'табельный номер сотрудника',
            'FNCARD' => 'карта сотрудника',
            'FPEOPLEGID' => 'Fpeoplegid',
            'FBDATE' => 'Fbdate',
            'FTYPE' => 'Ftype',
            'FBDTIME' => 'Fbdtime',
            'FEDTIME' => 'Fedtime',
            'FEDITOR' => 'Feditor',
            'FORG' => 'Forg',
            'FNHELP' => 'Fnhelp',
            'FDEPGID' => 'Fdepgid',
            'FTYPESYSTEM' => 'Ftypesystem',
            'LAST_CHANGE' => 'Last Change',
            'FTYPECARD' => 'Ftypecard',
            'FEDATE' => 'Fedate',
        ];
    }
}
