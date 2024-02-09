<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "solution_operation".
 *
 * @property int $id ключ операции к решению/устранению ситуации
 * @property int|null $operation_id ключ операции
 * @property string|null $description
 * @property string $operation_type тип действия (manual - ручное, auto - автоматическое)
 * @property int|null $status_id ключ статуса выполнения операции
 * @property int|null $equipment_id ключ оборудования на которое назначили операцию
 * @property int|null $worker_id ключ работника, которому назначили операцию
 * @property int|null $position_id ключ должности работника на которого назначили операцию
 * @property int|null $company_department_id ключ департамента работника на которого назанчили операцию
 * @property string|null $date_time дата и время изменения статуса ситуации
 * @property int $solution_card_id ключ карточки решения ситуации
 * @property int|null $on_shift оповещать работника на смене или первого на участке с такой должностью
 *
 * @property SolutionCard $solutionCard
 * @property SolutionOperationStatus[] $solutionOperationStatuses
 */
class SolutionOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'solution_operation';
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
            [['operation_id', 'status_id', 'equipment_id', 'worker_id', 'position_id', 'company_department_id', 'solution_card_id', 'on_shift'], 'integer'],
            [['operation_type', 'solution_card_id'], 'required'],
            [['date_time'], 'safe'],
            [['description'], 'string', 'max' => 1000],
            [['operation_type'], 'string', 'max' => 6],
            [['solution_card_id'], 'exist', 'skipOnError' => true, 'targetClass' => SolutionCard::className(), 'targetAttribute' => ['solution_card_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'operation_id' => 'Operation ID',
            'description' => 'Description',
            'operation_type' => 'Operation Type',
            'status_id' => 'Status ID',
            'equipment_id' => 'Equipment ID',
            'worker_id' => 'Worker ID',
            'position_id' => 'Position ID',
            'company_department_id' => 'Company Department ID',
            'date_time' => 'Date Time',
            'solution_card_id' => 'Solution Card ID',
            'on_shift' => 'On Shift',
        ];
    }

    /**
     * Gets query for [[SolutionCard]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSolutionCard()
    {
        return $this->hasOne(SolutionCard::className(), ['id' => 'solution_card_id']);
    }

    /**
     * Gets query for [[SolutionOperationStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSolutionOperationStatuses()
    {
        return $this->hasMany(SolutionOperationStatus::className(), ['solution_operation_id' => 'id']);
    }

    /**
     * Gets query for [[SolutionOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * Gets query for [[SolutionOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }

    /**
     * Gets query for [[SolutionOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id']);
    }

    /**
     * Gets query for [[SolutionOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * Gets query for [[SolutionOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * Gets query for [[SolutionOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'company_id'])->via('companyDepartment');
    }
}
