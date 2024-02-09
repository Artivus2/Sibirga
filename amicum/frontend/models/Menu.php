<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "menu".
 *
 * @property int $id
 * @property string $title
 * @property int $page_id
 * @property int $upper_menu_id
 * @property int $view
 *
 * @property Menu $upperMenu
 * @property Menu[] $menus
 * @property Page $page
 * @property WorkstationMenu[] $workstationMenus
 */
class Menu extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'menu';
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
            [['title', 'page_id', 'upper_menu_id', 'view'], 'required'],
            [['page_id', 'upper_menu_id', 'view'], 'integer'],
            [['title'], 'string', 'max' => 45],
            [['upper_menu_id'], 'exist', 'skipOnError' => true, 'targetClass' => Menu::className(), 'targetAttribute' => ['upper_menu_id' => 'id']],
            [['page_id'], 'exist', 'skipOnError' => true, 'targetClass' => Page::className(), 'targetAttribute' => ['page_id' => 'id']],
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
            'page_id' => 'Page ID',
            'upper_menu_id' => 'Upper Menu ID',
            'view' => 'View',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpperMenu()
    {
        return $this->hasOne(Menu::className(), ['id' => 'upper_menu_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenus()
    {
        return $this->hasMany(Menu::className(), ['upper_menu_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPage()
    {
        return $this->hasOne(Page::className(), ['id' => 'page_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkstationMenus()
    {
        return $this->hasMany(WorkstationMenu::className(), ['menu_id' => 'id']);
    }
}
