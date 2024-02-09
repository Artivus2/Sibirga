<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical_kind".
 *
 * @property int $id
 * @property string $title Название вида графика ( периодический)
 *
 * @property Physical[] $physicals
 */
class PhysicalKind extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical_kind';
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
            'title' => 'Название вида графика ( периодический)',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicals()
    {
        return $this->hasMany(Physical::className(), ['physical_kind_id' => 'id']);
    }
}
