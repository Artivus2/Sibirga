<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "med_report".
 *
 * @property int $id
 * @property int $worker_id Ключ работника, кто проходил мед.осмотр
 * @property int|null $position_id Профессия сотрудника на момент проверки (внешний идентификатор профессии сотрудника)
 * @property string|null $med_report_date Дата выдачи заключения 
 * @property string|null $date_next Дата следующего мед.осмотра
 * @property string|null $comment_result Комментарий к заключительному результату мед.осмотра
 * @property int|null $disease_id Ключ проф. заболевания
 * @property int|null $med_report_result_id Ключ результата заключения мед. осмотра
 * @property int|null $classifier_diseases_id Комментарий дополнительное поле для внесение комментария (напр. для указания класса заболевания) 
 * @property int|null $attachment_id Путь к документу (заключению по мед. осмотру)
 * @property string $physical_worker_date Дата прохождения мед. осмотра
 * @property int $company_department_id ключ подразделения в котором работал работник на момент прохождения МО
 *
 * @property ClassifierDiseases $classifierDiseases
 * @property Diseases $disease
 * @property MedReportResult $medReportResult
 * @property Position $position
 * @property Worker $worker
 * @property Attachment $attachment
 * @property MedReportDisease[] $medReportDiseases
 */
class MedReport extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'med_report';
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
            [['worker_id', 'physical_worker_date', 'company_department_id'], 'required'],
            [['worker_id', 'position_id', 'disease_id', 'med_report_result_id', 'classifier_diseases_id', 'attachment_id', 'company_department_id'], 'integer'],
            [['med_report_date', 'date_next', 'physical_worker_date'], 'safe'],
            [['comment_result'], 'string', 'max' => 1000],
            [['classifier_diseases_id'], 'exist', 'skipOnError' => true, 'targetClass' => ClassifierDiseases::className(), 'targetAttribute' => ['classifier_diseases_id' => 'id']],
            [['disease_id'], 'exist', 'skipOnError' => true, 'targetClass' => Diseases::className(), 'targetAttribute' => ['disease_id' => 'id']],
            [['med_report_result_id'], 'exist', 'skipOnError' => true, 'targetClass' => MedReportResult::className(), 'targetAttribute' => ['med_report_result_id' => 'id']],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::className(), 'targetAttribute' => ['position_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_id' => 'Worker ID',
            'position_id' => 'Position ID',
            'med_report_date' => 'Med Report Date',
            'date_next' => 'Date Next',
            'comment_result' => 'Comment Result',
            'disease_id' => 'Disease ID',
            'med_report_result_id' => 'Med Report Result ID',
            'classifier_diseases_id' => 'Classifier Diseases ID',
            'attachment_id' => 'Attachment ID',
            'physical_worker_date' => 'Physical Worker Date',
            'company_department_id' => 'Company Department ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClassifierDiseases()
    {
        return $this->hasOne(ClassifierDiseases::className(), ['id' => 'classifier_diseases_id']);
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
    public function getMedReportResult()
    {
        return $this->hasOne(MedReportResult::className(), ['id' => 'med_report_result_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReportDiseases()
    {
        return $this->hasMany(MedReportDisease::className(), ['med_report_id' => 'id']);
    }
}
