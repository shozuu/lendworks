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

async function urlToFile(url, filename) {
	const response = await fetch(url);
	const blob = await response.blob();
	return new File([blob], filename, { type: blob.type });
}

const emit = defineEmits(["images"]);

const props = defineProps({
	initialFiles: {
		type: Array,
		default: () => [],
	},
});

// convert database images to File objects
const files = ref([]);
const initializeFiles = async () => {
	const convertedFiles = await Promise.all(
		props.initialFiles.map(async (img) => {
			const fileUrl = `/storage/${img.image_path}`;
			const filename = img.image_path.split("/").pop();
			const file = await urlToFile(fileUrl, filename);

			return {
				source: file,
				options: {
					type: "local",
					metadata: {
						id: img.id,
						order: img.order,
					},
				},
			};
		})
	);
	files.value = convertedFiles;
};

// initialize files when component mounts
initializeFiles();

const onUpdateFiles = (fileItems) => {
	const imagesArray = fileItems.map((fileItem) => {
		const file = fileItem.file;
		// preserve the original image id and order if it exists
		if (fileItem.options?.metadata?.id) {
			file.id = fileItem.options.metadata.id;
			file.order = fileItem.options.metadata.order;
		}
		return file;
	});

	emit("images", imagesArray);
};
</script>
<template>
	<FilePond
		:files="files"
		:server="false"
		:instant-upload="false"
		allow-multiple="true"
		allow-reorder="true"
		allow-remove="true"
		:allow-image-preview="true"
		accepted-file-types="image/jpeg, image/png, image/jpg, image/webp"
		max-file-size="3MB"
		label-idle="Drag & Drop your files or <span class='filepond--label-action'>Browse</span>"
		@updatefiles="onUpdateFiles"
		class="bg-background text-foreground"
	/>
</template>
