<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "group_operation".
 *
 * @property int $id ключ справочника операций
 * @property string $title название группы операции
 *
 * @property OperationGroup[] $operationGroups
 * @property OrderByChaneGroupOperation[] $orderByChaneGroupOperations
 * @property OrderByChaneGroupOperationStatus[] $orderByChaneGroupOperationStatuses
 * @property PassportGroupOperation[] $passportGroupOperations
 * @property Passport[] $passports
 */
class GroupOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_operation';
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
            'id' => 'ключ справочника операций',
            'title' => 'название группы операции',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationGroups()
    {
        return $this->hasMany(OperationGroup::className(), ['group_operation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneGroupOperations()
    {
        return $this->hasMany(OrderByChaneGroupOperation::className(), ['group_operation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneGroupOperationStatuses()
    {
        return $this->hasMany(OrderByChaneGroupOperationStatus::className(), ['group_operation_fact_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPassportGroupOperations()
    {
        return $this->hasMany(PassportGroupOperation::className(), ['group_operation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPassports()
    {
        return $this->hasMany(Passport::className(), ['id' => 'passport_id'])->viaTable('passport_group_operation', ['group_operation_id' => 'id']);
    }
}
