<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_operation_img".
 *
 * @property int $id
 * @property int $order_operation_id Внешний ключ на операцию наряда
 * @property string $path Путь до картинки
 *
 * @property OrderOperation $orderOperation
 */
class OrderOperationImg extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_operation_img';
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
            [['order_operation_id', 'path'], 'required'],
            [['order_operation_id'], 'integer'],
            [['path'], 'string', 'max' => 255],
            [['order_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderOperation::className(), 'targetAttribute' => ['order_operation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_operation_id' => 'Внешний ключ на операцию наряда',
            'path' => 'Путь до картинки',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperation()
    {
        return $this->hasOne(OrderOperation::className(), ['id' => 'order_operation_id']);
    }
}
