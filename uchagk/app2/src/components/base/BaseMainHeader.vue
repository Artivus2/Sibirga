<!--
  - Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
  -->

<!--Компонент шапки страниц, представляет из себя строку с названием страницы,
кнопки по переключению на полноэкранный режим и перехода на главную страницу Блока-->

<template>
  <div class="base-header-main" ref="baseHeader">
    <div class="caption-btn-back" @click.stop="returnToPreviousPage()">
      <img src="../../assets/arrow-left.svg" alt="arrowLeft">
    </div>
    <span>{{ headerText }}</span>
    <!-- <div class="caption-btn">
      <button @click.stop="$emit('setFullScreenMode')" class="caption-btn-maxWindow"></button>
      <span class="vertical-line">|</span>
      <button class="caption-btn-close" @click="$emit('closeHeaderEvent')"><span></span></button>
    </div> -->
  </div>
</template>

<script>
export default {
  name: "BaseMainHeader",
  props: {
    headerText: {
      type: String,
      required: true
    },
    headerColor: {
      type: String,
      required: false,
      default: () => ("rgba(86, 105, 143, 1)")
    },
  },
  methods: {
    /**
     * Возврат на предыдущую страницу
     */
    returnToPreviousPage() {
      window.history.length > 1 ? this.$router.go(-1) : this.$router.push('/order-system');
    },
  },
  // mounted() {
  //   this.$refs.baseHeader.style.background = this.headerColor;
  // },
  // updated() {
  //   this.$refs.baseHeader.style.background = this.headerColor;
  // }
}
</script>

<style scoped lang="less">
.base-header-main {
  
  position: relative;
  min-width: calc(35vw);
  height: 40px;
  color: #fff;
  display: flex;
  box-shadow: 3px 6px 5px rgba(0, 0, 0, 0.35);
  
  margin: 10px;
  background-color: #2c3e50;

  span {
    margin: auto;
    font-size: 16px;
  }

  .caption-btn-back {
    position: absolute;
    width: 40px;
    height: 40px;
    display: flex;
    left: 20px;
    cursor: pointer;

    img {
      margin: auto;
      width: 20px;
    }
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
      height: 14px;
      width: 14px;
      border: none;
      z-index: 140;
      cursor: pointer;
      position: relative;
      top: 4px;

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
</style>
