<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "operation_group".
 *
 * @property int $id
 * @property int $operation_id внешний ключ справочника операций
 * @property int $group_operation_id внешний ключ справочника групп операций
 *
 * @property GroupOperation $groupOperation
 * @property Operation $operation
 */
class OperationGroup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'operation_group';
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
            [['operation_id', 'group_operation_id'], 'required'],
            [['operation_id', 'group_operation_id'], 'integer'],
            [['group_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupOperation::className(), 'targetAttribute' => ['group_operation_id' => 'id']],
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
            'operation_id' => 'внешний ключ справочника операций',
            'group_operation_id' => 'внешний ключ справочника групп операций',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupOperation()
    {
        return $this->hasOne(GroupOperation::className(), ['id' => 'group_operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }
}
