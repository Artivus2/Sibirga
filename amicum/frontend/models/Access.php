<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "access".
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property int|null $page_id
 *
 * @property Page $page
 * @property UserAccess[] $userAccesses
 * @property WorkstationPage[] $workstationPages
 */
class Access extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'access';
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
            [['title', 'description'], 'required'],
            [['description'], 'string'],
            [['page_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
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
            'description' => 'Description',
            'page_id' => 'Page ID',
        ];
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
     * Gets query for [[UserAccesses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserAccesses()
    {
        return $this->hasMany(UserAccess::className(), ['access_id' => 'id']);
    }

    /**
     * Gets query for [[WorkstationPages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkstationPages()
    {
        return $this->hasMany(WorkstationPage::className(), ['access_id' => 'id']);
    }
}
