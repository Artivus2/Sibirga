<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "brigade_coal_mining_plan".
 *
 * @property int $id Идентификатор таблицы
 * @property int $brigade_id Внешний ключ к таблице бригад
 * @property int $value План добычи угля за месяц  в тоннах
 * @property int $month Мессяц на который задается план
 * @property int $year Год на который задается план
 *
 * @property Brigade $brigade
 */
class BrigadeCoalMiningPlan extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'brigade_coal_mining_plan';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['brigade_id', 'value', 'month', 'year'], 'required'],
            [['brigade_id', 'value', 'month', 'year'], 'integer'],
            [['brigade_id', 'month', 'year'], 'unique', 'targetAttribute' => ['brigade_id', 'month', 'year']],
            [['brigade_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brigade::className(), 'targetAttribute' => ['brigade_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы',
            'brigade_id' => 'Внешний ключ к таблице бригад',
            'value' => 'План добычи угля за месяц  в тоннах',
            'month' => 'Мессяц на который задается план',
            'year' => 'Год на который задается план',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigade()
    {
        return $this->hasOne(Brigade::className(), ['id' => 'brigade_id']);
    }
}
