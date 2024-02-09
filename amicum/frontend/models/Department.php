<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "department".
 *
 * @property int $id
 * @property string $title
 *
 * @property CompanyDepartment[] $companyDepartments
 * @property GraficTabelMain[] $graficTabelMains
 * @property Order[] $orders
 * @property Timetable[] $timetables
 * @property TimetableInstructionPb[] $timetableInstructionPbs
 */
class Department extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'department';
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
            'title' => 'Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartments()
    {
        return $this->hasMany(CompanyDepartment::className(), ['department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelMains()
    {
        return $this->hasMany(GraficTabelMain::className(), ['department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetables()
    {
        return $this->hasMany(Timetable::className(), ['department_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetableInstructionPbs()
    {
        return $this->hasMany(TimetableInstructionPb::className(), ['department_id' => 'id']);
    }
}
