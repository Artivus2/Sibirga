<!--
 Компонент простого одноуровневого контекстного меню
  -->

<template>
  <div class="context-menu-container">
    <div class="context-menu-option-down" @click.stop="goToOrderPage()">
      <span><img alt="Перейти к наряду" class="context-menu-option-img"
                 src="@/modules/order-system/assets/tableOrder/passport.png"/></span>
      <span class="context-menu-option-title">Перейти к наряду</span>
    </div>

    <div class="context-menu-option-down" @click.stop="goToFillReportPage()">
      <span><img alt="Перейти к отчету" class="context-menu-option-img"
                 src="@/modules/order-system/assets/tableOrder/table_form_check_knowledge_white.png"/></span>
      <span class="context-menu-option-title">Перейти к отчету</span>
    </div>

  </div>
</template>

<script>
export default {
  name: "OrderContextMenu",
  methods: {
    goToOrderPage(event) {
      this.$emit('goToOrderPage');
    },
    goToFillReportPage(event) {
      this.$emit('goToFillReportPage');
    },

  },
}
</script>

<style lang="less" scoped>
.context-menu-container {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.context-menu-option-down {
  background: #fff;
  display: flex;
  justify-content: flex-start;
  align-items: center;

  span:first-child {
    width: 30px;
    height: 30px;
    background: #598e9c;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

.context-menu-option-img {
  width: 20px;
  height: 20px;
}

.context-menu-option-title {
  padding-left: 10px;
}

.context-menu-option-down:hover {
  background: #eee;
  cursor: pointer;
}
</style>
