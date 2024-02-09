<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "med_inspection".
 *
 * @property int $id
 * @property int $checkup_id Дополнительное обследование
 * @property int $med_report_id
 *
 * @property Checkup $checkup
 * @property MedReport $medReport
 */
class MedInspection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'med_inspection';
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
            [['checkup_id', 'med_report_id'], 'required'],
            [['checkup_id', 'med_report_id'], 'integer'],
            [['checkup_id'], 'exist', 'skipOnError' => true, 'targetClass' => Checkup::className(), 'targetAttribute' => ['checkup_id' => 'id']],
            [['med_report_id'], 'exist', 'skipOnError' => true, 'targetClass' => MedReport::className(), 'targetAttribute' => ['med_report_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'checkup_id' => 'Дополнительное обследование',
            'med_report_id' => 'Med Report ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckup()
    {
        return $this->hasOne(Checkup::className(), ['id' => 'checkup_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReport()
    {
        return $this->hasOne(MedReport::className(), ['id' => 'med_report_id']);
    }
}
