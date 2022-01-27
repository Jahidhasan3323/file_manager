<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="border d-flex justify-content-between mb-2 px-2 py-2">
                                    <div class="w-100 d-flex gap-2">
                                        <nav aria-label="breadcrumb">
                                            <ol class="breadcrumb mb-0 py-1">
                                                <li class="breadcrumb-item active" aria-current="page"
                                                    @click="relativePath = '/'; getData(1)"
                                                    style="cursor:pointer">
                                                    <i class="fas fa-folder-open"></i> Root
                                                </li>
                                                <template v-if="relativePath != '/'">
                                                    <li class="breadcrumb-item active" aria-current="page"
                                                        @click="relativePath = path; getData(1)"
                                                        v-for="path in relativePath.split('/')" style="cursor:pointer">
                                                        {{ path }}
                                                    </li>
                                                </template>
                                            </ol>
                                        </nav>
                                    </div>
                                </div>
                                <ul class="list-group" v-if="directories.length > 0">
                                    <li v-for="directory in directories" class="list-group-item">
                                        <span style="cursor: pointer"
                                              @click="relativePath = directory.relativePath; getData(1)">
                                            <i class="fas fa-folder text-warning"></i>
                                            {{
                                                directory.text
                                            }}
                                        </span>
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
                                <div class="border d-flex justify-content-between mb-2 px-2 py-2">
                                    <div class="d-flex">
                                        <button @click="getData(1)" type="button" class="btn btn-default btn-sm mx-1"
                                                title="Refresh">
                                            <i :class="`fas fa-sync ${loading ? 'fa-pulse' : ''}`"></i>
                                        </button>
                                        <file-upload :max-file-size="maxFileSize" class="mx-1 "></file-upload>
                                        <directory class="mx-1" ref="header"></directory>
                                    </div>

                                    <div class="d-flex gap-1">
                                        <label for="search_"
                                               class="d-flex text-sm font-weight-normal align-items-center m-0">
                                            Search:&nbsp;
                                            <input id="search_" v-model="search" class="form-control form-control-sm"
                                                   placeholder="search here"/>
                                        </label>
                                        <label for="perPage_"
                                               class="d-flex text-sm font-weight-normal align-items-center m-0">
                                            Show &nbsp;
                                            <select id="perPage_" class="form-control form-control-sm"
                                                    style="width: 40px;"
                                                    title="Select items per page"
                                                    v-model="per_page" @change="getData(1)">
                                                <option class="3">3</option>
                                                <option class="5">5</option>
                                                <option class="10">10</option>
                                                <option class="25">25</option>
                                                <option class="50">50</option>
                                            </select>
                                            &nbsp; items
                                        </label>
                                    </div>
                                </div>
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col" @click="sort = sort=='asc' ? 'desc' : 'asc' ; getData(1);">
                                            Title
                                            <i :class="`fas fa-sort-amount-${sort=='asc' ? 'down':'up'}-alt`"></i>
                                        </th>
                                        <th scope="col">Ext</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(item, ind) in files.data">
                                        <td>{{ parseInt(ind) + 1 }}</td>
                                        <td>{{ item.text }}</td>
                                        <td>{{ item.ext }}</td>
                                        <td>
                                            <button class="btn btn-success btn-sm"
                                                    @click="downloadFile('storage/'+item.relativePath, item.ext)">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-primary btn-sm"
                                                    @click="changeType='copy'; changeFileType='file'; selectedDir=item.relativePath"
                                                    data-toggle="modal"
                                                    data-target="#directoryCopyModal" data-backdrop="static"
                                                    data-keyboard="false" title="copy">
                                                <i class="fas fa-copy "></i>
                                            </button>
                                            <button class="btn btn-warning btn-sm"
                                                    @click="changeType='move'; changeFileType='file'; selectedDir=item.relativePath"
                                                    data-toggle="modal"
                                                    data-target="#directoryCopyModal" data-backdrop="static"
                                                    data-keyboard="false" title="move">
                                                <i class="fas fa-arrows-alt text-white"></i>
                                            </button>
                                            <!--                                            <button class="btn btn-info btn-sm">view</button>-->
                                            <button class="btn btn-danger btn-sm"
                                                    @click="page=1;deleteFile(item.relativePath)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    </tbody>

                                </table>
                                <pagination :data="files" @pagination-change-page="getData" :limit="2"></pagination>

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
                        <h5 class="modal-title text-capitalize" id="directoryModalLabel">{{ changeType }}
                            {{ changeFileType }}</h5>
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
    props:{
        maxFileSize:{
            type:Number,
            default:100
        },
    },
    components: {
        FileUpload,
        Directory
    },
    data() {
        return {
            files         : {data: []},
            directories   : {data: []},
            isRoot        : false,
            prevDir       : '/',
            currentDir    : '',
            targetDir     : '',
            selectedDir   : '',
            changeType    : '',
            changeFileType: '',
            per_page      : 5,
            current_page  : 1,
            relativePath  : '/',
            loading       : true,
            sort          : 'asc',
            search        : '',
            searchTimeOut : null
        }
    },
    mounted() {
        this.getData()
    },
    methods   : {
        getData(page = 1) {
            this.loading      = true;
            this.current_page = page;
            let relativePath  = this.relativePath ?? "/";
            this.getPrevDir(relativePath);
            this.currentDir = relativePath;
            axios.post('/get-tree', {
                'type'  : this.current_page > 1 ? 'files' : 'all',
                'isRoot': this.isRoot,
                per_page: this.per_page,
                relativePath,
                page    : this.current_page,
                sort    : this.sort,
                search  : this.search
            }).then(({data}) => {
                this.files       = data[0].files;
                //this.directories = data[0].directories
                this.directories = this.current_page > 1
                    ? this.directories
                    : data[0].directories;
            }).finally(() => {
                this.loading = false;
            });
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
                    per_page      : this.per_page,
                    page          : this.current_page,
                    sort          : this.sort,
                    search        : this.search
                }
            }).then(({data}) => {
                if (data.status) {
                    Toast.fire({
                        icon : 'success',
                        title: data.message
                    })
                    this.getData(1);
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
                    this.getData()
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
    },
    watch     : {
        search: function (old_val, new_val) {
            clearTimeout(this.searchTimeOut);
            this.searchTimeOut = setTimeout(() => {
                this.getData();
            }, 700)
        }
    }
}
</script>
