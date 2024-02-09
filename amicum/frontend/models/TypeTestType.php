<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_test_type".
 *
 * @property int $id ключ привязки типа теста и самого теста
 * @property int $type_test_id ключ типа теста
 * @property int $test_id ключ теста
 * @property int $status выбран/не выбран
 *
 * @property Test $test
 * @property TypeTest $typeTest
 */
class TypeTestType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_test_type';
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
            [['type_test_id', 'test_id'], 'required'],
            [['type_test_id', 'test_id', 'status'], 'integer'],
            [['type_test_id', 'test_id'], 'unique', 'targetAttribute' => ['type_test_id', 'test_id']],
            [['test_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test::className(), 'targetAttribute' => ['test_id' => 'id']],
            [['type_test_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeTest::className(), 'targetAttribute' => ['type_test_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ привязки типа теста и самого теста',
            'type_test_id' => 'ключ типа теста',
            'test_id' => 'ключ теста',
            'status' => 'выбран/не выбран',
        ];
    }

    /**
     * Gets query for [[Test]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTest()
    {
        return $this->hasOne(Test::className(), ['id' => 'test_id']);
    }

    /**
     * Gets query for [[TypeTest]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTypeTest()
    {
        return $this->hasOne(TypeTest::className(), ['id' => 'type_test_id']);
    }
}
