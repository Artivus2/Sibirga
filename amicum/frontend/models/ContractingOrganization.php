<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "contracting_organization".
 *
 * @property int $id
 * @property int $worker_id работник на которого назначается проверка знаний подрядной организацией
 * @property int $company_department_id внешний идентификатр компании подрядчика (участка)
 * @property int $role_id внешний идентификатор роли
 * @property int $reason_check_knowledge_id внешний идентификатор причины проверки знаний-
 * @property string $number_certificate Номер выданного удостоверения
 * @property string $date Дата проведения
 *
 * @property CompanyDepartment $companyDepartment
 * @property ReasonCheckKnowledge $reasonCheckKnowledge
 * @property Role $role
 * @property Worker $worker
 */
class ContractingOrganization extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contracting_organization';
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
            [['worker_id', 'company_department_id', 'role_id', 'reason_check_knowledge_id', 'number_certificate', 'date'], 'required'],
            [['worker_id', 'company_department_id', 'role_id', 'reason_check_knowledge_id'], 'integer'],
            [['date'], 'safe'],
            [['number_certificate'], 'string', 'max' => 255],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['reason_check_knowledge_id'], 'exist', 'skipOnError' => true, 'targetClass' => ReasonCheckKnowledge::className(), 'targetAttribute' => ['reason_check_knowledge_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_id' => 'работник на которого назначается проверка знаний подрядной организацией',
            'company_department_id' => 'внешний идентификатр компании подрядчика (участка)',
            'role_id' => 'внешний идентификатор роли',
            'reason_check_knowledge_id' => 'внешний идентификатор причины проверки знаний-',
            'number_certificate' => 'Номер выданного удостоверения',
            'date' => 'Дата проведения',
        ];
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
    public function getReasonCheckKnowledge()
    {
        return $this->hasOne(ReasonCheckKnowledge::className(), ['id' => 'reason_check_knowledge_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
