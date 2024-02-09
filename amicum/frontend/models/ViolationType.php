<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "violation_type".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)
 * @property string $title Название типа нарушения
 * @property int $kind_violation_id Внешний ключ вида нарушения
 * @property string $date_time_sync дата синхронизации
 * @property int $ref_error_direction_id
 *
 * @property Violation[] $violations
 * @property KindViolation $kindViolation
 */
class ViolationType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'violation_type';
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
            [['title', 'kind_violation_id'], 'required'],
            [['kind_violation_id', 'ref_error_direction_id'], 'integer'],
            [['date_time_sync'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['kind_violation_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindViolation::className(), 'targetAttribute' => ['kind_violation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор текущей таблицы (автоинкрементный)',
            'title' => 'Название типа нарушения',
            'kind_violation_id' => 'Внешний ключ вида нарушения',
            'date_time_sync' => 'дата синхронизации',
            'ref_error_direction_id' => 'Ref Error Direction ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getViolations()
    {
        return $this->hasMany(Violation::className(), ['violation_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKindViolation()
    {
        return $this->hasOne(KindViolation::className(), ['id' => 'kind_violation_id']);
    }
}
