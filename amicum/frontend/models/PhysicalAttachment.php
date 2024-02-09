<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical_attachment".
 *
 * @property int $id
 * @property int $physical_id Внешний идентификатр  графика проведения медосмотров
 * @property string $date дата согласования
 * @property int $attachment_id внешний идентификатор документа согласования
 * @property int $company_department_id Внешний ключ участка на который сохраняется Согласование/Приказ
 * @property string $title Наименование файла Согласования/Приказа
 *
 * @property Attachment $attachment
 * @property CompanyDepartment $companyDepartment
 * @property Physical $physical
 */
class PhysicalAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical_attachment';
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
            [['physical_id', 'date', 'attachment_id', 'company_department_id'], 'required'],
            [['physical_id', 'attachment_id', 'company_department_id'], 'integer'],
            [['date'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['physical_id'], 'exist', 'skipOnError' => true, 'targetClass' => Physical::className(), 'targetAttribute' => ['physical_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'physical_id' => 'Внешний идентификатр  графика проведения медосмотров',
            'date' => 'дата согласования',
            'attachment_id' => 'внешний идентификатор документа согласования',
            'company_department_id' => 'Внешний ключ участка на который сохраняется Согласование/Приказ',
            'title' => 'Наименование файла Согласования/Приказа',
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
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysical()
    {
        return $this->hasOne(Physical::className(), ['id' => 'physical_id']);
    }
}
