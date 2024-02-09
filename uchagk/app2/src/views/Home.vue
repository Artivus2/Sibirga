<!--
  - Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
  -->

<!--
Блок навигации по блокам системы
-->
<template>
  <div class="apps">
    <div class="home">
      <div class="navbar">

        <div class="navbar-info hidden-print">
          <div class="menu-item top-left-position2">
            <div class="white-layout" @click.stop="openPageVue('/uchetagz')">
              <div class="colorful-layout">
                <div class="gradient-layer"></div>
                <div class="menu-item-icon"></div>
                <div class="menu-item-title">
                  <span>УЧЕТ <br>АГЗ</span>
                </div>
              </div>
            </div>
          </div>

          <div class="menu-item center-position">
            <div class="white-layout" @click.stop="openPageVue('/order-system/statistic-reports')">
              <div class="colorful-layout">
                <div class="gradient-layer"></div>
                <div class="menu-item-icon"></div>
                <div class="menu-item-title">
                  <span>СТАТИСТИЧЕСКАЯ <br>ОТЧЕТНОСТЬ</span>
                </div>
              </div>
            </div>
          </div>

          <div class="menu-item top-right-position">
            <div class="white-layout" @click.stop="openPageVue('/order-system/occupational_health_and_safety')">
              <div class="colorful-layout">
                <div class="gradient-layer"></div>
                <div class="menu-item-icon"></div>
                <div class="menu-item-title">
                  <span>ОТ и ПБ</span>
                </div>
              </div>
            </div>
          </div>

          <div class="menu-item bottom-right-position">
            <div class="white-layout"
                 @click.stop="showDocumentationModal = true">
              <div class="colorful-layout">
                <div class="gradient-layer"></div>
                <div class="menu-item-icon"></div>
                <div class="menu-item-title">
                  <span>ДОКУМЕНТАЦИЯ</span>
                </div>
              </div>
            </div>
          </div>

          <div class="menu-item bottom-position">
            <div class="white-layout">
              <div class="colorful-layout"
                   @click.stop="openPageVue('/order-system/routes')">
                <div class="gradient-layer"></div>
                <div class="menu-item-icon"></div>
                <div class="menu-item-title">
                  <span>МАРШРУТЫ</span>
                </div>
              </div>
            </div>
          </div>

          <div class="menu-item bottom-left-position">
            <div class="white-layout"
                 @click.stop="openPageVue('/order-system/book-directive')">
              <div class="colorful-layout">
                <div class="gradient-layer"></div>
                <div class="menu-item-icon"></div>
                <div class="menu-item-title">
                  <span>КНИГА <br>ПРЕДПИСАНИЙ</span>
                </div>
              </div>
            </div>
          </div>


          <div class="menu-item middle-left-position">
            <div class="white-layout"
                 @click.stop="openPage('/risk-assessment-and-management-system/')">
              <div class="colorful-layout">
                <div class="gradient-layer"></div>
                <div class="menu-item-icon"></div>
                <div class="menu-item-title">
                  <span>СОУР</span>
                </div>
              </div>
            </div>
          </div>

          <div class="menu-item top-left-position">
            <div class="white-layout"
                 @click.stop="openPageVue('/order-system/order-system')">
              <div class="colorful-layout">
                <div class="gradient-layer"></div>
                <div class="menu-item-icon"></div>
                <div class="menu-item-title">
                  <span>ЭЛЕКТРОННАЯ<br>КНИГА<br>НАРЯДОВ</span>
                </div>
              </div>
            </div>
          </div>

          <div class="menu-item top-position">
            <div class="white-layout"
                 @click.stop="openPageVue('/order-system/methane-analysis')">
              <div class="colorful-layout">
                <div class="gradient-layer"></div>
                <div class="menu-item-icon"></div>
                <div class="menu-item-title">
                  <span
                      class="menu-item-title-text">АНАЛИЗ<br/>ПОКАЗАТЕЛЕЙ<br/>ДУБЛИРУЮЩЕГО<br/>КОНТРОЛЯ<br/>ГАЗОВ</span>
                </div>
              </div>
            </div>
          </div>

          <div class="menu-item middle-right-position">
            <div class="white-layout"
                 @click.stop="openPage('/shift-examiner/')">
              <div class="colorful-layout">
                <div class="gradient-layer"></div>
                <div class="menu-item-icon"></div>
                <div class="menu-item-title">
                  <span class="menu-item-title-text">Система контроля уровня знаний работников</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="navbar-systemNotification" :style="mappingNotification">
          <!-- Компонент уведомлений -->
          <!-- <systemNotification @setMappingNotification='setMappingNotification'/> -->
        </div>

      </div>
    </div>


    <!--         Модальное окно для кнопки "Документация" -->
    <div class="documentation-modal-container" v-if="showDocumentationModal">
      <div class="documentation-modal">
        <div class="title-modal">
          <span>Документация</span>
          <div class="close-modal-btn" @click="showDocumentationModal = false"></div>
        </div>
        <div class="btns-container">
          <router-link to="/order-system/passport-objects">
            <div class="passport-btn-container">Паспорта объектов</div>
          </router-link>
          <router-link to="/order-system/digital-library">
            <div class="library-btn-container">Электронная библиотека</div>
          </router-link>
        </div>
      </div>
    </div>
    <!-- Модальное окно "Обновления системы-->
    <!-- <AmicumUpdInfo/> -->

    <!--Уведомления о тех.работах-->
    <!-- <notice-of-technical-works/> -->

  </div>
</template>

<script>
// @ is an alias to /src
//import systemNotification from '@/components/systemNotification/systemNotification.vue' // компонент уведомлений в левом нижнем углу
//import AmicumUpdInfo from '../modules/amicum-update/views/AmicumUpdInfo.vue'
//import NoticeOfTechnicalWorks from "@/modules/amicum-update/components/NoticeOfTechnicalWorks";


export default {

  name: 'home',
  components: {
   // systemNotification,
    //AmicumUpdInfo,
    //NoticeOfTechnicalWorks,
  },
  data() {
    return {
      mappingNotification: {
        left: '-340px'
      },
      showDocumentationModal: false
    }
  },
  methods: {
    /**
     * Метод проверки прав доступа
     * */
    setMappingNotification() {
      if (this.mappingNotification.left !== '-340px') {
        this.mappingNotification = {
          left: '-340px',
        };

      } else {
        this.mappingNotification = '0';
      }
    },

    /**
     * Метод перехода на страницу
     */
    openPageVue(urlAddress) {
      this.$router.push(urlAddress);
    },

    openPage(urlAddress) {
      window.open(urlAddress, '_parent');
    }
  },
}
</script>

<style scoped lang="less">
body {
  background-color: #EBEDEC;
}

.navbar {
  display: flex;
  align-items: center;
  height: calc(100vh - 120px);
  min-height: 660px;
  margin: 0;

  .siz-control-link, .injury-accounting-link {
    padding: 0;
  }

  .injury-accounting-link {
    position: relative;
    right: 35px;
  }

  .siz-control-link {
    position: relative;
    left: 30px;
  }

  &-systemNotification {
    position: absolute;
    bottom: 10px;
    left: 0;
    z-index: 1000;
    transition: 0.6s;
  }

  &-info {
    display: flex;
    justify-content: center;
    width: 100%;
    margin-right: 14%;

  }

  &-block-sour {
    position: relative;
    left: 135px;
    margin-top: 193px;
  }

  .menu-item {
    width: 300px;
    height: 260px;
    filter: drop-shadow(0px 0px 5px rgba(0, 0, 0, 0.5));
    position: absolute;
    top: 50%;
    left: 50%;

    @media all and (max-width: 1519px) {
      width: 200px;
      height: 173px;
    }

    @media all and (min-width: 1520px) and (max-width: 1850px) {
      width: 220px;
      height: 190px;
    }

    @media all and (min-width: 1851px) and (max-width: 2000px) {
      width: 250px;
      height: 210px;
    }

    @media all and (min-width: 2001px) {
      width: 300px;
      height: 260px;
    }

    .white-layout {
      background: #fff;
      width: 100%;
      height: 100%;
      clip-path: polygon(25% 0, 75% 0, 100% 50%, 75% 100%, 25% 100%, 0 50%);
      cursor: pointer;

      .colorful-layout {
        position: relative;
        width: calc(100% - 16px);
        height: calc(100% - 16px);
        margin: 8px;
        top: 8px;
        clip-path: polygon(25% 0, 75% 0, 100% 50%, 75% 100%, 25% 100%, 0 50%);

        .gradient-layer {
          width: 100%;
          height: 50%;
          position: absolute;
          top: 0;
          left: 0;
          z-index: 2;
          background: -moz-linear-gradient(-45deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.005) 40%, rgba(255, 255, 255, 0.15) 100%);
          background: -webkit-linear-gradient(-45deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.005) 40%, rgba(255, 255, 255, 0.15) 100%);
          background: linear-gradient(135deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.005) 40%, rgba(255, 255, 255, 0.15) 100%);
        }
      }

    }

    &.center-position {
      transform: translate(-50%, -50%);

      .colorful-layout {
        background: #6a7181;

        .menu-item-icon {
          width: 30%;
          background-image: url("../assets/menuOrderSystem/icon/iconReportForThePreviousPeriodV2.svg");
        }
      }
    }

    &.top-right-position {
      transform: translate(30%, -102.5%);

      .colorful-layout {
        background: #4d897d;

        .menu-item-icon {
          width: 30%;
          background-image: url("../assets/menuOrderSystem/icon/iconSafety.svg");
        }
      }
    }

    &.middle-right-position {
      transform: translate(110%, -50%);

      .colorful-layout {
        background: #7C6580;

        .menu-item-icon {
          background-image: url("../assets/menuOrderSystem/icon/lens.svg");
          background-size: 40%;

        }
      }
    }

    &.bottom-right-position {
      transform: translate(30%, 2.5%);

      .colorful-layout {
        background: #566a8f;
        .menu-item-icon {
          background-image: url(../assets/menuOrderSystem/icon/iconDocumentation.svg);
        }
      }
    }

    &.bottom-position {
      transform: translate(-50%, 55%);

      .colorful-layout {
        background: #598d9b;

        .menu-item-icon {
          width: 30%;
          background-image: url("../assets/menuOrderSystem/icon/iconRoutes.svg");
        }
      }
    }

    &.bottom-left-position {
      transform: translate(-130%, 2.5%);

      .colorful-layout {
        background: #4d897d;

        .menu-item-icon {
          width: 21%;
          background-image: url("../assets/menuOrderSystem/icon/iconBookPrescription.svg");
        }
      }
    }

    &.middle-left-position {
      transform: translate(-210%, -50%);

      .colorful-layout {
        background: #7C6580;

        .menu-item-icon {
          width: 30%;
          background-image: url("../assets/menuOrderSystem/icon/iconSOUR.svg");
        }
      }
    }

    &.top-left-position {
      transform: translate(-130%, -102.5%);

      .colorful-layout {
        background: #566a8f;

        .menu-item-icon {
          width: 40%;
          background-image: url("../assets/menuOrderSystem/icon/iconToGiveOutOrderV2.svg");
        }
      }
    }

    &.top-left-position2 {
      transform: translate(-210%, 55%);

      .colorful-layout {
        background: #566a8f;

        .menu-item-icon {
          width: 40%;
          background-image: url("../assets/menuOrderSystem/icon/iconSOUR.svg");
        }
      }
    }

    &.top-position {
      transform: translate(-50%, -155%);

      .colorful-layout {
        background: #598d9b;

        .menu-item-icon {
          width: 30%;
          background-image: url("../assets/menuOrderSystem/icon/iconGasAnalyse.svg");
        }
      }
    }


    &-icon {
      background-size: contain;
      height: 50%;
      background-repeat: no-repeat;
      position: relative;
      margin: 0 auto;
      top: 0;
      background-position: center;
    }

    &-title {
      position: relative;
      height: 50%;
      text-align: center;
      color: #fff;
      margin: 0 auto;
      line-height: 1.4;

      &-text {
        font-size: 14px;
      }

      span {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
      }

      @media all and (max-width: 1519px) {
        font-size: 12px;
      }

      @media all and (min-width: 1520px) and (max-width: 1680px) {

        font-size: 13px;

        .menu-item-title-text {
          font-size: 13px !important;
        }
      }

      @media all and (min-width: 1681px) and (max-width: 1920px) {
        font-size: 16px;
      }

      @media all and (min-width: 1921px) and (max-width: 2559px) {
        font-size: 16px;
      }

      @media all and (min-width: 2560px) {
        font-size: 18px;
      }
    }
  }

  a {
    padding: 10px 20px;
    color: black;
    font-size: 14px;
    text-decoration: none;

  }
}

.navbar-toro {
  margin-top: 30px;
  margin-left: 110px;
  width: 70px;
  border: none;
  text-align: center;
  background: #fff;
  box-shadow: 1px 1px 2px 1px #a7a7a7;

  p {
    margin: 0;
    padding-top: 5px;
    width: 100%;
    height: 30px;
  }
}

.navbar-injury-accounting {
  margin: 0;
  border: none;
  text-align: center;
  background: #fff;
  box-shadow: 1px 1px 2px 1px #a7a7a7;
  white-space: nowrap;

  p {
    margin: 0;
    padding-top: 5px;
    width: 100%;
    height: 30px;
  }
}

.navbar-ppk {
  margin-top: 30px;
  margin-left: 80px;
  width: 70px;
  border: none;
  text-align: center;
  background: #fff;
  box-shadow: 1px 1px 2px 1px #a7a7a7;

  p {
    margin: 0;
    padding-top: 5px;
    width: 100%;
    height: 30px;
  }
}

.navbar-siz-control {
  margin: 0;
  border: none;
  text-align: center;
  background: #fff;
  box-shadow: 1px 1px 2px 1px #a7a7a7;
  white-space: nowrap;

  p {
    margin: 0;
    padding-top: 5px;
    width: 100%;
    height: 30px;
  }
}

.triangle {
  position: absolute;
  content: '';
  display: inline-block;
  top: 40%;
  left: 5px;
  width: 0;
  height: 0;
  border-left: 3px solid transparent;
  border-right: 3px solid transparent;
  border-top: 6px solid #727272;
}

.triangleRotate {
  transform: rotate(180deg);
}

.methane-analysis-box {
  padding-right: 45px;
  padding-top: 120px;
  line-height: 1.3;

  .ch4-icon {
    width: 70px;
    position: absolute;
    top: 15%;
    left: 50%;
    transform: translateX(-50%);
  }

  .methane-analysis-block {
    font-size: 15px;
  }
}

.routes-box {
  padding-top: 150px;
  

  img {
    padding-top: 10px;
    padding-left: 10px;
    padding-right: 15px;
  }
}

.documentation-modal-container {
  position: absolute;
  top: 0;
  left: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.3);
  z-index: 9990;

  .documentation-modal {
    width: 450px;
    background-color: white;
    font-size: 12px;
    border: 2px solid white;
    color: white;


    .title-modal {
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      margin-bottom: 15px;
      background-color: #56698f;

      .close-modal-btn {
        position: absolute;
        top: 50%;
        right: 10px;
        width: 20px;
        height: 20px;
        transform: translate(0, -50%);
        background: url(../modules/prof-security/assets/close-white-fire.png) center no-repeat;
        background-size: 120% 120%;
        cursor: pointer;
      }
    }

    .btns-container {
      position: relative;
      display: flex;
      justify-content: space-between;

      a {
        text-decoration: none;
      }

      .passport-btn-container {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 35px;
        width: 200px;
        background-color: #5f94a2;
        color: white;
        cursor: pointer;
        clip-path: polygon(0 0, 0 100%, 100% 100%, 88.7% 0);
      }

      .passport-btn-container:hover {
        background-color: #518593;
      }

      .library-btn-container {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 35px;
        width: 200px;
        padding-left: 20px;
        background-color: #4d897c;
        color: white;
        cursor: pointer;
        clip-path: polygon(11.7% 0, 0 100%, 100% 100%, 100% 0);
      }

      .library-btn-container:hover {
        background-color: #3f7a6d;
      }
    }
  }
}

.documentation-btn {
  cursor: pointer;
}

.user-account {
  height: 40px;
  position: absolute;
  right: 0;
  opacity: 0;
}

.invisible-btn {
  position: absolute;
  left: 0;
  top: 30%;
  height: 30px;
  width: 30px;
  background: #999;
  color: #fff;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 0 15px 0 30px;
  text-decoration: none;
  opacity: 0;
}

.body {
  background-color: #f2f2f2;
}

</style>
