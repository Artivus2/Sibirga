
<template>
  <div class="uchet-agz-container">

    <div class="main-content">
     
      <!-- Заголовок таблицы -->
      <div class="table-header">
        <div class="table-column"
        @click="search"
        >
          <div v-if="visible">
            <input class="input-search" type="text" v-model="idsearch" :onchange="filterId"/>
            
          </div>
          
          <div v-else class="input-close">
            № п/п
          </div>
          
        </div>
        <div class="table-column"
             >
          <span>Наименование <br/>датчика</span>
          <div class="icon-sort-none" v-if="sortedField !== 'shift_id'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.shift_id"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
        <div class="table-column"
             >
          <span>Зав. №</span>
          <div class="icon-sort-none" v-if="sortedField !== 'creating_worker_full_name'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.creating_worker_full_name"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
        <div class="table-column"
             >
          <span>Год выпуска</span>
          <div class="icon-sort-none" v-if="sortedField !== 'created_worker_full_name'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.created_worker_full_name"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
        <div class="table-column"
             >
          <span>Срок службы (лет)</span>
          <div class="icon-sort-none" v-if="sortedField !== 'matched_worker_full_name'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.matched_worker_full_name"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
        <div class="table-column"
             >
          <span>Место установки</span>
          <div class="icon-sort-none" v-if="sortedField !== 'count_accept_worker'"></div>
          <div class="icon-sort-down" v-else-if="filterProps.count_accept_worker"></div>
          <div class="icon-sort-up" v-else></div>
        </div>
      </div>
      
     
    <div class="orders-container">
          
      <draggable 
      item-key="title" 
      :list="options" 
      tag="div" 
      @change="log"
      v-bind="dragOptions"
      group="sensorDep"

      
      
      :tabindex="sensorIndex"
 
      >
        <template #item="{element: sensor}">
          <div class="table-body">
            
              <div class="table-column">{{ sensor.id }}</div>
              <div class="table-column">{{ sensor.title }}</div>
              <div class="table-column">{{ sensor.zavod_id }}</div>
              <div class="table-column">{{ sensor.madate }}</div>
              <div class="table-column">{{ getdiff(sensor.madate) }}</div>
              <div class="table-column">{{ sensor.location }}</div>
            
          </div>
        </template>
      </draggable>
      
    </div>
    
    
</div>  
</div>
    

   </template>

<script>

import draggable from 'vuedraggable';
import uchetAgzStore from "../uchetAgzStore";

export default {

  name: "tableUchetAgz",
  props: {
    options: {
      type: Array,
      required: true,
      
    },
    default: {
      type: String,
      required: false,
      default: null,
    },
    mineIndex: {
      type: Number,
      required: false,
      default: 0,
    },
    locationIndex: {
      type: Number,
      required: false,
      default: 0,
    },
    sensorIndex: {
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
      selectedSensorId: null,
      visible: false,
      dragging: false,
      maxWindowClass: 'fullscreen-mode',                                                                                     // переменная для переключения полноэкранного режима
      enabled: true,
      dragging: false,
      idsearch: null,
      
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
      dragPreview: null,
      selectedOrder: null
    }
  },

  computed: {
    draggingInfo() {
      console.log(this.dragging)
      return this.dragging ? "under drag" : "";
    },
    dragOptions() {
      return {
        animation: 250,
        disabled: false,
        ghostClass: "ghost-class",
        sort: false,
        
      };
    }
 
  },


  methods: {
   

  
    search: function (){
      console.log(this.visible)
      this.visible = true
    },
    closeSearch: function (){
      console.log(this.visible)
      this.visible = false
    },
    filterId: function() {
      
      
    console.log(this.idsearch)
    uchetAgzStore.dispatch('setActiveSensorId', this.idsearch)
    this.$emit("selectedFilter", uchetAgzStore.getters.SELECTEDSENSORID);
    //this.visible = false
    },
    checkMove: function(e) {
      //console.log("Future index: " + e.draggedContext.futureIndex);
      
    },
    // add: function() {
    //   this.list.push({ name: "Juan" });
    // },
    // replace: function() {
    //   this.list = [{ name: "Edgard" }];
    // },
    clone: function(el) {
      return {
        name: el.name + " cloned"
      };
    },
    log(event) {
            console.log("с таблицы");
            console.log(event);
            this.$emit('selectedSensor', event.removed.element)
            uchetAgzStore.dispatch('setActiveReportId', event.removed.element.location);
          },
    getdiff(madate) {
      return this.chosenDate.year - madate
    },
    
    returnToTheMainPage() {
      this.$router.push('/order-system/order-system');
    },

    /**
     * Метод установки полноэкранного режима
     */
    setFullScreenMode() {
      this.maxWindowClass === 'fullscreen-mode' ? this.maxWindowClass = '' : this.maxWindowClass = 'fullscreen-mode';
    },
    // onDragEnter(e) {
    //   e.currentTarget.classList.add('drag-enter');
    // },
    // onDragLeave(e) {
    //   e.currentTarget.classList.remove('drag-enter');
    // }

    
  },
 
  
  created() {
    // this.chosenDepartment = this.$store.getters.CURRENTDEPARTMENT;
    //this.uchetAgzStore.dispatch('setSensorsData');
    // this.getsensors();
    
  },

  mounted() {
    
    //this.$emit("selectedFilter", this.sensorIndex);
    //console.log(this.sensorIndex);

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

.ghost-class {
  
  width: 48px;
  height: 48px;
  font-size: 0.85em;
  background: #c6eb41 ;
  -webkit-clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
  clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
  
  align-self: center;
  justify-content: center;
  // border: 1px solid #b2b3b3;
  margin-left: 5px;

  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  display: flex;
}




.input-search {
  // display: block;
  width: 100%;
  margin: 20px auto;
  padding: 10px 10px;
  background: #dedede url(../assets/close.svg) no-repeat 90%;
  
  background-size: 15px 15px;
  font-size: 14px;
  border: none;
  cursor: pointer;
  
  // box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px,
  //   rgba(0, 0, 0, 0.3) 0px 1px 3px -1px;
}

.input-close {
  width: 100%;
  margin: 20px auto;
  padding: 10px 10px;
  background: #dedede url(../assets/search.svg) no-repeat 90%;
  background-size: 15px 15px;
  font-size: 14px;
  border: none;
  
  
}

.item {
  width: 350px;
  margin: 0 auto 10px auto;
  padding: 10px 20px;
  color: rgb(19, 8, 8);
  border-radius: 5px;
  box-shadow: rgba(0, 0, 0, 0.1) 0px 1px 3px 0px,
    rgba(0, 0, 0, 0.06) 0px 1px 2px 0px;
}



.flip-list-move {
  transition: transform 0.5s; 
}
.uchet-agz-container {
  position: relative;
  margin-left: 15px;
  min-height: 450px;
  height: calc(90vh - 280px);
  width: 60vw;
  border: 1px solid rgb(77, 137, 124);
  box-shadow: 2px 2px 5px rgba(0, 0, 0, .5);
  border-top: none;

  &.fullscreen-mode {
    height: 94vh;
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
      width: 98%;
      height: 45px;
      display: flex;
      // flex-flow: row nowrap;
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
          width: 20%;
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
      min-height: 100%;
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
        
        
        
        
        min-height: 50px;
        font-size: 1.1em;
        text-align: center;
        justify-content: center;
        &:hover {
          background-color: #ccc;
        }
        &.flip-list-move {
         transition: transform 0.5s;
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
        align-self: center;
        cursor: pointer;
        

        &:first-of-type {
          width: 20%;
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

.uchet-agz-container > button:nth-child(1) {
  border: none;
  background: transparent;
  display: flex;
  position: absolute;
  top: 11px;
  z-index: 1;
  left: 20px;
}

.uchet-agz-container > button:nth-child(1) > img:nth-child(1) {
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
::-webkit-scrollbar{
    height: 4px;
    width: 7px;
    background: #c6eb41;
}

/* Track */
::-webkit-scrollbar-track {
  background: rgba(22, 23, 27, 0.2);
}

/* Handle */
::-webkit-scrollbar-thumb {
  background: #030303;
}

/* Handle on hover */
::-webkit-scrollbar-thumb:hover {
  background: rgba(86, 105, 143, 1);
}

::-webkit-scrollbar-thumb:horizontal{
  background: #c6eb41;
    border-radius: 10px;
}


</style>

