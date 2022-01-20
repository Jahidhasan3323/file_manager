<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Example Component</div>

                    <div class="card-body">
                        I'm an example component.
                        <file-manager :settings="settings"></file-manager>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data(){
          return{
              settings: {
                  // axios headers
                  headers: {
                      'X-Requested-With': 'XMLHttpRequest',
                      // Authorization: `Bearer ${window.localStorage.getItem('user-token')}`,
                  },
                  baseUrl: 'http://test.loc/file-manager/',   // overwrite base url Axios
                  windowsConfig: 2,                           // overwrite config
                  lang: 'de',                                 // set language

              },
          }
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
