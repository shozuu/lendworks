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
import {
	Bell,
	Dot,
	Loader2,
	PackageIcon,
	CreditCard,
	Calendar,
	ShieldAlert,
} from "lucide-vue-next";
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

// Add helper to get notification icon
const getNotificationIcon = (type) => {
	switch (type) {
		case "rental":
			return PackageIcon;
		case "payment":
			return CreditCard;
		case "schedule":
			return Calendar;
		case "alert":
			return ShieldAlert;
		default:
			return Dot;
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
		<DropdownMenuContent align="end" class="w-[380px]">
			<div class="flex items-center justify-between px-4 py-3 border-b">
				<h3 class="font-semibold">Notifications</h3>
				<span v-if="unreadCount > 0" class="text-xs text-muted-foreground">
					{{ unreadCount }} unread
				</span>
			</div>

			<!-- Content Area -->
			<div class="max-h-[calc(100vh-200px)] overflow-y-auto scrollbar-thin">
				<!-- Loading State -->
				<div v-if="loading" class="p-4 space-y-4">
					<div v-for="i in 3" :key="i" class="flex items-start gap-3 animate-pulse">
						<div class="w-8 h-8 rounded-full bg-muted"></div>
						<div class="space-y-2 flex-1">
							<div class="h-4 bg-muted rounded w-3/4"></div>
							<div class="h-3 bg-muted rounded w-1/2"></div>
						</div>
					</div>
				</div>

				<!-- Error State -->
				<div v-else-if="error" class="p-8 text-center text-muted-foreground">
					<ShieldAlert class="w-6 h-6 mx-auto mb-2" />
					<p>{{ error }}</p>
				</div>

				<!-- Empty State -->
				<div
					v-else-if="notifications.length === 0"
					class="p-8 text-center text-muted-foreground"
				>
					<Bell class="w-6 h-6 mx-auto mb-2 text-muted-foreground/50" />
					<p class="text-sm">No notifications</p>
				</div>

				<!-- Notifications List -->
				<div v-else class="py-2">
					<div
						v-for="notification in notifications"
						:key="notification.id"
						class="group relative px-4 py-3 hover:bg-muted transition-colors cursor-pointer"
						:class="{ 'bg-muted/50': !notification.read_at }"
						role="button"
						@click="markAsRead(notification.id)"
					>
						<!-- Unread Indicator -->
						<div
							v-if="!notification.read_at"
							class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-1/2 bg-primary rounded-r"
						></div>

						<div class="flex gap-3">
							<!-- Icon -->
							<div class="mt-1">
								<component
									:is="getNotificationIcon(notification.data.type)"
									class="w-5 h-5 text-muted-foreground"
								/>
							</div>

							<!-- Content -->
							<div class="space-y-1 flex-1">
								<p
									class="text-sm line-clamp-2"
									:class="{ 'font-medium': !notification.read_at }"
								>
									{{ notification.data.message }}
								</p>
								<div class="flex items-center gap-2">
									<p class="text-xs text-muted-foreground font-medium">
										{{ notification.data.title }}
									</p>
									<span class="text-xs text-muted-foreground">•</span>
									<p class="text-xs text-muted-foreground">
										{{ formatDate(notification.created_at) }}
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</DropdownMenuContent>
	</DropdownMenu>
</template>

<style scoped>
.scrollbar-thin {
	scrollbar-width: thin;
	scrollbar-color: hsl(var(--muted)) transparent;
}

.scrollbar-thin::-webkit-scrollbar {
	width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
	background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
	background-color: hsl(var(--muted));
	border-radius: 3px;
}
</style>
