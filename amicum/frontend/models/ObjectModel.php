<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "object_model".
 *
 * @property int $id
 * @property int $object_id
 * @property string $model
 * @property string $icon
 * @property string $date_time
 *
 * @property Object $object
 */
class ObjectModel extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'object_model';
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
            [['object_id', 'model', 'icon', 'date_time'], 'required'],
            [['object_id'], 'integer'],
            [['date_time'], 'safe'],
            [['model', 'icon'], 'string', 'max' => 255],
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
            'model' => 'Model',
            'icon' => 'Icon',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }
}
