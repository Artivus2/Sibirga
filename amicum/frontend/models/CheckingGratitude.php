<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checking_gratitude".
 *
 * @property int $id
 * @property int $checking_id
 * @property int $company_department_id
 * @property int $place_id
 * @property string|null $comment
 * @property string $date_time
 *
 * @property Checking $checking
 * @property Place $place
 * @property CompanyDepartment $companyDepartment
 * @property CheckingGratitudeAttachment[] $checkingGratitudeAttachments
 * @property CheckingGratitudeWorker[] $checkingGratitudeWorkers
 */
class CheckingGratitude extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checking_gratitude';
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
            [['checking_id', 'company_department_id', 'place_id', 'date_time'], 'required'],
            [['checking_id', 'company_department_id', 'place_id'], 'integer'],
            [['comment'], 'string'],
            [['date_time'], 'safe'],
            [['checking_id'], 'exist', 'skipOnError' => true, 'targetClass' => Checking::className(), 'targetAttribute' => ['checking_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'checking_id' => 'Checking ID',
            'company_department_id' => 'Company Department ID',
            'place_id' => 'Place ID',
            'comment' => 'Comment',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * Gets query for [[Checking]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecking()
    {
        return $this->hasOne(Checking::className(), ['id' => 'checking_id']);
    }

    /**
     * Gets query for [[Place]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * Gets query for [[CompanyDepartment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * Gets query for [[CheckingGratitudeAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingGratitudeAttachments()
    {
        return $this->hasMany(CheckingGratitudeAttachment::className(), ['checking_gratitude_id' => 'id']);
    }

    /**
     * Gets query for [[CheckingGratitudeWorkers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingGratitudeWorkers()
    {
        return $this->hasMany(CheckingGratitudeWorker::className(), ['checking_gratitude_id' => 'id']);
    }
}
