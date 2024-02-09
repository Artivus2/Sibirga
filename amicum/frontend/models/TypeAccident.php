<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_accident".
 *
 * @property int $id
 * @property string $title
 * @property int $kind_accident_id
 *
 * @property KindAccident $kindAccident
 */
class TypeAccident extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_accident';
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
            [['kind_accident_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['kind_accident_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindAccident::className(), 'targetAttribute' => ['kind_accident_id' => 'id']],
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
            'kind_accident_id' => 'Kind Accident ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKindAccident()
    {
        return $this->hasOne(KindAccident::className(), ['id' => 'kind_accident_id']);
    }
}
