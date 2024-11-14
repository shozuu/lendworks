<script setup>
    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
    import { MailWarning  } from 'lucide-vue-next'
    import InputField from '../../../Components/InputField.vue'
    import { Button } from '@/components/ui/button'
    import { router, useForm } from '@inertiajs/vue3'

    const props = defineProps({ 
        user: Object,
        status: String,
    });

    const form = useForm({
        name: props.user.name,
        email: props.user.email
    })

    const submit = () => {
        form.patch(route('profile.info'))
    }

    const verifyEmail = (e) => {
        router.post(route('verification.send'), {}, {
            onStart: () => e.target.disabled = true,
            onFinish: () => e.target.disabled = false,
        })
    }
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Update Information</CardTitle>
            <CardDescription>Update your account's profile information and email address.</CardDescription>
        </CardHeader>
        
        <CardContent>
            <p 
                v-if="status"
                class="mb-2 text-sm text-green-700 ">{{ status }}
            </p>
            <Alert 
                v-if="user.email_verified_at === null" 
                variant="warning" class="w-full lg:w-1/2 mb-5">
                <MailWarning class="shrink-0"/>
                <div>
                    <AlertTitle>Reminder</AlertTitle>
                    <AlertDescription>
                        <p>Your email address has been successfully updated. Please verify your email again.</p>
                        <Button 
                            @click="verifyEmail"
                            class="p-0 text-inherit" 
                            variant="link">Verify Now
                        </Button>
                    </AlertDescription>
                </div>
            </Alert>

            <form @submit.prevent="submit" class="space-y-6">
                <InputField 
                    label="Name" 
                    icon="id-badge" 
                    class="w-full lg:w-1/2" 
                    v-model="form.name"
                    :error="form.errors.name"
                />

                <InputField 
                    label="Email" 
                    icon="id-badge" 
                    class="w-full lg:w-1/2" v-model="form.email"
                    :error="form.errors.email"
                />

                <Button
                    :disabled="form.processing"
                    class="">
                    Save
                </Button>
            </form>
            
        </CardContent>
    </Card>
</template>