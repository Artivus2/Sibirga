<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_position_update".
 *
 * @property int $id
 * @property int $STELL
 * @property string $STEXT
 * @property string $qualification
 * @property int $num_sync
 * @property int $status
 * @property string $date_modified
 */
class SapPositionUpdate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_position_update';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['STELL'], 'required'],
            [['STELL', 'num_sync', 'status'], 'integer'],
            [['date_time'], 'safe'],
            [['STEXT'], 'string', 'max' => 512],
            [['qualification'], 'string', 'max' => 5],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'STELL' => 'Stell',
            'STEXT' => 'Stext',
            'qualification' => 'Qualification',
            'num_sync' => 'Num Sync',
            'status' => 'Status',
            'date_modified' => 'Date Modified',
        ];
    }
}
