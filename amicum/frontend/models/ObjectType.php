<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "object_type".
 *
 * @property int $id
 * @property string $title
 * @property int $kind_object_id
 *
 * @property Object[] $objects
 * @property KindObject $kindObject
 */
class ObjectType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'object_type';
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
            [['title', 'kind_object_id'], 'required'],
            [['kind_object_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['kind_object_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindObject::className(), 'targetAttribute' => ['kind_object_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'kind_object_id' => 'Kind Object ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObjects()
    {
        return $this->hasMany(TypicalObject::className(), ['object_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKindObject()
    {
        return $this->hasOne(KindObject::className(), ['id' => 'kind_object_id']);
    }
}
