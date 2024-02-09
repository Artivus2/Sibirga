<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "page".
 *
 * @property int $id
 * @property string $title
 * @property string $url
 *
 * @property Access[] $accesses
 * @property Menu[] $menus
 * @property WorkstationPage[] $workstationPages
 */
class Page extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'page';
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
            [['title', 'url'], 'required'],
            [['title', 'url'], 'string', 'max' => 255],
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
            'url' => 'Url',
        ];
    }

    /**
     * Gets query for [[Accesses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAccesses()
    {
        return $this->hasMany(Access::className(), ['page_id' => 'id']);
    }

    /**
     * Gets query for [[Menus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMenus()
    {
        return $this->hasMany(Menu::className(), ['page_id' => 'id']);
    }

    /**
     * Gets query for [[WorkstationPages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkstationPages()
    {
        return $this->hasMany(WorkstationPage::className(), ['page_id' => 'id']);
    }
}
