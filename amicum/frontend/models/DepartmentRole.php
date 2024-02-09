<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "department_role".
 *
 * @property int $id
 * @property string $title
 * @property string $general_type
 *
 * @property TimetableTabel[] $timetableTabels
 */
class DepartmentRole extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'department_role';
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
            [['title', 'general_type'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['general_type'], 'string', 'max' => 1],
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
            'general_type' => 'General Type',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetableTabels()
    {
        return $this->hasMany(TimetableTabel::className(), ['department_role_id' => 'id']);
    }
}
