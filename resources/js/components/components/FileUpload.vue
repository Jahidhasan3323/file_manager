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

                            <vue-dropzone
                                ref="fileUpload"
                                id="dropzone"
                                :options="dropzoneOptions"
                                @vdropzone-files-added="uploadImageSuccess"
                                @vdropzone-removed-file="removeFile"
                            ></vue-dropzone>
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
    name      : "FileUpload",
     props:{
        maxFileSize:{
            type:Number,
            default:100
        },
    },
    components: {
        VueUploadMultipleImage,
        ProgressBar,
        vueDropzone: vue2Dropzone
    },
    data() {
        return {
            images         : [],
            isUploading    : false,
            file           : null,
            selectedFile   : {},
            chunks         : [],
            uploaded       : 0,
            totalChunksSize: 0,
            dropzoneOptions: {
                autoProcessQueue: false,
                url             : 'no-url',
                thumbnailWidth  : 150,
                // acceptedFiles  : '.exe',
                maxFilesize     : ((this.maxFileSize / 1024) / 1024),
                maxFiles        : 1,
                addRemoveLinks: true,
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
            // TODO::remove file list from dropzon
            // this.$refs.fileUpload.removeAllFiles(false)
            this.$parent.getData(1)
            this.afterUpload()
        },
        removeFile(file, error, xhr) {
            this.$parent.deleteApiCall(this.$parent.currentDir + '/' + file.name)
        },
        uploadImageSuccess(fileList) {
            console.log(fileList)
            $(".dz-progress").remove();
            this.$refs.fileUpload.removeEventListeners()
            Object.entries(fileList).forEach(ele => {
                this.selectedFile = ele[1]
                // console.log(formData, fileList, '1', fileList[0].isUploaded)
                const fileUrl     = setInterval(() => {
                    if (ele[1].dataURL) {
                        // TODO::need to upload multiple image
                        if (ele[1].status === "queued") {
                            this.isUploading = true
                            var ImageURL     = ele[1].dataURL
                            // Split the base64 string in data and contentType
                            var block        = ImageURL.split(";");
                            // Get the content type of the image
                            var contentType  = block[0].split(":")[1];// In this case "image/gif"
                            // get the real base64 content of the file
                            var realData = block[1].split(",")[1];// In this case "R0lGODlhPQBEAPeoAJosM...."
                            // Convert it to a blob to upload
                            this.file      = this.b64toBlob(realData, contentType);
                            this.file.name = ele[1].name
                            this.createChunks();
                        }
                        ele[1].status = 'uploaded'
                        clearInterval(fileUrl)
                    }
                }, 100)
            })
            // console.log(fileList, '2', fileList[0].isUploaded)
        },
        beforeRemove(index, done, fileList) {
            var r = confirm("remove image")
            if (r == true) {
                done()
            } else {
            }
        },
        afterUpload() {
            this.isUploading     = false
            this.file            = null
            this.chunks          = []
            this.uploaded        = 0
            this.totalChunksSize = 0
            this.selectedFile    = {}
            this.$refs.fileUpload.setupEventListeners()
        },
        cancelUpload(errors) {
            //this.$refs.fileUpload.removeFile(this.selectedFile)
            Object.entries(errors).forEach(([key, value]) => {
                Toast.fire({
                    icon : 'error',
                    title: value
                })
            })
            this.selectedFile = {}
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
                 this.$refs.fileUpload.setupEventListeners()
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
