<template>
<div class="user-table"><span>Таблица водителей:</span></div>
<div class="table-row">
 <div class = "table-header" style="width: 5%;">№ п/п</div>
 <div class = "table-header" style="width: 20%;">ФИО</div>
 <div class = "table-header" style="width: 10%;">Табельный номер</div>
 <div class = "table-header" style="width: 12%;">Статус</div>
 <div class = "table-header" style="width: 12%;">Действия</div>
</div>
<!-- d9d9d9 seashell 
-->
<div class="table-row" v-for="(item, index) in requestData" :key="index">
 <div :class = "getRowClass(index)" style="width: 5%;">{{ item.id  }}</div>
 <div :class = "getRowClass(index)" style="width: 20%;">{{ item.fio }}</div>
 <div :class = "getRowClass(index)" style="width: 10%;">{{ item.tabelnom }}</div>
 <div :class = "getRowClass(index)" style="width: 12%;">{{ item.status }}</div>
 <div :class = "getRowClass(index)" style="width: 12%;">
    <img class = "buttons" src="../assets/add.svg"/>
    <img class = "buttons" src="../assets/edit.svg"/>
    <img class = "buttons" src="../assets/delete.svg"/>
 </div>
</div>
</template>

<script>

import axios from 'axios';
import { ref } from 'vue';

export default {
data() {

return {
//    requestData: 
//    [
//    {id:0, fio:"sfeh"},
//    {id:1, fio:"werthwrt"},
//    {id:2, fio:"rwergwe"},
//    ],
    requestData: [],
    };
},
methods: {
getRowClass(index) {
return index % 2 === 0 ? 'even-row' : 'odd-row';
}
},
mounted () {
let requestData = [];
axios.post('/garage/get-drivers',
//'Content-Type': 'application/json; charset=utf-8'
).then(response => {
this.requestData = response.data;
}).catch(error => {
console.log(error);
});

console.log(requestData);
return {
requestData,
};
},
};
</script>

<style>
.user-table {
padding: 2px;
position: relative;

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
  
</style>