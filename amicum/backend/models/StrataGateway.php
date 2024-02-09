<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "strata_gateway".
 *
 * @property int $id
 * @property string $ip
 * @property int $strata_main_id
 * @property int $status_id
 *
 * @property RecievedStrataPackage[] $recievedStrataPackages
 * @property Status $status
 * @property StrataMain $strataMain
 * @property StrataNode[] $strataNodes
 * @property StrataNode[] $strataNodes0
 */
class StrataGateway extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'strata_gateway';
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
            [['ip', 'strata_main_id', 'status_id'], 'required'],
            [['strata_main_id', 'status_id'], 'integer'],
            [['ip'], 'string', 'max' => 15],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['strata_main_id'], 'exist', 'skipOnError' => true, 'targetClass' => StrataMain::className(), 'targetAttribute' => ['strata_main_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'strata_main_id' => 'Strata Main ID',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecievedStrataPackages()
    {
        return $this->hasMany(RecievedStrataPackage::className(), ['strata_gateway_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrataMain()
    {
        return $this->hasOne(StrataMain::className(), ['id' => 'strata_main_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrataNodes()
    {
        return $this->hasMany(StrataNode::className(), ['timing_root_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrataNodes0()
    {
        return $this->hasMany(StrataNode::className(), ['routing_root_id' => 'id']);
    }
}
