<template>
    <div>
        <button type="button" class="btn btn-info" data-target="#fileModal" data-toggle="modal"
                data-backdrop="static" data-keyboard="false">
            Upload File
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
                            <vue-upload-multiple-image
                                dragText="Drag an image here"
                                browseText=""
                                @upload-success="uploadImageSuccess"
                                @before-remove="beforeRemove"
                                :data-images="images"
                                idUpload="myIdUpload"
                                editUpload="myIdEdit"
                                :disabled="isUploading"
                                dropText="drop your image here"
                                ref="image"
                                :showPrimary=false
                                :showEdit=false
                                accept="all"
                            ></vue-upload-multiple-image>
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
// import vue2Dropzone from 'vue2-dropzone'
// import 'vue2-dropzone/dist/vue2Dropzone.min.css'
export default {
    name      : "FileUpload",
    components: {
        VueUploadMultipleImage,
        ProgressBar
    },
    data() {
        return {
            images         : [],
            isUploading    : false,
            file           : null,
            chunks         : [],
            uploaded       : 0,
            totalChunksSize: 0,
            options        : {
                text    : {
                    color          : '#FFFFFF',
                    shadowEnable   : true,
                    shadowColor    : '#000000',
                    fontSize       : 14,
                    fontFamily     : 'Helvetica',
                    dynamicPosition: false,
                    hideText       : false
                },
                progress: {
                    color          : '#2dbd2d',
                    backgroundColor: '#333333',
                    inverted       : false
                },
                layout  : {
                    height             : 35,
                    width              : 140,
                    verticalTextAlign  : 61,
                    horizontalTextAlign: 43,
                    zeroOffset         : 0,
                    strokeWidth        : 30,
                    progressPadding    : 0,
                    type               : 'line'
                }
            }
        }
    },
    computed  : {
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
    methods   : {
        closeDirModal() {
            this.afterUpload()
        },
        async uploadImageSuccess(formData, index, fileList) {
            // console.log(formData, fileList, '1', fileList[0].isUploaded)
            Object.entries(fileList).forEach(ele => {
                // TODO::need to upload multiple image
                if (!ele[1].isUploaded) {
                    this.isUploading = true
                    var ImageURL     = ele[1].path
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
                ele[1].isUploaded = true
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
        },
        cancelUpload(errors){
            // TODO::remove file when cancel upload
            // setTimeout(()=>{
            //     console.log(this.images)
            // },300)
            Object.entries(errors).forEach(([key, value]) => {
                Toast.fire({
                    icon : 'error',
                    title: value
                })
            })
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
                this.afterUpload()
                this.cancelUpload(errors)
            });
        },
        createChunks() {
            // let size             = 2408;
            let size             = 1048576;
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
    watch     : {
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
