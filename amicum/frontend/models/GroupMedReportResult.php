<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "group_med_report_result".
 *
 * @property int $id ключ группы медицинского заключения
 * @property string|null $title Ниаменование группы медицинского заключения
 *
 * @property MedReportResult[] $medReportResults
 */
class GroupMedReportResult extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_med_report_result';
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
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * Gets query for [[MedReportResults]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMedReportResults()
    {
        return $this->hasMany(MedReportResult::className(), ['group_med_report_result_id' => 'id']);
    }
}
