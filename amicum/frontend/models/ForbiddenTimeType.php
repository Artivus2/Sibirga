<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "forbidden_time_type".
 *
 * @property int $id
 * @property int $forbidden_type_id
 * @property int $forbidden_time_id
 * @property string $value
 *
 * @property ForbiddenTime $forbiddenTime
 * @property ForbiddenType $forbiddenType
 */
class ForbiddenTimeType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'forbidden_time_type';
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
            [['forbidden_type_id', 'forbidden_time_id', 'value'], 'required'],
            [['forbidden_type_id', 'forbidden_time_id'], 'integer'],
            [['value'], 'string', 'max' => 255],
            [['forbidden_time_id'], 'exist', 'skipOnError' => true, 'targetClass' => ForbiddenTime::className(), 'targetAttribute' => ['forbidden_time_id' => 'id']],
            [['forbidden_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ForbiddenType::className(), 'targetAttribute' => ['forbidden_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'forbidden_type_id' => 'Forbidden Type ID',
            'forbidden_time_id' => 'Forbidden Time ID',
            'value' => 'Value',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenTime()
    {
        return $this->hasOne(ForbiddenTime::className(), ['id' => 'forbidden_time_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenType()
    {
        return $this->hasOne(ForbiddenType::className(), ['id' => 'forbidden_type_id']);
    }
}
