<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <button @click="getData('/')" class="btn btn-info">Root</button>
                        <button @click="getData(prevDir)" class="btn btn-info">Previous</button>
                        <file-upload class="float-right"></file-upload>
                        <directory class="float-right mr-1" ref="header"></directory>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <ul class="list-group" v-if="directories.length > 0">
                                    <li v-for="directory in directories" class="list-group-item">
                                        <span style="cursor: pointer"
                                              @click="getData(directory.relativePath)">{{ directory.text }}</span>
                                        <span @click="deleteDir(directory.relativePath)" class="float-end"><label
                                            class="badge badge-danger mr-1" title="delete"><i class="fas fa-trash"></i></label></span>

                                        <span
                                            @click="changeType='copy'; changeFileType='directory'; selectedDir=directory.relativePath"
                                            class="float-end"><label
                                            class="badge badge-success mr-1" data-toggle="modal"
                                            data-target="#directoryCopyModal" data-backdrop="static"
                                            data-keyboard="false" title="copy"><i
                                            class="fas fa-copy"></i></label></span>
                                        <span
                                            @click="changeType='move'; changeFileType='directory'; selectedDir=directory.relativePath"
                                            class="float-end"><label
                                            class="badge badge-warning mr-1 " data-toggle="modal"
                                            data-target="#directoryCopyModal" data-backdrop="static"
                                            data-keyboard="false" title="move"><i
                                            class="fas fa-arrows-alt text-white"></i></label></span>
                                        <span @click="renameDir(directory.relativePath, directory.text)"
                                              class="float-end mr-1" data-toggle="modal"
                                              data-target="#directoryModal" title="edit"><label
                                            class="badge badge-info"><i class="fas fa-edit"></i></label></span>
                                    </li>
                                </ul>
                                <ul class="list-group" v-else>
                                    <li class="list-group-item">No directory found</li>
                                </ul>
                            </div>
                            <div class="col-md-8">
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Ext</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(item, index) in files">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ item.text }}</td>
                                        <td>{{ item.icon }}</td>
                                        <td>{{ item.ext }}</td>
                                        <td>
                                            <button class="btn btn-success"
                                                    @click="downloadFile('storage/'+item.relativePath, item.ext)">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-primary"
                                                    @click="changeType='copy'; changeFileType='file'; selectedDir=item.relativePath" data-toggle="modal"
                                                    data-target="#directoryCopyModal" data-backdrop="static"
                                                    data-keyboard="false" title="copy">
                                                <i class="fas fa-copy "></i>
                                            </button>
                                            <button class="btn btn-warning"
                                                    @click="changeType='move'; changeFileType='file'; selectedDir=item.relativePath" data-toggle="modal"
                                                    data-target="#directoryCopyModal" data-backdrop="static"
                                                    data-keyboard="false" title="move">
                                                <i class="fas fa-arrows-alt text-white"></i>
                                            </button>
                                            <button class="btn btn-info">view</button>
                                            <button class="btn btn-danger"
                                                    @click="deleteFile(item.relativePath)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="directoryCopyModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-capitalize" id="directoryModalLabel">{{ changeType }} {{ changeFileType }}</h5>
                        <button @click="closeCopyMoveModal" type="button" class="close" data-dismiss="modal"
                                aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form v-on:submit.prevent>
                            <div class="form-group">
                                <label for="directoryName">Target Dir</label>
                                <input type="text" v-model="targetDir" class="form-control" id="directoryName"
                                       placeholder="Target Dir..." required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button ref="Close" @click="closeCopyMoveModal" type="button" class="btn btn-secondary"
                                data-dismiss="modal">
                            Close
                        </button>
                        <button @click="submitCopyMoveForm" type="button" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Directory  from "./components/Directory";
import FileUpload from "./components/FileUpload";

export default {
    components: {
        FileUpload,
        Directory
    },
    data() {
        return {
            files         : [],
            directories   : [],
            isRoot        : false,
            prevDir       : '/',
            currentDir    : '',
            targetDir     : '',
            selectedDir   : '',
            changeType    : '',
            changeFileType: ''
        }
    },
    mounted() {
        this.getData()
    },
    methods   : {
        getData(relativePath = '/') {
            this.getPrevDir(relativePath);
            this.currentDir = relativePath;
            axios.post('/get-tree', {
                'type'        : 'all',
                'isRoot'      : this.isRoot,
                'relativePath': relativePath
            }).then(({data}) => {
                this.files       = data[0].files
                this.directories = data[0].directories
            })
        },
        getPrevDir(relativePath) {
            let relativePathArray = relativePath.split('/')
            relativePathArray.splice(-1)
            this.prevDir = relativePathArray.join('/') ? relativePathArray.join('/') : '/'
        },
        renameDir(relativePath, text) {
            this.$refs.header.editDirectoryName = relativePath
            this.$refs.header.directoryName     = text
            this.$refs.header.isEdit            = true
        },
        deleteDir(relativePath) {
            if (confirm("Do you really want to delete?")) {
                axios.get('/delete-directory', {
                    params: {
                        'relativePath': relativePath,
                        'currentDir'  : this.currentDir,
                    }
                }).then(({data}) => {
                    if (data.status) {
                        Toast.fire({
                            icon : 'success',
                            title: data.message
                        })
                        this.files       = data.data.files
                        this.directories = data.data.directories
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
            }
        },
        deleteFile(relativePath) {
            if (confirm("Do you really want to delete?")) {
                this.deleteApiCall(relativePath)
            }
        },
        deleteApiCall(relativePath) {
            axios.get('/delete-file', {
                params: {
                    'relativePath': relativePath,
                    'currentDir'  : this.currentDir,
                }
            }).then(({data}) => {
                if (data.status) {
                    Toast.fire({
                        icon : 'success',
                        title: data.message
                    })
                    this.files       = data.data.files
                    this.directories = data.data.directories
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
        downloadFile(url, ext) {
            if (!url) {
                alert('Please provide url to download.');
                return;
            } else {
                var m = url.toString().match(/.*\/(.+?)\./);
                if (m && m.length > 1) {
                    var name = m[1];
                }
            }

            axios({
                url         : url,
                method      : 'GET',
                responseType: 'blob',
            }).then((file) => {
                const url  = window.URL.createObjectURL(new Blob([file.data]));
                const link = document.createElement('a');
                link.href  = url;
                let time   = new Date();
                link.setAttribute('download', name + '.' + ext);
                document.body.appendChild(link);
                link.click();
            }).catch(error => {
                toastr.error(error.response.statusText);
            });
        },
        submitCopyMoveForm() {
            axios.post('/change-directory', {
                'targetDir'     : this.targetDir,
                'selectedDir'   : this.selectedDir,
                'currentDir'    : this.currentDir,
                'changeType'    : this.changeType,
                'changeFileType': this.changeFileType,
            }).then(({data}) => {
                if (data.status) {
                    Toast.fire({
                        icon : 'success',
                        title: data.message
                    })
                    this.files       = data.data.files
                    this.directories = data.data.directories
                    this.$refs.Close.click();
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
        closeCopyMoveModal() {
            this.changeType     = ''
            this.changeFileType = ''
            this.targetDir      = ''
            this.selectedDir    = ''
        }
    }
}
</script>
