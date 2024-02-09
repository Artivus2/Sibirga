<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "audit_place".
 *
 * @property int $id
 * @property int $audit_id Внешний идентификатор запланированного аудита
 * @property int $place_id Место запланированного аудита
 *
 * @property Audit $audit
 * @property Place $place
 */
class AuditPlace extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'audit_place';
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
            [['audit_id', 'place_id'], 'required'],
            [['audit_id', 'place_id'], 'integer'],
            [['audit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Audit::className(), 'targetAttribute' => ['audit_id' => 'id']],
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
            'audit_id' => 'Внешний идентификатор запланированного аудита',
            'place_id' => 'Место запланированного аудита',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAudit()
    {
        return $this->hasOne(Audit::className(), ['id' => 'audit_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }
}
