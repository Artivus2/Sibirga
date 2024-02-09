<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checklist_item".
 *
 * @property int $id
 * @property string $title
 * @property int $number
 * @property string|null $description
 * @property int $checklist_id
 * @property int|null $checklist_group_id
 * @property int $operation_id
 *
 * @property ChecklistCheckingItem[] $checklistCheckingItems
 * @property Checklist $checklist
 * @property Operation $operation
 * @property ChecklistGroup $checklistGroup
 */
class ChecklistItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checklist_item';
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
            [['title', 'number', 'checklist_id', 'operation_id'], 'required'],
            [['number', 'checklist_id', 'checklist_group_id', 'operation_id'], 'integer'],
            [['title'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 255],
            [['checklist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Checklist::className(), 'targetAttribute' => ['checklist_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['checklist_group_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChecklistGroup::className(), 'targetAttribute' => ['checklist_group_id' => 'id']],
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
            'number' => 'Number',
            'description' => 'Description',
            'checklist_id' => 'Checklist ID',
            'checklist_group_id' => 'Checklist Group ID',
            'operation_id' => 'Operation ID',
        ];
    }

    /**
     * Gets query for [[ChecklistCheckingItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistCheckingItems()
    {
        return $this->hasMany(ChecklistCheckingItem::className(), ['checklist_item_id' => 'id']);
    }

    /**
     * Gets query for [[Checklist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecklist()
    {
        return $this->hasOne(Checklist::className(), ['id' => 'checklist_id']);
    }

    /**
     * Gets query for [[Operation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * Gets query for [[ChecklistGroup]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistGroup()
    {
        return $this->hasOne(ChecklistGroup::className(), ['id' => 'checklist_group_id']);
    }
}
