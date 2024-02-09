<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "norm_siz_need".
 *
 * @property int $id ключ потребности в СИЗ работника
 * @property int $worker_id ключа работника
 * @property string $date_time_need Дата и время назначения нормы
 * @property int|null $count_siz количество сиз установленных по норме
 * @property int $norm_siz_id ключ связки нормы СИЗ и СИЗ по норме
 */
class NormSizNeed extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'norm_siz_need';
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
            [['worker_id', 'date_time_need', 'norm_siz_id'], 'required'],
            [['worker_id', 'count_siz', 'norm_siz_id'], 'integer'],
            [['date_time_need'], 'safe'],
            [['worker_id', 'date_time_need', 'norm_siz_id'], 'unique', 'targetAttribute' => ['worker_id', 'date_time_need', 'norm_siz_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_id' => 'Worker ID',
            'date_time_need' => 'Date Time Need',
            'count_siz' => 'Count Siz',
            'norm_siz_id' => 'Norm Siz ID',
        ];
    }
}
