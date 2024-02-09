<template>
<div class="upromb">
<div class="shadow-romb">
<div class="romb position">
<div class="colorful-layout" @click="openusers">
<img class = "romb-icon" src='../assets/users.png'>
<div class="romb-text">Пользователи</div>
</div>
</div>
</div>
</div>
<transition name="modal2">
<div v-if="showUsers" class="modal-shadow2" @click.self="closeModal">
 <div class="modal2">
 <div class = "info-header">  
    <div class ="close-left"></div>
    <div class = "close-button close-romb">
     <div class = "close-layout" @click="closeModal">&#10006;
     </div>
    </div>
 </div>
  <div class="user-table-modal">
   <div class="user-table"><span>Таблица пользователей:</span></div>
   <div class="table-row">
    <div class = "table-header" style="width: 5%;">№ п/п</div>
    <div class = "table-header" style="width: 18%;">ФИО</div>
    <div class = "table-header" style="width: 18%;">Имя пользователя</div>
    <div class = "table-header" style="width: 10%;">Табельный номер</div>
    <div class = "table-header" style="width: 20%;">Эл. почта</div>
    <div class = "table-header" style="width: 10%;">Статус</div>
    <div class = "table-header" style="width: 10%;">Действия</div>
   </div>

   <div class="table-row" v-for="(item, index) in requestData" :key="index">
    <div :class = "getRowClass(index)" style="width: 5%;">{{ item.id  }}</div>
    <div :class = "getRowClass(index)" style="width: 18%;">{{ item.fio }}</div>
    <div :class = "getRowClass(index)" style="width: 18%;">{{ item.login }}</div>
    <div :class = "getRowClass(index)" style="width: 10%;">{{ item.tabel_nom }}</div>
    <div :class = "getRowClass(index)" style="width: 20%;">{{ item.email }}</div>
    <div :class = "getRowClass(index)" style="width: 10%;">{{ item.status }}</div>
    <div :class = "getRowClass(index)" style="width: 10%;">
     <img class = "buttons" src="../assets/edit.svg" @click="openModalEdit(index)"/>
    </div>
   </div>
</div>
</div>
</div>
</transition>

<transition name="modal3">
<div v-if="showEdit" class="modal-shadow3" @click.self="closeEdit">
 <div class="modal3">
  <div class = "info-header">  
    <div class ="close-left"></div>
    <div class = "close-button close-romb">
     <div class = "close-layout" @click="closeEdit">&#10006;
     </div>
    </div>
  </div>
 
 <div class="modal2-content2"><span>Редактирование пользователя {{ userData.id }}</span></div>
 <div class="edit-block">
  <div class="edit">Фамилия Имя Отчество: </div><input class="edit-text" v-model="userData.fio"><br />
 </div>
 <div class="edit-block">
  <div class="edit">Имя пользователя(login): </div><input  class="edit-text" v-model="userData.login"><br />
 </div>
 <div class="edit-block">
  <div class="edit">Табельный номер: </div><input class="edit-text" v-model="userData.tabel_nom"><br />
 </div>
 <div class="edit-block">
  <div class="edit">Эл. почта: </div><input class="edit-text" v-model="userData.email"><br />
 </div>
 <div class="edit-block">
  <div class="edit">Статус: </div><input class="edit-text" v-model="userData.status"><br />
 </div>
 <div class="login-button2 dialog-button2" @click="closeEdit">
  <span class="login-button2-text"> Отменить </span>
 </div>
 <div class="login-button2 dialog-button" @click="edit">
  <span class="login-button2-text"> Сохранить </span>
 </div> 
</div> 
</div>
</transition>

</template>

<script>
//import UsersComponent from './components/UsersComponent.vue';
import axios from 'axios';
export default {
data() {
return {
    showUsers: false,
    showEdit: false,
    requestData:[],
    userData:[],
    };
    },
    methods: {
    getRowClass(index) {
    return index % 2 === 0 ? 'even-row' : 'odd-row';
    },
    openModalEdit(index) {
    this.showEdit = true;
    let formData = new FormData();
    formData.append('id', index);
    axios.post('/admin/get-users-by-id', formData).then(response => {
    this.userData = response.data;
    //console.log(this.userData);
    }).catch(error => {
    console.log(error);
    });
    //console.log(index);
    },
    openusers() {
    this.showUsers = true;
    axios.post('/admin/get-users',
    ).then(response => {
    this.requestData = response.data;
    }).catch(error => {
    console.log(error);
    });
    }, 
    closeModal() {
    this.showUsers = false;
    },
    closeEdit() {
    this.showEdit = false;
    },
    edit() {
    console.log(this.userData);
    let editData = new FormData();
    editData.append('id',this.userData.id);
    editData.append('fio',this.userData.fio);
    editData.append('login',this.userData.login);
    editData.append('tabel_nom',this.userData.tabel_nom);
    editData.append('email',this.userData.email);
    editData.append('status',this.userData.status);
    axios.post('/admin/edit-users', editData).then(response => {
    this.userData = response.data;
    console.log(this.userData);
    }).catch(error => {
    console.log(error);
    });
    this.showEdit = false;
    },
    
    },
    };
</script>

<style scoped lang="scss">

.romb-text {
text-align: center;
color: #moccasin;
filter: drop-shadow(0px 0px 5px grey);
padding:12px;
font-size: 13px;
}

.upromb {
display: flex;
z-index: 0;
position: absolute;
}

.romb-icon {
width:100px;
position: relative;
margin-left:23%;
margin-top: 10px;
}

.position {
transform: translate(50vh, 102.5%)
}

.colorful-layout {
    background: lightsteelblue;
    clip-path: polygon(25% 0,75% 0,100% 50%,75% 100%,25% 100%,0 50%);
    position: relative;
    width: calc(100% - 16px);
    height: calc(100% - 16px);
    margin: 8px;
}

.shadow-romb:hover {
filter: drop-shadow(0px 0px 5px black)
}

.romb {
position:absolute;
background: cadetblue;
width: 200px;
height: 200px;
/*-webkit-clip-path: polygon(25% 0,75% 0,100% 50%,75% 100%,25% 100%,0 50%);*/
clip-path: polygon(25% 0,75% 0,100% 50%,75% 100%,25% 100%,0 50%);
cursor: pointer;
/*filter: drop-shadow(0px 0px 5px blue);*/
}

.user-table {
padding: 2px;
position: relative;

}

.edit-block {
padding: 2px;
position: relative;
display: flex;
}

.edit-text {
padding: 2px;
min-width: 350px;
}

.table-header {
display: flex;
padding: 2px;
text-align: center;
justify-content: space-evenly;
border-right: 1px solid #fff;
min-width: 90px;
font-weight: 500;
background: #25987f;
}

.edit {
display: flex;
padding: 2px;
min-width:200px;
}

.table-header:hover {
background: #567CAB;
}

.table-row {
display: flex;
width: 100%;
}

.table-row:hover {
background: lightblue;
}

.even-row {
display: flex;
width: 100%;
padding: 2px;
text-align: center;
justify-content: space-evenly;
border-right: 1px solid #fff;
min-width: 90px;
font-weight: 500;
background: #d9d9d9;
}

.odd-row {
display: flex;
padding: 2px;
text-align: center;
justify-content: space-evenly;
border-right: 1px solid #fff;
min-width: 90px;
font-weight: 500;
width: 100%;
background: seashell;
}



.modal-shadow2 {
position: absolute;
top: 0;
left: 0;
height: 100vh;
width: 100%;
background: rgba(0, 0, 0, 0.29);
}

.modal-shadow3 {
position: absolute;
top: 0;
left: 0;
height: 100vh;
width: 100%;
background: rgba(0, 0, 0, 0.19);
}

.modal3 {
border-radius: 4px;
opacity: 0.95;
z-index: 2050;
animation: appear 350ms ease-in;
animation-fill-mode: forwards; 
width: 80%;
min-height: 400px;
box-shadow:0 14px 28px rgba(0,0,0,0.25);
background: #d6d6d6;
padding: 15px;
position: absolute;
top: 25%;
left: 15%;
text-align: center;
}
.modal2 {
  border-radius: 4px;
  opacity: 0.9;
  z-index: 1050;
  animation: appear 350ms ease-in;
  animation-fill-mode: forwards; 
  width: 80%;
  min-height: 400px;
  box-shadow:0 14px 28px rgba(0,0,0,0.25);
background: #d7d7d7;
padding: 15px;
position: absolute;
top: 20%;
left: 10%;
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
.modal2-enter-active, .modal2-leave-active {
transition: opacity 1s
}
.modal2-enter, .modal2-leave-to {
opacity: 0
}
.modal3-enter-active, .modal3-leave-active {
transition: opacity 1s
}
.modal3-enter, .modal3-leave-to {
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
min-width: 98%;
}
.close-button {
font-size: large;
position:relative;
text-align-last: center;
margin-top: -24px;
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