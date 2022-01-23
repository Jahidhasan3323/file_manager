<template>
    <div>

        <button type="button" class="btn btn-info" data-target="#directoryModal" data-toggle="modal"
                data-backdrop="static" data-keyboard="false">
            Create Directory
        </button>
        <!-- Modal -->
        <div class="modal fade" id="directoryModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="directoryModalLabel">Create New Directory</h5>
                        <button @click="closeDirModal" type="button" class="close" data-dismiss="modal"
                                aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form v-on:submit.prevent>
                            <div class="form-group">
                                <label for="directoryName">Directory Name</label>
                                <input type="text" v-model="directoryName" class="form-control" id="directoryName"
                                       placeholder="Directory Name..." required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button @click="closeDirModal" type="button" class="btn btn-secondary" data-dismiss="modal">
                            Close
                        </button>
                        <button @click="submitDirForm" type="button" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name   : "Directory",
    data() {
        return {
            directoryName    : '',
            editDirectoryName: '',
            isEdit           : false
        }
    },
    mounted() {

    },
    methods: {
        submitDirForm() {
            if (this.isEdit) {
                this.renameDir()
            } else {
                this.makeDir()
            }
            $("#directoryModal .close").click()
            this.closeDirModal()
        },
        closeDirModal() {
            this.directoryName     = ''
            this.isEdit            = false
            this.editDirectoryName = ''
        },
        makeDir() {
            axios.post('/make-directory', {
                'relativePath' : this.$parent.currentDir,
                'directoryName': this.directoryName
            }).then(({data}) => {
                if (data.status) {
                    Toast.fire({
                        icon : 'success',
                        title: data.message
                    })
                    this.$parent.files       = data.data.files
                    this.$parent.directories = data.data.directories
                } else {
                    Toast.fire({
                        icon : 'error',
                        title: data.message
                    })
                }
            }).catch((error) => {
                let errors = error.response.data.errors;
                Object.entries(errors).forEach(([key, value]) => {
                    Toast.fire({
                        icon : 'error',
                        title: value
                    })
                })
            })
        },
        renameDir() {
            axios.post('/rename-directory', {
                'relativePath'     : this.$parent.currentDir,
                'editDirectoryName': this.editDirectoryName,
                'directoryName'    : this.directoryName
            }).then(({data}) => {
                if (data.status) {
                    Toast.fire({
                        icon : 'success',
                        title: data.message
                    })
                    this.$parent.files       = data.data.files
                    this.$parent.directories = data.data.directories
                    this.directoryName       = ''
                    this.isEdit              = false
                } else {
                    Toast.fire({
                        icon : 'error',
                        title: data.message
                    })
                }
            }).catch((error) => {
                let errors = error.response.data.errors;
                Object.entries(errors).forEach(([key, value]) => {
                    Toast.fire({
                        icon : 'error',
                        title: value
                    })
                })
            })
        },
    }
}
</script>

<style scoped>

</style>
