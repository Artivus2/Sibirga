<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_violation".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)\\\\n
 * @property string $title Название вида нарушения\\\\n
 * @property string $date_time_sync дата и время синхронизации
 * @property int $ref_error_direction_id ключ внешней таблицы САП синхронизации
 *
 * @property ViolationType[] $violationTypes
 */
class KindViolation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_violation';
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
            [['date_time_sync'], 'safe'],
            [['ref_error_direction_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор текущей таблицы (автоинкрементный)\\\\\\\\n',
            'title' => 'Название вида нарушения\\\\\\\\n',
            'date_time_sync' => 'дата и время синхронизации',
            'ref_error_direction_id' => 'ключ внешней таблицы САП синхронизации',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getViolationTypes()
    {
        return $this->hasMany(ViolationType::className(), ['kind_violation_id' => 'id']);
    }
}
