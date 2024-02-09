<!--
 Компонент простого одноуровневого контекстного меню
  -->

<template>
    <div class="context-menu-container">
        <div class="context-menu-option" @click.stop="deleteOrderTemplate($event)">
            <span><img class="context-menu-option-img" alt="Удалить шаблон" src="../../assets/delete.png" /></span>
            <span class="context-menu-option-title">Удалить шаблон</span>
        </div>
    </div>
</template>

<script>
    export default {
        name: "orderTemplateContextMenu",
        data() {
            return {

            }
        },
        computed: {

        },
        methods: {
            deleteOrderTemplate() {
                this.$emit("deleteOrderTemplate");
            }
        },
    }
</script>

<style scoped>
    .context-menu-container {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .context-menu-option {
        background: #fff;
        display: flex;
        align-items: center;
    }

    .context-menu-option-img {
        background: #f3f3f3;
        width: 30px;
        height: 30px;
    }

    .context-menu-option-title {
        padding-left: 10px;
    }

    .context-menu-option:hover {
        background: #eee;
        cursor: pointer;
    }
    .context-menu-option:not(:last-of-type) {
        /*border-bottom: 1px solid black;*/
    }
</style>
