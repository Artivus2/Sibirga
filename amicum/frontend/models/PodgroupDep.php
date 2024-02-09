<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "podgroup_dep".
 *
 * @property int $id
 * @property string $title
 * @property int $group_dep_id
 * @property int $chaner_id
 * @property int $chane_type_id
 *
 * @property GroupDepConfig $groupDep
 * @property ChaneType $chaneType
 * @property Worker $chaner
 * @property PodgroupDepWorker[] $podgroupDepWorkers
 */
class PodgroupDep extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'podgroup_dep';
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
            [['title', 'group_dep_id', 'chaner_id', 'chane_type_id'], 'required'],
            [['group_dep_id', 'chaner_id', 'chane_type_id'], 'integer'],
            [['title'], 'string', 'max' => 120],
            [['group_dep_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupDepConfig::className(), 'targetAttribute' => ['group_dep_id' => 'id']],
            [['chane_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChaneType::className(), 'targetAttribute' => ['chane_type_id' => 'id']],
            [['chaner_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['chaner_id' => 'id']],
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
            'group_dep_id' => 'Group Dep ID',
            'chaner_id' => 'Chaner ID',
            'chane_type_id' => 'Chane Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDep()
    {
        return $this->hasOne(GroupDepConfig::className(), ['id' => 'group_dep_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChaneType()
    {
        return $this->hasOne(ChaneType::className(), ['id' => 'chane_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChaner()
    {
        return $this->hasOne(Worker::className(), ['id' => 'chaner_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPodgroupDepWorkers()
    {
        return $this->hasMany(PodgroupDepWorker::className(), ['podgroup_department_id' => 'id']);
    }
}
