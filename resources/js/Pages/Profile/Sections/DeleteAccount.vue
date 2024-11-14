<script setup>
    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
    import InputField from '../../../Components/InputField.vue'
    import { TriangleAlert } from 'lucide-vue-next'
    import { Button } from '@/components/ui/button'
    import { useForm } from '@inertiajs/vue3'
    import { ref } from 'vue'

    const form = useForm({
        password: '',
    })

    const showConfirmPassword = ref(false)

    const submit = () => {
        form.delete(route('profile.destroy'), {
            onError: () => form.reset(),
            preserveScroll: true,
        });

        
    }
</script>

<template>
    <Card class="flex flex-col border-destructive">
        <CardHeader>
            <CardTitle class="text-destructive">Delete Account</CardTitle>
            <CardDescription class="lg:w-2/5">Once your account is deleted, all of its resources data will be permanently deleted. This action cannot be undone.</CardDescription>
        </CardHeader>
        
        <CardContent class="flex-1 lg:w-3/5 lg:self-end">

            <div v-if="showConfirmPassword">
                <form @submit.prevent="submit" class="space-y-6">
                    <InputField 
                        label="Confirm Password" 
                        icon="key"
                        type="password"
                        v-model="form.password"
                        :error="form.errors.password"
                    />

                    <Button
                        :disabled="form.processing"
                        variant="destructive"
                        class="mr-2">
                        Delete
                    </Button>
                    <Button
                        @click="showConfirmPassword = false"
                        :disabled="form.processing"
                        variant="outline">
                        Cancel
                    </Button>
                </form>
            </div>

            <Button
                v-if="!showConfirmPassword"
                @click="showConfirmPassword = true"
                variant="destructive">
                <TriangleAlert />      
                Delete Account
            </Button>
            
        </CardContent>
    </Card>
</template>