<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "passport_group_operation".
 *
 * @property int $id ключ привязки группы операций к справочнику паспартов
 * @property int $passport_id внешний ключ справочника паспартов
 * @property int $group_operation_id внешний ключ справочника групп операций
 *
 * @property GroupOperation $groupOperation
 * @property Passport $passport
 */
class PassportGroupOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'passport_group_operation';
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
            [['id', 'passport_id', 'group_operation_id'], 'required'],
            [['id', 'passport_id', 'group_operation_id'], 'integer'],
            [['id'], 'unique'],
            [['passport_id', 'group_operation_id'], 'unique', 'targetAttribute' => ['passport_id', 'group_operation_id']],
            [['group_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupOperation::className(), 'targetAttribute' => ['group_operation_id' => 'id']],
            [['passport_id'], 'exist', 'skipOnError' => true, 'targetClass' => Passport::className(), 'targetAttribute' => ['passport_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ привязки группы операций к справочнику паспартов',
            'passport_id' => 'внешний ключ справочника паспартов',
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
    public function getPassport()
    {
        return $this->hasOne(Passport::className(), ['id' => 'passport_id']);
    }
}
