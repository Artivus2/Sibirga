<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checklist_group".
 *
 * @property int $id
 * @property string $title
 *
 * @property ChecklistItem[] $checklistItems
 */
class ChecklistGroup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checklist_group';
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
            [['title'], 'string', 'max' => 50],
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
     * Gets query for [[ChecklistItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistItems()
    {
        return $this->hasMany(ChecklistItem::className(), ['checklist_group_id' => 'id']);
    }
}
