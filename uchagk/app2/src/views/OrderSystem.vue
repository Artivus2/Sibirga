<!--
Блок Нарядной системы - главное меню
http://amicum.local/order-system/e-book-orders-navigation
-->
<template>
  <div class="orderSystem">
    <div class="orderSystem-arrow hidden-print">
      <router-link to="/order-system">
        <img src="@/assets/menuOrderSystem/arrow.png" alt="Вернуться назад">
      </router-link>
    </div>

    <!--    <div class="orderSystem-order-route-map hidden-print">-->
    <!--      <router-link to="/order-system/order-route-map-mine">-->
    <!--        <img class="order-route-map-icon" src="@/assets/map-icon.png" alt="Маршрутная карта">-->
    <!--        <span>Маршрутная карта <br> выдачи нарядов</span>-->
    <!--      </router-link>-->
    <!--    </div>-->

    <div class="orderSystem-order-vtb order-vtb-buttons hidden-print">
      <router-link to="/order-system/order-ab-mine">
        <span class="btn-title">Выдача наряда на<br/>производство работ по<br/>линии АБ (ВТБ)</span>
      </router-link>
    </div>

    <div class="orderSystem-order-vtb-matching order-vtb-buttons hidden-print">
      <router-link to="/order-system/order-ab-matching-mine">
        <span class="notify-icon"></span>
        <span class="btn-title">Согласование наряда на<br/>производство работ</span>
      </router-link>
    </div>


    <div class="orderSystem-order-vtb-approving order-vtb-buttons hidden-print">
      <router-link to="/order-system/order-approving-mine">
        <span class="notify-icon"></span>
        <span class="btn-title">Общешахтная книга <br/>нарядов</span>
      </router-link>
    </div>

    <div class="orderSystem-navbar hidden-print">

      <div class="orderSystem-navbar-body">


        <div class="orderSystem-navbar-body-left logbook-order">

          <div class="orderSystem-navbar-block logbook-order-btn">
            <router-link to="/order-system/logbook-order">
              <div class="navbar-block-box">
                <div class="navbar-block-box-img">
                  <img src="../assets/menuOrderSystem/icon/iconLogbookOrder.svg"
                       alt="Журнал регистрации наряд-допусков"/>
                </div>
                <p class="child-block-instr">
                  <span>Журнал регистрации<br>наряд-допусков</span>
                </p>
              </div>
            </router-link>
          </div>
        </div>

        <div class="orderSystem-navbar-body-left">

          <div class="orderSystem-navbar-block downtime-acc-btn">
            <router-link to="/order-system/os-shift-schedule-mine">
              <div class="navbar-block-box">

                <div class="navbar-block-box-img">
                  <img class="navbar-block-box-img-picture"
                       src="../assets/menuOrderSystem/icon/iconReleaseScheduleV2.svg" alt="График выходов">
                </div>

                <p class="child-block-instr">График<br/>выходов</p>
              </div>

            </router-link>
          </div>




          <div class="orderSystem-navbar-block orderSystem-navbar-block-report">
            <router-link to="/order-system/previous-period-report">
              <div class="orderSystem-navbar-block-icon">

                <img src="@/assets/menuOrderSystem/icon/iconReportForThePreviousPeriodV2.svg"
                     class="orderSystem-navbar-block-icon-pic"

                     alt="Отчет за предыдущий период">
              </div>

              <p class="orderSystem-navbar-block-report-text">Отчёт <br> за предыдущий период</p>

            </router-link>
          </div>
        </div>


        <div class="orderSystem-navbar-body-center">

          <div class="orderSystem-navbar-body-center-main">
            <div class="orderSystem-navbar-block fill-out-report">
              <router-link to="/order-system/fill-report">

                <div class="orderSystem-navbar-block-icon">
                  <img class="orderSystem-navbar-block-icon-image"
                       src="@/assets/menuOrderSystem/icon/iconFillOutReportV2.svg" alt="Заполнить отчет">
                </div>

                <p>Заполнить<br/>отчёт за смену</p>
              </router-link>
            </div>
          </div>


          <div class="orderSystem-navbar-body-center-left">
            <!--            <router-link to="/order-system/table-form-mine">-->
            <!--              <div class="orderSystem-navbar-blockMin orderSystem-navbar-blockMinLeft">-->
<!--                <p>Табличная<br/>форма</p>-->
<!--              </div>-->
<!--            </router-link>-->
          </div>
          <div class="orderSystem-navbar-body-center-main">
            <div class="orderSystem-navbar-block  issue-of-an-order">
              <router-link to="/order-system/table-form-mine">

                <div class="orderSystem-navbar-block-icon issue-of-an-order-img">
                  <img class="issue-of-an-order-img-icon" src="@/assets/menuOrderSystem/icon/iconToGiveOutOrderV2.svg"
                       alt="Выдача наряда">
                </div>

                <p>Выдача<br/>наряда</p>
              </router-link>
            </div>
          </div>


          <div class="orderSystem-navbar-body-center-right">
<!--            <router-link to="/order-system/interactive-form">-->
<!--              <div class="orderSystem-navbar-blockMin orderSystem-navbar-blockMinRight">-->
<!--                <p>Интерактивная<br/>форма</p>-->
<!--              </div>-->
<!--            </router-link>-->
          </div>

        </div>

        <div class="orderSystem-navbar-body-right">
          <div class="orderSystem-navbar-block briefing-control-block">
            <router-link to="/order-system/briefings-control">
              <div class="navbar-block-box">

                <div class="navbar-block-box-img">
                  <img src="../assets/menuOrderSystem/icon/iconBriefingControl.svg" alt="Контроль инструктажей">
                </div>

                <p class="child-block-instr">
                  <span>Инструктажи,<br>проверки знаний,<br>аттестации</span>
                </p>
              </div>
            </router-link>
          </div>

          <div class="orderSystem-navbar-block">
            <router-link to="/order-system/downtime-accounting">
              <div class="orderSystem-navbar-block-img">
                <img src="../assets/menuOrderSystem/icon/iconDowntimeAccounting.svg" alt="Учет простоев">
              </div>
              <p class="orderSystem-navbar-block-title">Учёт <br> простоев</p>
            </router-link>
          </div>

        </div>

        <div class="orderSystem-navbar-body-right order-route-map">
          <div class="orderSystem-navbar-block order-route-map-btn">
            <router-link to="/order-system/order-route-map-mine">
              <div class="navbar-block-box">

                <div class="navbar-block-box-img">
                  <img src="../assets/menuOrderSystem/icon/iconOrderRouteMap.svg" alt="Маршрутная карта выдачи нарядов">
                </div>

                <p class="child-block-instr">
                  <span>Маршрутная<br>карта выдачи<br>нарядов</span>
                </p>
              </div>
            </router-link>
          </div>
        </div>

        <div class="orderSystem-navbar-body-foot">
          <router-link to="/order-system/accounting-for-materials">
            <div class="orderSystem-navbar-body-foot-butt">
              <div class="orderSystem-navbar-body-foot-butt-img">
                <img src="../assets/menuOrderSystem/icon/iconAccountingForMaterials.svg" alt="Учет материалов">
              </div>
              <p>Учёт материалов</p>
            </div>
          </router-link>
        </div>

      </div>
    </div>
    <div class="orderSystem-systemNotification" :style="mappingNotification">
      <!-- Компонент уведомлений -->
      <systemNotification
          @setMappingNotification='setMappingNotification'
      >
      </systemNotification>
    </div>
  </div>
</template>

<script>
import systemNotification from '@/components/systemNotification/systemNotification.vue' // компонент уведомлений в левом нижнем углу
export default {
  name: 'orderSystem',
  components: {
    systemNotification
  },
  data() {
    return {
      mappingNotification: {
        left: '0'
      },
      workstationMenu: [1, 2, 3, 4, 5], // массив id страниц
      rights: [] // объект прав доступа
    }
  },
  methods: {
    /**
     * Метод проверки прав доступа
     * */
    setMappingNotification() {
      if (this.mappingNotification.left !== '-340px') {
        this.mappingNotification = {
          left: '-340px'
        }
      } else {
        this.mappingNotification = '0'
      }
    },
    searchRights(type) {
      let users = {
        id_user: 11,                        // id пользователя
        name_user: 'Алексей',
        role_type_user: 'РВН',              // тип/название роли
        workstation_menu: [                           // массив доступа пользователья
          {
            workstation_menu_id: 1,    // id странице
            status: true        		// доступ на взаимодействие
          },
          {
            workstation_menu_id: 2,    // id странице
            status: true        		// доступ на взаимодействие
          },
          {
            workstation_menu_id: 3,    // id странице
            status: true        		// доступ на взаимодействие
          },
          {
            workstation_menu_id: 4,    // id странице
            status: true        		// доступ на взаимодействие
          },
          {
            workstation_menu_id: 5,    // id странице
            status: true        		// доступ на взаимодействие
          },
        ]
      };

      for (let i = 0; i < users.workstation_menu.length; i++) {
        let el = users.workstation_menu[i];
        if (el.workstation_menu_id === type && el.status === true) {

          return true;
        }
      }
    },
    /**
     * Метод установки прав пользователя
     * */

    setRights() {
      for (let i = 0; i < this.workstationMenu.length; i++) {
        this.rights.push({
          workstation_menu_id: this.workstationMenu[i],
          status: this.searchRights(this.workstationMenu[i])
        })
      }
    }
  },
  created() {
    if (!this.$store.state.handbookWorkers || Object.keys(this.$store.state.handbookWorkers).length === 0) {
      this.$store.dispatch('ajaxGetWorkersForHandbook');
    }
  },
  mounted() {
    // this.setRights()
  }
}
</script>

<style lang="less" scoped>
body {
  background-color: #EBEDEC;

  p {
    margin: 0;
    font-size: 15px;
  }
}

.orderSystem {
  position: relative;
  display: flex;
  justify-content: center;
  height: calc(100vh - 120px);
  min-height: 600px;
  width: 100%;

  &-systemNotification {
    position: absolute;
    bottom: 10px;
    left: 0;
    z-index: 999;
    transition: 0.6s;
  }

  &-arrow {
    position: absolute;
    top: 0;
    left: 0;

    a {
      width: 100%;
      height: 100%;
      display: inline-block;
    }

    img {
      width: 90px;
    }
  }

  &-navbar {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;

    &-body {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      width: 1160px;
      margin-top: 60px;

      &-left {
        display: flex;
        align-items: center;
        position: relative;
        left: 65px;

        &.logbook-order {
          left: 0;
        }

        a {
          /*                        -webkit-clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
                                  clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%)*/
        }
      }

      &-right {
        display: flex;
        align-items: center;
        position: relative;
        right: 65px;

        a {
          /*                        -webkit-clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
                                  clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%)*/
        }
      }

      &-center {
        position: relative;

        &-main {

        }

        &-left {
          position: absolute;
          bottom: 2px;
          left: -92px;
          z-index: 2;
        }

        &-right {
          position: absolute;
          bottom: 2px;
          right: -87px;
          z-index: 2;
        }
      }

      &-foot {
        display: flex;
        justify-content: center;
        width: 100%;
        margin-top: 25px;

        &-butt {
          width: 280px;
          height: 70px;
          background: #6A7180;
          border: 6px solid #FFFFFF;
          box-sizing: border-box;
          box-shadow: 4px 4px 4px rgba(0, 0, 0, 0.25);
          display: flex;
          align-items: center;
          justify-content: space-evenly;

          p {
            color: #fff;
            text-transform: uppercase;
            font-style: normal;
            font-weight: normal;
            font-size: 15px;
            line-height: 21px;
          }

          img {
            width: 31px;
          }
        }
      }
    }

    &-block {
      position: relative;
      height: 230px;
      width: 270px;
      text-align: center;
      background: url('../assets/menuOrderSystem/ButtonRouteDowntimeV2.png') no-repeat center/cover;
      -webkit-clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
      clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);

      a {
        position: relative;
        z-index: 10;
        display: block;
        width: 100%;
        height: 100%;
        padding: 35px 50px 0 50px;
      }

      &-img {
        position: relative;

        &:after {
          content: '';
          position: absolute;
          width: 255px;
          height: 106px;
          top: -26px;
          left: -49px;
          background: url('../assets/menuOrderSystem/icon/iconOpacity.svg') no-repeat center/cover;
        }


        img {
          margin: -6px 0 0 15px;
          vertical-align: baseline;
        }
      }

      &-fon {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1;
      }

      &-icon {
        position: relative;
        height: 70px;
        z-index: 2;

        &:after {
          content: '';
          position: absolute;
          width: 255px;
          height: 106px;
          top: -26px;
          left: -49px;
          background: url('../assets/menuOrderSystem/icon/iconOpacity.svg') no-repeat center/cover;
        }

        &-image {
          margin: 4px 0 0 10px;
          vertical-align: baseline;
        }

        &-pic {
          margin-top: 12px;
        }
      }

      &-title {
        margin-top: 27px !important;
      }

      p {
        margin-top: 30px;
        position: relative;
        text-transform: uppercase;
        line-height: 1.2;
        color: #fff;
        z-index: 2;
      }
    }

    &-report {
      background: url('../assets/menuOrderSystem/ButtonRouteDowntimeV2.png') no-repeat center/cover;

      img {
        margin-top: 12px !important;
      }
    }

    .briefing-control-block {
      position: absolute;
      left: 0;
      top: -120px;
      clip-path: unset !important;
      background: url('../assets/menuOrderSystem/die/lilacDiceV2.png') no-repeat center/cover;

      a {
        padding: 0 !important;

        .navbar-block-box {
          position: relative;
          height: 100%;
          width: 100%;
          padding: 0;
          cursor: pointer;

          &-img {
            padding-top: 25px;
            position: relative;

            &:after {
              content: '';
              position: absolute;
              width: 255px;
              height: 117px;
              top: 0;
              left: 0;
              background: url('../assets/menuOrderSystem/icon/iconOpacity.svg') no-repeat center/cover;
            }

            img {
              margin: 17px 0 0 20px;
              vertical-align: baseline;
            }
          }

          p {
            margin-top: 30px;
            color: #fff;
            font-size: 15px;

            span {
              text-transform: uppercase;
            }
          }
        }
      }
    }

    .logbook-order-btn {
      position: absolute;
      top: 0;
      left: -140px;
      background: url('../assets/menuOrderSystem/order_issue_journalV2.png') no-repeat center/cover;


      a {
        padding: 0 !important;

        .navbar-block-box {
          position: relative;
          height: 100%;
          width: 100%;
          padding: 0;
          cursor: pointer;

          &-img {
            padding-top: 25px;
            position: relative;

            &:after {
              content: '';
              position: absolute;
              width: 255px;
              height: 117px;
              top: 0;
              left: 0;
              background: url('../assets/menuOrderSystem/icon/iconOpacity.svg') no-repeat center/cover;
            }

            img {
              margin-top: 15px;
              vertical-align: baseline;
            }
          }

          p {
            width: 168px;
            margin: 22px auto 0 auto;
            color: #fff;
            font-size: 15px;
          }
        }
      }
    }

    .downtime-acc-btn {
      position: absolute;
      left: 0;
      top: -120px;
      /*clip-path: unset!important;*/

      a {
        padding: 0 !important;

        .navbar-block-box {
          position: relative;
          height: 100%;
          width: 100%;
          padding: 0;
          background: url('../assets/menuOrderSystem/die/lilacDiceV2.png') no-repeat center/cover;
          cursor: pointer;

          &-img {
            padding-top: 25px;
            position: relative;

            &:after {
              content: '';
              position: absolute;
              width: 255px;
              height: 117px;
              top: 0;
              left: 0;
              background: url('../assets/menuOrderSystem/icon/iconOpacity.svg') no-repeat center/cover;
            }

            &-picture {
              margin: 10px 0 0 5px;
              vertical-align: baseline;
            }
          }

          p {
            color: #fff;
            font-size: 15px;
          }
        }
      }
    }

    .fill-out-report {
      bottom: 5px;
      background: url('../assets/menuOrderSystem/order_issue_journalV2.png') no-repeat center/cover;

      p {
        margin-top: 35px;
      }
    }

    .issue-of-an-order {
      background: url('../assets/menuOrderSystem/die/lilacDiceV2.png') no-repeat center/cover;

      &-img {

        &-icon {
          width: 100px;
          margin-left: 0;
        }
      }
    }

    .order-route-map-btn {
      position: absolute;
      top: 0;
      right: -205px;
      background: url('../assets/menuOrderSystem/order_issue_journalV2.png') no-repeat center/cover;

      a {
        padding: 0 !important;

        .navbar-block-box {
          position: relative;
          height: 100%;
          width: 100%;
          padding: 0;
          cursor: pointer;

          &-img {
            padding-top: 25px;
            position: relative;

            &:after {
              content: '';
              position: absolute;
              width: 255px;
              height: 117px;
              top: 0;
              left: 0;
              background: url('../assets/menuOrderSystem/icon/iconOpacity.svg') no-repeat center/cover;
            }

            img {
              vertical-align: baseline;
              margin-top: 18px;
            }
          }

          p {
            width: 168px;
            margin: 22px auto 0 auto;
            color: #fff;
            font-size: 15px;
          }
        }
      }
    }

    &-blockMin {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 80px;
      width: 145px;
      border: 8px solid #ffffff;
      box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.2);

      p {
        font-style: normal;
        font-weight: normal;
        font-size: 14px;
        color: #ffffff;
      }

    }

    &-blockMinLeft {
      background-color: #7c6580;
      transform: skewX(30deg);

      p {
        transform: skewX(-30deg);
      }
    }

    &-blockMinRight {
      background-color: #598D9B;
      transform: skewX(-30deg);

      p {
        transform: skewX(30deg);
      }
    }

    a {
      text-decoration: none;
    }

    @media (max-height: 900px) {
      transform: scale(0.9);
    }
  }
}

.orderSystem-order-route-map {
  position: absolute;
  z-index: 999;
  top: 100px;
  width: 250px;
  height: 60px;
  background: #fff;
  clip-path: polygon(0 50%, 10% 0, 100% 0, 100% 100%, 10% 100%);
  right: 0;

  a {
    display: flex;
    width: 100%;
    height: 100%;
    color: #000;
    text-decoration: none;

    img {
      position: relative;
      height: 100%;
      z-index: 2;
      margin: auto 0 auto auto;
    }

    span {
      margin: auto 45px auto 10px;
      font-size: 15px;
    }
  }
}

.orderSystem-order-vtb {
  top: 200px;
  background-color: #56698f;
}

.orderSystem-order-vtb-matching {
  top: 280px;
  background-color: #598d9b;

  .notify-icon {
    /*background: url('');*/
  }
}

.orderSystem-order-vtb-approving {
  top: 360px;
  background-color: #4d987c;

  .notify-icon {
    /*background: url('');*/
  }
}

.order-vtb-buttons {
  position: absolute;
  right: 0;
  z-index: 999;
  width: 250px;
  height: 60px;
  clip-path: polygon(0 50%, 10% 0, 100% 0, 100% 100%, 10% 100%);

  & > a {
    color: #fff;
    text-decoration: none;
    text-align: left;
    width: 100%;
    height: 100%;
    display: flex;

    & > span:last-of-type {
      display: inline;
      text-align: left;
      margin: auto auto auto 60px;
      line-height: 1.2;
    }
  }
}

@media (max-width: 1530px) {
  .orderSystem-navbar {
    transform: scale(0.8);
  }
}
</style>
