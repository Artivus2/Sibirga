<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "conjunction".
 *
 * @property int $id
 * @property string $title
 * @property int $object_id
 * @property double $x
 * @property double $z
 * @property double $y
 * @property int $mine_id
 * @property int $ventilation_id
 *
 * @property Mine $mine
 * @property Object $object
 * @property ConjunctionFunction[] $conjunctionFunctions
 * @property ConjunctionParameter[] $conjunctionParameters
 * @property Edge[] $edges
 */
class Conjunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conjunction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'title', 'object_id', 'x', 'z', 'y', 'mine_id'], 'required'],
            [['id', 'object_id', 'mine_id', 'ventilation_id'], 'integer'],
            [['x', 'z', 'y'], 'number'],
            [['title'], 'string', 'max' => 30],
            [['title'], 'unique'],
            [['id'], 'unique'],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
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
            'x' => 'X',
            'z' => 'Z',
            'y' => 'Y',
            'mine_id' => 'Mine ID',
            'ventilation_id' => 'Ventilation ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
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
    public function getConjunctionFunctions()
    {
        return $this->hasMany(ConjunctionFunction::className(), ['conjunction_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionParameters()
    {
        return $this->hasMany(ConjunctionParameter::className(), ['conjunction_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdges()
    {
        return $this->hasMany(Edge::className(), ['conjunction_start_id' => 'id']);
    }
}
