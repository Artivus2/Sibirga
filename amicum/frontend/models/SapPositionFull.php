<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_position_full".
 *
 * @property int $id
 * @property int $STELL
 * @property string $STEXT
 * @property string $qualification
 * @property int $status
 */
class SapPositionFull extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_position_full';
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
            [['STELL', 'STEXT'], 'required'],
            [['STELL', 'status'], 'integer'],
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
            'status' => 'Status',
        ];
    }
}
