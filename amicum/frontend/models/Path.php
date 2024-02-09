<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "path".
 *
 * @property int $id
 * @property string $title
 *
 * @property OrderPlacePath[] $orderPlacePaths
 * @property PathEdge[] $pathEdges
 */
class Path extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'path';
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
    public function getOrderPlacePaths()
    {
        return $this->hasMany(OrderPlacePath::className(), ['path_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPathEdges()
    {
        return $this->hasMany(PathEdge::className(), ['path_id' => 'id']);
    }
}
