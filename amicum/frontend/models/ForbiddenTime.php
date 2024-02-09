<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "forbidden_time".
 *
 * @property int $id
 * @property int $forbidden_zapret_id Внешний идентификатор запрета
 * @property string $date_start Дата и время вхождения в запретную зону
 * @property string|null $date_end Дата и время выхода из запретной зоны
 * @property int $status_id Статус нахождения в запретной зоне
 *
 * @property ForbiddenZapret $forbiddenZapret
 */
class ForbiddenTime extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'forbidden_time';
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
            [['forbidden_zapret_id', 'date_start', 'status_id'], 'required'],
            [['forbidden_zapret_id', 'status_id'], 'integer'],
            [['date_start', 'date_end'], 'safe'],
            [['forbidden_zapret_id'], 'exist', 'skipOnError' => true, 'targetClass' => ForbiddenZapret::className(), 'targetAttribute' => ['forbidden_zapret_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'forbidden_zapret_id' => 'Forbidden Zapret ID',
            'date_start' => 'Date Start',
            'date_end' => 'Date End',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenZapret()
    {
        return $this->hasOne(ForbiddenZapret::className(), ['id' => 'forbidden_zapret_id']);
    }
}
