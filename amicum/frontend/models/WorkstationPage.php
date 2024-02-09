<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "workstation_page".
 *
 * @property int $id
 * @property int $workstation_id
 * @property int $modul_amicum_id
 * @property int $page_id
 * @property int $access_id
 * @property int $permission_amicum права доступа амикум (да1 нет0)
 *
 * @property Access $access
 * @property ModulAmicum $modulAmicum
 * @property Page $page
 * @property Workstation $workstation
 */
class WorkstationPage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'workstation_page';
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
            [['workstation_id', 'modul_amicum_id', 'page_id', 'access_id', 'permission_amicum'], 'required'],
            [['workstation_id', 'modul_amicum_id', 'page_id', 'access_id', 'permission_amicum'], 'integer'],
            [['workstation_id', 'modul_amicum_id', 'page_id', 'access_id'], 'unique', 'targetAttribute' => ['workstation_id', 'modul_amicum_id', 'page_id', 'access_id']],
            [['access_id'], 'exist', 'skipOnError' => true, 'targetClass' => Access::className(), 'targetAttribute' => ['access_id' => 'id']],
            [['modul_amicum_id'], 'exist', 'skipOnError' => true, 'targetClass' => ModulAmicum::className(), 'targetAttribute' => ['modul_amicum_id' => 'id']],
            [['page_id'], 'exist', 'skipOnError' => true, 'targetClass' => Page::className(), 'targetAttribute' => ['page_id' => 'id']],
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
            'modul_amicum_id' => 'Modul Amicum ID',
            'page_id' => 'Page ID',
            'access_id' => 'Access ID',
            'permission_amicum' => 'права доступа амикум (да1 нет0)',
        ];
    }

    /**
     * Gets query for [[Access]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAccess()
    {
        return $this->hasOne(Access::className(), ['id' => 'access_id']);
    }

    /**
     * Gets query for [[ModulAmicum]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getModulAmicum()
    {
        return $this->hasOne(ModulAmicum::className(), ['id' => 'modul_amicum_id']);
    }

    /**
     * Gets query for [[Page]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPage()
    {
        return $this->hasOne(Page::className(), ['id' => 'page_id']);
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
