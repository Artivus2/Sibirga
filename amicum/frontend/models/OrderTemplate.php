<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_template".
 *
 * @property int $id Идентификатор таблицы (автоинкриментный)
 * @property int $company_department_id Внешний идентификатор участка
 * @property string|null $date_time_create Дата и время создания шаблона наряда
 * @property string $title Название шаблона наряда
 *
 * @property CompanyDepartment $companyDepartment
 * @property OrderTemplateInstructionPb[] $orderTemplateInstructionPbs
 * @property InstructionPb[] $instructionPbs
 * @property OrderTemplatePlace[] $orderTemplatePlaces
 * @property Place[] $places
 */
class OrderTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_template';
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
            [['company_department_id', 'title'], 'required'],
            [['company_department_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['company_department_id', 'title'], 'unique', 'targetAttribute' => ['company_department_id', 'title']],
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
            'company_department_id' => 'Company Department ID',
            'date_time_create' => 'Date Time Create',
            'title' => 'Title',
        ];
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
     * Gets query for [[OrderTemplateInstructionPbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplateInstructionPbs()
    {
        return $this->hasMany(OrderTemplateInstructionPb::className(), ['order_template_id' => 'id']);
    }

    /**
     * Gets query for [[InstructionPbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInstructionPbs()
    {
        return $this->hasMany(InstructionPb::className(), ['id' => 'instruction_pb_id'])->viaTable('order_template_instruction_pb', ['order_template_id' => 'id']);
    }

    /**
     * Gets query for [[OrderTemplatePlaces]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplatePlaces()
    {
        return $this->hasMany(OrderTemplatePlace::className(), ['order_template_id' => 'id']);
    }

    /**
     * Gets query for [[Places]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlaces()
    {
        return $this->hasMany(Place::className(), ['id' => 'place_id'])->viaTable('order_template_place', ['order_template_id' => 'id']);
    }
}
