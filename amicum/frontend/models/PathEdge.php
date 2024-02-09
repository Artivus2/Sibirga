<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "path_edge".
 *
 * @property int $id
 * @property int $path_id
 * @property int $edge_id
 *
 * @property Edge $edge
 * @property Path $path
 */
class PathEdge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'path_edge';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['path_id', 'edge_id'], 'required'],
            [['path_id', 'edge_id'], 'integer'],
            [['edge_id'], 'exist', 'skipOnError' => true, 'targetClass' => Edge::className(), 'targetAttribute' => ['edge_id' => 'id']],
            [['path_id'], 'exist', 'skipOnError' => true, 'targetClass' => Path::className(), 'targetAttribute' => ['path_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'path_id' => 'Path ID',
            'edge_id' => 'Edge ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdge()
    {
        return $this->hasOne(Edge::className(), ['id' => 'edge_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPath()
    {
        return $this->hasOne(Path::className(), ['id' => 'path_id']);
    }
}
