<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_test".
 *
 * @property int $id ключ вида тестов
 * @property string $title название вида тестов
 * @property int|null $actual_status
 *
 * @property Test[] $tests
 */
class KindTest extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_test';
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
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'actual_status' => 'Actual Status',
        ];
    }

    /**
     * Gets query for [[Tests]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTests()
    {
        return $this->hasMany(Test::className(), ['kind_test_id' => 'id']);
    }
}
