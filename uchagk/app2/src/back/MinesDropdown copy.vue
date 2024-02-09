<template>
<div class="custom-select" :tabindex="tabindex" @blur="open = false">
    <div class="selected report-block" :class="{ open: open }" @click="open = !open">
        <img  :src="selectedid"> <div>&nbsp;&nbsp;&nbsp;{{ selected }}</div>
    </div>
    <div class="items" :class="{ selectHide: !open }">
      <div class="report-block"
        v-for="(option, i) of options"
        :key="i"
        @click="
          selected = option.title;
          selectedid = option.iconActive;
          open = false;
          $emit('selectedReport', option.id);
        "
      >
      
      <img  :src="option.iconActive"> <div>{{ option.title }}</div>
      </div>
    </div>
  </div>

</template>

<script>

   
    export default {
  props: {
    options: {
      type: Array,
      required: true,
    },
    default: {
      type: String,
      required: false,
      default: null,
    },
    tabindex: {
      type: Number,
      required: false,
      default: 0,
    },
  },
  data() {
    return {
    selected: this.default
        ? this.default
        : this.options.length > 0
        ? this.options[0]
        : null,
    selectedid: this.options.iconActive
        ? this.options.iconActive
        : this.options.length > 0
        ? this.options[0].iconActive
        : null,
      open: false,
    };
  },
  mounted() {
    
    this.$emit("input", this.tabindex);
    console.log(this.tabindex);
  },
//   methods: {
//     selecteditem() {
//         console.log(this.selectedid)
//         this.open = !this.open;
//     }
// }
};
</script>

<style scoped lang="less">



.custom-select {

  position: relative;

  width: 100%;

  text-align: left;

  outline: none;

  height: 87px;

  line-height: 87px;
  box-shadow: 3px 6px 5px rgba(0,0,0,.35);

}



.custom-select .selected {

  background-color: #f2f2f2;

  border-radius: 6px;

  border: 1px solid #666666;

  color: #000000;

  padding-left: 1em;

  cursor: pointer;

  user-select: none;

  

}



.custom-select .selected.open {

  border: 1px solid #4d897c;

  border-radius: 6px 6px 0px 0px;

  box-shadow: 3px 6px 5px rgba(0,0,0,.35);

}



.custom-select .selected:after {

  position: absolute;

  content: "";

  top: 22px;

  right: 1em;

  width: 0;

  height: 0;

  border: 2px solid #4d897c;

  border-color: #4d897c transparent transparent transparent;

}



.custom-select .items {

  color: #000000;

//   border-radius: 0px 0px 6px 6px;

  overflow: hidden;

  
  position: absolute;

  background-color: #f2f2f2;

  left: 0;

  right: 0;

  z-index: 1;
  box-shadow: 3px 6px 5px rgba(0,0,0,.35);
}



.custom-select .items div {

  

  padding-left: 1em;

  cursor: pointer;

  user-select: none;

}



.custom-select .items div:hover {

  background-color: #ccc;

}



.selectHide {

  display: none;

}

.report-block {
      width: 100%;
      display: flex;
      position: relative;
      border: 0.5px solid #4d897c;

     

      img {
        
        
        margin-top: 12px;
        align-items: center;
        width: 70px;
        height: 60px;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        

        &:hover {
          cursor: pointer;
        }
      }

      div {
        
        
        
        font-size: 12px;
        
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        margin: auto 0;
        text-align: left;
        
        display: flex;
        align-items: center;

        &:hover {
          cursor: pointer;
        }
      }
    }

</style>


