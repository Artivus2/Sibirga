<template>
 <div class = "main-content"><span class="main-text">Управление</span></div>
 <br />
 <RombUsersComponent></RombUsersComponent>
 <RombDriversComponent></RombDriversComponent>
 <RombAutoComponent></RombAutoComponent>
</template>


<script>
import RombUsersComponent from './components/RombUsersComponent.vue'
import RombDriversComponent from './components/RombDriversComponent.vue'
import RombAutoComponent from './components/RombAutoComponent.vue'

export default {
components: {
RombUsersComponent,
RombDriversComponent,
RombAutoComponent,
}
};
</script>

<style>
.main-content {
display: block;
margin-top:70px;
text-align: center;
}
.main-text{

font-size: x-large;
}
</style>
