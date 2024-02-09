<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company".
 *
 * @property int $id
 * @property string $title
 * @property int $upper_company_id
 * @property string date_time_sync
 * @property string link_1c
 *
 * @property CompanyDepartment[] $companyDepartments
 * @property Mine[] $mines
 * @property ShiftMine[] $shiftMines
 */
class Company extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company';
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
            [['upper_company_id'], 'integer'],
            [['date_time_sync'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['link_1c'], 'string', 'max' => 100],
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
            'upper_company_id' => 'Upper Company ID',
            'link_1c' => 'link_1c',
            'date_time_sync' => 'date_time_sync',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies()
    {
        return $this->hasMany(Company::className(), ['upper_company_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartments()
    {
        return $this->hasMany(CompanyDepartment::className(), ['company_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMines()
    {
        return $this->hasMany(Mine::className(), ['company_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShiftMines()
    {
        return $this->hasMany(ShiftMine::className(), ['company_id' => 'id']);
    }

    public function getLastShiftMine()
    {
        return $this->hasMany(ShiftMine::className(), ['company_id' => 'id'])->orderBy(['date_time'=>SORT_DESC])
            ->select('plan_shift_id')->asArray(true)->limit(1)->all();
    }
}
