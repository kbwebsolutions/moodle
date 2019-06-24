<template>
    <div>
        <datatable v-if="$data" v-bind="$data">
        </datatable>
    </div>
</template>
<script>
    export default {
        data: () => {
            return {
                columns: [
                    {title: '', field: 'id', sortable: true},
                    {title: '', field: 'context', sortable: true},
                    {title: '', field: 'instance', sortable: true},
                    {title: '', field: 'authoredbyname', sortable: true},
                    {title: '', field: 'commenttext', sortable: false}
                ],
                data: [],
                total: 0,
                query: {}
            };
        },
        watch: {
            query: {
                handler (query) {
                    var self = this;
                    requirejs(["core/config", "local_tlcore/rest"], function(Config, Rest) {
                        Rest.setRestURL(Config.wwwroot+'/local/commentbank/rest.php');
                        Rest.get('get_comments', {query: JSON.stringify(query)}).then((r) => {
                            const result = r.result;
                            self.data = result.data;
                            self.total = result.total;
                            self.columns = result.columns;
                            // Note, we cannot set the query here or we would go into an infinite loop!
                        });
                    });
                },
                deep: true
            }
        },
        beforeCreate() {
            requirejs([
                "local_commentbank/vue",
                "local_commentbank/vuecomp/th-Filter",
                "local_commentbank/vuecomp/td-HTML"
                ], function(Vue, thFilter, tdHTML) {
                    Vue.component('thFilter', thFilter);
                    Vue.component('tdHTML', tdHTML);
            }); 
        },
        mounted() {
            var self = this;
            requirejs(["local_tlcore/strings"], function(Strings) {
                Strings.get_strings([
                    {key: 'id',             component: 'local_commentbank'},
                    {key: 'context',        component: 'role'},
                    {key: 'instance',       component: 'local_commentbank'},
                    {key: 'authoredby',     component: 'local_commentbank'},
                    {key: 'commenttext',    component: 'local_commentbank'}
                ]).then(function(strings) {
                    self.columns = self.columns.map(c => {
                        let key = c.field;
                        if (key.indexOf('.') > -1) {
                            key = key.split('.')[1];
                        }
                        if (typeof(strings[key]) !== 'undefined') {
                            c.title = strings[key];
                        }
                        return c;
                    });
                });
                
            });
        }
    }
</script>
