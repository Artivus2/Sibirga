<template>
 <div class="upromb">
  <div class="shadow-romb">
   <div class="romb position">
    <div class="colorful-layout">
     <img class = "romb-icon" src='../assets/auto.png'>
      <div class="romb-text">АвтоТранспорт</div>
      </div>
     </div>
    </div>
   </div>
</template>

<script>

</script>

<style scoped lang="scss">

.position {
transform: translate(150vh, 102.5%)
}

.romb-text {
text-align: center;
color: #moccasin;
filter: drop-shadow(0px 0px 5px grey);
padding:12px;
font-size: 13px;
}

.upromb {
display: flex;
}

.romb-icon {
width:100px;
position: relative;
margin-left:23%;
margin-top: 10px;
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
</style>