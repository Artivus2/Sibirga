<template>
<div class="custom-select" :tabindex="tabindex" @blur="open = false">
    <div class="selected report-block" :class="{ open: open }" @click="open = !open">
        <span>{{ selected }}</span>
    </div>
    <div class="items" :class="{ selectHide: !open }">
      <div class="report-block"
        v-for="(option, i) of options"
        :key="i"
        @click="
          selected = option.title;
          selectedid = option.iconActive;
          open = false;
          $emit('selectedMine', option.id);
        "
      >
      
     <div>{{ option.title }} </div>
     
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

      chosenDate: {                                                                                           // выбранная дата
        monthNumber: new Date().getMonth(),                                                                 // месяц(по умолчанию текущий)
        year: new Date().getFullYear(),                                                                     // год(по умолчанию текущий)
      },
      
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
    
    this.$emit("selectedMine", this.tabindex);
    console.log(this.tabindex);
  },
  methods: {

    getdiff(madate) {
      return this.chosenDate.year - madate
    },
   
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
  width: 360px;
  text-align: center;
  display: flex;
  // outline: none;
  clip-path: polygon(0 0, 100% 0, calc(100% - 30px * 0.6) 100%, calc(100% - 120px * 0.6) 400%, calc(120px * 0.6) 400%, calc(30px * 0.6) 100%);
  
  flex-flow: row nowrap;
  justify-content: center;
  //align-items: center;
  height: 27px;
  line-height: 27px;
  margin-top: -10px;
  margin-bottom: 10px;
  margin-left: 100px;
  background: #B2D63C;
  z-index: 1;
  
}





.custom-select .selected {

  background: #B2D63C;
  justify-content: center;
  align-items: center;
  color: #000000;
  padding-left: 1em;
  cursor: pointer;
  user-select: none;
}



.custom-select .selected.open {

  justify-content: center;
  align-items: center;

}



.custom-select .selected:after {

  position: absolute;
  content: "";
  top: 22px;
  right: 1em;
  width: 0;
  height: 0;

}



.custom-select .items {

  color: #000000;
  overflow: hidden;
  position: absolute;
  background: #B2D63C;
  left: 0;
  right: 0;
  z-index: 1;
 
}



.custom-select .items div {

  justify-content: center;
  align-items: center;
  position: relative;
  padding-left: 1em;
  cursor: pointer;
  user-select: none;
}



.custom-select .items div:hover {

  background: #c6eb41;

}



.selectHide {

  display: none;

}

.report-block {
      width: 100%;
      justify-content: center;
      align-items: center;
      
      
     

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
          background: #c6eb41;
        }
      }
    }
   .department-block {
        width: 360px;
        display: flex;
        flex-flow: row nowrap;
        background: #B2D63C;
        clip-path: polygon(0 0, 100% 0, calc(100% - 30px * 0.6) 100%, calc(30px * 0.6) 100%);
        justify-content: center;
        align-items: center;
        position: relative;
        

        &:hover {
          cursor: pointer;
          background: #c6eb41;
        }

        & > span:first-of-type {
          white-space: nowrap;
          text-overflow: ellipsis;
          overflow: hidden;
        }

        .glyphicon {
          position: absolute;
          top: 12px;
          right: 15px;
          font-size: 8px;
        }
      }

      .departmentDropDownList {
        width: 400px;
        position: absolute;
        top: 33px;
        z-index: 9999;
      }
   
    .dropDownList {
  position: relative;
  font-size: 14px;
  text-align: left;
  background-color: #6A7080;
  padding: 3px;
  box-shadow: 5px 6px 4px 0 rgba(0, 0, 0, 0.35);

  &__list {
    overflow-y: scroll;
    overflow-x: hidden;
    height: 250px;
    margin: 2px;
    background-color: #fff;

    .worker-row {
      padding: 5px 5px 5px 15px;
      position: relative;
      display: flex;
      height: 40px;

      &:hover {
        background-color: #ebecec;
        cursor: pointer;
      }
    }

    .worker-row > .tabel-number {
      position: absolute;
      right: 5px;
      width: 100px;
      text-align: right;
    }

    .worker-row > .fullname {
      position: absolute;
      left: 10px;
    }
  }

  &__header {
    display: flex;
    align-items: center;
    padding-left: 5px;
    color: #fff;
    height: 30px;

    &:hover {
      cursor: move;
    }

    p {
      margin: 0;
    }
  }

  &__search {
    input {
      width: 90%;
      margin: 3px;
      border-radius: 5px;
      border: unset;
      padding-left: 5px;
    }
  }

  .search-input {
    width: 100%;
    height: 100%;
    padding-right: 47px;
    padding-left: 10px;
    color: #000;
    outline: none;
    border: none;
  }

  .container-search-input {
    position: relative;
    width: calc(100%);
    height: 32px;
    margin: 1px;
    border: 2px solid #ababab;
  }

  .btn-search {
    width: 23px;
    height: 26px;
    background: url('../assets/icon/search.png') no-repeat center;
    background-size: 100%, 100%;
  }

  .input-form-btns {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translate(0, -50%);
    padding: 0;
    border: none;
    cursor: pointer;
    outline: none;
  }

  .btn-reset-text {
    width: 23px;
    height: 26px;
    margin-right: 23px;
    background: url('../assets/icon/resetText.png') no-repeat center;
    background-size: 100%, 100%;
  }

  &__closeBtn {
    position: absolute;
    z-index: 1000;
    top: 15px;
    right: 5px;
    cursor: pointer;
  }

  &__closeBtnLine {
    display: block;
    width: 15px;
    height: 3px;
    background-color: #fff;

    transform: rotate(45deg);

    &::before {
      content: '';
      display: block;
      width: 15px;
      height: 3px;
      background-color: inherit;
      transform: rotate(90deg);

    }

  }
}


.company-title {
  background-color: #0003;
}

.company-title, .department-title {
  padding: 5px;
  margin: 0;
  border-bottom: 1px solid #fff;
  cursor: pointer;
  width: 100%;
}

.collapsed-items {
  padding-left: 20px;
  display: none;
}

.department-title:hover {
  background-color: #eee;
}

.company-title:hover {
  background-color: #00000015;
}

.ghost {
  opacity: 0.9;
  background: #c6eb41;
 
}

</style>


