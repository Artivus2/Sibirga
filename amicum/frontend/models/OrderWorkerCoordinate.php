<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_worker_coordinate".
 *
 * @property int $edge_id ключ выработки, в котором установлено звено
 * @property int $place_id ключ места, в котором установлено звено
 * @property int $order_id ключ наряда в котором установлено звено
 * @property int $worker_id ключ работника, который устанавливается на карте
 * @property int $brigade_id ключ бригады, в которое добавлено звено
 * @property int $chane_id ключ звена, в которое добавлен работник
 * @property string $coordinate_chane координата звена
 * @property string $coordinate_worker координата работника
 * @property int $id ключ все таблицы
 *
 * @property Brigade $brigade
 * @property Chane $chane
 * @property Order $order
 * @property Place $place
 * @property Worker $worker
 */
class OrderWorkerCoordinate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_worker_coordinate';
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
            [['edge_id', 'place_id', 'order_id', 'worker_id', 'brigade_id', 'chane_id'], 'integer'],
            [['place_id', 'order_id', 'worker_id', 'brigade_id', 'chane_id'], 'required'],
            [['coordinate_chane', 'coordinate_worker'], 'string', 'max' => 50],
            [['brigade_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brigade::className(), 'targetAttribute' => ['brigade_id' => 'id']],
            [['chane_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chane::className(), 'targetAttribute' => ['chane_id' => 'id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'edge_id' => 'ключ выработки, в котором установлено звено',
            'place_id' => 'ключ места, в котором установлено звено',
            'order_id' => 'ключ наряда в котором установлено звено',
            'worker_id' => 'ключ работника, который устанавливается на карте',
            'brigade_id' => 'ключ бригады, в которое добавлено звено',
            'chane_id' => 'ключ звена, в которое добавлен работник',
            'coordinate_chane' => 'координата звена',
            'coordinate_worker' => 'координата работника',
            'id' => 'ключ все таблицы',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigade()
    {
        return $this->hasOne(Brigade::className(), ['id' => 'brigade_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChane()
    {
        return $this->hasOne(Chane::className(), ['id' => 'chane_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerRole()
    {
        return $this->hasOne(WorkerObject::className(), ['worker_id' => 'id'])->viaTable('worker as workerObjectsAlias',['id' => 'worker_id'])->alias('workerRole1');
    }
}
