<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "med_report_result".
 *
 * @property int $id
 * @property string $title Текстовое значение результата медосмотра
 * @property int $group_med_report_result_id ключ группы медицинского заключения
 *
 * @property MedReport[] $medReports
 * @property GroupMedReportResult $groupMedReportResult
 */
class MedReportResult extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'med_report_result';
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
            [['title', 'group_med_report_result_id'], 'required'],
            [['group_med_report_result_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['group_med_report_result_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupMedReportResult::className(), 'targetAttribute' => ['group_med_report_result_id' => 'id']],
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
            'group_med_report_result_id' => 'Group Med Report Result ID',
        ];
    }

    /**
     * Gets query for [[MedReports]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMedReports()
    {
        return $this->hasMany(MedReport::className(), ['med_report_result_id' => 'id']);
    }

    /**
     * Gets query for [[GroupMedReportResult]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGroupMedReportResult()
    {
        return $this->hasOne(GroupMedReportResult::className(), ['id' => 'group_med_report_result_id']);
    }
}
