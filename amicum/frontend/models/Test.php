<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "test".
 *
 * @property int $id
 * @property int $kind_test_id
 * @property string $title название теста
 * @property int|null $actual_status Статус актуальности теста
 * @property string|null $date_time_create Дата и время созданий
 *
 * @property Examination[] $examinations
 * @property KindTest $kindTest
 * @property TestCompanyDepartment[] $testCompanyDepartments
 * @property TestMap[] $testMaps
 * @property TestQuestion[] $testQuestions
 * @property TypeTestType[] $typeTestTypes
 */
class Test extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test';
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
            [['kind_test_id', 'title'], 'required'],
            [['kind_test_id', 'actual_status'], 'integer'],
            [['date_time_create'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['kind_test_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindTest::className(), 'targetAttribute' => ['kind_test_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'kind_test_id' => 'Kind Test ID',
            'title' => 'название теста',
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
        return $this->hasMany(Examination::className(), ['test_id' => 'id']);
    }

    /**
     * Gets query for [[KindTest]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKindTest()
    {
        return $this->hasOne(KindTest::className(), ['id' => 'kind_test_id']);
    }

    /**
     * Gets query for [[TestCompanyDepartments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTestCompanyDepartments()
    {
        return $this->hasMany(TestCompanyDepartment::className(), ['test_id' => 'id']);
    }

    /**
     * Gets query for [[TestMaps]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTestMaps()
    {
        return $this->hasMany(TestMap::className(), ['test_id' => 'id']);
    }

    /**
     * Gets query for [[TestQuestions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTestQuestions()
    {
        return $this->hasMany(TestQuestion::className(), ['test_id' => 'id']);
    }

    /**
     * Gets query for [[TypeTestTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTypeTestTypes()
    {
        return $this->hasMany(TypeTestType::className(), ['test_id' => 'id']);
    }
}
