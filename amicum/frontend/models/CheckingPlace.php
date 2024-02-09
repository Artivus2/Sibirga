<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checking_place".
 *
 * @property int $id
 * @property int $checking_id Внешний ключ проверки
 * @property int $place_id Внешний ключ места
 *
 * @property Checking $checking
 * @property Place $place
 */
class CheckingPlace extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checking_place';
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
            [['checking_id', 'place_id'], 'required'],
            [['checking_id', 'place_id'], 'integer'],
            [['checking_id'], 'exist', 'skipOnError' => true, 'targetClass' => Checking::className(), 'targetAttribute' => ['checking_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'checking_id' => 'Внешний ключ проверки',
            'place_id' => 'Внешний ключ места',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChecking()
    {
        return $this->hasOne(Checking::className(), ['id' => 'checking_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }
}
