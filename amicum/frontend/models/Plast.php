<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "plast".
 *
 * @property int $id
 * @property string $title
 * @property int $object_id
 *
 * @property Place[] $places
 * @property Object $object
 * @property PlastFunction[] $plastFunctions
 * @property PlastParameter[] $plastParameters
 */
class Plast extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'plast';
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
            [['id', 'title', 'object_id'], 'required'],
            [['id', 'object_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['id'], 'unique'],
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
            'title' => 'Title',
            'object_id' => 'Object ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaces()
    {
        return $this->hasMany(Place::className(), ['plast_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastFunctions()
    {
        return $this->hasMany(PlastFunction::className(), ['plast_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastParameters()
    {
        return $this->hasMany(PlastParameter::className(), ['plast_id' => 'id']);
    }
}
