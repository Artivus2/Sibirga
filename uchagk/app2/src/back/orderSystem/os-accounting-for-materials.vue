<template>
    <div>
        <div class="container">
            <h2>Учёт материалов</h2>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'accounting-for-materials',
        props: {
        },
        components: {

        },
        data() {
            return {

            }
        },
        created() {
            this.$store.dispatch('setDefaultData');
        },
    }
</script>

<style>


</style>