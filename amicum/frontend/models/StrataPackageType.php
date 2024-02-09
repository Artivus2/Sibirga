<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "strata_package_type".
 *
 * @property int $id
 * @property string $title
 * @property int $code
 *
 * @property RecievedStrataPackage[] $recievedStrataPackages
 */
class StrataPackageType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'strata_package_type';
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
            [['title', 'code'], 'required'],
            [['code'], 'integer'],
            [['title'], 'string', 'max' => 120],
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
            'code' => 'Code',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecievedStrataPackages()
    {
        return $this->hasMany(RecievedStrataPackage::className(), ['strata_package_type_id' => 'id']);
    }
}
