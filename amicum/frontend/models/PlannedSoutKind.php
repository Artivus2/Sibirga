<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "planned_sout_kind".
 *
 * @property int $id
 * @property string $title Наименование вида планового СОУТ/ПК
 *
 * @property PlannedSout[] $plannedSouts
 */
class PlannedSoutKind extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'planned_sout_kind';
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
            'title' => 'Наименование вида планового СОУТ/ПК',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlannedSouts()
    {
        return $this->hasMany(PlannedSout::className(), ['planned_sout_kind_id' => 'id']);
    }
}
