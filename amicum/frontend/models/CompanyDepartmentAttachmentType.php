<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company_department_attachment_type".
 *
 * @property int $id
 * @property string $title Наименование типа
 *
 * @property CompanyDepartmentAttachment[] $companyDepartmentAttachments
 */
class CompanyDepartmentAttachmentType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_department_attachment_type';
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
            [['title'], 'required'],
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
            'title' => 'Наименование типа',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartmentAttachments()
    {
        return $this->hasMany(CompanyDepartmentAttachment::className(), ['company_department_attachment_type_id' => 'id']);
    }
}
