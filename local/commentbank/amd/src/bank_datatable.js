(function() {
    var pluginJSURL = function(path) {
        return M.cfg.wwwroot + "/pluginfile.php/" + M.cfg.contextid + "/local_commentbank/" + path;
    };

    require.config({
        enforceDefine: false,
        paths: {
            // Vendor code.
            "local_commentbank/vue": [
                "https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.min",
                // CDN Fallback - whoop whoop!
                pluginJSURL("vendorjs/vue")
            ],
            "local_commentbank/vuerouter": [
                "https://cdn.jsdelivr.net/npm/vue-router@2.5.3/dist/vue-router.min",
                // CDN Fallback - whoop whoop!
                pluginJSURL("vendorjs/vuerouter")
            ],

            // Note, vuedatable is not via a CDN because it has been customised (made more accessible).
            "local_commentbank/vuedatatable": pluginJSURL("vendorjs/vuedatatable"),

            // Vue components
            "local_commentbank/vuecomp": [
                pluginJSURL('vue/comps')
            ],
        }
    });
})();

define(['local_commentbank/vue', 'local_commentbank/vuedatatable', 'local_commentbank/vuecomp/main'], function(
    Vue, VueDataTable, VueCompMain
) {
    return {
        init: function() {
            var opts = {
                el: '#local_commentbank_vue',
                router: null
            };
            Vue.use(VueDataTable.default);
            Vue.component('commentbank-main', VueCompMain);


            new Vue(opts).$mount('#local_commentbank_vue');
        }
    };
});