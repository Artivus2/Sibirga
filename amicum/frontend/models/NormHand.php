<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "norm_hand".
 *
 * @property int $id ключ нормы
 * @property string|null $title Название нормы выдачи СИЗ
 * @property string|null $link_1c ключ нормы выдачи СИЗ из внешней системы 1С
 * @property string|null $issue_type тип нормы выдачи (персональная, групповая)
 * @property string|null $calculation_type тип расчета нормы выдачи (период, до даты)
 * @property string|null $period_type тип периода нормы выдачи - месяц, год, день и т.д.
 * @property int|null $period_count длительность периода
 * @property int|null $period_quantity количество раз выдачи в период
 *
 * @property NormSiz[] $normSizs
 * @property SizHandNorm[] $sizHandNormIdLink1cs
 */
class NormHand extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'norm_hand';
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
            [['period_count', 'period_quantity'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['link_1c'], 'string', 'max' => 100],
            [['issue_type', 'calculation_type', 'period_type'], 'string', 'max' => 150],
            [['link_1c'], 'unique'],
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
            'link_1c' => 'Link 1c',
            'issue_type' => 'Issue Type',
            'calculation_type' => 'Calculation Type',
            'period_type' => 'Period Type',
            'period_count' => 'Period Count',
            'period_quantity' => 'Period Quantity',
        ];
    }

    /**
     * Gets query for [[NormSizs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNormSizs()
    {
        return $this->hasMany(NormSiz::className(), ['norm_hand_id_link_1c' => 'link_1c']);
    }

    /**
     * Gets query for [[SizHandNormIdLink1cs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSizHandNormIdLink1cs()
    {
        return $this->hasMany(SizHandNorm::className(), ['link_1c' => 'siz_hand_norm_id_link_1c'])->viaTable('norm_siz', ['norm_hand_id_link_1c' => 'link_1c']);
    }
}
