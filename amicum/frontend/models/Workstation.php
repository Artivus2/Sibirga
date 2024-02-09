<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "workstation".
 *
 * @property int $id
 * @property string $title
 * @property int $default
 *
 * @property User[] $users
 * @property WorkstationMenu[] $workstationMenus
 * @property WorkstationPage[] $workstationPages
 */
class Workstation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'workstation';
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
            [['title', 'default'], 'required'],
            [['default'], 'integer'],
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
            'default' => 'Default',
        ];
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['workstation_id' => 'id']);
    }

    /**
     * Gets query for [[WorkstationMenus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkstationMenus()
    {
        return $this->hasMany(WorkstationMenu::className(), ['workstation_id' => 'id']);
    }

    /**
     * Gets query for [[WorkstationPages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkstationPages()
    {
        return $this->hasMany(WorkstationPage::className(), ['workstation_id' => 'id']);
    }
}
