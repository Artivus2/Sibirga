<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "action_operation".
 *
 * @property int $id
 * @property int $operation_id
 * @property int $regulation_action_id
 * @property string $operation_type тип действия (manual - ручное, auto - автоматическое)
 *
 * @property RegulationAction $regulationAction
 * @property Operation $operation
 * @property ActionOperationEquipment[] $actionOperationEquipments
 * @property ActionOperationPosition[] $actionOperationPositions
 */
class ActionOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'action_operation';
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
            [['operation_id', 'regulation_action_id', 'operation_type'], 'required'],
            [['operation_id', 'regulation_action_id'], 'integer'],
            [['operation_type'], 'string', 'max' => 6],
            [['regulation_action_id'], 'exist', 'skipOnError' => true, 'targetClass' => RegulationAction::className(), 'targetAttribute' => ['regulation_action_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
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
            'regulation_action_id' => 'Regulation Action ID',
            'operation_type' => 'Operation Type',
        ];
    }

    /**
     * Gets query for [[RegulationAction]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegulationAction()
    {
        return $this->hasOne(RegulationAction::className(), ['id' => 'regulation_action_id']);
    }

    /**
     * Gets query for [[Operation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * Gets query for [[ActionOperationEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActionOperationEquipments()
    {
        return $this->hasMany(ActionOperationEquipment::className(), ['action_operation_id' => 'id']);
    }

    /**
     * Gets query for [[ActionOperationPositions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActionOperationPositions()
    {
        return $this->hasMany(ActionOperationPosition::className(), ['action_operation_id' => 'id']);
    }
}
