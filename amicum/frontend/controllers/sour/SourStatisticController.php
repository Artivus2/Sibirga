<?php

namespace frontend\controllers\sour;

use frontend\controllers\handbooks\BrigadeController;
use frontend\models\BrigadeWorker;
use frontend\models\Chane;
use frontend\models\ChaneWorker;
use yii\db\Query;
use yii\web\Controller;
use frontend\controllers\Assistant;

class SourStatisticController extends Controller
{
    // GetSourStatistic - метод получения статистики по шахтам СОУР

    /**
     * Назначение: GetSourStatistic - метод получения статистики по шахтам СОУР
     * Название метода: GetSourStatistic()
     * алгоритм:
     *  1. получить статистику событий по ситуациям по системам по шахтам за период
     *  2. сформировать выходной объект
     * @return array
     * statisticData:
     *      {mine_id}
     *          mine_id: 290,
     *          mine_title: 'ш. Заполярная',
     *          statistics:
     *              sensor_objects:
     *                  {object_id}:
     *                      object_id: 1,
     *                      object_title: 'CH4',
     *                      object_count: '100',
     *                      situations:
     *                          {situation_id}:
     *                              situation_id: 11,
     *                              situation_title: 'Загазирование',
     *                              situation_count: '55',
     *                              events: {
     *                                  {event_id}:
     *                                      event_id: 1,
     *                                      event_title: 'Остановка ВМП',
     *                                      event_count: '15'
     *              asmtp:
     *                  {asmtp_id}:
     *                      asmtp_id: 1,
     *                      asmtp_title: 'Strata система позиционирования',
     *                      asmtp_count: '45',
     *                      situations:
     *                          {situation_id}:
     *                              situation_id: 60,
     *                              situation_title: 'Отказ индив.датч.',
     *                              situation_count: '5',
     *                              events:
     *                                  {event_id}:
     *                                  event_id: 1,
     *                                  event_title: 'Остановка ВМП',
     *                                  event_count: '5'
     *              graphics_by_situation:
     *                  {situation_id}:
     *                      situation_id: 1,
     *                      situation_title: 'Загазирование',
     *                      situation_data: [10, 20, 33, 14, 54, 11, 1, 6, 15, 9, 21]
     *              graphics_summary:
     *                  title: 'Общая статистика событий по месяцам',
     *                  summary_data: [10, 20, 33, 14, 54, 11, 1, 6, 15, 9, 21, 43]
     * @package frontend\controllers
     * @see
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=sour\SourStatistic&method=GetSourStatistic&subscribe=&data={"year":2020}
     * @author Якимов М.Н.
     * Created date: on 30.06.2020 17:22
     * @since ver
     */
    public static function GetSourStatistic($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetSourStatistic. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new \Exception('GetSourStatistic. Данные с фронта не получены');
            }
            $warnings[] = 'GetSourStatistic. Данные успешно переданы';
            $warnings[] = 'GetSourStatistic. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetSourStatistic. Декодировал входные параметры';
            if (
            !(property_exists($post_dec, 'year'))
            ) {
                throw new \Exception('GetSourStatistic. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetSourStatistic. Данные с фронта получены и они правильные';
            $year = $post_dec->year;

            // Расчет статистики по АСУТП системам их ситуациям и событиям
            $asmtps = (new Query())
                ->select('
                    mine_id, mine_title,
                    situation_id, situation_title,
                    event_id, event_title,
                    situation_journal_id,
                    event_journal_id,
                    asmtp_id, asmtp_title,
                    year_situation
               ')
                ->from('view_get_sour_statistic_asmtp')
                ->groupBy('mine_id, mine_title,
                    situation_id, situation_title,
                    event_id, event_title,
                    situation_journal_id,
                    event_journal_id,
                    asmtp_id, asmtp_title,
                    year_situation')
                ->where('year_situation=' . $year)
                ->all();
            foreach ($asmtps as $asmtp) {
                $mine_id = $asmtp['mine_id'];
                $asmtp_id = $asmtp['asmtp_id'];
                $situation_id = $asmtp['situation_id'];
                $event_id = $asmtp['event_id'];

                $statistic[$mine_id]['mine_id'] = $mine_id;
                $statistic[$mine_id]['mine_title'] = $asmtp['mine_title'];

                $statistic[$mine_id]['statistics']['asmtp'][$asmtp_id]['asmtp_id'] = $asmtp_id;
                $statistic[$mine_id]['statistics']['asmtp'][$asmtp_id]['asmtp_title'] = $asmtp['asmtp_title'];
                $statistic[$mine_id]['statistics']['asmtp'][$asmtp_id]['asmtp_count'] = 0;
                $statistic[$mine_id]['statistics']['asmtp'][$asmtp_id]['situations'][$situation_id]['situation_id'] = $situation_id;
                $statistic[$mine_id]['statistics']['asmtp'][$asmtp_id]['situations'][$situation_id]['situation_title'] = $asmtp['situation_title'];
                $statistic[$mine_id]['statistics']['asmtp'][$asmtp_id]['situations'][$situation_id]['situation_count'] = 0;
                $statistic[$mine_id]['statistics']['asmtp'][$asmtp_id]['situations'][$situation_id]['events'][$event_id]['event_id'] = $event_id;
                $statistic[$mine_id]['statistics']['asmtp'][$asmtp_id]['situations'][$situation_id]['events'][$event_id]['event_title'] = $asmtp['event_title'];
                $statistic[$mine_id]['statistics']['asmtp'][$asmtp_id]['situations'][$situation_id]['events'][$event_id]['event_journals'][$asmtp['event_journal_id']] = $asmtp['event_journal_id'];
                $statistic[$mine_id]['statistics']['asmtp'][$asmtp_id]['situations'][$situation_id]['events'][$event_id]['event_count'] = 0;
            }
            if (isset($statistic)) {
                foreach ($statistic as $mine) {
                    foreach ($mine['statistics']['asmtp'] as $asmtp) {
                        foreach ($asmtp['situations'] as $situation) {
                            foreach ($situation['events'] as $event) {
                                $events_count = count($event['event_journals']);
                                $statistic[$mine['mine_id']]['statistics']['asmtp'][$asmtp['asmtp_id']]['situations'][$situation['situation_id']]['events'][$event['event_id']]['event_count'] = $events_count;
                                $statistic[$mine['mine_id']]['statistics']['asmtp'][$asmtp['asmtp_id']]['situations'][$situation['situation_id']]['situation_count'] += $events_count;
                                $statistic[$mine['mine_id']]['statistics']['asmtp'][$asmtp['asmtp_id']]['asmtp_count'] += $events_count;
                                unset($statistic[$mine['mine_id']]['statistics']['asmtp'][$asmtp['asmtp_id']]['situations'][$situation['situation_id']]['events'][$event['event_id']]['event_journals']);
                            }
                        }
                    }
                }
            }

            // Расчет статистики по сенсорам их ситуациям и событиям
            $objects = (new Query())
                ->select('
                    mine_id, mine_title,
                    situation_id, situation_title,
                    event_id, event_title,
                    situation_journal_id,
                    event_journal_id,
                    object_id, object_title,
                    year_situation
               ')
                ->from('view_get_sour_statistic_sensor')
                ->groupBy('mine_id, mine_title,
                    situation_id, situation_title,
                    event_id, event_title,
                    situation_journal_id,
                    event_journal_id,
                    object_id, object_title,
                    year_situation')
                ->where('year_situation=' . $year)
                ->all();
            foreach ($objects as $object) {
                $mine_id = $object['mine_id'];
                $object_id = $object['object_id'];
                $situation_id = $object['situation_id'];
                $event_id = $object['event_id'];

                $statistic[$mine_id]['mine_id'] = $mine_id;
                $statistic[$mine_id]['mine_title'] = $object['mine_title'];

                $statistic[$mine_id]['statistics']['sensor_objects'][$object_id]['object_id'] = $object_id;
                $statistic[$mine_id]['statistics']['sensor_objects'][$object_id]['object_title'] = $object['object_title'];
                $statistic[$mine_id]['statistics']['sensor_objects'][$object_id]['object_count'] = 0;
                $statistic[$mine_id]['statistics']['sensor_objects'][$object_id]['situations'][$situation_id]['situation_id'] = $situation_id;
                $statistic[$mine_id]['statistics']['sensor_objects'][$object_id]['situations'][$situation_id]['situation_title'] = $object['situation_title'];
                $statistic[$mine_id]['statistics']['sensor_objects'][$object_id]['situations'][$situation_id]['situation_count'] = 0;
                $statistic[$mine_id]['statistics']['sensor_objects'][$object_id]['situations'][$situation_id]['events'][$event_id]['event_id'] = $event_id;
                $statistic[$mine_id]['statistics']['sensor_objects'][$object_id]['situations'][$situation_id]['events'][$event_id]['event_title'] = $object['event_title'];
                $statistic[$mine_id]['statistics']['sensor_objects'][$object_id]['situations'][$situation_id]['events'][$event_id]['event_journals'][$object['event_journal_id']] = $object['event_journal_id'];
                $statistic[$mine_id]['statistics']['sensor_objects'][$object_id]['situations'][$situation_id]['events'][$event_id]['event_count'] = 0;
            }
            if (isset($statistic)) {
                foreach ($statistic as $mine) {
                    if(isset($mine['statistics']['sensor_objects'])) {
                        foreach ($mine['statistics']['sensor_objects'] as $object) {
                            foreach ($object['situations'] as $situation) {
                                foreach ($situation['events'] as $event) {
                                    $events_count = count($event['event_journals']);
                                    $statistic[$mine['mine_id']]['statistics']['sensor_objects'][$object['object_id']]['situations'][$situation['situation_id']]['events'][$event['event_id']]['event_count'] = $events_count;
                                    $statistic[$mine['mine_id']]['statistics']['sensor_objects'][$object['object_id']]['situations'][$situation['situation_id']]['situation_count'] += $events_count;
                                    $statistic[$mine['mine_id']]['statistics']['sensor_objects'][$object['object_id']]['object_count'] += $events_count;
                                    unset($statistic[$mine['mine_id']]['statistics']['sensor_objects'][$object['object_id']]['situations'][$situation['situation_id']]['events'][$event['event_id']]['event_journals']);
                                }
                            }
                        }
                    }
                }
            }

            // Расчет статистики по видам ситуаций
            $situations = (new Query())
                ->select('
                    mine.id as mine_id, mine.title as mine_title,
                    situation.id as situation_id, situation.title as situation_title,
                    count(situation_journal.id) as count_situation_journal,
                    MONTH(situation_journal.date_time) as month_situation,
                    YEAR(situation_journal.date_time) as year_situation
               ')
                ->from('situation_journal')
                ->innerJoin('situation','situation.id=situation_journal.situation_id')
                ->innerJoin('mine','mine.id=situation_journal.mine_id')
                ->where('YEAR(situation_journal.date_time)=' . $year)
                ->groupBy('mine_id,mine_title,situation_title,situation_id,month_situation,year_situation')
                ->all();
            foreach ($situations as $situation) {
                $mine_id = $situation['mine_id'];
                $situation_id = $situation['situation_id'];

                if(!isset($statistic[$mine_id]['statistics']['graphics_by_situation'][$situation_id])) {
                    for($i=0; $i<12; $i++) {
                        $statistic[$mine_id]['statistics']['graphics_by_situation'][$situation_id]['situation_data'][$i] = 0;
                    }
                }

                $statistic[$mine_id]['mine_id'] = $mine_id;
                $statistic[$mine_id]['mine_title'] = $situation['mine_title'];

                $statistic[$mine_id]['statistics']['graphics_by_situation'][$situation_id]['situation_id'] = $situation_id;
                $statistic[$mine_id]['statistics']['graphics_by_situation'][$situation_id]['situation_title'] = $situation['situation_title'];
                $statistic[$mine_id]['statistics']['graphics_by_situation'][$situation_id]['situation_data'][$situation['month_situation']-1] = (int) $situation['count_situation_journal'];
            }
            if(!isset($statistic)) {
                $statistic = (object) array();
            } else {
                foreach ($statistic as $mine) {
                    if(!isset($mine['statistics']['graphics_by_situation'])) {
                        $statistic[$mine['mine_id']]['statistics']['graphics_by_situation'] = (object) array();
                    }
                    if(!isset($mine['statistics']['sensor_objects'])) {
                        $statistic[$mine['mine_id']]['statistics']['sensor_objects'] = (object) array();
                    }
                    if(!isset($mine['statistics']['asmtp'])) {
                        $statistic[$mine['mine_id']]['statistics']['asmtp'] = (object) array();
                    }
                }
            }

        } catch (\Throwable $exception) {
            $errors[] = 'GetSourStatistic. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetSourStatistic. Конец метода';
        if (!isset($statistic)) {
            $statistic = (object)array();
        }
        $result_main = array('Items' => $statistic, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
