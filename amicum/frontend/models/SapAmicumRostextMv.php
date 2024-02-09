<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_amicum_rostext_mv".
 *
 * @property int $instruction_rostext_id
 * @property int $struct_id
 * @property string $rostex_nomer
 * @property string $rostex_date
 * @property string $rostex_fio
 * @property int $rostex_otv_id
 * @property string $fio_otv
 * @property string $prof_otv
 * @property string $desc_error
 * @property string $desc_action
 * @property string $date_plan
 * @property string $date_fact
 * @property string $def_work
 * @property int $int_doc
 * @property string $stop_work
 * @property string $date_stop_work
 * @property int $ref_error_direction_id
 * @property int $color
 */
class SapAmicumRostextMv extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_amicum_rostext_mv';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['instruction_rostext_id'], 'required'],
            [['instruction_rostext_id', 'struct_id', 'rostex_otv_id', 'int_doc', 'ref_error_direction_id', 'color'], 'integer'],
            [['rostex_date', 'date_plan', 'date_fact', 'date_stop_work'], 'safe'],
            [['rostex_nomer', 'rostex_fio'], 'string', 'max' => 415],
            [['fio_otv', 'prof_otv', 'desc_error', 'desc_action'], 'string', 'max' => 1000],
            [['def_work'], 'string', 'max' => 600],
            [['stop_work'], 'string', 'max' => 400],
            [['instruction_rostext_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'instruction_rostext_id' => 'Instruction Rostext ID',
            'struct_id' => 'Struct ID',
            'rostex_nomer' => 'Rostex Nomer',
            'rostex_date' => 'Rostex Date',
            'rostex_fio' => 'Rostex Fio',
            'rostex_otv_id' => 'Rostex Otv ID',
            'fio_otv' => 'Fio Otv',
            'prof_otv' => 'Prof Otv',
            'desc_error' => 'Desc Error',
            'desc_action' => 'Desc Action',
            'date_plan' => 'Date Plan',
            'date_fact' => 'Date Fact',
            'def_work' => 'Def Work',
            'int_doc' => 'Int Doc',
            'stop_work' => 'Stop Work',
            'date_stop_work' => 'Date Stop Work',
            'ref_error_direction_id' => 'Ref Error Direction ID',
            'color' => 'Color',
        ];
    }
}
