<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_group_situation".
 *
 * @property int $id
 * @property string $title
 *
 * @property GroupSituation[] $groupSituations
 */
class KindGroupSituation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_group_situation';
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
            [['title'], 'unique'],
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
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupSituations()
    {
        return $this->hasMany(GroupSituation::className(), ['kind_group_situation_id' => 'id']);
    }
}
