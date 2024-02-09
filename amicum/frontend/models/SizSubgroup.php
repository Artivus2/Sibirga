<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "siz_subgroup".
 *
 * @property int $id Идентификатор(автоинкрементный)
 * @property string $title Наименование подгруппы СИЗ
 * @property int $siz_group_id
 *
 * @property Siz[] $sizs
 * @property SizGroup $sizGroup
 */
class SizSubgroup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'siz_subgroup';
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
            [['title', 'siz_group_id'], 'required'],
            [['siz_group_id'], 'integer'],
            [['title'], 'string', 'max' => 45],
            [['siz_group_id'], 'exist', 'skipOnError' => true, 'targetClass' => SizGroup::className(), 'targetAttribute' => ['siz_group_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор(автоинкрементный)',
            'title' => 'Наименование подгруппы СИЗ',
            'siz_group_id' => 'Siz Group ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizs()
    {
        return $this->hasMany(Siz::className(), ['siz_subgroup_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizGroup()
    {
        return $this->hasOne(SizGroup::className(), ['id' => 'siz_group_id']);
    }
}
