<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_edge_forbidden_zone".
 *
 * @property int $forbidden_zone_id
 * @property string $forbidden_zone_title
 * @property int $edge_id
 * @property int $mine_id внешний ключ справочника шахт
 * @property string $date_start
 * @property string $date_end
 * @property int $forbidden_type_id
 */
class ViewEdgeForbiddenZone extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_edge_forbidden_zone';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['forbidden_zone_id', 'edge_id', 'mine_id', 'forbidden_type_id'], 'integer'],
            [['forbidden_zone_title', 'edge_id', 'mine_id'], 'required'],
            [['date_start', 'date_end'], 'safe'],
            [['forbidden_zone_title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'forbidden_zone_id' => 'Forbidden Zone ID',
            'forbidden_zone_title' => 'Forbidden Zone Title',
            'edge_id' => 'Edge ID',
            'mine_id' => 'внешний ключ справочника шахт',
            'date_start' => 'Date Start',
            'date_end' => 'Date End',
            'forbidden_type_id' => 'Forbidden Type ID',
        ];
    }
}
