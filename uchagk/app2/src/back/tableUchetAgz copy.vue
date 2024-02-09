
<template>
  <div class="order-route-map-container">

    <div class="main-content">
      <!-- <div class="top-row hidden-print">

        
        <div class="headerButtons hidden-print">



          <div class="headerButtons__calendarWrapper">
            <div class="headerButtons__calendar headerButtons__buttonTrapezium"
                 @click.stop="changeActiveModal('calendarModal')">
              <div class="headerButtons__buttonTrapeziumBg">
                <p>
                  <span>{{ chosenDate.monthTitle }} </span>
                  <span>{{ chosenDate.year }}</span>
                </p>
                <img class="headerButtons__calendarIcon"
                     src="@/assets/workShedule/calendar.svg"
                     alt="календарь">
              </div>
            </div>
            <div class="calendarModal" v-show="activeModal === 'calendarModal'">
              <calendar @closeDropDownCalendar="changeActiveModal"
                        @setCurrentDate="changeChosenDate"
              />
            </div>
          </div>

          <div class="department-filter">
            <div class="header__titleSkewedLeft"></div>
            <div class="header__titleSkewedRight"></div>
            <div class="header__departmentBtn" @click.stop="changeActiveModal('departmentList')">
              <p class="department-filter-title"><span>{{ chosenDepartment.title }}</span>
                <i class="header__departmentTriangle header-triangle"
                   :class="{'openedDepartmentList': activeModal === 'departmentList'}"></i>
              </p>
            </div>
            <div class="departmentDropDownList" v-show="activeModal === 'departmentList'">
              <department-list @setDepartmentInFilter="changeCurrentDepartment"
                               :company-departments="allCompanyDepartments"
                               @closeListDepartment="changeActiveModal"
              />
            </div>
          </div>

          <MenuTileWithDropdown
              :nameList="'mineList'"
              :all-tabs="allMines"
              :active-modal="activeModal"
              :chosenTab="chosenMine"
              @changeTab="changeMine"
              @changeActiveModal="changeActiveModal"
          />
        </div>

        <router-link to="/order-system/order-failure-reasons" class="order-failure-reasons-btn hidden-print">
          <span>Причины невыполнения наряда</span>
        </router-link>

        <router-link to="/order-system/order-restrictions" class="order-restrictions-btn hidden-print">
          <span>Ограничения по наряду</span>
        </router-link>
      </div> -->

      <!-- Заголовок таблицы -->
      <div class="table-header">
        <div class="table-column"
             @click.stop="sortOrders('company_department_title')">
          <span>№ п/п</span>
          <div class="icon-sort-none" v-if="sortedField !== 'company_department_title'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.company_department_title"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
        <div class="table-column"
             @click.stop="sortOrders('shift_id')">
          <span>Наименование <br/>подразделения</span>
          <div class="icon-sort-none" v-if="sortedField !== 'shift_id'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.shift_id"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
        <div class="table-column"
             @click.stop="sortOrders('creating_worker_full_name')">
          <span>Зав. №</span>
          <div class="icon-sort-none" v-if="sortedField !== 'creating_worker_full_name'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.creating_worker_full_name"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
        <div class="table-column"
             @click.stop="sortOrders('created_worker_full_name')">
          <span>Год выпуска</span>
          <div class="icon-sort-none" v-if="sortedField !== 'created_worker_full_name'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.created_worker_full_name"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
        <div class="table-column"
             @click.stop="sortOrders('matched_worker_full_name')">
          <span>Срок службы (лет)</span>
          <div class="icon-sort-none" v-if="sortedField !== 'matched_worker_full_name'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.matched_worker_full_name"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
        <div class="table-column"
             @click.stop="sortOrders('count_accept_worker')">
          <span>Место установки</span>
          <div class="icon-sort-none" v-if="sortedField !== 'count_accept_worker'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.count_accept_worker"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
      </div>
      
     
        <div class="orders-container">
          
          <div class="col-6">
      <h3>Draggable {{ draggingInfo }}</h3>

      <draggable
        :list="sensorsdr"
        item-key="id"
        :disabled="!enabled"
        
        ghost-class="ghost"
        :move="checkMove"
        @start="dragging = true;"
        @end="dragging = false"
      >
      <template #item = "{sensorsdr}">
      <div
          v-for="element in sensorsdr"
          :key="element.id"
        >
          {{ element.title }}
        </div>
      </template> 
      </draggable>
    </div>
            
        <!-- <draggable 
        :list="list"
        :disabled="!enabled"
        class="list-group"
        ghost-class="ghost"
        :move="checkMove"
        @start="dragging = true"
        @end="dragging = false"
        >
          <template #item = "{ sensorsdr }">
            <div class="table-column"><span>{{ sensorsdr }}</span></div>
            
          <div class="table-body"  v-for="(sensors) in sensorsdr">
        
              <div class="table-column"><span>{{ sensorsdr.title }}</span></div>
              <div class="table-column"><span>{{ sensors.zavod_id }}</span></div>
              <div class="table-column"><span>{{ sensors.madate }}</span></div>
              <div class="table-column"><span>{{ getdiff(sensors.madate) }}</span></div>
              <div class="table-column"><span>{{ sensors.location }}</span></div>
            
            </div>
          </template>  
            </draggable>     -->
            
        </div>
    
</div>  
</div>
    

    <!-- <order-information v-if="activeModal === 'OrderInformation'"
                       :ordersObject="ordersObject"
                       :selectedDate="selectedDate"
                       :selectedOrderId="selectedOrderId"
                       :selectedShift="selectedShift"
                       :chosenDepartment="chosenDepartment"
                       @closeAllModalAndDropdownWindows="closeAllModalAndDropdownWindows"/>

    <div id="orderContextMenuWrapper" v-if="activeModal === 'orderContextMenu'">
      <order-context-menu
          @goToFillReportPage="goToFillReportPage"
          @goToOrderPage="goToOrderPage"
      />
    </div>

  </div> -->

</template>

<script>

import moment from 'moment';
import draggable from 'vuedraggable';


export default {

  name: "tableUchetAgz",
  props: {
    options: {
      type: Array,
      required: true,
    },
    mineindex: {
      type: Number,
      required: false,
      default: 0,
    },
    
    
  },
  components: {
    draggable,
    // OrderInformation,                                                                                           // Модальное окно с информацией о наряде
    // baseHeader,                                                                                                 // Шапка компонента
    // calendar,                                                                                                   // Календарь
    // departmentList,                                                                                             // Выпадающий список департаментов
    // MenuTileWithDropdown                                                                                        // Выпадающий список шахт

  },

  data() {
    return {
      maxWindowClass: 'fullscreen-mode',                                                                                     // переменная для переключения полноэкранного режима
      sensorsdr: this.options,
      enabled: true,
      dragging: false,
      
      selected: this.default
        ? this.default
        : null,
      // selectedDate: null,                                                                                     // текущая выбранная дата
      // selectedOrderId: null,                                                                                  // текущий выбранный наряд
      // selectedShift: null,                                                                                    // текущая выбранная смена

      chosenDepartment: {                                                                                     // выбранный департамент
        id: null,                             // id депаратамента
        title: ''                             // наименование компании
      },
      
      chosenDate: {                                                                                           // выбранная дата
        monthNumber: new Date().getMonth(),                                                                 // месяц(по умолчанию текущий)
        year: new Date().getFullYear(),                                                                     // год(по умолчанию текущий)
      },
      
      activeOrders: {},                                                                                       // переменная для развертывания/скрытия списка нарядов

      filterProps: {},                                                                                        // объект со списком флагов для сортировки
      sortedField: null,                                                                                      // Содержит наименование пол для сортировки

      selectedOrder: null
    }
  },

  computed: {
    draggingInfo() {
      return this.dragging ? "under drag" : "";
    },
    /**
     * вычисляемое свойство - получение выбранной шахты по-умолчанию
     */
    chosenMine: {
      get() {
        return localStore.getters.CHOSENMINE;
      },
      set(newMineId) {
        return localStore.dispatch('setChosenMine', newMineId);
      }
    },
    /**
     * вычисляемое свойство - получение списка шахт
     */
    allMines: {
      get() {
        return this.$store.getters.HANDBOOKMINE;
      }
    },

    /**
     * Массив для построения списка и его сортировка
     */
    ordersArray() {

      let orderArray = localStore.getters.ORDER_ARRAY;                                                        // присвоение переменной исходного массива
      let result = [];

      //сортировка исходного списка по дате по убыванию
      orderArray.sort((a, b) => {
        if (new Date(a.date_time_source) > new Date(b.date_time_source)) {
          return -1;
        } else if (new Date(a.date_time_source) < new Date(b.date_time_source)) {
          return 1;
        }
        return 0;
      });

      // сортировка списка
      let orderArrayLength = orderArray.length;
      for (let i = 0; i < orderArrayLength; i++) {
        result[i] = {
          date_time: orderArray[i].date_time,
          date_time_source: orderArray[i].date_time_source,
          orders: []
        };
        let ordersLength = orderArray[i].orders.length;
        for (let j = 0; j < ordersLength; j++) {
          let createdWorkerFullName = orderArray[i].orders[j].shifts[0].last_status.hasOwnProperty('50') ? orderArray[i].orders[j].shifts[0].last_status['50'].full_name : '',
              creatingWorkerFullName = orderArray[i].orders[j].shifts[0].last_status.hasOwnProperty('2') ? orderArray[i].orders[j].shifts[0].last_status['2'].full_name : '',
              matchedWorkerFullName = orderArray[i].orders[j].shifts[0].last_status.hasOwnProperty('4') ? orderArray[i].orders[j].shifts[0].last_status['4'].full_name : orderArray[i].orders[j].shifts[0].last_status.hasOwnProperty('61') ? orderArray[i].orders[j].shifts[0].last_status['61'].full_name : '',
              acceptedWorkerFullName = orderArray[i].orders[j].shifts[0].last_status.hasOwnProperty('6') ? orderArray[i].orders[j].shifts[0].last_status['6'].full_name : orderArray[i].orders[j].shifts[0].last_status.hasOwnProperty('10') ? orderArray[i].orders[j].shifts[0].last_status['10'].full_name : '';
          result[i].orders[j] = {
            order_id: orderArray[i].orders[j].order_id,
            creating_worker_full_name: creatingWorkerFullName,
            created_worker_full_name: createdWorkerFullName,
            matched_worker_full_name: matchedWorkerFullName,
            accepted_worker_full_name: acceptedWorkerFullName,
          };
          Object.assign(result[i].orders[j], orderArray[i].orders[j].shifts[0]);

        }
      }

      for (let index in result) {
        for (let order in result[index].orders) {
          result[index].orders.sort((a, b) => {                                                                         // сортировка по полю worker_full_name
            if (this.filterProps[this.sortedField]) {
              if (a[this.sortedField] > b[this.sortedField]) {
                return 1;
              } else if (a[this.sortedField] < b[this.sortedField]) {
                return -1;
              }
              return 0;
            } else {
              if (a[this.sortedField] > b[this.sortedField]) {
                return -1;
              } else if (a[this.sortedField] < b[this.sortedField]) {
                return 1;
              }
              return 0;
            }
          });
        }
      }

      return result;
    },


    /**
     * Объект для получения истории выбранного наряда
     */
    ordersObject: {
      get() {
        if (localStore.getters.ORDER_OBJECT[this.selectedDate].orders[this.selectedOrderId]) {                          // если объект существует
          if (this.selectedDate && this.selectedOrderId && this.selectedShift) {                                        // если существует выбранная дата, id наряда, смена
            return localStore.getters.ORDER_OBJECT[this.selectedDate].orders[this.selectedOrderId].shifts[this.selectedShift];
          }
        } else {
          console.log("computed ordersObject. Не найден");
          return {};
        }
      }
    },

    /**
     * Свойство для получения и установки имени активного окна/выпадающего списка/контекстного меню
     */
    activeModal: {
      get() {
        return localStore.getters.ACTIVEMODAL;
      },
      set(newActiveModal) {
        localStore.dispatch('setActiveModal', newActiveModal);
      }
    },

    /**
     * Свойство для получения списка участков
     */
    allCompanyDepartments: {
      get() {
        return this.$store.getters.GETALLCOMPDEPAR;
      }
    },
  },


  methods: {
    checkMove: function(e) {
      window.console.log("Future index: " + e.draggedContext.futureIndex);
    },
    log(event) {
            console.log(event);
          },
    getdiff(madate) {
      return this.chosenDate.year - madate
    },
    /**
     * Метод меняет значение переменной chosenMine, на значение выбранное в поле шахты
     * @param mine (Object) - объект выбранной шахты
     */
    changeMine(mine) {
      this.chosenMine = mine;

      this.getsensors();

      this.activeModal = false;
      this.activeModal2 = '';
    },

    /**
     * Метод возвращения на главный экран(вызывается при клике на кнопку закрытия)
     */
    returnToTheMainPage() {
      this.$router.push('/order-system/order-system');
    },

    /**
     * Метод установки полноэкранного режима
     */
    setFullScreenMode() {
      this.maxWindowClass === 'fullscreen-mode' ? this.maxWindowClass = '' : this.maxWindowClass = 'fullscreen-mode';
    },

    /**
     * Метод установки даты из календаря
     */
    changeChosenDate(dateObject) {
      this.chosenDate = dateObject;
      this.getsensors();
    },

    /**
     * Метод устанавки выбранного участка из выпадающего списка департаментов
     * @param departmentObject {object} - объект выбранного участка
     **/
    changeCurrentDepartment(departmentObject) {
      this.chosenDepartment = departmentObject;
      this.getsensors();

      if (hasProperty(localStorage, 'serialWorkerData')) {
        let serialWorkerData = {};
        serialWorkerData = JSON.parse(localStorage.getItem("serialWorkerData"));
        serialWorkerData.userCompanyDepartmentId = this.chosenDepartment.id;
        serialWorkerData.userCompany = this.chosenDepartment.title;

        localStorage.setItem("serialWorkerData", JSON.stringify(serialWorkerData));
      }
    },

    /**
     * Метод получения списка всех нарядов
     */
    getsensors() {
      if (this.chosenDate && Number(this.chosenDate.monthNumber) + 1 && this.chosenDepartment.id) {                       // если существует выбранная дата, месяц и id департамента
        let config = {
          month: Number(this.chosenDate.monthNumber) + 1,
          year: this.chosenDate.year,
          mine_id: this.chosenMine.id,
          company_department_id: this.chosenDepartment.id,
          mine_id: this.chosenMine.id,
        };
        localStore.dispatch('getOrderInfo', config);
      }
    },

    /**
     * Метод установки активного модального окна первого уровня
     * @param activeModalName {String} - название активного модального окна первого уровня
     **/
    changeActiveModal(activeModalName = '') {
      if (this.activeModal !== activeModalName) {
        this.activeModal = activeModalName;
      } else {
        this.activeModal = '';
      }
    },

    /**
     * Метод открытия модального окна сведений о наряде
     * @param dateTime - дата выбранного наряда
     * @param orderId - id наряда
     * @param shiftId - id смены
     **/
    openModalOrderInfo(dateTime, orderId, shiftId) {
      this.selectedShift = shiftId;
      this.selectedOrderId = orderId;
      this.selectedDate = dateTime;
      this.activeModal = 'OrderInformation';                                                                  // присвоение имени компонента, которое необходимо открыт
    },

    /**
     * Метод закрытия модальных окон всех уровней
     **/
    closeAllModalAndDropdownWindows() {
      if (this.activeModal.length) {                                                                          // если есть какое-то открытое модальное окно
        this.changeActiveModal();                                                                           // вызываем функцию переключения активного модального окна, в котором при отсутствии переданного аргумента, устанавливается пустая строка, а значит ни одно условие v-if не сработает, и все модалки скроятся
      }
    },

    /**
     * Метод свертывания развертываение списка при клике на дату
     */
    showDateTab(date) {
      if (this.activeOrders[date] == undefined) {
        this.$set(this.activeOrders, date, 1);
      } else {
        this.activeOrders[date] = !this.activeOrders[date];
      }
    },


    /**
     * Метод для сортировки списка нарядов
     * @param sortedField(string) - тип поля для сортировки
     */
    sortOrders(sortedField) {
      this.sortedField = null;
      this.sortedField = sortedField;                                                                         // присовение переменной поля по которому происходит сортировка
      this.filterProps[sortedField] = !this.filterProps[sortedField];                                         // переключение сортировки по возрастанию/убыванию
    },


    /**
     * Метод для расчета ширины и цвета индикаторов выполнения работы
     */
    calcColorDone(percentValue) {
      if (percentValue === 100) {
        return 'background-color: #b5d33d; width:' + percentValue + '%';
      } else if (percentValue > 1 && percentValue <= 99) {
        return 'background-color: #ea853f; width:' + percentValue + '%';
      } else if (percentValue === 0) {
        return 'background-color: #b65671; width:' + percentValue + '%';
      } else if (percentValue > 100) {
        return 'background-color: #727272; width:' + percentValue + '%';
      }
    },

    /**
     * setTextColorIndicator - Метод установки цвета текста при проценте выполнения работ более 100%
     **********************************************************************
     * Входной параметр:
     *  percentValue - процент выполнения
     **/
    setTextColorIndicator(percentValue) {
      // проверка значения процента выполнения
      if (percentValue > 100) {
        return 'color: #ffffff';
      } else {
        return 'color: #000000';
      }
    },

    /**
     * Метод для расчета ширины индикаторов выполнения работы
     */
    calcWidthUnDone(percentValue) {
      let balance = 100 - parseInt(percentValue);
      return 'width:' + balance + '%';
    },

    /**
     * отображает контекстное меню наряда
     *
     * @param {String} dateTime
     * @param {Object} orderObject
     * @param {EventObject} event
     **/
    showOrderContextMenu(dateTime, orderObject, event) {
      // console.log('order-route-map.vue. showOrderContextMenu. args ', arguments);
      this.selectedOrder = {
        ...orderObject,
        date_time_source: dateTime
      };
      this.activeModal = 'orderContextMenu';

      this.$nextTick(() => {

        let modal = document.getElementById('orderContextMenuWrapper'); // Находим модальное окно добавления звена по классу
        if (!modal) {
          throw new Error("showOrderContextMenu. элемент orderContextMenuWrapper выпадающий список не найден");
        }

        let positionModal = calculateModalPosition(event.clientX, event.clientY, modal.getBoundingClientRect().width, modal.getBoundingClientRect().height, ".max-content");
        if (positionModal.status === 0) {
          throw new Error("showOrderContextMenu. Ошибка при расчете места открытия окна");
        }

        modal.style.left = positionModal.left;
        modal.style.top = positionModal.top;
      });

    },

    // переход на страницу наряда
    goToOrderPage() {
      // console.log('order-route-map.vue. goToOrderPage. selectedOrder ', this.selectedOrder);
      let params =
          {
            date: this.selectedOrder.date_time_source,
            shift_id: this.selectedOrder.shift_id,
            shift_title: 'Смена ' + this.selectedOrder.shift_id,
            company_department_id: this.selectedOrder.company_department_id,
            company_department_title: this.selectedOrder.company_department_title,
            mine: this.chosenMine
          };
      this.activeModal = '';
      localStorage.setItem('orderInfo', JSON.stringify(params));
      window.open("/order-system/table-form-mine", '_blank');
    },

    // переход на страницу наряда
    goToFillReportPage() {
      // console.log('order-route-map.vue. goToFillReportPage. selectedOrder ', this.selectedOrder);
      let params =
          {
            date: this.selectedOrder.date_time_source,
            shift_id: this.selectedOrder.shift_id,
            shift_title: 'Смена ' + this.selectedOrder.shift_id,
            company_department_id: this.selectedOrder.company_department_id,
            company_department_title: this.selectedOrder.company_department_title,
            mine: this.chosenMine
          };
      this.activeModal = '';
      localStorage.setItem('fillReportInfo', JSON.stringify(params));
      window.open("/order-system/fill-report", '_blank');
    },
  },


  
  created() {
    // this.chosenDepartment = this.$store.getters.CURRENTDEPARTMENT;
    // this.$store.dispatch('setDefaultData');
    // this.getsensors();
  },

  mounted() {


  
  this.$emit("mine", this.mineindex);
  console.log(this.mineindex);



  },

  beforeDestroy() {

  }
}

</script>

<style lang="less" scoped>

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


.ghost {
  opacity: 0.5;
  background: #c8ebfb;
}
.order-route-map-container {
  position: relative;
  min-height: 450px;
  height: calc(90vh - 180px);
  width: 95%;
  border: 1px solid rgb(77, 137, 124);
  box-shadow: 2px 2px 5px rgba(0, 0, 0, .5);
  border-top: none;

  &.fullscreen-mode {
    height: 95vh;
    width: calc(80% - 50px);
    position: absolute;
    top: 0;
    left: 5px;
    z-index: 1061;
    background: #EBECEC;
    min-width: 1200px;
  }

  .main-content {
    display: flex;
    flex-direction: column;
    height: calc(90% - 20px);
    font-size: 12px;

    .top-row {
      height: 30px;
      position: relative;

      .headerButtons {
        width: 770px;
        height: 100%;
        display: flex;
        position: relative;
        margin: 0 auto;

        &__calendarWrapper {
          position: relative;
          width: 150px;

          .calendarModal {
            position: relative;
            z-index: 11;
            width: 250px;
          }
        }

        &__buttonTrapezium {
          position: relative;
          width: 25%;
          /*clip-path: polygon(0 0, 90% 0, 100% 100%, 10% 100%);*/
          transform: skew(30deg);
          background-color: #B2D63C;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;

          &:hover {
            background-color: #c6eb41;
          }

          & > * {
            transform: skew(-30deg);
          }
        }

        &__calendar {
          width: 100%;
          height: 100%;
        }

        &__buttonTrapeziumBg {

          z-index: 10000;
          width: 100%;
          height: 30px;

          display: flex;
          align-items: center;
          justify-content: center;


        }

        .department-filter {
          position: relative;
          width: 300px;
          left: 3px;
          margin-right: 10px;

          &:hover {
            cursor: pointer;

            .header__titleSkewedLeft, .header__titleSkewedRight {
              background-color: #c6eb41;
            }
          }

          .header__titleSkewedLeft {
            position: absolute;
            left: 0;
            width: 70%;
            height: 100%;
            transform: skew(30deg);
            background-color: #B2D63C;

          }

          .header__titleSkewedRight {
            position: absolute;
            right: 0;
            width: 70%;
            height: 100%;
            transform: skew(-30deg);
            background-color: #B2D63C;

          }

          .header__departmentBtn {
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
                position: absolute;
                top: 12px;
                right: 8px;
              }

              .header__departmentTriangle.openedDepartmentList {
                border-bottom: 6px solid #000000;
                border-top: none;
              }
            }
          }

          .departmentDropDownList {
            width: 400px;
            position: absolute;
            top: 32px;
            left: 8px;
          }
        }

        &__calendarIcon {
          width: 20px;
          position: relative;
          right: 15px;
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

      .order-failure-reasons-btn {
        position: absolute;
        right: 180px;
        width: 150px;
        background: #6a7180;
        color: #fff;
        height: 30px;
        margin: auto 0;
        top: 5px;
        line-height: 1.2;
        padding: 0 10px;
        display: flex;
        justify-content: center;
        align-items: center;

        &:hover {
          cursor: pointer;
          text-decoration: none;
          color: #fff;
          background: #6f7f8f;
        }
      }

      .order-restrictions-btn {
        position: absolute;
        right: 20px;
        width: 150px;
        background: #6a7180;
        color: #fff;
        height: 30px;
        margin: auto 0;
        top: 5px;
        line-height: 1.2;
        display: flex;
        justify-content: center;
        align-items: center;

        &:hover {
          cursor: pointer;
          text-decoration: none;
          color: #fff;
          background: #6f7f8f;
        }
      }
    }

    .table-header {
      width: 100%;
      height: 45px;
      display: flex;
      flex-flow: row nowrap;
      background: #dedede;
      border: 1px solid #fff;
      margin-top: 10px;
      padding: 5px 11px 5px 0;
      text-align: center;
      cursor: pointer;

      .table-column {
        display: flex;
        padding: 0 5px;
        

        &:first-of-type {
          width: 10%;
          display: flex;
          align-items: center;

          span {
            width: 82%;
            margin: auto;
            text-align: center;
            word-break: break-all;
            
          }
        }

        &:nth-of-type(2) {
          width: 20%;
          display: flex;
          align-items: center;

          span {
            width: 82%;
            margin: auto;
            text-align: center;
          }
        }

        &:nth-of-type(3) {
          width: 20%;
          display: flex;
          align-items: center;

          span {
            width: 82%;
            margin: auto;
            text-align: center;
          }
        }

        &:nth-of-type(4) {
          width: 10%;
          display: flex;
          align-items: center;

          span {
            width: 82%;
            margin: auto;
            text-align: center;
          }
        }

        &:nth-of-type(5) {
          width: 20%;
          display: flex;
          align-items: center;

          span {
            width: 82%;
            margin: auto;
            text-align: center;
          }
        }

        // &:nth-of-type(6) {
        //   width: 8%;
        //   display: flex;
        //   align-items: center;

        //   span {
        //     width: 82%;
        //     margin: auto;
        //     text-align: center;
        //   }
        // }

        // &:nth-of-type(7) {
        //   width: 13%;
        //   display: flex;
        //   align-items: center;

        //   span {
        //     width: 82%;
        //     margin: auto;
        //     text-align: center;
        //   }
        // }

        // &:nth-of-type(8) {
        //   width: 13%;
        //   display: flex;
        //   align-items: center;

        //   span {
        //     width: 82%;
        //     margin: auto;
        //     text-align: center;
        //   }
        // }

        &:last-of-type {
          width: 10%;
          display: flex;
          align-items: center;

          span {
            margin: auto;
            text-align: center;
            width: 82%;
            line-height: 1;
          }

          div {
            margin-right: -10px;
          }
        }
      }

      .table-column:not(:last-of-type) {
        border-right: 1px solid #f2f2f2;
      }

      .table-column:nth-of-type(4),
      .table-column:nth-of-type(5),
      .table-column:nth-of-type(6),
      .table-column:nth-of-type(7),
      .table-column:nth-of-type(8) {
        span {
          margin: auto;
          text-align: center;
        }
      }
    }

    .orders-container {
      overflow-y: scroll;
      margin-top: 5px;
      text-align: center;

      .created {
        width: 15%;
        margin: 10px;

        input {
          width: 20px;
          height: 15px;
          margin-right: 10px;
        }
      }

      .table-body {
        display: flex;
        // flex-direction: column;
        margin-top: 30px;
        &:hover {
          background-color: #ccc;
        }
        .date-block {
          display: flex;
          flex-direction: column;

          
            span {
              margin: auto 0 auto 15px;
              
            }
          

          .table-row {
            display: flex;
            min-height: 30px;
            background-color: #f2f2f2;
            padding: 5px 0;
            cursor: pointer;
         

            &:not(:last-of-type) {
              border-bottom: 1px solid #dedede;
              
            }
         

            .table-column:not(:last-of-type) {
              border-right: 1px solid #dedede;
              
            }
        
          }
        }
      }

      .table-column {
        display: flex;
        padding: 0 5px;
        cursor: pointer;
        

        &:first-of-type {
          width: 10%;
          display: flex;
          align-items: center;
        }

        &:nth-of-type(2) {
          width: 20%;

          span {
            
            margin: auto;
          }
          
        }

        &:nth-of-type(3) {
          width: 20%;
          flex-direction: column;
          text-align: left;

          span:last-of-type {
            display: flex;
          }
        }

        &:nth-of-type(4) {
          width: 10%;
          flex-direction: column;
          text-align: left;

          span:last-of-type {
            display: flex;
          }
        }

        &:nth-of-type(5) {
          width: 20%;
          flex-direction: column;
          text-align: center;

          span:last-of-type {
            display: flex;
          }
        }

        // &:nth-of-type(6) {
        //   width: 8%;
        //   text-align: center;

        //   span {
        //     margin: auto;
        //   }
        // }

        // &:nth-of-type(7) {
        //   width: 13%;
        //   flex-direction: column;
        //   text-align: left;

        //   span:last-of-type {
        //     display: flex;
        //   }
        // }

        // &:nth-of-type(8) {
        //   width: 13%;
        //   flex-direction: column;
        //   text-align: left;
        // }

        &:last-of-type {
          width: 10%;
          position: relative;
          display: flex;

          .indicator {
            width: 94%;
            height: 100%;
            position: absolute;
            display: flex;
            z-index: 4;

            // &-done {
            // }

            &-undone {
              background-color: #dedede;
            }
          }

          .text {
            display: flex;
            position: relative;
            margin: auto;
            z-index: 5;
            


            span {
              color: #000;
              margin: auto;
              
            }
          }
        }
      }
    }
  }
}

#orderContextMenuWrapper {
  position: absolute;
  top: 0;
  left: 0;
  text-align: left;
  width: 200px;
  z-index: 10;
  border: 1px solid #999;
 
}

.percent {
  border-left: 5px solid #fff;
  border-right: 5px solid #fff;
  background: #dedede;

  div {
    margin: auto;
  }

}

.green-column {
  display: flex;
  flex-direction: column;
  padding: 0 3px;
  background-color: #e5f2c4;
}

.red-triangle {
  display: flex;
  flex-direction: column;
  padding: 0 3px;
  background-color: #e5f2c4;
  position: relative;

  &-exclamation-point {
    position: absolute;
    top: -2px;
    left: 7px;
    z-index: 1;
    color: #fff;
    font-size: 16px;
  }

  &-text {
    margin-left: 30px;
  }

  &:after {
    position: absolute;
    content: "";
    top: 0;
    left: 0;
    width: 0;
    height: 0;
    border-top: 30px solid @replacingRed;
    border-right: 30px solid transparent;
  }

  &:before {
    position: absolute;
    content: "";
    top: 0;
    left: 2px;
    width: 0;
    height: 0;
    border-top: 31px solid #fff;
    border-right: 31px solid transparent;
  }
}

.orange-column {
  height: 100%;
  display: flex;
  align-items: center;
  padding: 0 3px;
  background-color: #ffab72;
}

.alignment {
  width: 99%;
  display: flex;
}

.triangle {
  width: 0;
  border-top: 35px solid @replacingRed;
  border-right: 35px solid transparent;
}

.order-route-map-container > button:nth-child(1) {
  border: none;
  background: transparent;
  display: flex;
  position: absolute;
  top: 11px;
  z-index: 1;
  left: 20px;
}

.order-route-map-container > button:nth-child(1) > img:nth-child(1) {
  width: 20px;
  height: 20px;
}

.icon-sort-none {
  width: 20px;
  height: 20px;
  background: url(../assets/arrows.png) center no-repeat;
  background-size: 100% 100%;
}

.icon-sort-down {
  width: 20px;
  height: 20px;
  background: url(../assets/Arrow_down_grey.png) center no-repeat;
  background-size: 100% 100%;
}

.icon-sort-up {
  width: 20px;
  height: 20px;
  background: url(../assets/Arrow_up_grey.png) center no-repeat;
  background-size: 100% 100%;
}



</style>

<!--<style scoped>-->

<!--    /*@media all and (min-width: 1650px) and (max-height: 1730px){*/-->
<!--    /*    .table-header {*/-->
<!--    /*        width: 95.8%;*/-->
<!--    /*    }*/-->
<!--    /*}*/-->

<!--    @media all and (min-height: 2601px) {-->
<!--        .order-route-map-container {-->
<!--            height: 93vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 2501px) and (max-height: 2600px) {-->
<!--        .order-route-map-container {-->
<!--            height: 92vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 2401px) and (max-height: 2500px) {-->
<!--        .order-route-map-container {-->
<!--            height: 92vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 2301px) and (max-height: 2400px) {-->
<!--        .order-route-map-container {-->
<!--            height: 92vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 2201px) and (max-height: 2300px) {-->
<!--        .order-route-map-container {-->
<!--            height: 91vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 2101px) and (max-height: 2200px) {-->
<!--        .order-route-map-container {-->
<!--            height: 91vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 2001px) and (max-height: 2100px) {-->
<!--        .order-route-map-container {-->
<!--            height: 90vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1901px) and (max-height: 2000px) {-->
<!--        .order-route-map-container {-->
<!--            height: 90vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1801px) and (max-height: 1900px) {-->
<!--        .order-route-map-container {-->
<!--            height: 89vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1701px) and (max-height: 1800px) {-->
<!--        .order-route-map-container {-->
<!--            height: 89vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1601px) and (max-height: 1700px) {-->
<!--        .order-route-map-container {-->
<!--            height: 88vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1501px) and (max-height: 1600px) {-->
<!--        .order-route-map-container {-->
<!--            height: 88vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1451px) and (max-height: 1500px) {-->
<!--        .order-route-map-container {-->
<!--            height: 87vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1401px) and (max-height: 1450px) {-->
<!--        .order-route-map-container {-->
<!--            height: 87vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1351px) and (max-height: 1400px) {-->
<!--        .order-route-map-container {-->
<!--            height: 86vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1301px) and (max-height: 1350px) {-->
<!--        .order-route-map-container {-->
<!--            height: 85vh;-->
<!--        }-->
<!--    }-->


<!--    @media all and (min-height: 1251px) and (max-height: 1300px) {-->
<!--        .order-route-map-container {-->
<!--            height: 84.5vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1201px) and (max-height: 1250px) {-->
<!--        .order-route-map-container {-->
<!--            height: 84vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1151px) and (max-height: 1200px) {-->
<!--        .order-route-map-container {-->
<!--            height: 83vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1101px) and (max-height: 1150px) {-->
<!--        .order-route-map-container {-->
<!--            height: 82.5vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1051px) and (max-height: 1100px) {-->
<!--        .order-route-map-container {-->
<!--            height: 82vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 1001px) and (max-height: 1050px) {-->
<!--        .order-route-map-container {-->
<!--            height: 81.5vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 951px) and (max-height: 1000px) {-->
<!--        .order-route-map-container {-->
<!--            height: 81vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 901px) and (max-height: 950px) {-->
<!--        .order-route-map-container {-->
<!--            height: 80vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 851px) and (max-height: 900px) {-->
<!--        .order-route-map-container {-->
<!--            height: 78.5vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 801px) and (max-height: 850px) {-->
<!--        .order-route-map-container {-->
<!--            height: 77vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 751px) and (max-height: 800px) {-->
<!--        .order-route-map-container {-->
<!--            height: 76vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 701px) and (max-height: 750px) {-->
<!--        .order-route-map-container {-->
<!--            height: 74vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (min-height: 651px) and (max-height: 700px) {-->
<!--        .order-route-map-container {-->
<!--            height: 72vh;-->
<!--        }-->
<!--    }-->

<!--    @media all and (max-height: 650px) {-->
<!--        .order-route-map-container {-->
<!--            height: 70vh;-->
<!--        }-->
<!--    }-->
<!--</style>-->
