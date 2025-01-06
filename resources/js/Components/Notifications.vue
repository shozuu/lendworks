<script setup>
import { ref, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/components/ui/button";
import { Bell } from "lucide-vue-next";
import { formatDistanceToNow } from "date-fns";

const notifications = ref([]);
const unreadCount = ref(0);
const error = ref(null);
const loading = ref(false);

const fetchNotifications = async () => {
	try {
		loading.value = true;
		error.value = null;
		const { data } = await axios.get("/notifications");
		notifications.value = data.notifications;
		unreadCount.value = data.unreadCount;
	} catch (e) {
		error.value = "Failed to load notifications";
		console.error("Error fetching notifications:", e);
	} finally {
		loading.value = false;
	}
};

const formatDate = (date) => {
	return formatDistanceToNow(new Date(date), { addSuffix: true });
};

const markAsRead = async (notificationId) => {
	try {
		await axios.post(`/notifications/${notificationId}/mark-as-read`);
		await fetchNotifications();
	} catch (e) {
		console.error("Error marking notification as read:", e);
	}
};

// Fetch notifications when component mounts
onMounted(() => {
	fetchNotifications();
});
</script>

<template>
	<DropdownMenu>
		<DropdownMenuTrigger asChild>
			<Button variant="ghost" size="icon" class="relative">
				<Bell class="h-5 w-5" />
				<span
					v-if="unreadCount > 0"
					class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-primary text-[10px] font-medium text-primary-foreground flex items-center justify-center"
				>
					{{ unreadCount }}
				</span>
			</Button>
		</DropdownMenuTrigger>
		<DropdownMenuContent align="end" class="w-80">
			<div class="max-h-[300px] overflow-y-auto py-2">
				<div v-if="loading" class="p-4 text-center text-muted-foreground">Loading...</div>
				<div v-else-if="error" class="p-4 text-center text-destructive">
					{{ error }}
				</div>
				<div
					v-else-if="notifications.length === 0"
					class="p-4 text-center text-muted-foreground"
				>
					No notifications
				</div>
				<div
					v-else
					v-for="notification in notifications"
					:key="notification.id"
					class="px-4 py-2 hover:bg-accent"
					:class="{ 'bg-muted': !notification.read_at }"
					role="button"
					@click="markAsRead(notification.id)"
				>
					<div class="flex flex-col gap-1">
						<p class="text-sm font-medium">{{ notification.data.message }}</p>
						<p class="text-xs text-muted-foreground">{{ notification.data.title }}</p>
						<p class="text-xs text-muted-foreground">
							{{ formatDate(notification.created_at) }}
						</p>
					</div>
				</div>
			</div>
		</DropdownMenuContent>
	</DropdownMenu>
</template>
