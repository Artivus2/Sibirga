<template>
  
<div class="content">
  
  <div class="row" style="display: flex;">
    
      <base-main-header
      @closeHeaderEvent="returnToTheMainPage"
      header-text="Места хранения"
      />
      <base-header
        :headerColor="selectedReportObject.color"
        :headerText="selectedReportObject.fullTitle ? selectedReportObject.fullTitle : selectedReportObject.title.replace(/['<br/>']/g, '')"
        />
    
  </div>  
  
  <div class="row">
      <div class="col" style="display: flex;">
        <div class="uchet-agk">
          <div v-for="reportObject in reportsList" :key="reportObject.id"
            
            class="report-items"
            @click.stop="setActiveReport(reportObject.id)"
            >
              <img :src="selectedReportId === reportObject.id ? reportObject.iconActive : reportObject.iconInactive" class="place-img">
              <div :style="selectedReportId === reportObject.id ? {outlineColor: reportObject.color} : {outlineColor: '#000'}">
              <div class="report"><span class="reports-span">{{ reportObject.title }}</span></div>
          
              
            </div>
            <draggable 
                  item-key="title" 
                  :list="list[reportObject.id]" 
                  class="drag-enter"
                  @change="log"
                  v-bind="dragOptions"
                  group="sensorDep"
                  >
                    <template #item="{element: sensor}">
                      
                      <div class="sensors">
                        <span class="sensors-span">{{ sensor.zavod_id }}</span>
                      </div>
                      
                    </template>      
                  </draggable>
          
          </div>
        <div class="buttons">
        <div class="update-button" @click.stop="getSensorsData">
            <span>Сохранить</span>
        </div>
        <div class="cancel-button" >
          <span>Отменить</span>
        </div>
    </div>     
          
        </div>
        
        <div class="col">
      <mines-dropdown
      :options="MinesList"
      :default="MinesList[0].title"
      :tabindex="selectedMineId"
      @selectedMine="oninput"
      >
      </mines-dropdown>
    
    <tableUchetAgz
    :options="dataagz"
    :sensorIndex="selectedSensorId"
    @selectedFilter="filterById"
  
  />
        </div>
        <!-- <div class="col">контент</div> -->
        
      </div>
          
  </div>
  

</div>


    
  
  
  

  
  

</template>

<script>
import draggable from 'vuedraggable';
import baseHeader from "../components/base/BaseHeader.vue";
import baseMainHeader from "../components/base/BaseMainHeader.vue";
import uchetAgzStore from "../modules/uchet-agz/uchetAgzStore.js";
import MinesDropdown from "../modules/uchet-agz/components/MinesDropdown.vue";
import tableUchetAgz from "../modules/uchet-agz/components/tableUchetAgz.vue";


export default {
  name: "uchetagz",
    components: {
    baseHeader,
    baseMainHeader,
    MinesDropdown,
    tableUchetAgz,
    draggable,
    
    },
    data() {
        return {
          chosenDate: {                                                                                           // выбранная дата
            monthNumber: new Date().getMonth(),                                                                 // месяц(по умолчанию текущий)
            year: new Date().getFullYear(),                                                                     // год(по умолчанию текущий)
          },
          dragOptions() {
            return {
              animation: 250,
              disabled: false,
              ghostClass: "ghost-class",
              sort: false
            };
          },
          
          maxWindowClass: 'fullscreen-mode',
          list:{
            0: [],
            1: [],
            2: [],
            3: [],
          },
          //selectedList: null,
          MinesList: [
              {id:0 , title:'Шахта Ленина'},
              {id:1 , title:'Шахта Сибригринская'},
              {id:2 , title:'Шахта Ольжерасская'},
              ],
            reportsList: [
            {
              id: 0,
              title: 'Складское хозяйство',
              iconActive: '../src/modules/uchet-agz/assets/11_active.png',
              iconInactive: '../src/modules/uchet-agz/assets/11_grey.png',
              color: '#4d897d',
              componentName: 'previousPeriodReportByDepartment',
              
            },
            {
              id: 1,
              title: 'Каптерка',
              iconActive: '../src/modules/uchet-agz/assets/9_active.png',
              iconInactive: '../src/modules/uchet-agz/assets/9_grey.png',
              color: '#56698f',
              componentName: 'previousPeriodReportByDepartment'
              
            },
            {
              id: 2,
              title: 'Горные выработки',
              iconActive: '../src/modules/uchet-agz/assets/4_active.png',
              iconInactive: '../src/modules/uchet-agz/assets/4_grey.png',
              color: '#55599f',
              componentName: 'previousPeriodReportByDepartment'
            },
            {
              id: 3,
              title: 'Ремонты',
              iconActive: '../src/modules/uchet-agz/assets/12_active.png',
              iconInactive: '../src/modules/uchet-agz/assets/12_grey.png',
              color: '#2c3e50',
              componentName: 'previousPeriodReportByDepartment'
            },
            
          ],
      
      dataagz: [
        {id: 1, title:'Датчик 1', zavod_id: 'EEL2L4', madate: 2022, location: 0, mine: 0},
        {id: 2, title:'Датчик 2', zavod_id: 'EEL2L5', madate: 2021, location: 1,mine: 0},
        {id: 3, title:'Датчик 3', zavod_id: 'EEL2L6', madate: 2020, location: 2,mine: 0},
        {id: 4, title:'Датчик 4', zavod_id: 'EEL2L7', madate: 2021, location: 3,mine: 1},
        {id: 5, title:'Датчик 5', zavod_id: 'EEL2L8', madate: 2018, location: 3,mine: 2},
        {id: 6, title:'Датчик 6', zavod_id: 'EEL2L8', madate: 2018, location: 2,mine: 1},
        {id: 7, title:'Датчик 7', zavod_id: 'EEL2L8', madate: 2018, location: 1,mine: 1},
        {id: 8, title:'Датчик 8', zavod_id: 'EEL2L8', madate: 2016, location: 0,mine: 0},
        {id: 9, title:'Датчик 9', zavod_id: 'EEL2L8', madate: 2013, location: 1,mine: 0},
        {id: 10, title:'Датчик 10', zavod_id: 'EEL25L8', madate: 2010, location: 2,mine: 0},
        {id: 12, title:'Датчик 12', zavod_id: 'EE35L2L5', madate: 2021, location: 3, mine: 0},
        {id: 13, title:'Датчик 13', zavod_id: 'EEL2L6', madate: 2020, location: 3, mine: 0},
        {id: 14, title:'Датчик 14', zavod_id: 'EEL42L7', madate: 2021, location: 2 ,mine: 1},
        {id: 15, title:'Датчик 15', zavod_id: 'EE2L2L8', madate: 2018, location: 2 ,mine: 2},
        {id: 16, title:'Датчик 16', zavod_id: 'EEL12L8', madate: 2018, location: 2 ,mine: 1},
        {id: 17, title:'Датчик 17', zavod_id: 'EEL212L8', madate: 2018, location: 1,mine: 1},
        {id: 18, title:'Датчик 18', zavod_id: 'EEL2L8', madate: 2016, location: 1,mine: 0},
        {id: 19, title:'Датчик 19', zavod_id: 'EE1L2L8', madate: 2013, location: 0,mine: 0},
        {id: 11, title:'Датчик 110', zavod_id: 'E2EL2L8', madate: 2010, location: 0,mine: 0},


      ],
      filterData: [],
      }
    },
    computed: {
      sensorsdata: {
        get() {
          console.log(uchetAgzStore.getters.sensorsdata)
          return uchetAgzStore.getters.SENSORSDATA;
        },
        // set(selectedSensorId) {
        //   console.log(selectedSensorId)
        //   uchetAgzStore.dispatch('setActiveSensorId', selectedSensorId);
        // }
      },
      selectedSensorId: {
        get() {
          return uchetAgzStore.getters.SELECTEDSENSORID;
        },
        set(selectedSensorId) {
          console.log(selectedSensorId)
          uchetAgzStore.dispatch('setActiveSensorId', selectedSensorId);
        }
      },
      selectedMineId: {
        get() {
          return uchetAgzStore.getters.SELECTEDMINEID;
        },
        set(selectedMineId) {
          console.log("установлена шахта: " + selectedMineId)
          uchetAgzStore.dispatch('setActiveMineId', selectedMineId);
        }
      },
      selectedReportId: {
        get() {
          return uchetAgzStore.getters.SELECTEDREPORTID;
        },
        set(selectedReportId) {
          console.log(selectedReportId)
          uchetAgzStore.dispatch('setActiveReportId', selectedReportId);
        }
    },
    selectedReportObject: {
      get() {
        return this.reportsList[this.selectedReportId];
      }
    },
    activeModal: {
      get() {
        return uchetAgzStore.getters.ACTIVEMODAL;
      },
      set(activeModalName) {
        uchetAgzStore.dispatch('setActiveModal', activeModalName);
      }
    },
    },
    methods: {
      addSensor() {
      console.log("Итого: " + this.list) 
    },
      onDragEnter(e) {
      console.log(e)
      e.currentTarget.classList.add('drag-enter');
    },
    onDragLeave(e) {
      //e.currentTarget.classList.remove('drag-enter');
    },
      selectedList(){
        console.log(this.selectedList)
      },
      log(event) {
            //event.currentTarget.classList.add('drag-enter');
            console.log(event);
            //this.list.push(event.added.element) 
            console.log(this.list)
            console.log("выбран: " + event.added.element.id) 
            //this.$emit('selectedSensor', this.list)
          },
          getdiff(madate) {
      return this.chosenDate.year - madate
    },
        setBorderColorWrapper() {
      switch (this.selectedReportId) {
        }
        },
        
    returnToTheMainPage() {
      this.$router.push('/');
    },
    countSensors(){
      return length(list)
    },
    setFullScreenMode() {
      this.maxWindowClass = this.maxWindowClass === 'fullscreen-mode' ? '' : 'fullscreen-mode';
    },
    filteredList() {
      this.filterData = this.dataagz.filter((e) => e.mine === 0).map((e) => 
      {
        return {
        id: e.id, title: e.title, zavod_id: e.zavod_id, madate: e.madate, location: e.location, mine: e.mine
      }
    });
    },
    oninput(selectedMineId) {
      //console.log("setActiveMine.MineId", MineId);
      //uchetAgzStore.dispatch('setActiveMine', selectedMineId)
      //this.activeModal = "";
      //this.selectedMineId = MineId;
      console.log("Выбрана шахта: " + selectedMineId)
      //this.ajaxGetStatisticsData();  {id: 8, title:'Датчик 8', zavod_id: 'EEL2L8', madate: 2018, location: 'Штрек 5-C',mine: 2}
    //   this.filterData = this.dataagz.filter((e) => e.mine === selectedMineId).map((e) => 
    //   {
    //     return {
    //       id: e.id, title: e.title, zavod_id: e.zavod_id, madate: e.madate, location: e.location, mine: e.mine
    //   }
    // });
    console.log(this.dataagz);
    },
    filterById(selectedSensorId) {
      this.filterData = this.dataagz.filter((e) => e.id == selectedSensorId).map((e) => 
      {
        return {
        id: e.id, title: e.title, zavod_id: e.zavod_id, madate: e.madate, location: e.location, mine: e.mine
      }
    });
    },

    
    // onmine(MineId) {
    //   //console.log("setActiveReport.reportId", reportId);
    //   //this.activeModal = "";
    //   this.selectedMineId = MineId;
    //   //this.ajaxGetStatisticsData();
    // },
    setActiveReport(reportId) {
      console.log("setActiveReport.reportId", reportId);
      
      this.selectedReportId = reportId;
      
    },
    setActiveMine(MineId) {
      console.log("setActiveMine.MineId", MineId);
     
      this.selectedMineId = MineId;
     
    },
    setActiveSensor(SensorId) {
      console.log("setActiveSensor.SensorId", SensorId);
      this.activeModal = "";
      this.selectedSensorId = SensorId;
      //this.ajaxGetStatisticsData();
    },
    setSensorsData(){
      uchetAgzStore.dispatch('ajaxGetAllSensors', this.list);
    },
    getSensorsData(){
      console.log("Отправлен запрос на получение датчиков: " + this.selectedMineId)
      uchetAgzStore.dispatch('ajaxGetAllSensors', this.selectedMineId);
    }
    },
}



</script>
<style scoped lang="less">
.bottom-buttons {
  display: flex;
  position: relative;
  color: #fff;
  align-items: center;
  overflow: hidden;
  height: 30px;
 

  p {
    padding: 5px 15px;
    margin: 0;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
  }

  > div {
    margin-right: 3px;
    -webkit-transform: skew(-35deg);
    -moz-transform: skew(-35deg);
    -ms-transform: skew(-35deg);
    -o-transform: skew(-35deg);
    transform: skew(-35deg);
    cursor: pointer;
  }

  > div:first-of-type {
    margin-left: -10px;
  }

  > div:last-of-type {
    margin-left: 5px;
  }

  > div > div {
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: skew(35deg);
  }

  img {
    width: 20px;
    margin-left: 10px;
    user-select: none;
  }

  &-save {
    background-color: #4D897C;
    width: 185px;

    &:hover {
      background-color: #5EA898;
    }
  }

  &-send-print {
    background-color: #808080;

    &:hover {
      background-color: #707070;
    }
  }
}
.drag-enter {
  position: absolute;
  left: 270px;
  width: 350px;
  height: 60px;
  font-size: 0.85em;
  // background: #c6eb41;
  align-items: center;
  margin-left: 10px;
  // box-shadow: 0 0 5px 1px rgba(0, 0, 0, 0.5);
  // -webkit-clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
  // clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
  cursor: move;
  z-index: 1000;
  display: flex;
}

.sensors {
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

.sensors-span {
  align-self: center;
  
}

.reports-span {
  align-self: center;
  
}

.place-img {
      
      // position: absolute;
      //top: 0;
      height: 60px;
      
      width: 70px;
      
      //z-index: 1;

      &:hover {
        cursor: pointer;
        
        
      }
.place-div {
        background: #a52a2a;
        //outline: 1px solid #b2b3b3;
        //outline-offset: -2px;
        font-size: 12px;
        //line-height: 1;
        
        // margin: auto 0;
        text-align: left;
        //width: calc(100% - 15px);
        height: 50px;
        
        // align-items: center;
        // min-width: 50%;
        // margin-left: -20px;

        &:hover {
          cursor: pointer;
          background-color: #b2b3b3;
        }


.content {
  position: absolute;
  top:0;
  left: 0;
}

.row {
  
  display: block;
  min-width: 100vh;
  position: relative;
  margin: 5px;
  
}

.col-2 {
  min-width: 100px;
  display: flex;
  position: relative;
  margin: 5px;
}


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
// .uchet-agk {
  
//   // display: block;
//   // min-height: 710px;
//   // height: calc(90vh - 120px);
//   box-shadow: 3px 6px 5px rgba(0, 0, 0, 0.35);
//   width: 200px;
  
  


//   .sidebar {
//     min-width: 250px;
//     min-height: 50vh;
//     //display: flex;
//     //flex-direction: column;
//     //align-items: flex-end;
    

    
    
    
    
    
//   }
//     }

    
    .report-block {

      //width: 160px;
      display: flex;
      height: 60px;
      position: relative;
      
      //justify-content: flex-end;

      &:first-of-type {
        margin-top: -7px;
      }

      &:not(:last-of-type) {
        margin-bottom: 2.5px;
      }

      // img {
      //   // position: absolute;
      //   //top: 0;
      //   height: 60px;
      //   display: block;
      //   width: 70px;
      //   -webkit-user-select: none;
      //   -moz-user-select: none;
      //   -ms-user-select: none;
      //   user-select: none;
      //   z-index: 1;

      //   &:hover {
      //     cursor: pointer;
          
          
      //   }
      // }
      span {
        
        font-size: 16px;
        line-height: 1;
        
        margin: auto;
        text-align: center;
        border: 1px solid #B2D63C;
        border-radius: 8px;
        
        width: 30px;
        //height: 30px;
        display: flex;
        align-items: center;
        &:hover {
          cursor: pointer;
          background-color: #b2b3b3;
        }

      }
      // div {
      //   background: #f2f2f2;
      //   //outline: 1px solid #b2b3b3;
      //   //outline-offset: -2px;
      //   font-size: 12px;
      //   line-height: 1;
      //   -webkit-user-select: none;
      //   -moz-user-select: none;
      //   -ms-user-select: none;
      //   user-select: none;
      //   margin: auto 0;
      //   text-align: left;
      //   //width: calc(100% - 15px);
      //   height: 50px;
      //   display: flex;
      //   align-items: center;
      //   min-width: 50%;
      //   margin-left: -20px;

      //   &:hover {
      //     cursor: pointer;
      //     // background-color: #b2b3b3;
      //   }
      // }

      &:nth-of-type(odd) {
        //width: 220px;

        

        div {
          padding: 5px 5px 5px 40px;
        }
      }

      &:nth-of-type(even) {
        //width: 100%;

        

        div {
          padding: 5px 5px 5px 40px;
        }
      }
    }
  }

  .main-content {
   // width: calc(60%);
    margin-left: 5px;
    padding-top: 5px;
    padding-right: 5px;

    background: #f2f2f2;

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
          background: #f2f2f2;
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
      background: #f2f2f2;
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
          color: #f2f2f2;
          font-size: 12px;
        }
      }
    }
  }


}

.departmentDropDownList {
        width: 400px;
        position: absolute;
        top: 33px;
        z-index: 9999;
      }


</style>