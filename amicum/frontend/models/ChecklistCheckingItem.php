<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checklist_checking_item".
 *
 * @property int $id
 * @property int $checklist_checking_id
 * @property int $checklist_item_id
 * @property int $complete
 *
 * @property ChecklistChecking $checklistChecking
 * @property ChecklistItem $checklistItem
 */
class ChecklistCheckingItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checklist_checking_item';
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
            [['checklist_checking_id', 'checklist_item_id', 'complete'], 'required'],
            [['checklist_checking_id', 'checklist_item_id', 'complete'], 'integer'],
            [['checklist_checking_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChecklistChecking::className(), 'targetAttribute' => ['checklist_checking_id' => 'id']],
            [['checklist_item_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChecklistItem::className(), 'targetAttribute' => ['checklist_item_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'checklist_checking_id' => 'Checklist Checking ID',
            'checklist_item_id' => 'Checklist Item ID',
            'complete' => 'Complete',
        ];
    }

    /**
     * Gets query for [[ChecklistChecking]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistChecking()
    {
        return $this->hasOne(ChecklistChecking::className(), ['id' => 'checklist_checking_id']);
    }

    /**
     * Gets query for [[ChecklistItem]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistItem()
    {
        return $this->hasOne(ChecklistItem::className(), ['id' => 'checklist_item_id']);
    }
}
