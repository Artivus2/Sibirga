<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "harmful_factors".
 *
 * @property int $id Ключ
 * @property string $title Название вредного производственного фактора
 * @property int|null $period Период проведения осмотра по закону
 *
 * @property FactorsOfContingent[] $factorsOfContingents
 */
class HarmfulFactors extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'harmful_factors';
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
            [['period'], 'integer'],
            [['title'], 'string', 'max' => 600],
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
            'period' => 'Period',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFactorsOfContingents()
    {
        return $this->hasMany(FactorsOfContingent::className(), ['harmful_factors_id' => 'id']);
    }
}
