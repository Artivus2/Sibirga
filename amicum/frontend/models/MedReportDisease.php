<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "med_report_disease".
 *
 * @property int $id
 * @property int $med_report_id Внешний ключ медосмотра
 * @property int $disease_id Внешний ключ проф заболевания
 * @property string $comment_disease комментраий проф заболевания
 *
 * @property Diseases $disease
 * @property MedReport $medReport
 */
class MedReportDisease extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'med_report_disease';
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
            [['med_report_id', 'disease_id', 'comment_disease'], 'required'],
            [['med_report_id', 'disease_id'], 'integer'],
            [['comment_disease'], 'string', 'max' => 255],
            [['disease_id'], 'exist', 'skipOnError' => true, 'targetClass' => Diseases::className(), 'targetAttribute' => ['disease_id' => 'id']],
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
            'med_report_id' => 'Внешний ключ медосмотра',
            'disease_id' => 'Внешний ключ проф заболевания',
            'comment_disease' => 'комментраий проф заболевания',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDisease()
    {
        return $this->hasOne(Diseases::className(), ['id' => 'disease_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReport()
    {
        return $this->hasOne(MedReport::className(), ['id' => 'med_report_id']);
    }
}
