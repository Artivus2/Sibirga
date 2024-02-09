<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checklist_checking".
 *
 * @property int $id
 * @property string|null $json
 * @property int $checklist_id
 * @property int $audit_id
 * @property int|null $checking_id
 *
 * @property Checklist $checklist
 * @property Audit $audit
 * @property Checking $checking
 * @property ChecklistCheckingItem[] $checklistCheckingItems
 */
class ChecklistChecking extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checklist_checking';
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
            [['json'], 'safe'],
            [['checklist_id', 'audit_id'], 'required'],
            [['checklist_id', 'audit_id', 'checking_id'], 'integer'],
            [['checklist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Checklist::className(), 'targetAttribute' => ['checklist_id' => 'id']],
            [['audit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Audit::className(), 'targetAttribute' => ['audit_id' => 'id']],
            [['checking_id'], 'exist', 'skipOnError' => true, 'targetClass' => Checking::className(), 'targetAttribute' => ['checking_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'json' => 'Json',
            'checklist_id' => 'Checklist ID',
            'audit_id' => 'Audit ID',
            'checking_id' => 'Checking ID',
        ];
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
     * Gets query for [[Audit]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAudit()
    {
        return $this->hasOne(Audit::className(), ['id' => 'audit_id']);
    }

    /**
     * Gets query for [[Checking]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecking()
    {
        return $this->hasOne(Checking::className(), ['id' => 'checking_id']);
    }

    /**
     * Gets query for [[ChecklistCheckingItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChecklistCheckingItems()
    {
        return $this->hasMany(ChecklistCheckingItem::className(), ['checklist_checking_id' => 'id']);
    }
}
