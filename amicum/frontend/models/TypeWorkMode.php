<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_work_mode".
 *
 * @property int $id ключ спраочника типа режима работы
 * @property string $title Название типа режима работы
 *
 * @property WorkMode[] $workModes
 */
class TypeWorkMode extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_work_mode';
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
            'id' => 'ключ спраочника типа режима работы',
            'title' => 'Название типа режима работы',
        ];
    }

    /**
     * Gets query for [[WorkModes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkModes()
    {
        return $this->hasMany(WorkMode::className(), ['type_work_mode_id' => 'id']);
    }
}
