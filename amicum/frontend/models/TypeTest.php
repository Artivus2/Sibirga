<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_test".
 *
 * @property int $id ключ типа теста (тест/обучение)
 * @property string $title название типа теста
 * @property int $actual_status Статус актуальности теста
 * @property string $date_time_create Дата и время созданий
 *
 * @property Examination[] $examinations
 * @property TypeTestType[] $typeTestTypes
 * @property Test[] $tests
 */
class TypeTest extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_test';
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
            [['actual_status'], 'integer'],
            [['date_time_create'], 'safe'],
            [['title'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ типа теста (тест/обучение)',
            'title' => 'название типа теста',
            'actual_status' => 'Статус актуальности теста',
            'date_time_create' => 'Дата и время созданий',
        ];
    }

    /**
     * Gets query for [[Examinations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExaminations()
    {
        return $this->hasMany(Examination::className(), ['type_test_id' => 'id']);
    }

    /**
     * Gets query for [[TypeTestTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTypeTestTypes()
    {
        return $this->hasMany(TypeTestType::className(), ['type_test_id' => 'id']);
    }

    /**
     * Gets query for [[Tests]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTests()
    {
        return $this->hasMany(Test::className(), ['id' => 'test_id'])->viaTable('type_test_type', ['type_test_id' => 'id']);
    }
}
