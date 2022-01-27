<template>
    <div>
        <button type="button" class="btn btn-default btn-sm" data-target="#fileModal" data-toggle="modal"
                data-backdrop="static" data-keyboard="false" title="Upload File">
            <i class="fas fa-upload"></i>
        </button>

        <!-- Modal -->
        <div class="modal fade" id="fileModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="fileModalLabel">Upload New File</h5>
                        <button @click="closeDirModal" type="button" class="close" data-dismiss="modal"
                                aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form v-on:submit.prevent>
                            <template v-if="files.length > 0">
                                <p v-for="(item, index) in files">{{ item.name }} <span class="badge badge-danger"
                                                                                        @click="removeFile(item, index)"><i
                                    class="fas fa-times"></i></span></p>
                            </template>
                            <input ref="fileUpload" type="file" class="form-control" @change="uploadImageSuccess"
                                   :disabled="isUploading">
                            <progress-bar v-if="progress > 0" :options="options" :value="progress"/>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button @click="closeDirModal" type="button" class="btn btn-secondary" data-dismiss="modal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import VueUploadMultipleImage from 'vue-upload-multiple-image'
// vue progress bar
import ProgressBar            from 'vuejs-progress-bar'
import vue2Dropzone           from 'vue2-dropzone'
import 'vue2-dropzone/dist/vue2Dropzone.min.css'

export default {
    props     : {
        maxFileSize: {
            type     : Number,
            "default": 100
        }
    },
    name      : "FileUpload",
    components: {
        VueUploadMultipleImage,
        ProgressBar,
        vueDropzone: vue2Dropzone
    },
    data() {
        return {
            images         : [],
            isUploading    : false,
            files          : [],
            file           : null,
            selectedFile   : {},
            chunks         : [],
            uploaded       : 0,
            totalChunksSize: 0,
            dropzoneOptions: {
                autoProcessQueue: false,
                url             : 'no-url',
                thumbnailWidth  : 150,
                maxFilesize     : 0.5,
                maxFiles        : 1,
                acceptedFiles   : 'image/*',
                addRemoveLinks  : true,
                headers         : {'Content-Type': 'application/octet-stream'}
            },
            options        : {}
        }
    },
    computed: {
        progress() {
            if (this.totalChunksSize > 0 && this.file) {
                let progress = Math.floor((this.uploaded * 100) / this.totalChunksSize)
                if (progress >= 100) {
                    // this.file_upload_btn = false
                    return 0
                }
                return progress
            }
            return 0
        },
        formData() {
            let formData = new FormData;
            formData.set('is_first', this.chunks.length === this.totalChunksSize);
            formData.set('is_last', this.chunks.length === 1);
            if (this.chunks.length === 1) {
                formData.set('relativePath', this.$parent.currentDir);
            }
            formData.set('file', this.chunks[0], `${this.file.name}.part`);

            return formData;
        },
        config() {
            return {
                method : 'POST',
                data   : this.formData,
                url    : 'upload-file',
                headers: {
                    'Content-Type': 'application/octet-stream'
                },
                // onUploadProgress: event => {
                //     this.uploaded += event.loaded;
                // }
            };
        }
    },
    mounted() {

    },
    methods: {

        closeDirModal() {
            this.$parent.getData(1)
            this.afterUpload()
            this.files = []
        },
        removeFile(file, index) {
            this.files.splice(index, 1)
            this.$parent.deleteApiCall(this.$parent.currentDir + '/' + file.name)
        },
        uploadImageSuccess() {

            this.isUploading = true
            this.file        = this.$refs.fileUpload.files[0]
            if (this.file.size > this.maxFileSize) {
                Toast.fire({
                    icon : 'error',
                    title: '`File size must be greater than ${this.maxFileSize}`'
                })
                return
            }
            this.createChunks();
        },
        beforeRemove(index, done, fileList) {
            var r = confirm("remove image")
            if (r == true) {
                done()
            } else {
            }
        },
        afterUpload() {
            this.files.push(this.$refs.fileUpload.files[0])
            this.$refs.fileUpload.value = null
            this.isUploading            = false
            this.file                   = null
            this.chunks                 = []
            this.uploaded               = 0
            this.totalChunksSize        = 0
            this.selectedFile           = {}
        },
        cancelUpload(errors) {
            Object.entries(errors).forEach(([key, value]) => {
                Toast.fire({
                    icon : 'error',
                    title: value
                })
            })
            this.selectedFile = {}
            this.isUploading  = false
        },
        select(event) {
            this.file = event.target.files.item(0);
            this.createChunks();
        },
        upload() {
            axios(this.config).then(response => {
                this.chunks.shift();
                this.uploaded = this.uploaded + 1;
                if (this.chunks.length === 0) {
                    this.afterUpload()
                }
            }).catch(error => {
                let errors = error?.response?.data?.errors;
                this.cancelUpload(errors)
                this.afterUpload()
            });
        },
        createChunks() {
            let size             = 2408;
            // let size             = 1048576;
            let chunks           = Math.ceil(this.file.size / size);
            this.totalChunksSize = chunks
            for (let i = 0; i < chunks; i++) {
                this.chunks.push(this.file.slice(
                    i * size, Math.min(i * size + size, this.file.size), this.file.type
                ));
            }
        },
        b64toBlob(b64Data, contentType, sliceSize) {
            contentType = contentType || '';
            sliceSize   = sliceSize || 512;

            var byteCharacters = atob(b64Data);
            var byteArrays     = [];

            for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
                var slice = byteCharacters.slice(offset, offset + sliceSize);

                var byteNumbers = new Array(slice.length);
                for (var i = 0; i < slice.length; i++) {
                    byteNumbers[i] = slice.charCodeAt(i);
                }

                var byteArray = new Uint8Array(byteNumbers);

                byteArrays.push(byteArray);
            }

            var blob = new Blob(byteArrays, {type: contentType});
            return blob;
        }
    },
    watch  : {
        chunks(n, o) {
            if (n.length > 0) {
                this.upload();
            }
        }
    },

}
</script>

<style scoped>

</style>
