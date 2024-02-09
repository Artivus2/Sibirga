<!--
Блок Статистическая отчетность
-->
<template>
  <div :class="maxWindowClass" class="statistic-reports">
    <div class="sidebar">
      <div v-for="reportObject in reportsList" :key="reportObject.id"
           class="report-block"
           @click.stop="setActiveReport(reportObject.id)">
        <img :src="selectedReportId === reportObject.id ? reportObject.iconActive : reportObject.iconInactive" alt="">
        <div :style="selectedReportId === reportObject.id ? {outlineColor: reportObject.color} : {outlineColor: '#b2b3b3'}"
             v-html="reportObject.title"></div>
      </div>
    </div>
    <div :style="setBorderColorWrapper()" class="main-content">
      <base-header
          :headerColor="selectedReportObject.color"
          :headerText="selectedReportObject.fullTitle ? selectedReportObject.fullTitle : selectedReportObject.title.replace(/['<br/>']/g, '')"
          @closeHeaderEvent="returnToTheMainPage"
          @setFullScreenMode="setFullScreenMode"
      />
      <!-- Блок фильтра по дате и участку-->
      <div v-if="(selectedReportId > 2 && selectedReportId < 11) || selectedReportId > 11"
           class="calendar-period-container">
        <div class="period-block"
             @click.stop="showPeriodContainer()">
          <span>{{ chosenPeriod === 'month' ? 'месяц' : 'год' }}</span>
          <span
              :class="['glyphicon', {'glyphicon-triangle-top': activeModal === 'periodDropdown', 'glyphicon-triangle-bottom': activeModal !== 'periodDropdown'}]"/>
        </div>
        <div v-show="activeModal === 'periodDropdown'" class="period-dropdown">
          <div @click.stop="chosenPeriod = 'year', activeModal = ''"><span>год</span></div>
          <div @click.stop="chosenPeriod = 'month', activeModal = ''"><span>месяц</span></div>
        </div>
        <div ref="calendarBlock" class="calendar-block"
             @click.stop="showCalendar($refs.calendarBlock, chosenPeriod)">
          <template v-if="chosenPeriod === 'month'">
            <span>{{ chosenDate.monthTitle + ' ' + chosenDate.year }}</span>
          </template>
          <span v-else>{{ chosenDate.year }}</span>
          <span
              :class="['glyphicon', {'glyphicon-triangle-top': activeModal === 'calendar', 'glyphicon-triangle-bottom': activeModal !== 'calendar'}]"/>
        </div>

        <calendar v-show="activeModal === 'calendar' && chosenPeriod === 'year'"
                  :modal-calendar-with-month="false"
                  :modal-calendar-with-time="false"
                  @closeModals="changeActiveModal"
                  @selectedDateTime="setChosenYear"
        />
        <calendar-month-years-modal
            v-show="activeModal === 'calendar' && chosenPeriod === 'month'"
            :currentYear="chosenDate.year"
            :needToShowYears="false"
            @closeDropDownCalendar="changeActiveModal"
            @setCurrentDate="changeChosenDate"
        />


        <div ref="departmentBlock"
             :style="selectedReportId !== 12 ? {margin: 'auto auto auto 0'} : {margin: '0'}"
             class="department-block"
             @click.stop="showDepartmentsDropdown($refs.departmentBlock)">
          <span>{{ chosenDepartment.title }}</span>
          <span
              :class="['glyphicon', {'glyphicon-triangle-top': activeModal === 'departmentList', 'glyphicon-triangle-bottom': activeModal !== 'departmentList'}]"/>
        </div>
        <div v-show="activeModal === 'departmentList'" class="departmentDropDownList">
          <department-list :companyDepartments="allCompanyDepartments"
                           @closeListDepartment="changeActiveModal"
                           @setDepartmentInFilter="changeCurrentDepartment"
          />
        </div>

        <!-- блок бригад и звеньев в заголовке -->
        <div v-if="selectedReportId === 12" class="header__brigadeWrapper">
          <div class="header__brigadeBtn" @click.stop="changeActiveModal('brigadesList', $event.target)">
            <p>{{ selectedBrigadeAndChain }}</p>
          </div>
          <span
              :class="['glyphicon', {'glyphicon-triangle-top': activeModal === 'brigadesList', 'glyphicon-triangle-bottom': activeModal !== 'brigadesList'}]"/>
          <div v-show="activeModal === 'brigadesList'" class="header__brigadeDropDownList" @click.stop>
            <div class="os-headerDropDownList">
              <template v-if="Object.keys(allBrigadesInDepartment).length">
                <div v-for="brigade in allBrigadesInDepartment" :key="brigade.brigade_id"
                     class="brigade-option">
                  <div class="brigade-title" @click="showChains($event.target)"
                       @dblclick="selectCurrentBrigade(brigade)">
                    <span>{{ brigade.brigade_description }}</span>
                  </div>
                  <div v-if="Object.keys(brigade.chanes).length" class="collapsed-chain-list">
                    <div v-for="chain in brigade.chanes" :key="chain.chane_id" class="chain-row"
                         @click="selectCurrentChain(chain, brigade)">
                      <span class="chain-title">{{ chain.chane_title }}</span>
                    </div>
                  </div>
                  <div v-else class="collapsed-chain-list"><span>Нет звеньев</span></div>
                </div>

                <!--                                <div class="brigade-option" @click="changeBrigade(brigade)">{{ brigade.brigade_description }}</div>-->
              </template>
            </div>
          </div>


        </div>
      </div>

      <div :style="selectedReportId < 3 || selectedReportId === 11 ? {height: 'calc(100% - 40px)'} : {}"
           class="report-content">
        <component :is="selectedReportObject.componentName"/>
      </div>
      
      <div class="print-button-container hidden hidden-print">
        <div class="print-button">
          <span class="print-icon"/>
          <span class="print-title">Отправка на печать</span>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import reportsStatisticsStore from "../modules/reports-statistics/reportsStatisticsStore.js";

const baseHeader = () => import('../components/base/BaseHeader.vue'),
    calendar = () => import('@/components/bookDirectiveModule/Calendar.vue'),
    previousPeriodReportByDepartment = () => import('../modules/reports-statistics/components/previousPeriodReportByDepartment.vue'),
    injunctionsStatistics = () => import('../modules/reports-statistics/components/injunctionsStatistics.vue'),
    professionalDiseaseStatistics = () => import('../modules/reports-statistics/components/professionalDiseaseStatistics.vue'),
    workerViolationsAndInfoStatistics = () => import('../modules/reports-statistics/components/workerViolationsAndInfoStatistics.vue'),
    medicalExaminationStatistics = () => import('../modules/reports-statistics/components/medicalExaminationStatistics.vue'),
    briefingsStatistics = () => import('../modules/reports-statistics/components/briefingsStatistics.vue'),
    injuriesStatistics = () => import('../modules/reports-statistics/components/injuriesStatistics.vue'),
    fireSafetyEquipmentsStatistics = () => import('../modules/reports-statistics/components/fireSafetyEquipmentsStatistics.vue'),
    sizStatistics = () => import('../modules/reports-statistics/components/sizStatistics.vue'),
    expertiseStatistics = () => import('../modules/reports-statistics/components/expertiseStatistics.vue'),
    soutStatistics = () => import('../modules/reports-statistics/components/soutStatistics.vue'),
    productionStatistics = () => import('../modules/reports-statistics/components/productionStatistics.vue'),
    departmentList = () => import("../modules/order-system/components/shift-schedule/modals/ListDepartment.vue"),
    CalendarMonthYearsModal = () => import('../modules/reports-statistics/components/extra-components/calendarWithMonths'); // календарь в фильтре с выбором года и месяца

export default {
  name: "ReportStatistics",
  components: {
    baseHeader,
    calendar,
    previousPeriodReportByDepartment,
    injunctionsStatistics,
    professionalDiseaseStatistics,
    workerViolationsAndInfoStatistics,
    medicalExaminationStatistics,
    briefingsStatistics,
    injuriesStatistics,
    fireSafetyEquipmentsStatistics,
    sizStatistics,
    expertiseStatistics,
    soutStatistics,
    productionStatistics,
    departmentList,
    CalendarMonthYearsModal,
  },
  data() {
    return {

      maxWindowClass: '',
      reportsList:
          {
            1: {
              id: 1,
              title: 'Отчёт по<br/> подразделению за<br/> предыдущий период',
              iconActive: require('../modules/reports-statistics/assets/1_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/1_grey.png'),
              color: '#56698f',
              componentName: 'previousPeriodReportByDepartment'
            },
            2: {
              id: 2,
              title: 'Статистика<br/> Предписаний и ПАБ',
              iconActive: require('../modules/reports-statistics/assets/2_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/2_grey.png'),
              color: '#598d9b',
              componentName: 'injunctionsStatistics'
            },
            3: {
              id: 3,
              title: 'Статистика<br/> профзаболеваний',
              iconActive: require('../modules/reports-statistics/assets/3_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/3_grey.png'),
              color: '#4d897c',
              componentName: 'professionalDiseaseStatistics'
            },
            4: {
              id: 4,
              title: 'Статистика нарушений<br/> и иных сведений<br/> о персонале',
              iconActive: require('../modules/reports-statistics/assets/4_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/4_grey.png'),
              color: '#7c6580',
              componentName: 'workerViolationsAndInfoStatistics'
            },
            5: {
              id: 5,
              title: 'Статистика<br/> медосмотров',
              iconActive: require('../modules/reports-statistics/assets/5_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/5_grey.png'),
              color: '#56698f',
              componentName: 'medicalExaminationStatistics'
            },
            6: {
              id: 6,
              title: 'Статистика прохождения<br/> инструктажей, проверок<br/> знаний, аттестаций',
              iconActive: require('../modules/reports-statistics/assets/6_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/6_grey.png'),
              color: '#598d9b',
              componentName: 'briefingsStatistics'
            },
            7: {
              id: 7,
              title: 'Статистика<br/> травматизма<br/> и происшествий',
              iconActive: require('../modules/reports-statistics/assets/7_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/7_grey.png'),
              color: '#4d897c',
              componentName: 'injuriesStatistics'
            },
            8: {
              id: 8,
              title: 'Статистика наличия<br/> и состояния средств<br/> пожарной безопасности',
              iconActive: require('../modules/reports-statistics/assets/4_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/4_grey.png'),
              color: '#7c6580',
              componentName: 'fireSafetyEquipmentsStatistics'
            },
            9: {
              id: 9,
              title: 'Статистика наличия<br/> и состояния СИЗ',
              fullTitle: 'Статистика наличия и состояния средств индивидуальной защиты',
              iconActive: require('../modules/reports-statistics/assets/9_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/9_grey.png'),
              color: '#56698f',
              componentName: 'sizStatistics'
            },
            10: {
              id: 10,
              title: 'Статистика проведения и <br/> планирования ЭПБ',
              fullTitle: 'Статистика проведения и планирования экспертиз промышленной безопасности',
              iconActive: require('../modules/reports-statistics/assets/10_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/10_grey.png'),
              color: '#598d9b',
              componentName: 'expertiseStatistics'
            },
            11: {
              id: 11,
              title: 'Статистика СОУТ / ПК',
              iconActive: require('../modules/reports-statistics/assets/11_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/11_grey.png'),
              color: '#4d897c',
              componentName: 'soutStatistics'
            },
            12: {
              id: 12,
              title: 'Производственная статистика',
              iconActive: require('../modules/reports-statistics/assets/12_active.png'),
              iconInactive: require('../modules/reports-statistics/assets/12_grey.png'),
              color: '#7c6580',
              componentName: 'productionStatistics'
            }
          }
    }
  },
  computed: {
    /**
     * возвращает идентификатор выбранного отчета
     **/
    selectedReportId: {
      get() {
        return reportsStatisticsStore.getters.SELECTEDREPORTID;
      },
      set(selectedReportId) {
        reportsStatisticsStore.dispatch('setActiveReportId', selectedReportId);
      }
    },
    /**
     * возвращает выбранную дату
     **/
    chosenDate: {
      get() {
        return reportsStatisticsStore.getters.CHOSENDATE;
      },
      set(dateTimeObject) {
        reportsStatisticsStore.dispatch('changeChosenDate', dateTimeObject);
      }
    },
    /**
     * возвращает выбранный период (месяц/год month/year)
     **/
    chosenPeriod: {
      get() {
        return reportsStatisticsStore.getters.CHOSENPERIOD;
      },
      set(periodTitle) {
        reportsStatisticsStore.dispatch('changeChosenPeriod', periodTitle);
      }
    },
    /**
     * возвращает выбранный участок
     **/
    chosenDepartment: {
      get() {
        return reportsStatisticsStore.getters.CHOSENDEPARTMENT;
      },
      set(departmentObject) {
        reportsStatisticsStore.dispatch('changeChosenDepartment', departmentObject);
      }
    },
    /**
     * возвращает выбранную бригаду
     **/
    chosenBrigade: {
      get() {
        return reportsStatisticsStore.getters.CHOSENBRIGADE;
      },
      set(brigadeObject) {
        reportsStatisticsStore.dispatch('changeChosenBrigade', brigadeObject);
      }
    },
    /**
     * возвращает выбранное звено
     **/
    chosenChane: {
      get() {
        return reportsStatisticsStore.getters.CHOSENCHANE;
      },
      set(chaneObject) {
        reportsStatisticsStore.dispatch('changeChosenChane', chaneObject);
      }
    },
    /**
     * возвращает названия выбранной бригады и выбранного звена
     **/
    selectedBrigadeAndChain: {
      get() {
        return this.chosenChane.chane_id ? this.chosenBrigade.brigade_description + ' / ' + this.chosenChane.chane_title : this.chosenBrigade.brigade_description;
      }
    },
    /**
     * возвращает список всех бригад на участке
     **/
    allBrigadesInDepartment: {
      get() {
        return reportsStatisticsStore.getters.ALLBRIGADESINCOMPANYDEPARTMENT;
      }
    },
    /**
     * возвращает объект выбранного отчета
     **/
    selectedReportObject: {
      get() {
        return this.reportsList[this.selectedReportId];
      }
    },
    /**
     * возвращает название активного модального окна/выпадающего списка
     **/
    activeModal: {
      get() {
        return reportsStatisticsStore.getters.ACTIVEMODAL;
      },
      set(activeModalName) {
        reportsStatisticsStore.dispatch('setActiveModal', activeModalName);
      }
    },
    /**
     * возвращает список всех участков
     **/
    allCompanyDepartments: {
      get() {
        return this.$store.getters.GETALLCOMPDEPAR;
      }
    }
  },
  methods: {
    returnToTheMainPage() {
      this.$router.push('/order-system');
    },
    setFullScreenMode() {
      this.maxWindowClass = this.maxWindowClass === 'fullscreen-mode' ? '' : 'fullscreen-mode';
    },
    setActiveReport(reportId) {
      // console.log("setActiveReport. reportId", reportId);
      this.activeModal = "";
      this.selectedReportId = reportId;
      this.ajaxGetStatisticsData();
    },
    /**
     * устанавливает выбранный из выпадающего списка участков участок (компанию)
     * @param departmentObject {object} - объект выбранного участка
     **/
    changeCurrentDepartment(departmentObject) {
      this.chosenDepartment = departmentObject;
      this.ajaxGetStatisticsData();

      if (hasProperty(localStorage, 'serialWorkerData')) {
        let serialWorkerData = {};
        serialWorkerData = JSON.parse(localStorage.getItem("serialWorkerData"));
        serialWorkerData.userCompanyDepartmentId = departmentObject.id;
        serialWorkerData.userCompany = departmentObject.title;

        localStorage.setItem("serialWorkerData", JSON.stringify(serialWorkerData));
      }
    },
    /**
     * устанавливает выбранный из выпадающего списка участков участок (компанию)
     * @param dateTimeObject (object) - объект даты
     **/
    changeChosenDate(dateTimeObject) {
      // console.log('ReportStatistics.vue, changeChosenDate. dateTimeObject', dateTimeObject);
      this.chosenDate = dateTimeObject;
      if (this.selectedReportId !== 12) {
        this.ajaxGetStatisticsData();
      }
    },

    /**
     * устанавливает выбранный год
     * */
    setChosenYear(dateObject) {
      // console.log('setChosenYear.vue, setChosenYear. selected date ', dateObject);
      this.chosenDate = dateObject;
      this.chosenDate.year = dateObject.date.split('.')[2];
      let months = [
        "Январь",
        "Февраль",
        "Март",
        "Апрель",
        "Май",
        "Июнь",
        "Июль",
        "Август",
        "Сентябрь",
        "Октябрь",
        "Ноябрь",
        "Декабрь"];
      let monthNumber = Number(dateObject.date.split('.')[1]) - 1;
      // console.log('setChosenYear. monthNumber', monthNumber);
      this.chosenDate.monthTitle = months[monthNumber];
      this.chosenDate.monthNumber = monthNumber;
      this.ajaxGetStatisticsData();
      this.activeModal = '';
    },
    changeActiveModal(modalName = '') {
      if (this.activeModal !== modalName) {
        this.activeModal = modalName;
      } else {
        this.activeModal = '';
      }
    },
    ajaxGetStatisticsData() {
      // console.log('ReportStatistics.vue, ajaxGetStatisticsData. Начало метода');
      if (this.chosenDate && this.chosenDepartment.id) {
        let monthNumber = Number(this.chosenDate.monthNumber) + 1,
            config = {
              company_department_id: this.chosenDepartment.id,
              year: this.chosenDate.year ? this.chosenDate.year : new Date().getFullYear(),
              month: monthNumber < 10 ? '0' + monthNumber : monthNumber,
              period: this.chosenPeriod
            };
        // console.log('ReportStatistics.vue, ajaxGetStatisticsData. объект для передачи на бэк. ', config);
        switch (this.selectedReportId) {
          case 1:
            // this.$store.dispatch('OSStore/ModulePreviousPeriodReport/getPreviousPeriodReport', {
            //     dateTime: typeof this.chosenDate.date === 'object' ? this.chosenDate.date : new Date(this.chosenDate.date.split('.').reverse().join('-')),
            //     brigadeId: null,
            //     companyDepartmentId: this.chosenDepartment.id
            // });
            // this.$store.dispatch('OSStore/ModulePreviousPeriodReport/getDataReportMonth', {
            //     dateTime: typeof this.chosenDate.date === 'object' ? this.chosenDate.date : new Date(this.chosenDate.date.split('.').reverse().join('-')),
            //     brigadeId: null,
            //     companyDepartmentId: this.chosenDepartment.id
            // });
            break;
          case 2:
            break;
          case 3:
            reportsStatisticsStore.dispatch('ajaxGetProfessionalDiseaseStatisticsData', config);
            break;
          case 4:
            reportsStatisticsStore.dispatch('ajaxGetViolationStatisticsData', config);
            break;
          case 5:
            reportsStatisticsStore.dispatch('ajaxGetMedicalExaminationStatisticsData', config);
            break;
          case 6:
            reportsStatisticsStore.dispatch('ajaxGetBriefingsStatisticsData', config);
            break;
          case 7:
            reportsStatisticsStore.dispatch('ajaxGetInjuriesStatisticsData', config);
            break;
          case 8:
            reportsStatisticsStore.dispatch('ajaxGetFireSafetyEquipmentsStatisticsData', config);
            break;
          case 9:
            reportsStatisticsStore.dispatch('ajaxGetSizStatisticsData', config);
            break;
          case 10:
            reportsStatisticsStore.dispatch('ajaxGetIndustrialSafetyExpertise', config);
            break;
          case 11:
            break;
          case 12:
            reportsStatisticsStore.dispatch('ajaxGetBrigadesByCompanyDepartment', this.chosenDepartment.id);
            break;
        }
      }
    },
    closeAllModalAndDropdownWindows() {
      this.changeActiveModal();
    },
    /**
     * отображает календарь
     **/
    showPeriodContainer() {
      let warnings = [];
      try {
        let modal = document.querySelector('.period-dropdown'), // Находим модальное окно выбора периода по классу
            periodDropdownTriggerDiv = document.querySelector('.period-block'); // див, относительно которого нужно расположить окно выбора периода
        // console.log('showCalendar. modal = ', modal);

        if (!modal) {
          throw new Error("showCalendar. элемент .period-dropdown выпадающий список не найден");
        }

        let positionModal = calculateModalPosition(Math.round(periodDropdownTriggerDiv.getBoundingClientRect().left) + Math.round(periodDropdownTriggerDiv.getBoundingClientRect().width / 2) - 50, Math.round(periodDropdownTriggerDiv.getBoundingClientRect().top + periodDropdownTriggerDiv.getBoundingClientRect().height + 4), 100, 50, ".calendar-period-container");
        if (positionModal.status === 0) {
          throw new Error("showCalendar. Ошибка при расчете места открытия окна");
        }
        warnings.push("showCalendar. расчитали сдвиг: ", positionModal);

        modal.style.left = positionModal.left;
        modal.style.top = positionModal.top;
        warnings.push('modal is ', modal);
        warnings.push("showCalendar. Закончил выполнять метод");
      } catch (err) {
        console.log("showCalendar. Исключение");
        console.log(err);
      }
      // console.log(warnings);
      this.changeActiveModal('periodDropdown');
    },
    /**
     * отображает календарь
     **/
    showCalendar(calendarBlock, chosenPeriod) {
      // this.changeActiveModal();
      let warnings = [];
      try {
        let calendarSelector = chosenPeriod === 'month' ? 'calendarMonthYearsModalWrapper' : 'container-component';
        // console.log('showCalendar. calendarSelector = ', calendarSelector);

        let modal = document.querySelector('.' + calendarSelector); // Находим модальное окно добавления звена по классу
        // console.log('showCalendar. modal = ', modal);

        if (!modal) {
          throw new Error("showCalendar. элемент .container-component выпадающий список не найден");
        }

        let positionModal = calculateModalPosition(Math.round(calendarBlock.getBoundingClientRect().left) + Math.round(calendarBlock.getBoundingClientRect().width / 2) - 125, Math.round(calendarBlock.getBoundingClientRect().top + calendarBlock.getBoundingClientRect().height), 250, 240, ".calendar-period-container");
        if (positionModal.status === 0) {
          throw new Error("showCalendar. Ошибка при расчете места открытия окна");
        }
        warnings.push("showCalendar. расчитали сдвиг: ", positionModal);

        modal.style.position = 'absolute'; // Задаём абсолютное позиционирование
        modal.style.left = positionModal.left;
        modal.style.top = positionModal.top;
        warnings.push('modal is ', modal);
        warnings.push("showCalendar. Закончил выполнять метод");
      } catch (err) {
        console.log("showCalendar. Исключение");
        console.log(err);
      }
      // console.log(warnings);
      this.changeActiveModal('calendar');
    },
    /**
     * отображает календарь
     **/
    showDepartmentsDropdown(departmentBlock) {
      // this.changeActiveModal();
      let warnings = [];
      try {
        let modal = document.querySelector('.departmentDropDownList'); // Находим модальное окно добавления звена по классу
        // console.log('showDepartmentsDropdown. modal = ', modal);

        if (!modal) {
          throw new Error("showDepartmentsDropdown. элемент .departmentDropDownList выпадающий список не найден");
        }

        let positionModal = calculateModalPosition(Math.round(departmentBlock.getBoundingClientRect().left) + Math.round(departmentBlock.getBoundingClientRect().width / 2) - 200, Math.round(departmentBlock.getBoundingClientRect().top + departmentBlock.getBoundingClientRect().height + 2), 400, 325, ".calendar-period-container");
        if (positionModal.status === 0) {
          throw new Error("showDepartmentsDropdown. Ошибка при расчете места открытия окна");
        }
        warnings.push("showDepartmentsDropdown. расчитали сдвиг: ", positionModal);

        modal.style.position = 'absolute'; // Задаём абсолютное позиционирование
        modal.style.left = positionModal.left;
        modal.style.top = positionModal.top;
        warnings.push('modal is ', modal);
        warnings.push("showDepartmentsDropdown. Закончил выполнять метод");
      } catch (err) {
        console.log("showDepartmentsDropdown. Исключение");
        console.log(err);
      }
      // console.log(warnings);
      this.changeActiveModal('departmentList');
    },
    /**
     * метод по отображению/скрытию списка звеньев
     * Входные данные:
     * @params target (object) - объект события клика левой кнопкой мыши
     **/
    showChains(target) {
      let brigadeNodeElement = target.tagName === 'DIV' ? target : target.parentNode;
      if (brigadeNodeElement.nextElementSibling) {
        $(brigadeNodeElement.nextElementSibling).slideToggle('fast');
      }
    },

    /**
     * метод по установке выбранного звена
     * @param chaneObject
     * @param brigadeObject
     */
    selectCurrentChain(chaneObject, brigadeObject) {
      // console.log('selectCurrentChain. на входе', chaneObject);
      if (chaneObject.chane_id === 'all') {
        this.chosenBrigade = 'all';
        this.chosenChane = 'all';
      } else {
        this.chosenBrigade = chaneObject.brigade_id;
        this.chosenChane = chaneObject.chane_id;
      }
      this.chosenChane = chaneObject;
      this.chosenBrigade = brigadeObject;
      this.activeModal = "";
    },
    /**
     * метод по установке выбранной бригады
     * @param brigadeObject
     */
    selectCurrentBrigade(brigadeObject) {
      // console.log('selectCurrentBrigade. на входе', brigadeObject);
      if (brigadeObject.brigade_id === 'all') {
        this.chosenBrigade = 'all';
      } else {
        this.chosenBrigade = brigadeObject.brigade_id;
      }
      this.chosenChane = 'all';
      this.chosenChane = {
        chane_id: 'all',
        chane_title: 'Все звенья',
        brigade_id: 'all',
        chaner_id: null,
        chane_type: null,
        workers: {}
      };
      this.chosenBrigade = brigadeObject;
      this.activeModal2 = "";
      this.activeModal = "";
    },

    setBorderColorWrapper() {
      switch (this.selectedReportId) {
        case 1:
        case 5:
        case 9:
          return {
            'border': '2px solid #56698f'
          }
        case 2:
          return {
            'border': '2px solid #598d9b'
          }
        case 3:
        case 6:
        case 7:
        case 10:
        case 11:
          return {
            'border': '2px solid #4d897c'
          }
        case 4:
        case 8:
        case 12:
          return {
            'border': '2px solid #7c6580'
          }
      }
    },
  },
  created() {
    let parsedData = JSON.parse(localStorage.getItem('serialWorkerData'));
    // console.log('ReportStatistics.vue. created. parsedData ',parsedData);
    this.chosenDepartment.id = parsedData.userCompanyDepartmentId;
    this.chosenDepartment.title = parsedData.userCompany;
    this.$store.dispatch('setDefaultData');
    window.reportStatistics = this;
    // console.log('ReportStatistics.vue, created. this.$route',this.$route);
    if (this.$route.params.activeReportId) {
      this.setActiveReport(this.$route.params.activeReportId);
    }
    if (localStorage.getItem('activeStatisticReportId')) {
      this.setActiveReport(Number(localStorage.getItem('activeStatisticReportId')));
      localStorage.removeItem('activeStatisticReportId');
    }
  },
  beforeMount() {
    this.ajaxGetStatisticsData();
  },
  mounted() {
    document.addEventListener('click', this.closeAllModalAndDropdownWindows);
  },
  beforeDestroy() {
    document.removeEventListener('click', this.closeAllModalAndDropdownWindows);
  }
}
</script>

<style scoped lang="less">

.statistic-reports {
  width: 100%;
  display: flex;
  min-height: 710px;
  height: calc(100vh - 120px);

  &.fullscreen-mode {
    height: 99vh;
    width: calc(100vw - 10px);
    position: fixed; /*надо протестить этот момент*/
    top: 0;
    left: 5px;
    margin: 0;
    z-index: 1061;
    background: #fff;
    min-width: 1200px;

    .sidebar {
      padding-top: 30px;
    }
  }

  .sidebar {
    width: 250px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;

    .report-block {
      width: 160px;
      display: flex;
      height: 60px;
      position: relative;
      justify-content: flex-end;

      &:first-of-type {
        margin-top: -7px;
      }

      &:not(:last-of-type) {
        margin-bottom: 2.5px;
      }

      img {
        position: absolute;
        top: 0;
        height: 60px;
        display: block;
        width: 70px;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;

        &:hover {
          cursor: pointer;
        }
      }

      div {
        background: #fff;
        outline: 2px solid #b2b3b3;
        outline-offset: -4px;
        font-size: 12px;
        line-height: 1;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        margin: auto 0;
        text-align: left;
        width: calc(100% - 35px);
        height: 50px;
        display: flex;
        align-items: center;

        &:hover {
          cursor: pointer;
        }
      }

      &:nth-of-type(odd) {
        width: 220px;

        img {
          right: 140px;
        }

        div {
          padding: 5px 5px 5px 50px;
        }
      }

      &:nth-of-type(even) {
        width: 100%;

        img {
          right: calc(100% - 70px);
        }

        div {
          padding: 5px 5px 5px 40px;
        }
      }
    }
  }

  .main-content {
    width: calc(100% - 270px);
    margin-left: 20px;
    background: #fff;

    .calendar-modal {
      position: absolute;
      width: 150px;
      height: 100px;
      top: 33px;
      box-shadow: 0 0 5px 2px rgba(0, 0, 0, 0.35);
    }

    .calendar-block {
      width: 130px;
      height: 30px;
      padding-right: 15px;
      background: #b2d63c;
      display: flex;
      position: relative;
      left: -1px;
      transform: skew(30deg);
      margin: 0 -7px 0 0;

      &:hover {
        cursor: pointer;
        background: #c6eb41;
      }

      span {
        transform: skew(-30deg);
      }

      & > span:first-of-type {
        margin: auto;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
      }

      .glyphicon {
        position: absolute;
        top: 12px;
        right: 15px;
        font-size: 8px;
      }
    }

    .container-component {
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.35) !important;
    }

    .calendar-period-container {
      width: 100%;
      min-width: 785px;
      height: 30px;
      display: flex;
      margin: 0 auto;
      position: relative;

      .period-dropdown {
        position: absolute;
        top: 30px;
        left: 0;
        width: 100px;
        background: #fff;
        z-index: 999;
        box-shadow: 0 0 5px 2px rgba(0, 0, 0, 0.35);

        & > div {
          height: 25px;
          background: #fff;
          display: flex;
          justify-content: center;
          align-items: center;

          &:hover {
            cursor: pointer;
            /*background: #EBECEC;*/
          }

          &:first-of-type {
            border-bottom: 1px solid #ccc;

          }


        }
      }

      .period-block {
        width: 100px;
        height: 30px;
        display: flex;
        justify-content: center;
        margin: 0 0 0 auto;
        align-items: center;
        transform: skew(30deg);
        background: #B2D63C;
        position: relative;

        &:hover {
          cursor: pointer;
          background: #c6eb41;
        }

        span {
          transform: skew(-30deg);
        }

        .glyphicon {
          position: absolute;
          top: 12px;
          right: 15px;
          font-size: 8px;
        }
      }

      .calendarMonthYearsModalWrapper {
        position: absolute;
        /*top: 33px;*/
        /*left: 30px;*/
        z-index: 99;
      }

      .department-block {
        width: 360px;
        display: flex;
        flex-flow: row nowrap;
        background: #B2D63C;
        clip-path: polygon(0 0, 100% 0, calc(100% - 30px * 0.6) 100%, calc(30px * 0.6) 100%);
        justify-content: center;
        align-items: center;
        position: relative;
        height: 100%;

        &:hover {
          cursor: pointer;
          background: #c6eb41;
        }

        & > span:first-of-type {
          white-space: nowrap;
          text-overflow: ellipsis;
          overflow: hidden;
        }

        .glyphicon {
          position: absolute;
          top: 12px;
          right: 15px;
          font-size: 8px;
        }
      }

      .departmentDropDownList {
        width: 400px;
        position: absolute;
        top: 33px;
        z-index: 9999;
      }

      .header__brigadeWrapper {
        position: relative;
        min-width: 220px;
        display: flex;
        margin-right: auto;
        left: -10px;

        @media all and (max-width: 1240px) {
          max-width: 250px;
        }
      }

      .header__brigadeBtn {
        background-color: #B2D63C;
        transform: skew(-30deg);
        margin-left: 4px;
        cursor: pointer;
        height: 100%;
        display: flex;
        width: 100%;
        justify-content: center;
        align-items: center;


        &:hover {
          background-color: #C5EE43;
        }

        p {
          transform: skew(30deg);
          margin: 0 20px;
          font-size: 12px;
          white-space: nowrap;
          text-overflow: ellipsis;
          overflow: hidden;
        }
      }

      .header__brigadeDropDownList {
        position: absolute;
        width: 100%;
        left: 0;
        top: 33px;

        .brigade-option {
          .brigade-title {
            margin: 0;
            padding: 5px;
            text-align: left;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            background-color: #0003;

            &:hover {
              background-color: #00000015;
            }
          }
        }

        .collapsed-chain-list {
          padding-left: 20px;
          display: none;
          text-align: left;

          .chain-row {
            background-color: #fff;
            padding: 5px;
            margin: 0;
            border-bottom: 1px solid #fff;
            cursor: pointer;

            &:hover {
              background-color: #eeeeee;
            }
          }
        }

        .os-headerDropDownList {
          position: relative;
          z-index: 1;
          width: 100%;
          max-height: 200px;
          overflow-y: auto;
          background-color: #fff;
          box-shadow: 2px 2px 5px rgba(0, 0, 0, .5);

          p {
            margin: 0;
            padding: 5px;
            border-bottom: 1px solid #eee;
            cursor: pointer;

            &:hover {
              background-color: #eee;
            }
          }
        }

      }

      .glyphicon {
        position: absolute;
        top: 12px;
        right: 8px;
        font-size: 8px;
      }
    }

    .headerButtons {
      width: 40%;
      height: 30px;
      display: flex;
      position: relative;
      margin: 0 auto;

      &__calendarWrapper {
        position: relative;
        width: 25%;
        min-width: 230px;

        .calendarModal {
          position: relative;
          z-index: 10;
        }
      }

      &__buttonTrapezium {
        position: relative;
        width: 25%;
        clip-path: polygon(0 0, 90% 0, 100% 100%, 10% 100%);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      &__calendar {
        width: 100%;
        height: 100%;
      }

      &__buttonTrapeziumBg {

        z-index: 10000;
        width: 100%;
        height: 30px;
        background-color: #B2D63C;
        display: flex;
        align-items: center;
        justify-content: center;

        &:hover {
          background-color: #c6eb41;
        }
      }

      .department-filter {
        position: relative;
        width: 75%;
        left: -8px;


        &__titleSkewedLeft {
          position: absolute;
          left: 0;
          width: 70%;
          height: 100%;
          transform: skew(39deg);
          background-color: #B2D63C;
          @media print {
            display: none;
          }

        }

        &__titleSkewedRight {
          position: absolute;
          right: 0;
          width: 70%;
          height: 100%;
          transform: skew(-39deg);
          background-color: #B2D63C;
          @media print {
            display: none;
          }
        }

        &:hover {
          cursor: pointer;

          &__titleSkewedLeft, &__titleSkewedRight {
            background-color: #c6eb41;
          }
        }

        &__departmentBtn {
          height: 100%;
          position: relative;
          display: flex;

          p {
            .header__departmentTriangle {
              display: inline-block;
              width: 0;
              height: 0;
              border-left: 3px solid transparent;
              border-right: 3px solid transparent;
              border-top: 6px solid #000000;
              border-bottom: none;
              margin-left: 10px;
              @media print {
                display: none;
              }
            }

            .header__departmentTriangle.openedDepartmentList {
              display: inline-block;
              width: 0;
              height: 0;
              border-left: 3px solid transparent;
              border-right: 3px solid transparent;
              border-bottom: 6px solid #000000;
              border-top: none;
              margin-left: 10px;
              @media print {
                display: none;
              }
            }

            .glyphicon {
              position: absolute;
              top: 12px;
              right: 15px;
              font-size: 8px;
            }
          }
        }

        .departmentDropDownList {
          width: 400px;
          position: absolute;
          top: 33px;
        }
      }

      &__calendarIcon {
        width: 20px;
        position: relative;
        right: 35px;
      }

      p {
        width: 100%;
        margin: auto;
        overflow: hidden;
        -ms-text-overflow: ellipsis;
        text-overflow: ellipsis;
        max-width: 90%;
        white-space: nowrap;
      }
    }

    .report-content {
      width: 100%;
      overflow: auto;
      /*min-height: calc(100vh - 290px);*/
      background: #fff;
      box-shadow: 3px 6px 5px rgba(0, 0, 0, 0.35);
      /*height: calc(100% - 100px);*/
      height: calc(100% - 70px);
    }

    .print-button-container {
      width: 100%;
      height: 30px;
      overflow: hidden;
      display: flex;
      justify-content: flex-end;

      .print-button {
        transform: skew(30deg);
        display: flex;
        background: #4d897c;
        height: 100%;
        width: 200px;
        position: relative;
        right: -10px;

        & > span {
          transform: skew(-30deg);
        }

        .print-icon {
          margin: auto 15px auto 30px;
          width: 20px;
          height: 20px;
          background: url('../assets/print.png') center no-repeat;
          -webkit-background-size: contain;
          background-size: contain;
        }

        .print-title {
          margin: auto auto auto 0;
          color: #fff;
          font-size: 12px;
        }
      }
    }
  }


}


</style>
