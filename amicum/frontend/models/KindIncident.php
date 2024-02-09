<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_incident".
 *
 * @property int $id
 * @property string $title название инцидента
 *
 * @property EventPb[] $eventPbs
 */
class KindIncident extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_incident';
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
            'title' => 'название инцидента',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventPbs()
    {
        return $this->hasMany(EventPb::className(), ['kind_incident_id' => 'id']);
    }
}
