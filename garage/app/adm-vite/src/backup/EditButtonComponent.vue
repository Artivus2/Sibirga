<template>

<transition name="modal">
<div v-if="showModal" class="modal-shadow2" @click.self="closeModal">
 <div class="modal2">
 <div class = "info-header">  
 <div class ="close-left"></div>
 <div class = "close-button close-romb">
   <div class = "close-layout" @click="closeModal">&#10006;</div>
   </div>
 </div>
 
 <div class="modal2-content2"><span>Редактирование пользователя {{ index }}</span></div>
 <div>Фамилия Имя Отчество: <input v-model="fio"> fio </div>
 <br />
 <div>Имя пользователя: <input v-model="login">  userData   </div>
 <br />
 
 
 <div class="login-button2 dialog-button2" @click="closeModal">
  <span class="login-button2-text"> Отменить </span>
 </div>
 <div class="login-button2 dialog-button" @click="closeModal">
  <span class="login-button2-text"> Сохранить </span>
 </div>

 </div>
</div>
</transition>

</template>

<script>

import { ref } from 'vue';


export default {
setup() {
    const showModal = ref(false);
    const openModal = (index) => {
    showModal.value = true;
    localStorage.setItem('id', index);
    console.log(index);
    };
    
    const closeModal = () => {
    showModal.value = false;
    };
    return {
      showModal,
      openModal,
      closeModal,
    };
  }
};

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
  border-radius: 4px;
  opacity: 0.9;
  z-index: 1050;
  animation: appear 350ms ease-in;
  animation-fill-mode: forwards; 
  width: 600px;
  min-height: 400px;
  box-shadow:0 14px 28px rgba(0,0,0,0.25);
background: #d7d7d7;
padding: 15px;
position: absolute;
top: 20%;
left: 30%;
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
pointer: cursor;
text-align: center;
}
}
}
.modal-enter-active, .modal-leave-active {
transition: opacity 1s
}
.modal-enter, .modal-leave-to {
opacity: 0
}
.buttons {
    width: 15px;
    height: 15px;
    cursor: pointer;
    position: relative;
    top: 5px;
    right: 1px;
  }
 
.buttons:hover {
background: #567CAB;
}
  


.login-button2 {

  position: relative;
  color: white;
  font-weight: bold;
  text-decoration: none;
  text-shadow: -1px -1px #000;
  user-select: none;
  outline: none;
  background-color: #000;
  background-image: linear-gradient(45deg, rgba(255,255,255,.0) 30%, rgba(255,255,255,.8), rgba(255,255,255,.0) 70%), radial-gradient(190% 100% at 50% 0%, rgba(255,255,255,.7) 0%, rgba(0,0,0,.5) 50%, rgba(0,0,0,0) 50%);
  background-repeat: no-repeat;
  background-size: 200% 100%, auto;
  background-position: 200% 0, 0 0;
  margin: auto;
  width: 150px;
  height: 50px;
  border-width: 2px 2px 0;
  border-radius: 5px 5px 5px 5px;
  padding-top: 15px;
  text-align: center;
}

.login-button2:hover {
transition: 2s linear;
background-position: -200% 0, 0 0;
box-shadow:0 14px 28px rgba(0,0,0,0.25);
filter: drop-shadow(0px 0px 5px black);
cursor: pointer;
}

.login-button2-text {
color: white;
display: flex;
justify-content: center;
}

.dialog-button {
position: absolute;
bottom: 0px;
right: 0px;
margin: 10px;
width: 100px;
height: 30px;
padding: 2px;
}
.dialog-button2 {
position: absolute;
bottom: 0px;
right: 120px;
margin: 10px;
width: 100px;
height: 30px;
padding: 2px;
}

@keyframes appear {
  0%{
    opacity: 0;
  transform: translateY(-10px);
  }
}
.close-left {
min-width: 95%;
}
.close-button {
font-size: large;
position:relative;
text-align-last: center;
margin-top: -24px;
margin-left: -8px;
cursor: pointer;
transition: 0.5s;
}

.close-button:hover {
transform: rotate(360deg);
}

.close-romb {
background: darkblue;
width: 30px;
height: 30px;
-webkit-clip-path: polygon(25% 0,75% 0,100% 50%,75% 100%,25% 100%,0 50%);
clip-path: polygon(25% 0,75% 0,100% 50%,75% 100%,25% 100%,0 50%);
}

.close-layout {
    background: gray;
    clip-path: polygon(25% 0,75% 0,100% 50%,75% 100%,25% 100%,0 50%);
    width: 30px;
    height: 28px;
}
.close-layout:hover {
filter: drop-shadow(10px 10px 5px black);
}
.info-header {
display: flex;
width: 100%;
position: absolute;
}

</style>