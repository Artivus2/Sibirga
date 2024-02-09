<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company_department_attachment".
 *
 * @property int $id
 * @property string|null $title Наименование
 * @property string|null $date Дата
 * @property string|null $date_start Дата начала (используется в лицензии)
 * @property int $company_department_id Внешний идентификатор участка
 * @property int|null $attachment_id Внешний идентификатор вложения
 * @property int $company_department_attachment_type_id Внешний идентификатор типа вложения
 *
 * @property Attachment $attachment
 * @property CompanyDepartment $companyDepartment
 * @property CompanyDepartmentAttachmentType $companyDepartmentAttachmentType
 */
class CompanyDepartmentAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_department_attachment';
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
            [['date', 'date_start'], 'safe'],
            [['company_department_id', 'company_department_attachment_type_id'], 'required'],
            [['company_department_id', 'attachment_id', 'company_department_attachment_type_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['company_department_attachment_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartmentAttachmentType::className(), 'targetAttribute' => ['company_department_attachment_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование',
            'date' => 'Дата',
            'date_start' => 'Дата начала (используется в лицензии)',
            'company_department_id' => 'Внешний идентификатор участка',
            'attachment_id' => 'Внешний идентификатор вложения',
            'company_department_attachment_type_id' => 'Внешний идентификатор типа вложения',
        ];
    }

    /**
     * Gets query for [[Attachment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
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
     * Gets query for [[CompanyDepartmentAttachmentType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartmentAttachmentType()
    {
        return $this->hasOne(CompanyDepartmentAttachmentType::className(), ['id' => 'company_department_attachment_type_id']);
    }
}
