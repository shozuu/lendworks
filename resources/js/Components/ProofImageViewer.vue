<script setup>
import { ref, computed } from 'vue';
import { Dialog, DialogContent } from "@/components/ui/dialog";

const props = defineProps({
    imagePath: {
        type: String,
        required: true
    },
    alt: {
        type: String,
        default: 'Proof Image'
    }
});

// Add new refs for zoom and drag functionality
const showFullImage = ref(false);
const scale = ref(1);
const position = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const startPosition = ref({ x: 0, y: 0 });

const fullImageUrl = computed(() => `/storage/${props.imagePath}`);

// Zoom handlers
const handleWheel = (e) => {
    e.preventDefault();
    const delta = e.deltaY > 0 ? -0.1 : 0.1;
    scale.value = Math.min(Math.max(0.5, scale.value + delta), 3);
};

// Drag handlers
const startDrag = (e) => {
    isDragging.value = true;
    startPosition.value = {
        x: e.clientX - position.value.x,
        y: e.clientY - position.value.y
    };
};

const doDrag = (e) => {
    if (isDragging.value) {
        position.value = {
            x: e.clientX - startPosition.value.x,
            y: e.clientY - startPosition.value.y
        };
    }
};

const stopDrag = () => {
    isDragging.value = false;
};

// Reset zoom and position when dialog closes
const handleDialogClose = () => {
    scale.value = 1;
    position.value = { x: 0, y: 0 };
    showFullImage.value = false;
};
</script>

<template>
    <div>
        <!-- Thumbnail/Preview - reduced size -->
        <div 
            class="overflow-hidden border rounded-lg cursor-pointer w-48 h-32"
            @click="showFullImage = true"
        >
            <img 
                :src="fullImageUrl" 
                :alt="alt"
                class="object-cover w-full h-full transition-transform hover:scale-105"
            />
        </div>

        <!-- Fullscreen Dialog -->
        <Dialog :open="showFullImage" @update:open="handleDialogClose">
            <DialogContent class="sm:max-w-3xl overflow-hidden" @wheel.prevent>
                <div 
                    class="relative overflow-hidden w-full h-[60vh]"
                    @wheel="handleWheel"
                    @mousedown="startDrag"
                    @mousemove="doDrag"
                    @mouseup="stopDrag"
                    @mouseleave="stopDrag"
                >
                    <img 
                        :src="fullImageUrl" 
                        :alt="alt"
                        class="w-full h-full object-contain transition-transform cursor-move"
                        :style="{
                            transform: `scale(${scale}) translate(${position.x}px, ${position.y}px)`,
                        }"
                    />
                    <!-- Zoom controls -->
                    <div class="absolute bottom-4 right-4 flex gap-2">
                        <button 
                            class="bg-background/80 p-2 rounded-full hover:bg-background"
                            @click="scale = Math.min(scale + 0.1, 3)"
                        >
                            +
                        </button>
                        <button 
                            class="bg-background/80 p-2 rounded-full hover:bg-background"
                            @click="scale = Math.max(scale - 0.1, 0.5)"
                        >
                            -
                        </button>
                        <button 
                            class="bg-background/80 p-2 rounded-full hover:bg-background"
                            @click="scale = 1; position = { x: 0, y: 0 }"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
.object-contain {
    pointer-events: none;
    user-select: none;
}
</style>
