<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checklist".
 *
 * @property int $id
 * @property string $title
 * @property string|null $json
 *
 * @property ChecklistChecking[] $checklistCheckings
 * @property ChecklistItem[] $checklistItems
 */
class Checklist extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checklist';
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
            [['json'], 'safe'],
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
            'json' => 'Json',
        ];
    }

    /**
     * Gets query for [[ChecklistCheckings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistCheckings()
    {
        return $this->hasMany(ChecklistChecking::className(), ['checklist_id' => 'id']);
    }

    /**
     * Gets query for [[ChecklistItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistItems()
    {
        return $this->hasMany(ChecklistItem::className(), ['checklist_id' => 'id']);
    }
}
