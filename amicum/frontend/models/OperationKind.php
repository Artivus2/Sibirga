<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "operation_kind".
 *
 * @property int $id ключ таблицы видов операций
 * @property string $title название вида операции
 *
 * @property OperationType[] $operationTypes
 */
class OperationKind extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'operation_kind';
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
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ таблицы видов операций',
            'title' => 'название вида операции',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationTypes()
    {
        return $this->hasMany(OperationType::className(), ['operation_kind_id' => 'id']);
    }
}
