<script setup>
import { ref } from "vue";
import vueFilePond from "vue-filepond";
import FilePondPluginImagePreview from "filepond-plugin-image-preview";
import FilePondPluginFileValidateType from "filepond-plugin-file-validate-type";
import FilePondPluginFileValidateSize from "filepond-plugin-file-validate-size";
import "filepond/dist/filepond.min.css";
import "filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css";

// register plugins to create & use FilePond component
const FilePond = vueFilePond(
	FilePondPluginImagePreview,
	FilePondPluginFileValidateType,
	FilePondPluginFileValidateSize
);

const emit = defineEmits(["images"]);
const files = ref([]);

const onUpdateFiles = (fileItems) => {
	const imagesArray = fileItems.map((item) => item.file);
	emit("images", imagesArray);
};
</script>
<template>
	<FilePond
		:files="files"
		allow-multiple="true"
		allow-reorder="true"
		allow-remove="true"
		accepted-file-types="image/jpeg, image/png, image/jpg, image/webp"
		max-file-size="3MB"
		label-idle="Drag & Drop your files or <span class='filepond--label-action'>Browse</span>"
		@updatefiles="onUpdateFiles"
		class="bg-background text-foreground"
	/>
</template>
