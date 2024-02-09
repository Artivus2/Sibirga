<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "status_type".
 *
 * @property int $id ключ справочника типов статуса
 * @property string $title название типа статуса (нарядной системы, системы позиционирования) 
 *
 * @property Status[] $statuses
 */
class StatusType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'status_type';
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
            'id' => 'ключ справочника типов статуса',
            'title' => 'название типа статуса (нарядной системы, системы позиционирования) ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatuses()
    {
        return $this->hasMany(Status::className(), ['status_type_id' => 'id']);
    }
}
