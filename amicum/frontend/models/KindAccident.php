<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_accident".
 *
 * @property int $id идентификатор вида происшествия
 * @property string $title Наименование вида происшествия
 * @property int $parent_id
 *
 * @property EventPb[] $eventPbs
 * @property TypeAccident[] $typeAccidents
 */
class KindAccident extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_accident';
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
            [['parent_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'идентификатор вида происшествия',
            'title' => 'Наименование вида происшествия',
            'parent_id' => 'Parent ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventPbs()
    {
        return $this->hasMany(EventPb::className(), ['kind_incident_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeAccidents()
    {
        return $this->hasMany(TypeAccident::className(), ['kind_accident_id' => 'id']);
    }
    public function getKindAccidents()
    {
        return $this->hasMany(KindAccident::className(), ['parent_id' => 'id']);
    }
}
