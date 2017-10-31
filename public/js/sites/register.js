axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

var Register = new Vue({
    el: '#resgiter',
    data: {
        items: [],
        pagination: {
            total: 0,
            per_page: 2,
            from: 1,
            to: 0,
            current_page: 1
        },
        offset: 4,
        newItem: {'email': '',
                    'phone': '',
                    'name': '',
                    'password': '',
                    'password_confirmation': '',
                },
        token: [],
        formErrors: {'email': '',
                        'phone': '',
                        'name': '',
                        'password': '',
                    },
    },
        mounted : function(){},

        methods: {
            createItem: function() {
                var self = this;
                var input = this.newItem;
                 axios.post('/api/v0/register', input).then((response) => {
                    Vue.ls.set('token', response.data.data.token);
                    Vue.ls.set('user', response.data.data.user);
                    window.location = '/site/home/';
                 }).catch((error) => {
                    if (error.response.status == 403) {
                        self.formErrors = error.response.data.message;
                        for (key in self.formErrors) {
                            toastr.error(self.formErrors[key], '', {timeOut: 10000});
                        }     
                    }
                });
            }
        }
    });
