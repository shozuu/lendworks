<script setup>
    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
    import InputField from '../../../Components/InputField.vue'
    import { Button } from '@/components/ui/button'
    import { useForm } from '@inertiajs/vue3'
    import { KeyRound  } from 'lucide-vue-next'

    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: ''
    })

    const submit = () => {
        form.put(route('profile.password'), {
            onSuccess: () => form.reset(),
            onError: () => form.reset(),
            preserveScroll: true,
        })
    }
</script>

<template>
    <Card class="flex flex-col">
        <CardHeader>
            <CardTitle>Update Password</CardTitle>
            <CardDescription class="lg:w-2/5">Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.</CardDescription>
        </CardHeader>
        
        <CardContent class="flex-1 lg:w-3/5 lg:self-end">

            <Alert 
                v-if="form.recentlySuccessful === true" 
                class="mb-5"
                variant="success">
                <KeyRound class="shrink-0"/>
                <div>
                    <AlertTitle>Success!</AlertTitle>
                    <AlertDescription>
                        <p>Your password has been successfully updated.</p>
                    </AlertDescription>
                </div>
            </Alert>

            <form @submit.prevent="submit" class="space-y-6">
                <InputField 
                    label="Current Password" 
                    icon="key"
                    type="password"
                    v-model="form.current_password"
                    :error="form.errors.current_password"
                />

                <InputField 
                    label="New Password" 
                    icon="key" 
                    type="password"
                    v-model="form.password"
                    :error="form.errors.password"
                />

                <InputField 
                    label="Confirm New Password" 
                    icon="key"
                    type="password"
                    v-model="form.password_confirmation"
                    :error="form.errors.password"
                />

                <Button :disabled="form.processing">
                    Save
                </Button>
            </form>
            
        </CardContent>
    </Card>
</template>