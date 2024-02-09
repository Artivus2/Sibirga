<!--
    Главная страница графика выходов в нарядной системе
-->

<!--
    Блок заполнения данных шаблона печати - заполнятеся при выборе стандартного метода вызова на печать
-->
<template>
  <div class="os-shift-shedule" :class="maxWindow">
    <div class="blockForPrint">
      <div class="blockForPrint__mineName"><h5> {{ userSessionData && userSessionData['mainCompanyTitle'] ?  userSessionData['mainCompanyTitle'] : ''}}</h5></div>
      <div class="blockForPrint__signaturesWrapper">
        <div class="blockForPrint__signaturesLeft">
          <p>Согласованно:</p>
          <p>Должность</p>
          <p>_____________________/Фамилия И.О./</p>
          <p>"___"________________ 2019 г.</p>
        </div>
        <div class="blockForPrint__signaturesRight">
          <p>Утверждаю:</p>
          <p>Должность</p>
          <p>_____________________/Фамилия И.О./</p>
          <p>"___"________________ 2019 г.</p>
        </div>
      </div>
      <div class="blockForPrint__title">
        <p>ГРАФИК СМЕННОСТИ</p>
        <p>Участок</p>
        <p>{{chosenDate.month}} {{chosenDate.year}}г.</p>
      </div>
    </div>

    <div class="shift-charge-control">
      <workerShift
          :chosenDate="chosenDate"
      >

      </workerShift>
    </div>

    <div class="shift">
      <div class="header">

        <!--  заголовок логотип в плашке      -->
        <div class="header-logo">
          <router-link to="/order-system">
            <img src="@/assets/amicum-logo.png" alt="amicum logo"  v-if="maxWindow === 'os-shift-shedule-max'">
          </router-link>
        </div>

        <div class="header-title">
          <h1>ОКНО ГРАФИКА ВЫХОДОВ</h1>
        </div>

        <div class="header-btn">
          <button v-on:click="setMaxWindow()" class="header-btn-maxWindow">
            <img src="@/assets/orderSystem/blackMaximizeWindow.png" alt="max window" >
          </button>
          <span>|</span>
          <button class="header-btn-close"><span></span></button>
        </div>
      </div>

      <!--   меню фильтра дата     -->
      <div class="headerButtons">
        <div class="headerButtons__calendarWrapper">
          <div class="headerButtons__calendar headerButtons__buttonTrapezium"
               @click.stop="changeActiveModal('calendarModal')">
            <div class="headerButtons__buttonTrapeziumBg">
              <p>
                <span v-model="chosenDate.month">{{chosenDate.month}} </span>
                <span v-model="chosenDate.year">{{chosenDate.year}}</span>
              </p>
              <img class="headerButtons__calendarIcon"
                   src="@/assets/workShedule/calendar.svg"
                   alt="календарь">
            </div>
          </div>
          <div class="calendarModal" v-show="activeModal === 'calendarModal'">
            <calendar-month-years-modal @closeDropDownCalendar="changeDate"/>
          </div>
        </div>

        <!--   меню фильтра департамента     -->
        <div class="headerButtons__departmentWrapper"
             @click.stop="changeActiveModal('departmentList')">
          <div class="headerButtons__department  headerButtons__buttonTrapezium">
            <div class="headerButtons__buttonTrapeziumBg">
              <p class="headerButtons__departmentText" :title="currentDepartment.title">
                <span>{{currentDepartment.title}}</span>
              </p>
              <div class="headerButtons__departmentTriangle"
                   :class="{headerButtons__departmentTriangle_rotated: activeModal === 'departmentList'}">
              </div>
            </div>
          </div>
          <departmentCompaniesList v-if="activeModal === 'departmentList'"
                                   @setDepartmentInFilter="setCurrentDepartment"
                                   :rsFullListDepartmentCompanies="fullListOfCompaniesAndDepartments">
          </departmentCompaniesList>
        </div>

        <!--   меню фильтра бригад     -->
        <div class="headerButtons__brigadeWrapper"
             @click.stop="changeActiveModal('brigadeList')">
          <div class="headerButtons__brigade  headerButtons__buttonTrapezium">
            <div class="headerButtons__buttonTrapeziumBg">
              <p class="headerButtons__brigadeText"><span>{{currentBrigade.brigade_description}}</span></p>
              <div class="headerButtons__brigadeTriangle"
                   :class="{headerButtons__brigadeTriangle_rotated: activeModal === 'brigadeList'}">
              </div>
            </div>
          </div>
          <div class="brigadeListSelect" v-show="activeModal === 'brigadeList'">

            <div>
              <template v-for="brigade in allBrigadesInDepartment">
                <template>
                  <p @click.stop="setCurrentBrigade(brigade)"
                     :key="brigade.brigade_id"
                  >{{brigade.brigade_description}}</p>
                </template>
              </template>
            </div>
          </div>
        </div>

        <!--   меню фильтра информация о бригаде     -->
        <div class="headerButtons__brigadeInfo headerButtons__buttonParallelogram"
             v-show="false"
             @click.stop="showBrigadeInfoModal()">
          <div class="headerButtons__buttonParallelogramBg">
            <img class="headerButtons__brigadeInfoIcon" src="@/assets/workShedule/info-icon.svg" alt="инфо">
          </div>
        </div>
      </div>

      <!--   подгружаем компонент карточка сотрудника запускается по клику из контекстного меню на сотруднике -->
      <div class="components">
        <schedule
            :chosenDate="chosenDate"

            :showBrigadeTab="showBrigadeTab"
            :showChaneTab="showChaneTab"
            @openEmployeeCard="openEmployeeCard"
            @sendDataFromState="sendDataFromState"
            :key="componentKey"

        />
      </div>
    </div>
    <div class="blockForPrint blockForPrint__footer">
      <p>Руководитель участка ____________________ Фамилия И.О.</p>
    </div>
    <div class="brigadesInformationModal" v-if="activeModal === 'brigadeInformation'">
      <div class="brigadesInformationModal__content" @click.stop>
        <brigades-information
            @closeBrigadesInfo="changeActiveModal('brigadeInformation')"
            :requestParameters="getParametersForBrigadeInfo()"/>
      </div>
    </div>
    <div class="errChoseBrigadeWrapper" v-if="activeModal === 'errChoseBrigade'">
      <div class="errChoseBrigade" @click.stop>
        <p class="errChoseBrigade__text">Пожалуйста, выберите бригаду</p>
        <button class="errChoseBrigade__btn" >Закрыть</button>
      </div>
    </div>

    <employeeCard
        v-if="activeModal == 'EmployeeCard'"
        :idWorker="idWorker"
        @changeActiveModal="changeActiveModal"
    />
  </div>
</template>

<script>

const filterObject = (obj, filter, filterValue) =>
    Object.keys(obj).reduce((acc, val) =>
        (obj[val][filter] !== filterValue ? acc : {
              ...acc,
              [val]: obj[val]
            }
        ), {});

/** подключаем компоненты на главной странице графика выходов **/
import schedule from '@/components/orderSystem/workShedule/schedule_component';                                     // график выходов
import CalendarMonthYearsModal from "@/components/orderSystem/workShedule/modal/calendarMonthYearsModal";           // Модальное окно выбора месяца и года
import workerShift from '@/components/orderSystem/workShedule/ms-worker-shift';                                     // список работников в смене посуточно
import BrigadesInformation from "@/components/orderSystem/workShedule/modal/brigadesInformation";                   // Модальное окно информация о бригаде
import MsStatement from "@/components/orderSystem/workShedule/ms-statement";                                        // Модальное окно утверждения наряда
import employeeCard from '@/components/orderSystem/employeeCard/employeeCard.vue';                                  // карточка сотрудника
import departmentCompaniesList from '@/components/orderSystem/workShedule/department_list.vue';                     // выпадашка с участками и компаниями

export default {
  /** !!!!!! ----  подключенные по умолчанию компоненты **/
  components: {
    schedule,
    CalendarMonthYearsModal,
    BrigadesInformation,
    workerShift,
    MsStatement,
    employeeCard,
    departmentCompaniesList
  },
  /** !!!!!! ----  блок местных локальных переменных **/
  data() {
    return {
      showBrigadeTab: 0,                                                                                      // включает отображение вкладок бригад на центральном компоненте графика выходов
      showChaneTab: 0,                                                                                        // включает отображение вкладок звеньев на центральном компоненте графика выходов
      componentKey: 0,
      allBrigadesText: 'Бригады на участке отсутствуют',
      maxWindow: '',                                                                                          // для максимальной ширины
      idWorker: '',                                                                                          // id работника
      openWorkerInfo: false,                                                                                  // переменная отображение карточки сотрудника
      activeModal: '',
      // workers: this.$store.state.workers,
      workCount: 0,
      chosenDate: {
        year: '',
        month: ''
      },
      template: [
        {
          id: '',
          title: 'Выберите участок',
          brigade_list: {}
        }
      ]
    }
  },
  /** !!!!!! ----  вычисляемые свойства объекта. Аля обработчик переменных для верстки - для выноса из блока верстки, дабы не засирать там код **/
  computed: {
    //все бригады на участке
    allBrigadesInDepartment: {
      get() {
        return this.$store.getters.getBrigadeListInDepartment;
      }
    },
    //выбранный департамент
    currentDepartment: {
      get() {
        return this.$store.getters.getCurrentDepartment;
      },
      set(value) {
        this.$store.dispatch("setCurrentDepartment", value);
      }
    },
    //выбранная бригада
    currentBrigade: {
      get() {
        return this.$store.getters.getCurrentBrigade;
      },
      set(brigade) {
        this.$store.dispatch("updateCurrentBrigade", brigade);
      }
    },
    //выбранная вкладка
    currentTab: {
      get() {
        console.log("currentTab. ВЫЧИСЛЯЕМОЕ СВОЙСТВО ПОЛУЧИТЬ ТЕКУЩУЮ ВКЛАДКУ ГЕТ");
        return this.$store.getters.getCurrentTab;
      },
      set(currentTab) {
        console.log("currentTab. ВЫЧИСЛЯЕМОЕ СВОЙСТВО УСТАНОВИТЬ ТЕКУЩУЮ ВКЛАДКУ SET");
        this.$store.dispatch("setCurrentTab", currentTab);
      }
    },
    //список работников в департаменте
    workers: {
      get() {
        return this.$store.getters.getDepartmentWorkers;
      }
    },
    graphList: {
      get() {
        return this.$store.getters.getWorkersGraphic;
      }
    },
    // вкладка из центрального компонента графика выходов
    tabPersonal: {
      get() {
        return this.$store.getters.getTabPersonal;
      },
    },
    //свойтво используется для связи компонента выпадашки в меню фильтра
    fullListOfCompaniesAndDepartments: {
      get()
      {
        return this.$store.getters.getFullListOfCompaniesAndDepartments;
      },
    },
    userSessionData: {
      get() {
        return this.$store.getters.USER_SESSION_DATA;
      }
    }
  },
  /** !!!!!! ---- блок методов работы с графиком выходов **/
  methods: {
    setMaxWindow() {
      this.maxWindow === 'os-shift-shedule-max' ? this.maxWindow = '' : this.maxWindow = 'os-shift-shedule-max';
    },
    /**
     * метод выбора текущего подразделения из списка подразделений
     * @param departmentObj (Object) - объект выбранного подразделения
     */
    setCurrentDepartment(departmentObj){
      console.log("setCurrentDepartment. Начал выполнять метод");
      this.currentDepartment = departmentObj;                                                                  // меняем текущий участок на выбранный из списка
      /**
       * блок обновления данных по выбранному департаменту/участку
       * @type {{companyDepartmentId: (null|number|string), currentMonth: string, currentYear: string}}
       */
      let menuFilterParameters = {
        companyDepartmentId: this.currentDepartment.id,
        currentYear: this.chosenDate.year,
        currentMonth: this.chosenDate.month
      };
      this.$store.dispatch("getGraficMain", menuFilterParameters);
      this.activeModal = '';                                                                                  // скрытие выпадашки для выбора департамента
      this.searchInput = '';
      console.log("setCurrentDepartment. получил данные по графику выходов с сервера");
      /**
       * блок формирования вкладок по бригадам
       */
      if (this.currentBrigade==-1){
        console.log("setCurrentDepartment. отключить отображение вкладок бригад");
        this.showBrigadeTab=0;
      }
      else {
        console.log("setCurrentDepartment. включить отображение вкладок бригад");
        this.showBrigadeTab = 1;
      }
      this.showChaneTab=0;                                                                                    // отключить отображение вкладок звеньев
      this.currentTab = this.tabPersonal;                                                          // устанавливаем вкладку по умолчанию на персонал
      console.log("setCurrentDepartment. Закончил выполнять метод");
    },

    /**
     * Метод сохраняет в объект имя смены и его идентификатор
     * Входные параметры:
     * brigadeObj (Object) - Объект с данными о выбранной бригаде
     */
    setCurrentBrigade(chosenBrigade){
      this.defaultTab = this.tabPersonal;
      this.activeModal = '';
      this.currentBrigade = chosenBrigade;
      console.log("setCurrentBrigade. Начал выполнять метод выбранная бригада = ", chosenBrigade.brigade_id);
      console.log("setCurrentBrigade. Текущая бригада = ", this.currentBrigade);
      if(chosenBrigade.brigade_id===0){
        console.log("setCurrentBrigade. выбраны все бригады = ", chosenBrigade);
        this.showBrigadeTab = 1;
        this.showChaneTab=0;
      }else{
        console.log("setCurrentBrigade. выбрана конкретная бригада = ", chosenBrigade);
        this.showBrigadeTab = 0;
        this.showChaneTab=1;
      }
      this.currentTab = this.tabPersonal;

      //
      //
      //
      //
      //
      //     if(this.currentBrigade.brigade_description !== 'Выберите бригаду' ||
      //         this.currentBrigade.brigade_description !== 'Создать бригаду') {
      //
      //     //формирование запроса для получения графика выходов
      //     let data = {
      //         year: this.chosenDate.year,
      //         month: this.chosenDate.numberMonth+1,
      //         company_department_id: this.currentDepartment.id,
      //         brigade_id: brigadeObj.brigade_id
      //     };
      //
      //     let jsonData = {
      //         controller: 'ordersystem\\WorkSchedule',
      //         method: 'GetGraphicMain',
      //         subscribe: 'GetGraphicMain',
      //         data: JSON.stringify(data)
      //     };
      //
      //     $.ajax({
      //         type: 'post',
      //         url: '../read-manager-amicum',
      //         data: jsonData,
      //         dataType: 'json',
      //         error: function (response) {
      //             this.$alertInfo(response.responseText, ' ', 3);
      //             this.showMessage('Ошибка в получение данных', 'danger');
      //         },
      //         success: async (result) => {
      //             this.showMessage('Данные успешно получены ', 'success');
      //             let payload = {
      //                 brigade_id: brigadeObj.brigade_id,
      //                 month: this.chosenDate.numberMonth + 1,
      //                 year: this.chosenDate.year
      //             }; // объект параметров для получения шаблона графика на полученную бригаду
      //
      //
      //             this.$alertInfo('Получение графика для сотрудников в бригаде', result, 2);
      //
      //             let graphic = await result.Items.ListGrafic;
      //
      //             let workersWithGraph = null;
      //             if (graphic) {
      //                 for (let graph in graphic) {
      //                     payload.grafic_tabel_main_id =  graphic[graph].grafic_main_id;
      //                     this.$store.dispatch("setGraphicTabelMain", graphic[graph].grafic_main_id);
      //                     if (graphic[graph].workers) {
      //                         workersWithGraph = graphic[graph].workers;
      //                         self.$store.dispatch("getWorkersGraphic", graphic[graph].workers);
      //                     }
      //                 }
      //             } else {
      //                 this.$alertInfo('Для данной бригады нет активного графика');
      //                 payload.grafic_tabel_main_id = '-1';
      //                 this.$store.dispatch("setGraphicTabelMain", "-1");
      //             }
      //
      //             await this.$store.dispatch("closeTemplate");                                                // чистка шаблона во время смены бригады
      //             await this.$store.dispatch("getCurrentTemplate", payload);                                    // вызов метода на получение текущего шаблона для бригады
      //
      //             // for(let worker in workersWithGraph) {
      //             //     if(!self.workers[worker]) {
      //             //         console.log('send ajax for get Info about user');
      //             //     }
      //             // }
      //         }
      //     });
      // }
    },
    showMessage(text, type) {
      $.notify({                                                                                                          // Уведомление об успешном добавлении
        message: text
      }, {
        element: 'body',
        placement: {
          from: "top",
          align: "center"
        },
        delay: 30,
        type: type
      });
    },
    /**
     * Отображение мод. окна по сотруднику
     * */
    openEmployeeCard(id) {
      this.idWorker = id;
      this.activeModal="EmployeeCard";
    },
    // getParametersForBrigadeInfo() {
    //     return {
    //         brigade_id: this.currentBrigade.brigade_id,
    //         year: this.chosenDate.year
    //     }
    // },
    /**
     * Метод который проверяет какой пункт выбранн в списке выбора бригад, и в
     * зависимости от выбранного пункта отображает на экране модальное окно
     * "Информация о бригаде" или модальное окно, сообщающее что необходимо выбрать
     * какую нибудь бригаду в списке
     */
    // showBrigadeInfoModal() {
    //     if (this.currentBrigade.id !== ''
    //         && this.currentBrigade.name !== 'Выберите бригаду' || 'Создать бригаду') {
    //         this.changeActiveModal('brigadeInformation')
    //     } else {
    //         this.changeActiveModal('errChoseBrigade')
    //     }
    // },
    /**
     * Метод который устанавливает имя модального окна в переменную activeModal, в следствии чего данное модальное
     * окно отображается на экране
     * Входные параметры:
     * modalName (String) - наименование модального окна
     * Выходные параметры:
     * Отсутствуют
     */
    changeActiveModal(modalName = '') {

      if (modalName === this.activeModal) {
        this.activeModal = '';
      } else {
        this.activeModal = modalName
      }
    },
    /**
     * Когда в компоненте выпадающего меню выбора месяца и года, происходит клик по месяцу, он генерирует
     * событие и отправляет объект с данными (год, месяц). В данном методе мы его и принимаем,
     * подписавышись на событие closeDropDownCalendar
     * Входные параметры:
     * chosenDate (Object) - объект который хранит информацию с выбранным месяцем и годом в модальном окне
     * выбора даты
     * Выходные параметры:
     * Отсутствуют
     */
    changeDate(chosenDate) {
      this.activeModal = '';
      this.chosenDate = chosenDate;
      //this.$store.dispatch('actionsShiftSchedule', chosenDate);                                               // запрос на получение данных при загрузки и смены даты
    },
    setListenerForDocumentClick() {
      /**
       * Метод который подвешивает обработчик события click на весь document (DOM)
       * при наступлении которого закрываются все модальные окна
       */
      document.addEventListener('click', () => this.activeModal = '');
    },
    /**
     * Блок сохранения графика выходов на сервер. получает данные из компонента и отправляет на сервер
     */
    sendDataFromState() {

      console.log('this.choosen brigades ', this.currentBrigade);

      //let brigades = filterObject(this.$store.getters.getAllBrigadesInDepartment, 'flag_save', true);
      let BrigadeId = '';
      let brigadeName = () => {
        let id = '';
        for(let brigade in this.allBrigadesInDepartment) {
          return this.allBrigadesInDepartment[brigade].brigade_id === this.currentBrigade.brigade_id ? id = brigade : this.currentBrigade.brigade_id;
        }
        return id;
      };
      BrigadeId = brigadeName();
      let workersInfo = {};
      let graphicInfoObj = {
        grafic_tabel_main_id: this.$store.getters.gtm,
        workers: this.$store.getters.getWorkersGraphic
      };
      let graficInfo = {};
      this.$set(workersInfo, BrigadeId, this.currentBrigade);
      this.$set(graficInfo, this.$store.getters.gtm, graphicInfoObj);
      workersInfo[BrigadeId].flag_save = true; // TODO разобрать с флагом на сохранение график и нахер он нужен
      let localTemplate = undefined;
      if (this.$store.state.chaneTemplate) {

        localTemplate = {
          [this.$store.getters.gtm]: {
            grafic_table_main_id: this.$store.getters.gtm,
            chane: this.$store.state.chaneTemplate
          }
        };
      }

      let data = {
        year: this.chosenDate.year,
        month: this.chosenDate.numberMonth + 1,
        company_department_id: this.currentDepartment.id,
        workers_info: workersInfo,
        graphic_info: graficInfo,
        template: localTemplate ,                        // TODO решить что делаться newTemplate
        grafic_tabel_main_id: this.$store.getters.gtm
      };
      console.log('data string', data);
      console.log('data string', JSON.stringify(data));

      let jsonData = {
        controller: 'ordersystem\\WorkSchedule',
        method: 'SaveGraphic',
        subscribe: 'SaveGraphic',
        data: JSON.stringify(data)};

      console.log('jsonData', jsonData);
      this.$alertInfo('Сформированный объект data для сохранения', jsonData);
      let self = this;
      $.ajax({
        type: 'post',
        url: '/read-manager-amicum',
        data: jsonData,
        dataType: 'json',
        beforeSend: () => {
          self.$alertInfo("Отправка данных для сохранения графика выходов", ' ', 1);
          $('#preload').removeClass('hidden');
        },
        error:  (response) =>  {
          self.$alertInfo('Ошибка в запросе', response.responseText, 3);
          $('#preload').addClass('hidden');
          this.showMessage('Ошибка в сохранение графика', 'danger');
        },
        success: (result) => {
          self.$alertInfo('Ответ запроса на сохранение графика выходов', result, 2);
          $('#preload').addClass('hidden');
          this.showMessage('График успешно сохранен', 'success');
          // if (result.Items.new_brigade_id) {                                                           //todo: продумать логику после сохранения графика с бригадами, чтобы на фронте записался id созданной бригады, чтобы при повторном сохранении не создавались новые бригады
          //     this.currentBrigade.brigade_id = result.Items.new_brigade_id;
          // }
        }
      });
    },
    setObject(payload) {
      //console.log('payload setObjects', payload);
      for (let i in payload) {
        const payloadElement  = payload[i].plan_schedule;
        if (payloadElement.length === 0) {                                                                          // если график выходов пустой, то добавляем элементы (объекты((дни недели))
          for (let j = 0; j < 31; j++) {
            payloadElement.push(new NewShift(j));
          }
        } else  {                                                                                                   // если массив не пустой, то вызываем функцию на проверку и добавления необходимых элементов
          addShift(payloadElement)
        }
      }

      /**
       * Функция для добавления недостающих дней в массив
       * @param payloadElement - график выхода работника
       * */
      // function addShift(payloadElement) {
      //     //console.log(payloadElement.length)
      //     if (payloadElement.length < 31) {                                                                         // если 31 эл в массиве, то выходим из функции
      //         let day = payloadElement[0].day - 1;                                                                    // иначе берем 1й элемент для проверки,
      //         if (day > 1) {                                                                                          // является ли данный объект первым днем, если нет то
      //             for (let i = day; i > 0; i--) {                                                                     // в цикле добавляем недостающие дни
      //                 payloadElement.unshift(new NewShift(i))
      //             }
      //             addShift(payloadElement);
      //         } else {                                                                                                // иначе в цикле проходим по массив графика выходов
      //             for (let i = 0; i < payloadElement.length; i++) {
      //                 if(payloadElement[i + 1]) {                                                                    // проверка имеется ли следующие элемент в массиве
      //                     if(payloadElement[i].day + 1 !== payloadElement[i + 1].day) {                              // если элемент имеется, то проверяем является ли следующий элемент массив следующим днем, если нет то
      //                         addStartAndEnd(payloadElement, payloadElement[i].day,  payloadElement[i + 1].day - 1)  // вызываем функцию и передаем аргументы сколько дней необходимо добавить
      //                     }
      //                 } else {                                                                                       // если последующего элемента в массиве нет, вызываем функцию с передачей аргументов на достройку оставшихся дней
      //                     addStartAndEnd(payloadElement, payloadElement[i].day + 1, 32)
      //                 }
      //             }
      //             addShift(payloadElement);
      //         }
      //     } else {
      //         return
      //     }
      // }

      /**
       * Функция для добавления дней в массив начиная и заканчивая по заданным параметрам
       * @param payloadElement - график выхода работника
       * */
      // function addStartAndEnd(payloadElement, start, end) {
      //     //console.log(start, ' - ', end)
      //     for (let i = start; i < end; i++) {
      //         payloadElement.splice(i, 0, new NewShift(i))
      //     }
      // }

      /**
       * Конструктор для создания объекта
       * */
      // function NewShift(day) {
      //     this.day = day + 1;
      //     this.shifts = [
      //         {
      //             working_time_id: '',	    		        // Вид выхода/не выхода (Пример: Прогул - 21, Вахта - 2 и.т.д.)
      //             shift_id: 1,		    		                            // Идентификатор смены
      //             hrs_plan_value: 0,                                               // Количество часов для отработки
      //             short_title: '',
      //         }
      //     ]
      // }
    },
  },
  /** !!!!!! ---- хук (жизненный цикл страницы - события при инициализации самой страницы - объекта VUE) **/
  /** при создании страницы **/
  created() {
    console.log("created. Начал инициализировать объекты");
    this.$store.dispatch('setDefaultData');                                                                     // вызов из стора данных по умолчанию - справочных данных (ролей, работников, департаментов)
    console.log("created. Закончил инициализировать объекты");
  },
  beforeMount() {

  },
  /** виртуальный дом встроен в реальный - браузер отрендерил страницу **/
  mounted() {
    console.log("mounted. Начал инициализировать объекты");
    let menuFilterParameters = {
      companyDepartmentId: this.currentDepartment.id,
      currentYear: this.chosenDate.year,
      currentMonth: this.chosenDate.month
    };
    this.$store.dispatch("getGraficMain", menuFilterParameters);                                                        // функция вызывает экшен - само действие - отправляет запрос на получение самих данных

    this.setListenerForDocumentClick();                                                                         // навешываем клик на любой элемент страницы - для закрытия контекстных меню или модалок
    console.log("mounted. Закончил инициализировать объекты");
  },
}


</script>

<style scoped lang="less">

* {
  font-family: Arial, sans-serif;
}

@green-color: #4d897c;
/*colors*/
@green: rgba(178, 214, 60, 1);
@orange: rgba(239, 127, 26, 1);
@bluePurple: rgba(86, 105, 143, 1);
@turquoise: rgba(77, 137, 124, 1);
@dirtyPurple: rgba(106, 113, 128, 1);
@blue: rgba(89, 141, 155, 1);
@purple: rgba(124, 101, 128, 1);
@dirtyGray: rgba(208, 202, 181, 1);
@lightBlue: rgba(153, 192, 202, 1);
@gray: rgba(230, 230, 230, 1);
@replacingRed: rgba(181, 90, 110, 1);

.errChoseBrigadeWrapper {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, .5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.errChoseBrigade {
  padding: 30px;
  background-color: #fff;

  &__text {

  }

  &__btn {
    padding: 10px;
    color: #fff;
    background-color: #56698F;
    border: none;
    cursor: pointer;
    margin-top: 20px;

    &:hover {
      background-color: #677eac;
    }
  }
}

.blockForPrint {
  display: none;
  @media print {
    display: block;
  }

  &__signaturesWrapper {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__signaturesLeft {
    text-align: left;
    margin-left: 50px;
  }

  &__signaturesRight {
    text-align: right;
    margin-right: 50px;
  }

  &__footer {
    text-align: right;
    margin-top: 100px;
    margin-right: 50px;
  }
}

.brigadeListSelect, .departmentListSelect {
  position: absolute;
  background-color: #fff;
  /*top: 30px;*/
  /*left: calc(50% - 175px);*/
  width: 350px;
  max-height: 300px;
  z-index: 9999;
  overflow: auto;
  box-shadow: 4px 4px 4px 0 rgb(114, 114, 114);
  text-align: left;
  p {
    padding: 5px;
    margin: 0;
    border-bottom: 1px solid #eee;
    cursor: pointer;

    &:hover {
      background-color: #eee;
    }
  }
}

.calendarModal {
  position: absolute;
  background-color: #fff;
  top: 30px;
  z-index: 9999;
  left: 50%;
  margin-left: -125px;
}

.brigadesInformationModal {
  position: fixed;
  background-color: rgba(0, 0, 0, .7);
  width: 100vw;
  height: 100vh;
  top: 0;
  left: 0;
  padding-top: 50px;
  min-width: 1024px;
  z-index: 9999;
  @media (max-width: 1600px) {
    padding: 0;
  }

  &__content {
    position: relative;
    margin: 0 auto;
    width: 80%;
    height: 90%;
    z-index: 9999;
    @media (max-width: 1600px) {
      width: 100%;
      height: 100%;
    }
  }
}


.components {
  width: 98%;
  margin: 0 auto;
  /*overflow: hidden;*/
  position: relative;
  display: flex;
  height: calc(100% - 60px);
  /*padding-bottom: 30px;*/

}

.os-shift-shedule {
  display: flex;
  flex-flow: row nowrap;
  background-color: #e6e6e6;
  width: 100%;
  height: calc(100vh - 120px);
  min-height: 484px;
  padding-bottom: 15px;
  min-width: 1024px;
  margin: 0 auto;
  @media print {
    width: 100%;
    flex-direction: column;
  }
}
.os-shift-shedule-max {
  width: 100%;
  overflow: auto;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 9999;
  height: 100vh;
}

.shift {
  position: relative;
  width: 90%;
  height: 100%;
  @media print {
    width: auto;
  }
}

.shift-charge-control {
  width: 10%;
  color: #fff;
  height: 100%;
  display: flex;
  flex-direction: column;
  @media print {
    display: none;
  }
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  width: 100%;
  height: 30px;
  padding: 0 20px;
  background: #fff;
  @media print {
    display: none;
  }

  &-logo {
    img {
      height: 25px;
    }
  }

  &-title {
    h1 {
      margin: 0;
      padding: 0;
      font-size: 16px;
    }
  }

  &-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    button {
      border: none;
      background-color: transparent;
      padding: 0;
      margin-left: 3px;
      margin-right: 3px;
    }
    &-maxWindow {
      img {
        height: 12px;
      }
    }
    &-close {
      height: 14px;
      width: 12px;
      span {
        display: inline-block;
        position: relative;
        width: 100%;
        height: 100%;

        &:before, &:after {
          content: "";
          display: block;
          height: 2px;
          width: 100%;
          position: absolute;
          top: 6px;
          left: 0;
          background-color: #4C4B49;
        }
        &:before {
          -webkit-transform: rotate(45deg);
          -moz-transform: rotate(45deg);
          -ms-transform: rotate(45deg);
          -o-transform: rotate(45deg);
          transform: rotate(45deg);
        }
        &:after {
          -webkit-transform: rotate(-45deg);
          -moz-transform: rotate(-45deg);
          -ms-transform: rotate(-45deg);
          -o-transform: rotate(-45deg);
          transform: rotate(-45deg);
        }
      }
    }
  }
}


.headerButtons {
  display: flex;
  justify-content: center;
  height: 30px;
  margin: 0 auto;
  @media print {
    display: none;
  }

  &__calendarWrapper {
    position: relative;
    width: 20%;
    min-width: 230px;
  }

  &__buttonTrapezium {
    position: relative;
    background-color: #fff;
    width: 20%;
    clip-path: polygon(0 0, 100% 0, 90% 100%, 10% 100%);
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
    width: 95%;
    height: 26px;
    background-color: #B2D63C;
    clip-path: polygon(0 0, 100% 0, 91% 100%, 9% 100%);
    display: flex;
    align-items: center;
    justify-content: center;

    &:hover {
      background-color: #c6eb41;
    }
    .headerButtons__departmentText {
      overflow: hidden;
      -ms-text-overflow: ellipsis;
      text-overflow: ellipsis;
      max-width: 75%;
      white-space: nowrap;
    }
    p {
      margin: 0;
      padding: 0;
    }

    &::before {
      position: absolute;
      top: 0;
      content: '';
      display: block;
      width: 100%;
      height: 50%;
      background-image: linear-gradient(rgba(255, 255, 255, 0), rgba(255, 255, 255, .3));
    }
  }

  &__buttonParallelogram {
    position: relative;
    background-color: #fff;
    width: 15%;
    clip-path: polygon(25% 0, 100% 0, 75% 100%, 0 100%);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__buttonParallelogramBg {
    width: 88%;
    background-color: #B2D63C;
    height: 26px;
    clip-path: polygon(25% 0, 100% 0, 75% 100%, 0 100%);
    display: flex;
    align-items: center;
    justify-content: center;

    &:hover {
      background-color: #c6eb41;
    }

    &::before {
      position: absolute;
      top: 0;
      content: '';
      display: block;
      width: 100%;
      height: 50%;
      background-image: linear-gradient(rgba(255, 255, 255, 0), rgba(255, 255, 255, .3));
    }
  }
  &__departmentWrapper {
    position: relative;
    width: 20%;
    min-width: 230px;
  }
  &__department {
    width: 100%;
    height: 100%;
    background-color: #fff;
    clip-path: polygon(0 0, 100% 0, 90% 100%, 10% 100%);
    position: relative;
  }
  &__departmentTriangle {
    content: '';
    display: block;
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 8px solid #000000;
    margin-left: 10px;

    &_rotated {
      transform: rotate(180deg);
    }
  }

  &__brigadeWrapper {
    position: relative;
    width: 20%;
    margin-right: -50px;
    min-width: 230px;
  }

  &__brigade {
    width: 100%;
    height: 100%;
    background-color: #fff;
    clip-path: polygon(0 0, 100% 0, 90% 100%, 10% 100%);
    position: relative;
  }

  &__brigadeTriangle {
    content: '';
    display: block;
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 8px solid #000000;
    margin-left: 10px;

    &_rotated {
      transform: rotate(180deg);
    }
  }


  &__calendarIcon {
    width: 20px;
    margin-left: 30px;
  }

  &__brigadeInfo {

    min-width: 80px;
    width: 7%;
  }

  &__brigadeInfoIcon {
    width: 17px;
  }
}

</style>

<style>
@media print {
  #navbar, footer{
    display:none;
  }
}
</style>

