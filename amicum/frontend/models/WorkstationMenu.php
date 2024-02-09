<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "workstation_menu".
 *
 * @property int $id
 * @property int $workstation_id
 * @property int $menu_id
 *
 * @property Menu $menu
 * @property Workstation $workstation
 */
class WorkstationMenu extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'workstation_menu';
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
            [['workstation_id', 'menu_id'], 'required'],
            [['workstation_id', 'menu_id'], 'integer'],
            [['menu_id'], 'exist', 'skipOnError' => true, 'targetClass' => Menu::className(), 'targetAttribute' => ['menu_id' => 'id']],
            [['workstation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Workstation::className(), 'targetAttribute' => ['workstation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'workstation_id' => 'Workstation ID',
            'menu_id' => 'Menu ID',
        ];
    }

    /**
     * Gets query for [[Menu]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMenu()
    {
        return $this->hasOne(Menu::className(), ['id' => 'menu_id']);
    }

    /**
     * Gets query for [[Workstation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkstation()
    {
        return $this->hasOne(Workstation::className(), ['id' => 'workstation_id']);
    }
}
