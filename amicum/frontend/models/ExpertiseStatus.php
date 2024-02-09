<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "expertise_status".
 *
 * @property int $id
 * @property int $expertise_id Внешний ключ экспертизы
 * @property int $status_id Внешний ключ таблицы статусов
 *
 * @property Expertise $expertise
 * @property Status $status
 */
class ExpertiseStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'expertise_status';
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
            [['expertise_id', 'status_id'], 'required'],
            [['expertise_id', 'status_id'], 'integer'],
            [['expertise_id'], 'exist', 'skipOnError' => true, 'targetClass' => Expertise::className(), 'targetAttribute' => ['expertise_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'expertise_id' => 'Внешний ключ экспертизы',
            'status_id' => 'Внешний ключ таблицы статусов',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpertise()
    {
        return $this->hasOne(Expertise::className(), ['id' => 'expertise_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
