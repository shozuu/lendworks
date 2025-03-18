<script setup>
import UpdateInfo from "./Sections/UpdateInfo.vue";
import UpdatePassword from "./Sections/UpdatePassword.vue";
import DeleteAccount from "./Sections/DeleteAccount.vue";

defineProps({
	user: Object,
	profile: Object,
	status: String,
});
</script>

<template>
	<Head title=" | Profile" />
	<div class="space-y-5">
		<UpdateInfo :user="user" :profile="profile" :status="status" />
		<UpdatePassword />
		<DeleteAccount />
	</div>
</template>
