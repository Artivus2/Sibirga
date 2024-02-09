<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_object_function".
 *
 * @property int $id
 * @property int $object_id
 * @property int $func_id
 *
 * @property Func $func
 * @property Object $object
 */
class TypeObjectFunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_object_function';
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
            [['object_id', 'func_id'], 'required'],
            [['object_id', 'func_id'], 'integer'],
            [['func_id'], 'exist', 'skipOnError' => true, 'targetClass' => Func::className(), 'targetAttribute' => ['func_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'object_id' => 'Object ID',
            'func_id' => 'Func ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFunc()
    {
        return $this->hasOne(Func::className(), ['id' => 'func_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }
}
