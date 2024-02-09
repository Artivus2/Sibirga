<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "expertise_company_expert".
 *
 * @property int $id
 * @property int $company_expert_id Внешний идентификатор компании эксперта
 * @property string $number_expertise Номер проверки
 * @property string $date_expertise Дата ЭПБ
 * @property int $attachment_id Внешний идентификатор вложения
 *
 * @property Attachment $attachment
 * @property CompanyExpert $companyExpert
 */
class ExpertiseCompanyExpert extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'expertise_company_expert';
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
            [['company_expert_id', 'number_expertise', 'date_expertise'], 'required'],
            [['company_expert_id', 'attachment_id'], 'integer'],
            [['date_expertise'], 'safe'],
            [['number_expertise'], 'string', 'max' => 255],
            [['company_expert_id', 'number_expertise', 'date_expertise'], 'unique', 'targetAttribute' => ['company_expert_id', 'number_expertise', 'date_expertise']],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['company_expert_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyExpert::className(), 'targetAttribute' => ['company_expert_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_expert_id' => 'Внешний идентификатор компании эксперта',
            'number_expertise' => 'Номер проверки',
            'date_expertise' => 'Дата ЭПБ',
            'attachment_id' => 'Внешний идентификатор вложения',
        ];
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
    public function getCompanyExpert()
    {
        return $this->hasOne(CompanyExpert::className(), ['id' => 'company_expert_id']);
    }
}
