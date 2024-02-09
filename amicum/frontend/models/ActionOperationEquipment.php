<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "action_operation_equipment".
 *
 * @property int $id ключ привязки операции и оборудования
 * @property int $equipment_id ключ оборудования
 * @property int $action_operation_id
 *
 * @property Equipment $equipment
 * @property ActionOperation $actionOperation
 */
class ActionOperationEquipment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'action_operation_equipment';
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
            [['equipment_id', 'action_operation_id'], 'required'],
            [['equipment_id', 'action_operation_id'], 'integer'],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['action_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => ActionOperation::className(), 'targetAttribute' => ['action_operation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'equipment_id' => 'Equipment ID',
            'action_operation_id' => 'Action Operation ID',
        ];
    }

    /**
     * Gets query for [[Equipment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }

    /**
     * Gets query for [[ActionOperation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActionOperation()
    {
        return $this->hasOne(ActionOperation::className(), ['id' => 'action_operation_id']);
    }
}
