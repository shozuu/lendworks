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
		<UpdateInfo :user="user" :status="status" />
		<ProfileInfo v-if="profile" :profile="profile" />
		<UpdatePassword />
		<DeleteAccount />
	</div>
</template>
