<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "reason_danger_motion".
 *
 * @property int $id Идентификатор таблицы (автоинкрементный)
 * @property string $title Наименование опасного действия
 * @property int $parent_reason_danger_motion_id идентификатор родителя опасного действия (категория)
 */
class ReasonDangerMotion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'reason_danger_motion';
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
            [['parent_reason_danger_motion_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы (автоинкрементный)',
            'title' => 'Наименование опасного действия',
            'parent_reason_danger_motion_id' => 'идентификатор родителя опасного действия (категория)',
        ];
    }
}
