<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "violation".
 *
 * @property int $id
 * @property string $title
 * @property int $violation_type_id
 *
 * @property InjunctionViolation[] $injunctionViolations
 * @property ViolationType $violationType
 */
class Violation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'violation';
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
            [['title', 'violation_type_id'], 'required'],
            [['violation_type_id'], 'integer'],
            [['title'], 'string', 'max' => 1000],
            [['violation_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ViolationType::className(), 'targetAttribute' => ['violation_type_id' => 'id']],
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
            'violation_type_id' => 'Violation Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolations()
    {
        return $this->hasMany(InjunctionViolation::className(), ['violation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getViolationType()
    {
        return $this->hasOne(ViolationType::className(), ['id' => 'violation_type_id']);
    }
}
