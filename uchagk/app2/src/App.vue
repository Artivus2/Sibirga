<template>

  
    
      <router-view/>
  
      <!-- <MainChatWindow/> -->
  
      <!-- <QuickAccessToolbar v-if="visibleQuickAccessToolbar"/> -->
  
      <!-- <login-modal v-if="loginModalVisibilityFlag"
                   @closeLoginModalWindow="returnToMainPage"/> -->
  
      <!-- Кнопка "Молния" -->
      <!-- <lightning/> -->
  
      <!--Прелоадер загрузки данных-->
  <!--    <waiting-for-data-->
  <!--        :preloaderShow="false"-->
  <!--        :preloaderShowGroup="preloaderShowGroup"-->
  <!--        @close-preloader="closePreloader"-->
  <!--    />-->
  
    </template>
  
  <script>
    //import LoginModal from "./components/loginModal/loginModal.vue";                                                            // окно до авторизации
  // import Lightning from "./views/Lightning";                                                                              // молнии (уведомления ПБ)
  //const QuickAccessToolbar = () => import ("./modules/order-system/views/QuickAccessToolbar");                            // панель быстрого доступа к модулям ЭКВН
  
  export default {
  
  components: {
    // WaitingForData,
    // Lightning,
    //LoginModal,
    
    //QuickAccessToolbar,
  },
  data() {
    return {
  
      routeTitles: {                                                                                                    // список названий для вкладок, для ключа используется значение $route.name
        'amicum': 'Нарядная система',
  
  
        // Админка
        'AdminPanel': 'Личный кабинет администратора',
        'UserAccessSettings': 'Настройка прав доступа',
        'AssigningUserRights': 'Выдача прав пользователя',
        'AmicumUpdInfo': 'Обновления системы',
        'ArchiveUpd': 'Архив обновлений',
        'ArchiveWishUser': 'Архив пожеланий',
        'AddWish': 'Добавить пожелание',
  
        //учет АГК upd 01.11.2023
        'uchetagz': 'Система контроля за эксплуатацией оборудования аэрогазовой защиты',

        // Нарядная система
        'order-system': 'Электронная книга нарядов',
        'os-previous-period-report': 'Отчет за предыдущий период',
        'table-form': 'Табличная форма выдачи наряда',
        'interactive-form': 'Интерактивная форма выдачи наряда',
        'os-shift-schedule': 'График выходов',
        'work-modes-history': 'История изменения режимов работы',
        'fill-report': 'Отчет горного мастера',
        'order-route-map': 'Маршрутная карта выдачи нарядов',
        'OrderAb': 'Выдача наряда на производство работ по линии АБ (ВТБ)',
        'OrderAbMatching': 'Согласование наряда на производство работ',
        'OrderApproving': 'Общешахтная книга нарядов',
        'accounting-for-materials': 'Учет материала',
        'downtime-accounting': 'Учет простоев',
        'logbook-order': 'Журнал регистрации наряд-допусков',
        'OrderRestrictions': 'Ограничения по наряду',
        'OrderFailureReasons': 'Причины невыполнения наряда',
        'PrintedFormOrderV2': 'Печатная форма Книга нарядов',
        'PrintedFormVoucherV2': 'Печатная форма Наряд-путевка горного мастера',
  
  
        // Маршруты
        'routes': 'Маршруты',
        'routes-comparison': 'Журнал сравнения установленных и фактических маршрутов движения персонала',
        'routes-interactive': 'Сравнение установленного и фактического маршрута движения персонала',
        'constructor-routes': 'Конструктор шаблонов маршрутов',
        'forbidden-zone-interactive': 'Контроль запретных зон',
        'forbidden-zone-journal': 'Журнал запретных зон',
  
  
        // Электронная библиотека
        'DigitalLibrary': 'Электронная библиотека',
        'PassportObjects': 'Паспорта объектов',
        'ArchiveOfMessagesLightning': 'Архив сообщений "Молния"',
  
  
        // Книга предписаний
        'bookDirective': 'Книга предписаний',
        'FillInjunctionIssue': 'Формирование предписания',
        'FillViolationIssue': 'Формирование ПАБ',
        'ArchiveInjunctionPba': 'Архив Предписаний и ПАБ',
        'book-directive-statistic': 'Статистика Предписаний и ПАБ',
        'book-directive-planned-audits': 'График плановых аудитов',
  
  
        // сравнение 2-х газов
        'MethaneAnalysis': 'Анализ показателей дублирующего контроля',
        'SensorComparison': 'Журнал событий по индивидуальным датчикам',
        'SensorCH4Comparison': 'Журнал событий по стационарным датчикам CH4',
        'SensorsStatistics': 'Сводная статистика датчиков CH4',
        'SituationStatistics': 'Сводная статистика по ситуациям',
        'AGKOperatorJournal': 'Оперативный журнал инженера-оператора АГК(АБ)',
        'IndividualSensorsStatistics': 'Сводная статистика по расхождениям',
        'StationarySensorsStatistics': 'Сводная статистика по расхождениям стационарных датчиков',
  
  
        // Статистическая отчетность
        'ReportStatistics': 'Статистическая отчетность',
  
  
        // ОТ и ПБ
        'occupational_health_and_safety': 'ОТ и ПБ',
        // 'medicalExaminationPlanning': 'Планирование и контроль медосмотров',
        // 'medicalExaminationControl': 'Учет медосмотров',
        'medicalExaminationPlanningAndControl': 'Планирование и контроль медосмотров',
        'MedicalExaminationCheckup': 'Учет медосмотров',
        'medical-plan-schedule': 'Планирование медосмотров',
        'MedicalExaminationGraph': 'График прохождения медосмотров',
        'MedicalExaminationPlanOrder': 'Приказ на организацию и проведение медосмотров',
        'PaymentTimeMedicalExamination': 'Журнал учета оплаты времени прохождения предсменных МО',
        'JournalStartEndShift': 'Журнал учета начала/окончания смены',
        'JournalPredExam': 'Журнал прохождения предсменного экзаменатора',
        'MedicalDiseaseBook': 'Учет профзаболеваний',
        'ContingentHandbook': 'Список контингентов',
        'injuryAccounting': 'Учет травматизма и происшествий',
        'SizControl': 'Контроль наличия средств индивидуальной защиты',
  
        // Инструктажи
        'BriefingsControlSection': 'Контроль автоматизированного прохождения инструктажей, проверок знаний, аттестаций',
  
        // СПБ
        'fireSafetyControl': 'Контроль средств пожарной безопасности',
  
        // ЭПБ
        'accountingExpertise': 'Учет, проведение и планирование экспертизы промышленной безопасности',
  
        // СОУТ
        'sout': 'Специальная оценка условий труда и санитарный производственный контроль',
        'workingConditionsPlanner': 'График проведения СОУТ/ПК',
        'contractingOrganization': 'Подрядные организации',
  
        // Иные нарушения
        'registrationOfViolations': 'Учет нарушений и иных сведений о персонале',
  
  
        // Справочники системы
        'HandbookAccess': 'Справочник прав пользователя',
        'HandbookBrigade': 'Справочник бригад',
        'HandbookChatAttachmentType': 'Справочник типов вложения',
        'HandbookChatType': 'Справочник типов чата',
        'HandbookChecking': 'Справочник типа проверки ПК',
        'HandbookCheckKnowledge': 'Справочник причин проверки знаний',
        'HandbookCyclegramm': 'Справочник типа циклограммы',
        'HandbookDepartment': 'Справочник типов подразделений',
        'HandbookDiseases': 'Справочник диагнозов',
        'HandbookEdge': 'Справочник типов выработки ',
        'HandbookKindReason': 'Справочник видов причин ситуаций',
        'HandbookFireFighting': 'Справочник средств пожаротушения',
        'HandbookHarmfulFactors': 'Справочник вредных факторов',
        'HandbookInjury': 'Справочник обстоятельств',
        'HandbookInternship': 'Справочник причин стажировок',
        'HandbookKindCrash': 'Справочник видов аварий',
        'HandbookKindDirectionStore': 'Справочник направления списания материалов',
        'HandbookKindDocument': 'Справочник видов документов',
        'HandbookKindDuration': 'Справочник видов остановок',
        'HandbookKindFirePreventionInstruction': 'Справочник видов противопожарного инструктажа',
        'HandbookKindIncident': 'Справочник видов инцидентов',
        'HandbookKindMishap': 'Справочник видов несчастных случаев',
        'HandbookKindParameter': 'Справочник видов параметров',
        'HandbookKindViolation': 'Справочник видов нарушений',
        'HandbookMedReportResult': 'Справочник заключений медкомиссии',
        'HandbookPositions': 'Справочник должностей',
        'HandbookReasonOccupationalIllness': 'Справочник причин профзаболеваний',
        'HandbookRepairMap': 'Справочник видов ремонта',
        'HandbookRoles': 'Справочник ролей',
        'HandbookSituation': 'Справочник уровней риска',
        'HandbookSout': 'Справочник типов проверок СОУТ',
        'HandbookStopPb': 'Справочник видов простоев ПБ',
        'HandbookPlace': 'Справочник типов мест',
        'HandbookParameter': 'Справочник типов параметра',
        'HandbookSeason': 'Справочник сезонов',
        'HandbookHandbooks': 'Справочник системы',
        'HandbookESMO': 'Справочник допусков ЭСМО',
        'HandbookESMOAllowance': 'Справочник результатов ЭСМО',
        'HandbookSensor': 'Справочник типов сенсора',
        'HandbookInjuryOutcome': 'Справочник исходов травмы',
        'HandbookNomenclature': 'Справочник номенклатуры',
        'HandbookGroupOperation': 'Справочник групп операций',
        'HandbookOperationGroup': 'Справочник привязки операций и групп операций',
        'HandbookOperationType': 'Справочник типов операций',
        'HandbookOperationKind': 'Справочник видов операций',
        'HandbookForbiddenType': 'Справочник типов запретных зон',
        'HandbookGroupAlarm': 'Справочник групп оповещения',
        'HandbookInstructionPb': 'Справочник инструктажей ПБ',
        'HandbookInstructionType': 'Справочник типов инструктажей ПБ',
        'HandbookBriefingReason': 'Справочник причин инструктажа',
        'HandbookIndustrialSafetyObjectType': 'Справочник типов объекта для ЭПБ',
        'HandbookIndustrialSafetyObject': 'Справочник объектов ЭПБ',
        'HandbookMaterial': 'Справочник материалов',
        'HandbookResearchType': 'Справочник типов исследований',
        'HandbookResearchIndex': 'Справочник показателей исследований',
        'HandbookShift': 'Справочник смен',
        'HandbookKindWorkingTime': 'Справочник вида рабочего времени',
        'HandbookTypeOperation': 'Справочник типов операций ТП (выемка)',
        'HandbookKindAccident': 'Справочник видов происшествий приведших к несчастным случаям на производстве',
        'HandbookWorkerType': 'Справочник типов работника',
        'HandbookStatusType': "Справочник типов статуса",
        'HandbookParagraphPb': "Справочник пунктов ПБ",
        'HandbookVidDocument': "Справочник видов документов",
        'HandbookCheckKnowledgeType': "Справочник типов проверки знаний",
        'HandbookTypeBriefing': "Справочник типов инструктажей",
        'HandbookTypeAccident': "Справочник типов НС",
        'HandbookOperation': "Справочник операций",
        'HandbookChaneType': "Справочник типов звеньев",
        'HandbookChane': "Справочник звеньев",
        'HandbookKindObject': "Справочник видов объектов",
        'HandbookObjectType': "Справочник типовых объектов",
        'HandbookViolationType': "Справочник типов нарушений",
        'HandbookWorkingPlace': "Справочник рабочих мест",
        'HandbookKindSiz': "Справочник видов СИЗ",
        'HandbookGroupSiz': "Справочник групп СИЗ",
        'HandbookSubgroupSiz': "Справочник подгрупп СИЗ",
        'HandbookSiz': "Справочник СИЗ",
        'HandbookXmlSendType': "Справочник типов XML выгрузки",
        'HandbookXmlTimeUnit': "Справочник единиц измерения XML выгрузки",
        'HandbookGroupMedReportResult': "Справочник групп медицинских заключений",
        'HandbookClassifierDiseasesKind': "Справочник видов классификатора заболевания",
        'HandbookClassifierDiseasesType': "Справочник типов классификатора заболевания",
        'HandbookClassifierDiseases': "Справочник классификатора заболевания",
        'HandbookAmicumModules': "Справочник модулей системы",
        'HandbookUsersSystem': "Справочник пользователей системы",
        'HandbookPages': "Справочник разделов системы",
        'HandbookWorkstation': "Справочник АРМов",
      },
      visibleQuickAccessToolbar: false,                                                                                 // состояние видимости панели быстрого доступа в книге нарядов
      preloaderShowGroup: {},                                                                                           // список объектов на загрузку
    }
  },
  computed: {
    loginModalVisibilityFlag: {
      get() {
        return this.$store.getters.LOGIN_MODAL_VISIBILITY_FLAG;
      },
      set(newFlag) {
        this.$store.dispatch('switchLoginModalVisibilityFlag', newFlag);
      }
    },
  
  },
  methods: {
  
    // метод закрытия прелоадера по двойному клику
    closePreloader() {
      this.preloaderShow = false;
    },
  
    showLoginForm() {
      this.loginModalVisibilityFlag = true;
    },
    returnToMainPage(status) {
      this.loginModalVisibilityFlag = false;
      if (status === 'closedStatus') {
        document.location.href = '/';
      }
    },
    // проверка видимости панели быстрого доступа книги нарядов
    checkedVisibleQuickAccessToolbar() {
      let splitBrowserUrl = location.pathname.substring(1).split('/'),                                          // разделение адресной строки браузера и перегон ее в массив
          browserUrl = splitBrowserUrl[splitBrowserUrl.length - 1];                                                     // название страницы взятое из адресной строки
      if (
          browserUrl === 'table-form' ||                                                                                // табличная форма выдачи наряда
          browserUrl === 'logbook-order' ||                                                                             // журнал регистрации наряд допусков
          browserUrl === 'os-shift-schedule' ||                                                                         // график выходов
          browserUrl === 'briefings-control' ||                                                                         // инструктажи
          browserUrl === 'fill-report' ||                                                                               // заполнить отчет
          browserUrl === 'accounting-for-materials' ||                                                                  // учет материалов
          browserUrl === 'previous-period-report' ||                                                                    // отчет за предыдущие сутки
          browserUrl === 'order-route-map' ||                                                                           // маршрутная карта выдачи наряда
          browserUrl === 'order-restrictions' ||                                                                        // ограничения по наряду
          browserUrl === 'order-failure-reasons' ||                                                                     // Причины невыполнения наряда
          browserUrl === 'order-approving' ||                                                                           // Общешахтная книга выдачи нарядов
          browserUrl === 'order-ab-matching' ||                                                                         // Согласование наряда на производство работ
          browserUrl === 'order-ab' ||                                                                                  // Выдача наряда на производство работ по линии АБ (ВТБ)
          browserUrl === 'downtime-accounting'                                                                          // учет простоев
      ) {
        this.visibleQuickAccessToolbar = true;
      }
    },
  },
  
  created() {
    //webSocket.setWebSocketConnection();
  },
  mounted() {
    window.vueProject = this;
  },
  beforeUpdate() {
    // console.info("App.beforeUpdate. АМИКУМ: перед обновлением...");
  
    // проверяем, что название пути находится в списке названий,
    // оно там есть, то устанавливаем название вкладки из списка,
    // если нет, то задаём по умолчанию - "Нарядная система"
    if (hasProperty(this.routeTitles, this.$route.name)) {
      document.title = this.routeTitles[this.$route.name];
    } else {
      document.title = 'Нарядная система';
    }
  },
  updated() {
    // console.info("App.updated. АМИКУМ: обновлено!");
    this.checkedVisibleQuickAccessToolbar();
  },
  
  
  }
  </script>
  <style lang="less">
  // * {
  //   margin: 0;
  //   padding: 0;
  // }
  
  // #app {
  //   //border: 1px solid red;
  //   //height: calc(100vh - 180px);
  //   padding-bottom: 10px;
  //   font-family: Arial, sans-serif;
  //   -webkit-font-smoothing: antialiased;
  //   -moz-osx-font-smoothing: grayscale;
  //   text-align: center;
  //   color: #2c3e50;
  //   margin: 0 15px;
  // }
  
  // #nav {
  //   padding: 30px;
  
  //   a {
  //     font-weight: bold;
  //     color: #2c3e50;
  
  //     &.router-link-exact-active {
  //       color: #42b983;
  //     }
  //   }
  // }
  
  // footer {
  //   margin: 0;
  // }
  
  // :root {
  //   --white: #ffffff;
  //   --black: #000000;
  //   --border-gray: #e6e6e6;
  //   --border-bottom-gray: #aeaeae;
  //   --tab-gray: #b2b3b5;
  //   --green: rgba(178, 214, 60, 1);
  //   --orange: rgba(239, 127, 26, 1);
  //   --bluePurple: rgba(86, 105, 143, 1);
  //   --turquoise: rgba(77, 137, 124, 1);
  //   --dirtyPurple: rgba(106, 113, 128, 1);
  //   --blue: rgba(89, 141, 155, 1);
  //   --purple: rgba(124, 101, 128, 1);
  //   --dirtyGray: rgba(208, 202, 181, 1);
  //   --lightBlue: rgba(153, 192, 202, 1);
  //   --gray: rgba(230, 230, 230, 1);
  //   --replacingRed: rgba(181, 90, 110, 1);
  // }
  
  </style>
  <style>
/*   
  @media print {
    #navbar, footer {
      display: none;
    }
  } */
  
  </style>