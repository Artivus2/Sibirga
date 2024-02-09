<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "correct_measures_attachment".
 *
 * @property int $id Идентификатор таблицы (автоинкрементный)
 * @property int $correct_measures_id
 * @property string $attachment Вложение в результате проведения корректирующего мероприятия
 *
 * @property CorrectMeasures $correctMeasures
 */
class CorrectMeasuresAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'correct_measures_attachment';
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
            [['correct_measures_id', 'attachment'], 'required'],
            [['correct_measures_id'], 'integer'],
            [['attachment'], 'string', 'max' => 255],
            [['correct_measures_id'], 'exist', 'skipOnError' => true, 'targetClass' => CorrectMeasures::className(), 'targetAttribute' => ['correct_measures_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы (автоинкрементный)',
            'correct_measures_id' => 'Correct Measures ID',
            'attachment' => 'Вложение в результате проведения корректирующего мероприятия',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectMeasures()
    {
        return $this->hasOne(CorrectMeasures::className(), ['id' => 'correct_measures_id']);
    }
}
