<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical_history".
 *
 * @property int $id
 * @property string $date_update Дата обновления
 * @property int $physical_id
 *
 * @property Physical $physical
 */
class PhysicalHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical_history';
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
            [['date_update', 'physical_id'], 'required'],
            [['date_update'], 'safe'],
            [['physical_id'], 'integer'],
            [['physical_id'], 'exist', 'skipOnError' => true, 'targetClass' => Physical::className(), 'targetAttribute' => ['physical_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_update' => 'Дата обновления',
            'physical_id' => 'Physical ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysical()
    {
        return $this->hasOne(Physical::className(), ['id' => 'physical_id']);
    }
}
