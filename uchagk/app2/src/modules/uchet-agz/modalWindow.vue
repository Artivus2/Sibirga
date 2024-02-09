<template>

  <div class="signs-button" @click.stop="openModal">
        <span>{{ signed }}</span>
  </div>

<transition name="modal">
<div v-if="showModal" class="modal-shadow2" @click.self="closeModal">
    <div class="modal2">
<div class="modal2-close2" @click="closeModal">&#10006;</div>
    <slot name="title">
    <h3 class="modal2-title2">Прислоните идентификатор:</h3>
    </slot>

    <div class="modal2-content2">
    Для идентифиакции используется смарт-карта, считыватель, либо другое совместимое устройство
    </div>


    <div class="modal2-footer2">
    <button class="modal2-footer2__button2" @click="closeModal">
    {{ buttonCaption }} ({{ timerm }})
    </button>
    </div>

    </div>
 </div>
 </transition>
</template>
<script>


export default {
  name: "signs",
  props: {
    options: {
      type: String,
      required: false,
    },
    timer: {
      type: Number,
      required: false,
      default: 60,
    },
  },
  data() {
    return {
      signed: 'Подписать ЭЦП',
      showModal: false,
      timerm: this.timer,
      //buttonCaption: this.options ? this.options : 'Отменить'
      
    }
  },
  methods: {
    openModal() { 
      this.showModal = true
      let interval = setInterval(() => 
        {
          this.timerm--;
          if (this.timerm == 0) {
            clearInterval(interval); 
            this.closeModal();
            this.timerm = 60;
          }
        }, 1000);

    },
    closeModal() {
      this.showModal = false
      this.timerm = 1;
      this.signed = "Подписано ЭЦП";
    }
  }
}


</script>
<style scoped lang="scss">
.modal-shadow2 {
position: absolute;
top: 0;
left: 0;
height: 100vh;
width: 100%;
background: rgba(0, 0, 0, 0.39);
}
.modal2 {
background: #fff;
border-radius: 8px;
padding: 15px;
min-width: 420px;
max-width: 480px;
position: absolute;
top: 50%;
left: 50%;
transform: translate(-50%, -50%);
z-index: 2000;
text-align: center;
&-close2 {
border-radius: 50%;
color: #fff;
background: #4d897d;
display: flex;
align-items: center;
text-align: center;
justify-content: center;
position: absolute;
top: 7px;
right: 7px;
width: 30px;
height: 30px;
cursor: pointer;
}
&-title2 {
color: #000;
text-align: center;
}
&-content2 {
margin-bottom: 20px;
text-align: center;
}
&-footer2 {
&__button2 {
background-color: #4d897d;
color: #fff;
border: none;
text-align: center;
padding: 8px;
font-size: 17px;
font-weight: 500;
border-radius: 8px;
min-width: 200px;
cursor: pointer;
text-align: center;
}
}
}
.ButtonSave {
  display: flex;
  justify-content: center;
  height: 15px;
  width: 95px;
  overflow: hidden;
  position: relative;
  text-align: center;

  &-group {
    height: 100%;
    width: 80px;
    background: #4d897d;
    transform: skew(45deg);
    cursor: pointer;
    text-align: center;

    &-name {
      display: flex;
      height: 100%;
      text-align: center;

      span {
        margin: auto;
        transform: skew(-45deg);
        color: white;
        text-align: center;
        font-size: 8px;
        font-weight: 500;
      }
    }
  }
}
.modal-enter-active, .modal-leave-active {
transition: opacity 1s
}
.modal-enter, .modal-leave-to {
opacity: 0
}


.signs-button {
  
  justify-content: center;
  align-items: center;
  position: relative;
  height: 30px;
  width: 200px;
  background-color: #7c948e;
  
    margin-right: 3px;
    -webkit-transform: skew(-35deg);
    -moz-transform: skew(-35deg);
    -ms-transform: skew(-35deg);
    -o-transform: skew(-35deg);
    transform: skew(-35deg);
    cursor: pointer;
  
  > span {
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: skew(35deg);
  }
 
  &:hover {
    background-color: #5EA898;
  }
  
}
</style>