<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "case_pb".
 *
 * @property int $id ключ обстоятельства
 * @property string $title Название обстоятельства
 *
 * @property EventPb[] $eventPbs
 * @property InquiryPb[] $inquiryPbs
 */
class CasePb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'case_pb';
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
            [['title'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ обстоятельства',
            'title' => 'Название обстоятельства',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventPbs()
    {
        return $this->hasMany(EventPb::className(), ['case_pb' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiryPbs()
    {
        return $this->hasMany(InquiryPb::className(), ['case_pb_id' => 'id']);
    }
}
