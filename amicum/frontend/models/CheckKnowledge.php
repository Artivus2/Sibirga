<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "check_knowledge".
 *
 * @property int $id идентификатор таблицы проверки знаний
 * @property int $type_check_knowledge_id Внешний идентификатор типа проверки знаний
 * @property int $company_department_id внешний идентификатор участка на котором проводится проверка
 * @property int $reason_check_knowledge_id Внешний идентификатор причины проверки знаний
 * @property string $date дата в которой проводится проверка
 *
 * @property ReasonCheckKnowledge $reasonCheckKnowledge
 * @property TypeCheckKnowledge $typeCheckKnowledge
 * @property CompanyDepartment $companyDepartment
 * @property CheckKnowledgeWorker[] $checkKnowledgeWorkers
 * @property CheckProtocol[] $checkProtocols
 */
class CheckKnowledge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'check_knowledge';
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
            [['type_check_knowledge_id', 'company_department_id', 'date'], 'required'],
            [['type_check_knowledge_id', 'company_department_id', 'reason_check_knowledge_id'], 'integer'],
            [['date'], 'safe'],
            [['reason_check_knowledge_id'], 'exist', 'skipOnError' => true, 'targetClass' => ReasonCheckKnowledge::className(), 'targetAttribute' => ['reason_check_knowledge_id' => 'id']],
            [['type_check_knowledge_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeCheckKnowledge::className(), 'targetAttribute' => ['type_check_knowledge_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'идентификатор таблицы проверки знаний',
            'type_check_knowledge_id' => 'Внешний идентификатор типа проверки знаний',
            'company_department_id' => 'внешний идентификатор участка на котором проводится проверка',
            'reason_check_knowledge_id' => 'Внешний идентификатор причины проверки знаний',
            'date' => 'дата в которой проводится проверка',
        ];
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
    public function getTypeCheckKnowledge()
    {
        return $this->hasOne(TypeCheckKnowledge::className(), ['id' => 'type_check_knowledge_id']);
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
    public function getCheckKnowledgeWorkers()
    {
        return $this->hasMany(CheckKnowledgeWorker::className(), ['check_knowledge_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckProtocols()
    {
        return $this->hasMany(CheckProtocol::className(), ['check_knowledge_id' => 'id']);
    }
}
