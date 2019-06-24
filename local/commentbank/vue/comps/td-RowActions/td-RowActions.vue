<template>
    <div>
        <button class="btn btn-default">delete<button>
    </div>
</template>
<script>
export default {
    props: ['row'],
    data: function () {
        return {
            ajaxing: false
        };
    }
}
</script>
