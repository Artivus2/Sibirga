<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "pla_activity".
 *
 * @property int $id
 * @property int $pla_id
 * @property int $activity_id
 *
 * @property Activity $activity
 * @property Pla $pla
 */
class PlaActivity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pla_activity';
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
            [['pla_id', 'activity_id'], 'required'],
            [['pla_id', 'activity_id'], 'integer'],
            [['activity_id'], 'exist', 'skipOnError' => true, 'targetClass' => Activity::className(), 'targetAttribute' => ['activity_id' => 'id']],
            [['pla_id'], 'exist', 'skipOnError' => true, 'targetClass' => Pla::className(), 'targetAttribute' => ['pla_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pla_id' => 'Pla ID',
            'activity_id' => 'Activity ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActivity()
    {
        return $this->hasOne(Activity::className(), ['id' => 'activity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPla()
    {
        return $this->hasOne(Pla::className(), ['id' => 'pla_id']);
    }
}
