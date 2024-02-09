<template>
    <div class="situation-statistics-container max-content " @click.stop="closeListMines()" :class="maxWindowClass" :style="printMode ? {width: '28cm', height: 'unset'} : {}">
        <div class="base-header-main" ref="baseHeader">
            <router-link to="/order-system/methane-analysis" class="hidden-print" v-if="!printMode">
                <div class="btn-back"></div>
            </router-link>
            <div class="filter-block">
                <form class="period-part" v-if="printMode === false">
                    <label for="monthly" @click.stop="setPeriodForStatistics('monthly')">
                        <input type="radio" value="monthly" id="monthly" :checked="checkedPeriod === 'monthly'">
                        Помесячная
                    </label>
                    <label for="weekly" @click.stop="setPeriodForStatistics('weekly')">
                        <input type="radio" value="weekly" id="weekly" :checked="checkedPeriod === 'weekly'">
                        Понедельная
                    </label>
                </form>
                <span class="period-label" v-if="printMode && checkedPeriod === 'monthly'" :style="printMode ? {margin: 'auto 20px auto 0'} : {}">Помесячная</span>
                <span class="period-label" v-if="printMode && checkedPeriod === 'weekly'" :style="printMode ? {margin: 'auto 20px auto 0'} : {}">Понедельная</span>
                <div class="year-part hidden-print" v-if="printMode === false">
                    <span>Год</span>
                    <div class="display-year" @click.stop="activeModal = 'calendar'">{{chosenYear}}</div>
                    <!-- выпадающий календарь для выбора года -->
                    <div class="select-year-calendar" v-if="activeModal === 'calendar'">
                        <calendar
                                :modal-calendar-with-time="false"
                                :modal-calendar-with-month="false"
                                @closeModals="activeModal = ''"
                                @selectedDateTime="setChosenYear"
                        />
                    </div>
                </div>
                <div class="year-part" v-if="printMode">
                    <span>Год</span>
                    <div class="display-year">{{chosenYear}}</div>
                </div>

                <div class="mine-block hidden-print" ref="mineBlock"
                     @click.stop="showListMines()">
                    <span>Шахта</span>
                    <span class="display-year">{{chosenMine.title !== '' ? chosenMine.title : userMineTitle}}</span>
                </div>

            </div>
            <span class="page-title">Сводная статистика по ситуациям</span>
            <div class="caption-btn" v-if="printMode === false">
                <button @click.stop="setFullScreenMode()" class="caption-btn-maxWindow"/>
                <span class="vertical-line">|</span>
                <button class="caption-btn-close" @click.stop="returnToTheMainPage()"><span/></button>
            </div>
            <div class="btn-close-section hidden-print" v-if="printMode === true" title="Вернуться назад"
                 @click.stop="printMode = false">
            </div>
        </div>

        <div class="main-content">
            <div class="first-row">
                <div class="left-diagram">
                    <div class="block-description">
                        <span>Статистика ситуаций по количеству</span>
                    </div>
                    <div class="bar-chart-container monthly" v-if="checkedPeriod === 'monthly'">
                        <bar-chart id="monthlyChart" :chart-data="situationsMonthlyCountBarChartData"/>
                        <ul class="months" :style="printMode ? {marginLeft:  '8%', width: '91%'} : {}">
                            <li class="month-title" v-for="month in shortMonthTitle"><span>{{ month }}</span></li>
                        </ul>
                    </div>
                    <div class="bar-chart-container weekly" v-if="checkedPeriod === 'weekly'">
                        <bar-chart-weekly id="weeklyChart" :chart-data="situationsWeeklyCountBarChartData" :needXAxesLabels="true"/>
<!--                        <ul class="weeks" :style="printMode ? {marginLeft:  '34px', width: 'calc(100% - 33px)', fontSize: '8.5px'} : {}">-->
<!--                            <li class="week-title" v-for="week in countOfWeeks"><span>{{ week }}</span></li>-->
<!--                        </ul>-->
                    </div>
                </div>
                <div class="right-diagram">
                    <div class="block-description">
                        <span>Статистика причин ситуации</span>
                    </div>
                    <ul class="custom-legend">
                        <li v-for="(legendItem, idx) in situationKindReasonDonutChartData.labels">
                            <span class="legend-icon" :style="{backgroundColor: legendColors[idx]}"></span>
                            <span>{{ legendItem }}</span>
                        </li>
                    </ul>
                    <div class="donut-chart-container">
                        <doughnut-chart :chart-data="situationKindReasonDonutChartData" />
                    </div>
                </div>
            </div>
            <div class="second-row">
                <div class="left-diagram">
                    <div class="block-description">
                        <span>Статистика ситуаций по местам</span>
                    </div>
                    <div class="bar-chart-container chart-by-place">
                        <div class="statistics-table">
                            <div class="table-header">
                                <div class="table-column">Место</div>
                                <div class="table-column">Количество</div>
                            </div>
                            <div class="table-body">
                                <div class="table-row" v-for="situationItem in situationStatisticsByPlace" :key="situationItem.place_id">
                                    <div class="table-column">{{ situationItem.place_title }}</div>
                                    <div class="table-column">{{ situationItem.count_situation }}</div>
                                    <div class="table-column">
                                        <span :style="{width: calculateWidthOfRect(situationItem.count_situation, 'place')}"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="right-diagram">
                    <div class="block-description">
                        <span>Уровень риска</span>
                    </div>
                    <div class="bar-chart-container monthly" v-if="checkedPeriod === 'monthly'">
                        <bar-chart :chart-data="dangerCountMonthlyBarChartData"/>
                        <ul class="months" :style="printMode ? {marginLeft:  '8%', width: '91%'} : {}">
                            <li class="month-title" v-for="(month, idx) in shortMonthTitle"
                                title="Показать уровень риска"
                                :class="{'selected-month': dangerLevelVisibilityFlag && currentMonthIndex === idx}"
                                @click.stop="showDangerLevel(idx)">
                                <span>{{month}}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="bar-chart-container weekly" v-if="checkedPeriod === 'weekly'">
                        <bar-chart-weekly :chart-data="dangerCountWeeklyBarChartData"/>
                        <ul class="weeks" :style="printMode ? {marginLeft:  '34px', width: 'calc(100% - 33px)', fontSize: '8.5px'} : {}">
                            <li class="week-title" v-for="(week, idx) in countOfWeeks"
                                title="Показать уровень риска"
                                :class="{'selected-week': dangerLevelVisibilityFlag && currentMonthIndex === idx}"
                                @click.stop="showDangerLevel(idx)">
                                <span>{{ week }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bar-chart-container danger-level-chart" :class="{'hidden': !dangerLevelVisibilityFlag}" ref="dangerLevelDiv">
                        <svg viewBox="0 0 1000 500" height="90%" width="90%">
                            <!--        нижний ряд-->
                            <circle cx="200" cy="400" r="15" fill="white"/>                                             <!-- белые кружки между уровнями опасности -->
                            <circle cx="600" cy="400" r="15" fill="white"/>                                             <!-- белые кружки между уровнями опасности -->
                            <polygon points="100,400 700,400 700,500 50,500" style="fill:#c5c7c6;stroke:white;stroke-width:5" />    <!--горизонтальная полоса с обозначением рассчетного количества происшествий слева-->
                            <text x="90" y="460" font-size="25">{{ dangerLevelData.totalEventsCount }}</text>                  <!-- количество предполагаемых происшествий-->
                            <polygon points="400,0 650,500 150,500" ref="mainTriangle" style="fill:#b2b4b3;stroke:white;stroke-width:5" /> <!-- треугольник на каждом уровне опасности -->
                            <rect x="700" y="400" width="225" height="100" fill="#c5c7c6" stroke="white" stroke-width="5"/> <!-- прямоугольник - фон для подписи уровня опасности-->
                            <foreignObject width="225" height="60" x="710" y="440" font-size="22" style="line-height: 1; text-align: left">ситуации</foreignObject> <!-- текст уровня опасности-->

                            <!--        второй снизу ряд-->
                            <circle cx="250" cy="300" r="15" fill="white"/>                                             <!-- белые кружки между уровнями опасности -->
                            <circle cx="550" cy="300" r="15" fill="white"/>                                             <!-- белые кружки между уровнями опасности -->
                            <polygon points="150,300 700,300 700,400 100,400" style="fill:#c5c7c6;stroke:white;stroke-width:5" />   <!--горизонтальная полоса с обозначением рассчетного количества происшествий слева-->
                            <text x="150" y="360" font-size="25">{{ Math.round(dangerLevelData.totalEventsCount / 10)  }}</text>                    <!-- количество предполагаемых происшествий-->
                            <polygon points="400,0 600,400 200,400" style="fill:#9d9f9e;stroke:white;stroke-width:5" /> <!-- треугольник на каждом уровне опасности -->
                            <rect x="700" y="300" width="225" height="100" fill="#c5c7c6" stroke="white" stroke-width="5"/> <!-- прямоугольник - фон для подписи уровня опасности-->
                            <foreignObject width="225" height="70" x="710" y="330" font-size="22" style="line-height: 1; text-align: left">легкие <br/>происшествия</foreignObject> <!-- текст уровня опасности-->

                            <!--        средний ряд-->
                            <circle cx="300" cy="200" r="15" fill="white"/>                                             <!-- белые кружки между уровнями опасности -->
                            <circle cx="500" cy="200" r="15" fill="white"/>                                             <!-- белые кружки между уровнями опасности -->
                            <polygon points="200,200 700,200 700,300 150,300" style="fill:#c5c7c6;stroke:white;stroke-width:5" />   <!--горизонтальная полоса с обозначением рассчетного количества происшествий слева-->
                            <text x="210" y="260" font-size="25">{{ Math.round(dangerLevelData.totalEventsCount / 100)  }}</text>                   <!-- количество предполагаемых происшествий-->
                            <polygon points="400,0 550,300 250,300" style="fill:#898989;stroke:white;stroke-width:5" /> <!-- треугольник на каждом уровне опасности -->
                            <rect x="700" y="200" width="225" height="100" fill="#c5c7c6" stroke="white" stroke-width="5"/> <!-- прямоугольник - фон для подписи уровня опасности-->
                            <foreignObject width="225" height="90" x="710" y="210" font-size="22" style="line-height: 1.2; text-align: left">происшествия <br/>средней <br/>степени тяжести</foreignObject> <!-- текст уровня опасности-->

                            <!--        второй сверху ряд-->
                            <circle cx="350" cy="100" r="15" fill="white"/>                                             <!-- белые кружки между уровнями опасности -->
                            <circle cx="450" cy="100" r="15" fill="white"/>                                             <!-- белые кружки между уровнями опасности -->
                            <polygon points="250,100 700,100 700,200 200,200" style="fill:#c5c7c6;stroke:white;stroke-width:5" />   <!--горизонтальная полоса с обозначением рассчетного количества происшествий слева-->
                            <text x="260" y="160" font-size="25">{{ Math.round(dangerLevelData.totalEventsCount / 1000) < 1 ? 1 : Math.round(dangerLevelData.totalEventsCount / 1000)  }}</text>                    <!-- количество предполагаемых происшествий-->
                            <polygon points="400,0 500,200 300,200" style="fill:#727270;stroke:white;stroke-width:5" /> <!-- треугольник на каждом уровне опасности -->
                            <rect x="700" y="100" width="225" height="100" fill="#c5c7c6" stroke="white" stroke-width="5"/> <!-- прямоугольник - фон для подписи уровня опасности-->
                            <foreignObject width="225" height="75" x="710" y="125" font-size="22" style="line-height: 1.2; text-align: left">тяжелые <br/>происшествия</foreignObject> <!-- текст уровня опасности-->

                            <!--самый верхний ряд-->
                            <polygon points="300,0 700,0 700,100 250,100" style="fill:#c5c7c6;stroke:white;stroke-width:5" />   <!--горизонтальная полоса с обозначением рассчетного количества происшествий слева-->
                            <text x="310" y="60" font-size="25">{{ Math.round(dangerLevelData.totalEventsCount / 10000) < 1 ? 1 : Math.round(dangerLevelData.totalEventsCount / 10000)  }}</text>                   <!-- количество предполагаемых происшествий-->
                            <polygon points="400,0 450,100 350,100" style="fill:#5b5b5b;stroke:white;stroke-width:5" /> <!-- треугольник на каждом уровне опасности -->
                            <rect x="700" y="0" width="225" height="100" fill="#c5c7c6" stroke="white" stroke-width="5"/> <!-- прямоугольник - фон для подписи уровня опасности-->
                            <foreignObject width="225" height="75" x="710" y="50" font-size="22" style="line-height: 1; text-align: left">авария</foreignObject> <!-- текст уровня опасности-->

<!--                            <rect x="700" y="0" width="75" height="500" fill="#c5c7c6" stroke="white" stroke-width="5"/>-->
                            <!-- цветной полигон -->
                            <polygon style="stroke:white;stroke-width:5" :points="currentDangerLevelPolygonPoints" :fill="currentDangerLevelPolygonColor"/> <!-- цветной треугольник с текущим уровнем опасности-->

                        </svg>
                    </div>
                </div>
            </div>
        </div>


        <!-- выпадающий список шахт -->
        <mines-dropdown class="mines-dropdown"
                        v-show="activeDropdown === 'mineList'"
                        :mine-list="mineList"
                        @setSelectedMineId="selectMine"
                        @closeMineList="closeListMines"
        />

<!--        <div class="footer" v-if="printMode === false">-->
<!--            <div class="print-button" @click.stop="printMode = true">-->
<!--                <span>Предпросмотр</span>-->
<!--            </div>-->
<!--        </div>-->
<!--        <div class="footer" v-else>-->
<!--            <div class="print-button" @click.stop="printStatistics()">-->
<!--                <span>Отправить на печать</span>-->
<!--            </div>-->
<!--        </div>-->
    </div>
</template>

<script>

    import situationStatisticsStore from "../components/situation-statistics/SituationStatisticsStore.js";
    import MinesDropdown from "../components/agk-operator-journal/modals/MinesDropdown";
    import statisticsStore from "../components/sensors-statistics/SensorsStatisticsStore";
    const Calendar = () => import('@/components/bookDirectiveModule/Calendar.vue'),
          BarChart = () => import("../components/situation-statistics/charts/BarChart.vue"),
          BarChartWeekly = () => import("../components/situation-statistics/charts/BarChartWeekly.vue"),
          DoughnutChart = () => import("../components/situation-statistics/charts/DoughnutChart.vue");

    export default {
        name: "SituationStatistics",
        components: {
            MinesDropdown,
            DoughnutChart,
            Calendar,
            BarChartWeekly,
            BarChart
        },
        data() {
            return {
                maxWindowClass: '',                                                                                     // переменная для хранения css класса для установки полноэкранного режима
                activeModal: '',                                                                                        // активное модальное окно
                checkedPeriod: 'monthly',
                shortMonthTitle: ['янв', 'фев', 'март', 'апр', 'май', 'июнь', 'июль', 'авг', 'сен', 'окт', 'ноя', 'дек'],
                longMonthTitle: ["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],
                legendColors: ['#598d9b',
                    '#56698f',
                    '#b2b4b3',
                    '#666666',
                    '#4d897c',
                    '#b55a6e',
                    '#b2d63c',
                    '#ef7f1a',
                    '#d0cab5',
                    '#99c0ca',
                    '#7B91BA',
                    '#384A6B',
                    '#6DB5A7',
                    '#2C5950',
                    '#67A4B5',
                    '#46717D',
                    '#987B9E',
                    '#56405C',
                    '#DB6D87',
                    '#91374C'],
                countOfWeeks: getWeekOfYear(new Date().getFullYear(), 12, 31),
                printMode: false,
                dangerLevelVisibilityFlag: false,                                                                       // флаг видимости блока с уровнем риска
                currentDangerLevel: 0,                                                                                  // текущий уровень риска (нужен для отображения уровня риска за конкретный месяц)
                currentMonthIndex: -1,

                activeDropdown: '',
                chosenMine: {
                    mineId: null,
                    title: ''
                },
                userMineTitle: JSON.parse(localStorage.getItem('serialWorkerData')).userMineTitle,
                userMineId: JSON.parse(localStorage.getItem('serialWorkerData')).userMineId,

            }
        },
        computed: {

            /**
             * возвращает список всех шахт из хранилища модуля администрирования
             */
            mineList: {
                get() {
                    return statisticsStore.getters.MINELIST;
                },
            },

            /**
             * возвращает выбранный год
             **/
            chosenYear: {
                get() {
                    return situationStatisticsStore.getters.CHOSENYEAR;
                },
                set(newYear) {
                    situationStatisticsStore.dispatch('changeChosenYear', newYear);
                }
            },
            /**
             * возвращает статистические данные с сервера
             **/
            situationStatisticsData() {
                return JSON.parse(JSON.stringify(situationStatisticsStore.getters.SITUATIONSTATISTICSDATA));
            },
            /**
             * возвращает объект для столбчатой диаграммы за год по месяцам
             **/
            situationsMonthlyCountBarChartData() {
                let statisticsFromServer = this.situationStatisticsData.monthly.total_situations_count,
                    datasetObject = {};
                if (statisticsFromServer && statisticsFromServer.length) {
                    datasetObject = {
                        labels: this.longMonthTitle,
                            datasets: [{
                            label: '',
                            minBarLength: 5,
                            barPercentage: 0.5,
                            backgroundColor: '#7c6580',
                            data: statisticsFromServer
                        }]
                    };
                }
                return this.removeObservance(datasetObject);
            },
            /**
             * возвращает объект  для столбчатой диаграммы за год по неделям
             **/
            situationsWeeklyCountBarChartData() {
                let statisticsFromServer = this.situationStatisticsData.weekly.total_situations_count,
                    datasetObject = {}, labels = [];
                if (statisticsFromServer && statisticsFromServer.length) {
                    statisticsFromServer.forEach((week, idx) => {
                        labels.push((idx + 1));
                    });
                    datasetObject = {
                        labels: labels,
                        datasets: [{
                            label: '',
                            minBarLength: 5,
                            barPercentage: 0.8,
                            backgroundColor: '#7c6580',
                            data: statisticsFromServer
                        }]
                    };
                }
                return this.removeObservance(datasetObject);
            },
            dangerCountMonthlyBarChartData() {
                let statisticsFromServer = this.situationStatisticsData.monthly.danger_level_current,
                    datasetObject = {};
                if (statisticsFromServer && statisticsFromServer.length) {
                    datasetObject = {
                        labels: this.longMonthTitle,
                        datasets: [{
                            label: '',
                            minBarLength: 5,
                            barPercentage: 0.5,
                            backgroundColor: '#56698f',
                            data: statisticsFromServer
                        }]
                    };

                }
                return this.removeObservance(datasetObject);
            },
            dangerCountWeeklyBarChartData() {
                let statisticsFromServer = this.situationStatisticsData.weekly.danger_level_current,
                    datasetObject = {}, labels = [];
                if (statisticsFromServer && statisticsFromServer.length) {
                    statisticsFromServer.forEach((week, idx) => {
                        labels.push('Неделя ' + (idx + 1));
                    });
                    datasetObject = {
                        labels: labels,
                        datasets: [{
                            label: '',
                            minBarLength: 5,
                            barPercentage: 0.5,
                            backgroundColor: '#56698f',
                            data: statisticsFromServer
                        }]
                    };
                }
                return this.removeObservance(datasetObject);
            },

            situationKindReasonDonutChartData() {
                let statisticsFromServer = this.situationStatisticsData.kind_reason_situations_count;
                let datasetObject = {
                  labels: [],
                  datasets: [{
                      backgroundColor: this.legendColors,
                      data: []
                  }]
                };
                if (statisticsFromServer && statisticsFromServer.length) {
                    let labels = [], datasets = [];
                    for (let i = 0; i < statisticsFromServer.length; i++) {
                        labels.push(statisticsFromServer[i].kind_reason_title);
                        datasets.push(Number(statisticsFromServer[i].count_situation));
                    }
                    datasetObject.labels = labels;
                    datasetObject.datasets[0].data = datasets;
                }
                return this.removeObservance(datasetObject);
            },
            situationStatisticsByPlace() {
                if (this.situationStatisticsData && this.situationStatisticsData.place_situations_count && this.situationStatisticsData.place_situations_count.length) {
                    return  this.situationStatisticsData.place_situations_count.sort((place1, place2) => {
                        if (Number(place1.count_situation) > Number(place2.count_situation)) {
                            return -1;
                        } else if (Number(place1.count_situation) < Number(place2.count_situation)) {
                            return 1;
                        }
                        if (place1.place_title > place2.place_title) {
                            return 1;
                        } else if (place1.place_title < place2.place_title) {
                            return -1;
                        }
                        return 0;
                    });
                }

            },
            dangerLevelData() {
                // console.count('dangerLevelData');
                let dangerLevelFull = 0;
                if (this.situationStatisticsData.danger_level_full) {
                    if (this.checkedPeriod === 'monthly') {
                        dangerLevelFull = Number(this.situationStatisticsData.danger_level_full);
                    } else {
                        dangerLevelFull = Math.round(Number(this.situationStatisticsData.danger_level_full) / 4);
                    }
                }
                return {
                    totalEventsCount: dangerLevelFull,
                    countOfHappenedSituations: this.currentDangerLevel
                }
            },
            currentDangerLevelPolygonColor() {
                let dangerLevelData = this.dangerLevelData,
                    color = '';
                // console.log('SituationStatistics.vue, currentDangerLevelPolygonColor dangerLevelData', dangerLevelData);
                let percent = Number(dangerLevelData.countOfHappenedSituations / dangerLevelData.totalEventsCount * 100).toFixed(2);
                if (percent <= 40) {
                    color = '#b2d63c';
                } else if (percent > 40 && percent <= 80 ) {
                    color = '#ef7f1a';
                } else {
                   color = '#b55a6e';
                }
                return color;
            },
            currentDangerLevelPolygonPoints() {
                let dangerLevelData = this.dangerLevelData;
                console.log('SituationStatistics.vue, currentDangerLevelPolygonPoints. dangerLevelData ', dangerLevelData);
                try {
                    let x1 = 150,//берем первую точку большого треугольника
                        y1 = 500,//берем первую точку большого треугольника
                        mainTriangleWidth = 500, // ширина большого трегольника,
                        tempTriangleWidth = Math.round( dangerLevelData.countOfHappenedSituations * mainTriangleWidth / dangerLevelData.totalEventsCount), // рассчитываем ширину треугольника с риском
                        assumedTriangleWidth = 0;// предполагаемая ширина треугольника
                    /**
                     * если ширина получившегося треугольника больше 0,
                     *   то проверяем дальше, если ширина меньше 30,
                     *     то в результирующую переменную кладем минимальное значение ширины 30
                     *   иначе если ширина получившегося треугольника больше ширины основного серого треугольника
                     *     то в результирующую переменную кладем максимальное значение ширины, равное ширине основного
                     *   иначе для остальных случаев
                     *     в результирующую переменную кладем рассчетное значение ширины
                     *  иначе
                     *    в результирующую переменную записываем 0
                     */
                    if (tempTriangleWidth > 0) {
                        if (tempTriangleWidth < 30) {
                            assumedTriangleWidth = 30;
                        } else if (tempTriangleWidth > mainTriangleWidth) {
                            assumedTriangleWidth = mainTriangleWidth;
                        }  else {
                            assumedTriangleWidth = tempTriangleWidth;
                        }
                    } else {
                        assumedTriangleWidth = 0;
                    }

                   let x3 = assumedTriangleWidth + x1, // здесь составил пропорцию и высчитываю координату точи справа x3 = сколько произошло ситуаций * (ширину большого треугольника) / общее число ситуаций
                       y3 = y1,//высота полотна, так как отсчет координат начинается слева сверху
                       x2 = Math.round((x3 - x1) / 2) + x1,//середина нового треугольника равна длине основания треугольника пополам
                       y2 = y1 - (x3 - x1);

                    // console.log('x1', x1);
                    // console.log('y1', y1);
                    // console.log('x3', x3);
                    // console.log('y3', y3);
                    // console.log('x2', x2);
                    // console.log('y2', y2);
                    return x2 + ',' + y2 + ' ' + x3 + ',' + y3 + ' ' + x1 + ',' + y1;
                } catch(err) {
                    console.log('SituationStatistics.vue, currentDangerLevelPolygonPoints Исключение: ', err);
                }
                return '';
            }
        },
        methods: {
            returnToTheMainPage() {
                this.$router.push('/order-system/methane-analysis');
            },
            setFullScreenMode() {
                this.maxWindowClass === 'fullscreen-mode' ? this.maxWindowClass = '' : this.maxWindowClass = 'fullscreen-mode';
            },
            /**
             * закрывает модальные окна всех уровней
             **/
            closeAllModalAndDropdownWindows() {
                if (this.activeModal.length) {                                                                          // если есть какое-то открытое модальное окно
                    this.activeModal = '';                                                                        // вызываем функцию переключения активного модального окна, в котором при отсутствии переданного аргумента, устанавливается пустая строка, а значит ни одно условие v-if не сработает, и все модалки скроятся
                }
            },
            setPeriodForStatistics(checkedPeriod) {
                if (this.checkedPeriod !== checkedPeriod) {
                    this.checkedPeriod = checkedPeriod;
                    this.dangerLevelVisibilityFlag = false;
                    this.currentMonthIndex = -1;
                    this.currentDangerLevel = 0;
                    // this.getStatisticsData();
                }
            },

            setChosenYear(dateObject) {
                this.chosenYear = dateObject.date.split('.')[2];
                this.activeModal = '';
                this.getStatisticsData();
            },
            getStatisticsData() {
                if (!Number.isNaN(this.chosenYear)) {
                    situationStatisticsStore.dispatch('ajaxGetStatisticsData', {
                        year: this.chosenYear,
                        mine_id: this.chosenMine.mineId !== null ? this.chosenMine.mineId : this.userMineId,
                    });
                }
            },
            printStatistics() {
                this.$nextTick(() => {
                    window.print();
                });

            },
            calculateWidthOfRect(situationsCCount, type) {
                // console.log('SituationStatistics, calculateWidthOfRect situationsCCount',situationsCCount);
                let srcArray = this.situationStatisticsByPlace;
                let maxValue = 0;
                if (Array.isArray(srcArray)) {
                    srcArray.forEach(elem => {
                        if (Number(elem.count_situation) > maxValue) {
                            maxValue = Number(elem.count_situation);
                        }
                    });
                } else {
                    Object.keys(srcArray).forEach(key => {
                        if (Number(srcArray[key].count_situation) > maxValue) {
                            maxValue = Number(srcArray[key].count_situation);
                        }
                    });
                }
                // console.log('SituationStatistics, calculateWidthOfRect maxValue',maxValue);
                return Math.round(Number(situationsCCount) / maxValue * 100) + '%';
            },
            showDangerLevel(monthIdx) {
                console.log('SituationStatistics.vue, showDangerLevel. month idx', monthIdx);
                let statisticsFromServer = this.checkedPeriod === 'monthly' ? this.situationStatisticsData.monthly.total_situations_count : this.situationStatisticsData.weekly.total_situations_count;
                if (monthIdx === this.currentMonthIndex) {
                    this.dangerLevelVisibilityFlag = !this.dangerLevelVisibilityFlag;
                } else {
                    this.currentMonthIndex = monthIdx;
                    this.currentDangerLevel = Number(statisticsFromServer[monthIdx]);
                    this.dangerLevelVisibilityFlag = true;
                }
                // this.$nextTick(() => {
                //     if (!this.$refs.dangerLevelDiv.classList.contains('hidden')) {
                //         this.$refs.dangerLevelDiv.scrollIntoView({behavior: 'smooth', block: 'end'});
                //     }
                // });

            },
            removeObservance(value) {
                const Observer = this.$data.__ob__.constructor;
                value.__ob__ = new Observer({});
                return value;
            },

            /**
             * Метод отображения списка шахт
             **/
            showListMines() {
                let warnings = [];
                try {
                    warnings.push("showListMines. Начал выполнять метод");

                    console.log('showListMines this.mineList', this.mineList);

                    this.activeDropdown = 'mineList';

                    let dropdown = document.querySelector('.mines-dropdown');
                    if (!dropdown) {
                        throw new Error("showCalendar. элемент mineBlock выпадающий список не найден");
                    }
                    let mineBlock = this.$refs.mineBlock;

                    let positionModal = calculateModalPosition(Math.round(mineBlock.getBoundingClientRect().left)
                        + Math.round(mineBlock.getBoundingClientRect().width / 2) - 125,
                        Math.round(mineBlock.getBoundingClientRect().top) +
                        mineBlock.getBoundingClientRect().height + 1, 250, 199, ".max-content");

                    dropdown.style.position = 'absolute'; // Задаём абсолютное позиционирование
                    dropdown.style.left = positionModal.left;
                    dropdown.style.top = positionModal.top;


                    warnings.push("showListMines. Закончил выполнять метод");
                } catch (err) {
                    console.log("showListMines. Исключение");
                    console.log(err);
                }
                console.log(warnings);
            },

            closeListMines() {
                this.activeDropdown = ''
            },

            /**
             * устанавливает выбранную шахту в переменную chosenMineId, чтобы потом составить url для просмотра
             * ответа от сервера
             * Входные данные:
             *   @param mineObject (object) - объект шахты
             * Выходные данные:
             *   отсутствуют
             **/
            selectMine(mineObject) {
                this.chosenMine.mineId = mineObject.id;
                this.chosenMine.title = mineObject.title;

                this.getStatisticsData();

            },
        },
        created() {
            this.getStatisticsData();
            if (this.mineList && Object.keys(this.mineList).length === 0) {
                statisticsStore.dispatch('ajaxGetMineList');                                                       // вызов экшена из хранилища блока администрирования для отправки запроса на получение списка шахт
            }
        },
        mounted() {
            document.addEventListener('click', this.closeAllModalAndDropdownWindows);

        },
        beforeDestroy() {
            document.removeEventListener('click', this.closeAllModalAndDropdownWindows);
            this.currentMonthIndex = -1;
            this.currentDangerLevel = 0;
        }
    }
</script>

<style lang="less" scoped>

    .base-header-main {
        position: relative;
        width: 100%;
        height: 35px;
        color: #fff;
        display: flex;
        background: #598d9b;
        @media print {
            background: #598d9b !important;
        }

        /* Кнопка назад в блоке с названием */

        .btn-back {
            position: absolute;
            top: 50%;
            transform: translate(0, -50%);
            left: 5px;
            height: 100%;
            width: 40px;
            background: url('../../prof-security/assets/back.png') center no-repeat;
            background-size: 100% 100%;
            cursor: pointer;
        }

        .filter-block {
            width: 550px;
            display: flex;
            position: absolute;
            top: 5px;
            left: 60px;
            flex-flow: row nowrap;
            height: 25px;

            @media all and (max-width:1523px) {
                position: relative;
            }

            .period-part {
                width: 41%;
                height: 100%;
                display: flex;
                flex-flow: row nowrap;
                font-size: 12px;

                label {
                    margin: 0;
                    display: flex;
                    flex-flow: row nowrap;
                    justify-content: center;
                    align-items: center;
                    @media print {
                        color: #fff!important;
                    }
                    input {
                        margin: 0 5px 0 0;
                        @media print {
                            color: #fff!important;
                        }
                    }
                }

                label:last-of-type {
                    margin-left: 20px;
                }
            }

            .year-part {
                width: 18%;
                height: 100%;
                display: flex;
                flex-flow: row nowrap;
                justify-content: space-between;
                align-items: center;

                &.for-print {
                    display: none !important;
                    @media print {
                        color: #fff!important;
                    }
                }

                .display-year {
                    width: 60px;
                    height: 100%;
                    background: #fff;
                    border: 1px solid #ccc;
                    color: #000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    -webkit-user-select: none;
                    -moz-user-select: none;
                    -ms-user-select: none;
                    user-select: none;
                }

                .select-year-calendar {
                    top: 30px;
                    left: 250px;
                    position: absolute;
                    z-index: 100;
                }
            }

            .mine-block {
                width: 41%;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;

                &:hover {
                    cursor: pointer;
                }

                span:last-child {
                    padding: 1px 5px;
                    background: #fff;
                    border: 1px solid #ccc;
                    color: #000;
                    margin-right: 0;
                }

                span {
                    margin-right: 10px;
                    font-size: 12px;
                }

                .display-year {
                    width: 145px;
                    height: 100%;
                    background: #fff;
                    border: 1px solid #ccc;
                    color: #000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    -webkit-user-select: none;
                    -moz-user-select: none;
                    -ms-user-select: none;
                    user-select: none;
                }
            }
        }


        span.page-title {
            margin: auto;
            font-size: 16px;
        }
        .btn-close-section {
            position: absolute;
            top: 50%;
            transform: translate(0, -50%);
            right: 10px;
            height: 30px;
            width: 30px;
            background: url('../assets/CloseSensorModal.png') center no-repeat;
            background-size: 100% 100%;
            cursor: pointer;
        }

        .caption-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 8px;
            right: 10px;


            &-maxWindow {
                width: 12px;
                height: 12px;
                border: 2px solid #fff;
                border-radius: 1px;
                position: relative;
                top: 2px;
                z-index: 140;
            }

            button {
                outline: none;
                background: none;
            }

            .vertical-line {
                margin: 0 5px;
                color: #ffffff;
            }

            &-close {
                height: 12px;
                width: 14px;
                border: none;
                z-index: 140;
                cursor: pointer;

                span {
                    display: inline-block;
                    position: relative;
                    width: 100%;
                    height: 100%;
                    /*@media print {*/
                    /*    display: none;*/
                    /*}*/

                    &::before, &::after {
                        content: "";
                        display: block;
                        height: 2px;
                        width: 100%;
                        position: absolute;
                        top: 4px;
                        left: 0;
                        background-color: #fff;
                        /*@media print {*/
                        /*    display: none;*/
                        /*}*/
                    }

                    &:before {
                        -webkit-transform: rotate(45deg);
                        -moz-transform: rotate(45deg);
                        -ms-transform: rotate(45deg);
                        -o-transform: rotate(45deg);
                        transform: rotate(45deg);
                        /*@media print {*/
                        /*    display: none;*/
                        /*}*/
                    }

                    &:after {
                        -webkit-transform: rotate(-45deg);
                        -moz-transform: rotate(-45deg);
                        -ms-transform: rotate(-45deg);
                        -o-transform: rotate(-45deg);
                        transform: rotate(-45deg);
                        /*@media print {*/
                        /*    display: none;*/
                        /*}*/
                    }
                }
            }
        }
    }

    .situation-statistics-container {
        position: relative;
        min-height: 450px;
        height: calc(100vh - 120px);

        @media print {
            height: unset!important;
        }
        &.fullscreen-mode {
            height: 99vh;
            width: calc(100vw - 10px);
            position: absolute;
            top: 0;
            left: 5px;
            z-index: 1061;
            background: #fff;
            min-width: 1200px;
        }

        .main-content {
            width: 100%;
            height: calc(100% - 65px);
            display: flex;
            flex-direction: column;
            border: 2px solid #598d9b;
            border-top: none;
            overflow-y: auto;
            overflow-x: hidden;
            background: #fff;

            & > div {
                width: 100%;
                height: 50%;
                display: flex;
                flex-flow: row nowrap;

                &.first-row {
                    .left-diagram {
                        .weeks {
                            margin-left: 48px;
                            width: calc(100% - 48px);
                        }
                    }

                    .right-diagram {
                        .donut-chart-container {
                            margin-left: 200px;
                            width: auto;
                        }
                    }
                }

                &.second-row {
                    border-top: 1px solid #ccc;

                    @media print {
                        border-top: 1px solid #ccc !important;
                    }

                    .statistics-table {
                        width: 100%;
                        height: calc(100% - 10px) !important;
                        margin-top: 10px;
                        overflow-x: hidden;
                        overflow-y: auto;


                        .table-header {
                            display: flex;
                            height: 25px;
                            font-size: 12px;
                            color: #999;

                            & > div:first-of-type {
                                width: 45%;
                                text-align: right;
                                padding-right: 30px;
                                border-right: 1px solid #ccc;
                                display: flex;
                                align-items: center;
                                flex-flow: row nowrap;
                                justify-content: flex-end;

                                @media print {
                                    border-right: 1px solid #ccc !important;
                                }
                            }
                            & > div:last-of-type {
                                width: calc(50% - 40px);
                                text-align: left;
                                margin-left: 30px;
                            }
                        }
                        .table-body {
                            height: calc(100% - 35px);
                            width: 100%;
                            display: flex;
                            flex-direction: column;
                            margin-top: 10px;

                            .table-row {
                                display: flex;
                                flex-flow: row nowrap;
                                /*min-height: 20px;*/
                                margin-bottom: 5px;
                                line-height: 1.2;

                                .table-column:nth-of-type(2) {
                                    width: 40px;
                                    text-align: center;
                                    padding-left: 10px;
                                    margin-left: -40px;
                                }
                            }
                        }

                        .table-column:first-of-type {
                            width: 50%;
                            text-align: right;
                            padding-right: 50px;
                        }

                        .table-column:last-of-type {
                            width: calc(50% - 50px);
                            margin-left: 10px;
                            display: flex;
                            justify-content: flex-start;
                            align-items: center;

                            span {
                                display: inline-block;
                                background: #598d9b;
                                height: 15px;
                                min-width: 5px;

                                @media print {
                                    background: #598d9b !important;
                                }
                            }
                        }
                    }
                }


                & > div {
                    width: 50%;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    padding: 10px 15px;
                    position: relative;

                    &:first-of-type {
                        border-right: 1px solid #598d9b;

                        @media print {
                            border-right: 1px solid #598d9b !important;
                        }
                    }

                    @media print {
                        padding: 10px 5px;
                    }

                    .bar-chart-container, .donut-chart-container {
                        width: 100%;
                        height: calc(100% - 30px);
                        max-height: 343px;
                        position: relative;
                        /*z-index: 10;*/

                        @media print {
                            page-break-before: auto;
                            page-break-inside: avoid;
                        }

                        &.chart-by-place {
                            max-height: unset;

                            .table-row {
                                @media print {
                                    page-break-before: auto;
                                    page-break-inside: avoid;
                                }
                            }
                        }

                        &.monthly {
                            & > div {
                                height: calc(100% - 30px) !important;
                            }

                        }

                        &.weekly {
                            & > div {
                                height: calc(100% - 15px) !important;
                            }

                        }

                        & > div {
                            width: 100% !important;
                            height: calc(100% - 30px) !important;

                            @media all and (min-width: 2500px) {
                                height: calc(100% - 35px) !important;
                            }
                        }

                        .months {
                            display: flex;
                            flex-flow: row nowrap;
                            height: 25px;
                            width: 93.5%;
                            margin-left: 6.5%;
                            margin-top: 5px;
                            justify-content: space-between;
                            margin-bottom: 0;

                            @media all and (max-width: 1377px) {
                                width: 90.5%;
                                margin-left: 8%;
                            }
                            @media all and (min-width: 1378px) and (max-width: 1590px) {
                                width: 91%;
                                margin-left: 7.5%;
                            }
                            @media all and (min-width: 1591px) and (max-width: 1919px) {
                                width: 91%;
                                margin-left: 7%;
                            }
                            @media all and (min-width: 1920px) and (max-width: 2050px) {
                                width: 91%;
                                margin-left: 6.5%;
                            }
                            @media all and (min-width: 2051px) and (max-width: 2560px) {
                                width: 91.5%;
                                margin-left: 5.5%;
                            }
                            @media all and (min-width: 2500px) {
                                height: 30px;
                            }
                            .month-title {
                                width: 25px;
                                height: 25px;
                                border-radius: 50%;
                                background: #e6e6e6;
                                display: flex;
                                font-size: 10px;
                                line-height: 1;
                                -webkit-user-select: none;
                                -moz-user-select: none;
                                -ms-user-select: none;
                                user-select: none;

                                @media all and (min-width: 2500px) {
                                    width: 30px;
                                    height: 30px;
                                    font-size: 12px;
                                }

                                @media print {
                                    background: #e6e6e6 !important;
                                }

                                span {
                                    margin: auto;
                                }
                            }
                        }

                        .weeks {
                            display: flex;
                            flex-flow: row nowrap;
                            margin: 0 0 0 32px;
                            padding: 0;
                            width: calc(100% - 32px);
                            justify-content: space-between;

                            .week-title {
                                display: flex;
                                margin: 0;
                                writing-mode: vertical-rl;
                                transform: rotate(180deg);
                                font-size: 10px;
                                line-height: 0.9;
                                -webkit-user-select: none;
                                -moz-user-select: none;
                                -ms-user-select: none;
                                user-select: none;
                            }
                        }
                    }
                    &.right-diagram {
                        overflow-y: auto;
                        height: unset;

                        .custom-legend {
                            position: absolute;
                            left: 30px;
                            top: 20px;
                        }

                        .bar-chart-container:nth-of-type(2) {
                            min-height: 200px;
                            .months {
                                li:hover {
                                    cursor: pointer;
                                }

                                li.selected-month {
                                    background: #598d9b !important;
                                    color: #fff;

                                    @media print {
                                        background: #598d9b !important;
                                        color: #fff !important;
                                    }
                                }
                            }
                            .weeks {
                                .week-title {
                                    &:hover {
                                        cursor: pointer;
                                    }
                                    &.selected-week {
                                        color: #598d9b !important;

                                        @media print {
                                            color: #598d9b !important;
                                        }
                                    }
                                }
                            }
                        }
                        .danger-level-chart {
                            margin-top: 15px;
                            height: 300px;
                        }
                    }

                    .custom-legend {
                        width: 170px;
                        height: 70%;
                        overflow: auto;
                        position: relative;
                        left: 0;
                        bottom: 0;
                        list-style: none;
                        font-size: 12px;
                        margin: 20px auto 0 auto;
                        order: 1;
                        text-align: left;

                        li {
                            display: flex;
                            flex-flow: row nowrap;
                            width: 100%;
                            align-items: center;

                            &:not(:last-of-type) {
                                margin-bottom: 10px;
                            }

                            .legend-icon {
                                min-width: 20px;
                                width: 20px;
                                min-height: 20px;
                                height: 20px;
                                position: relative;
                                margin-right: 10px;
                            }
                        }

                    }


                    .bar-weekly-chart {
                        width: 75%;
                        margin: 0;
                        max-height: 230px;
                        @media print {
                            width: 70% !important;
                        }
                    }
                }




                .column-title {
                    display: flex;
                    height: 30px;
                    background: #ddd;
                    margin: 2px;
                    font-size: 14px;
                    @media print {
                        background: #ddd !important;
                    }
                    span {
                        margin: auto;
                    }
                }

                .block-description {
                    display: flex;
                    padding: 5px 0;
                    height: 30px;

                    span {
                        margin: auto;
                    }
                }
            }


        }

        .footer {
            width: 100%;
            height: 30px;
            display: flex;
            overflow: hidden;

            .print-button {
                width: 250px;
                display: flex;
                transform: skew(30deg);
                position: relative;
                right: -10px;
                background: #808080;
                margin-left: auto;

                &:hover {
                    cursor: pointer;
                }

                &::before {
                    content: '';
                    display: block;
                    transform: skew(-30deg);
                    position: absolute;
                    top: 2.5px;
                    left: 15px;
                    background: url('../assets/print.png') no-repeat;
                    background-size: contain;
                    width: 25px;
                    height: 25px;
                }

                span {
                    margin: auto;
                    transform: skew(-30deg);
                    color: #fff;
                }
            }
        }
    }


</style>
<style lang="less" scoped>

    @media print {
        /*@page {*/
        /*    margin: 0.5cm !important;*/
        /*    padding: 0 !important;*/
        /*}*/

        .situation-statistics-container {
            width: 28cm !important;
            height: 19cm !important;
        }

        .main-content {
            border: none !important;
            height: calc(100% - 35px) !important;
        }

        .caption-btn {
            display: none !important;
        }

        .container {
            margin: 0 !important;
        }

        .container-fluid {
            margin: 0 !important;
            padding: 0 !important;
        }

        #app {
            margin: 0 !important;
            padding: 0 !important;
        }

        .footer {
            display: none !important;
        }

        .period-label {
            display: block !important;
            margin: auto 20px auto 0;
            color: #fff!important;
        }

        .base-header-main .filter-block .year-part .display-year {
            border: none !important;
        }
        .page-title {
            color: #fff!important;
            sub {
                color: #fff!important;
            }
        }
        .period-part {
            display: none !important;
            color: #fff!important;
        }

        .year-part {
            justify-content: flex-start !important;

            span, .display-year {
                color: #fff!important;
            }
            &.for-print {
                display: flex !important;
                color: #fff!important;
            }
        }

        .line-chart {
            width: 311px !important;
            height: 200px !important;
        }
    }
</style>
