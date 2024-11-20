<script setup>
defineProps({
	paginator: Object,
});

const makeLabel = (label) => {
	if (label.includes("Previous")) {
		return "<<";
	} else if (label.includes("Next")) {
		return ">>";
	} else {
		return label;
	}
};
</script>

<template>
	<div class="flex flex-col items-center gap-5 md:flex-row md:justify-between">
		<div class="flex items-center gap-1">
			<div v-for="(link, i) in paginator.links" :key="i">
				<component
					:is="link.url ? 'Link' : 'span'"
					:href="link.url"
					v-html="makeLabel(link.label)"
					class="grid w-9 h-9 text-sm border rounded-md text-foreground place-items-center border-border"
					:class="{
						'hover:bg-accent': link.url,
						'text-muted-foreground cursor-not-allowed': !link.url,
						'bg-primary text-primary-foreground hover:bg-primary/70': link.active,
					}"
				/>
			</div>
		</div>

		<p class="text-sm text-muted-foreground">
			Showing {{ paginator.from }} to {{ paginator.to }} of {{ paginator.total }} results
		</p>
	</div>
</template>