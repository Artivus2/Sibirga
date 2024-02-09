<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_kind".
 *
 * @property int $id
 * @property string $title Виды нарядов: самостоятельные, звеньевые
 *
 * @property OrderByWorker[] $orderByWorkers
 */
class OrderKind extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_kind';
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
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Виды нарядов: самостоятельные, звеньевые',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByWorkers()
    {
        return $this->hasMany(OrderByWorker::className(), ['order_kind_id' => 'id']);
    }
}
