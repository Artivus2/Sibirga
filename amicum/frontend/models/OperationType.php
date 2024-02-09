<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "operation_type".
 *
 * @property int $id ключ справочника типов операций
 * @property string $title ключ справочника типов операций
 * @property int $operation_kind_id внешний ключ привязки типов операций к видам операций
 *
 * @property Operation[] $operations
 * @property OperationKind $operationKind
 */
class OperationType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'operation_type';
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
            [['id', 'title', 'operation_kind_id'], 'required'],
            [['id', 'operation_kind_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['id'], 'unique'],
            [['title', 'operation_kind_id'], 'unique', 'targetAttribute' => ['title', 'operation_kind_id']],
            [['operation_kind_id'], 'exist', 'skipOnError' => true, 'targetClass' => OperationKind::className(), 'targetAttribute' => ['operation_kind_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ справочника типов операций',
            'title' => 'ключ справочника типов операций',
            'operation_kind_id' => 'внешний ключ привязки типов операций к видам операций',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperations()
    {
        return $this->hasMany(Operation::className(), ['operation_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationKind()
    {
        return $this->hasOne(OperationKind::className(), ['id' => 'operation_kind_id']);
    }
}
