<script setup>
    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
    import { MailWarning, MailCheck  } from 'lucide-vue-next'
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
    <Card class="flex flex-col">
        <CardHeader>
            <CardTitle>Update Information</CardTitle>
            <CardDescription class="lg:w-2/5">Update your account's profile information and email address.</CardDescription>
        </CardHeader>
        
        <CardContent class="flex-1 lg:w-3/5 lg:self-end">
            <p 
                v-if="status"
                class="mb-2 text-sm text-green-700 ">{{ status }}
            </p>

            <Alert 
                v-if="form.recentlySuccessful === true" 
                class="mb-5"
                variant="success">
                <MailCheck class="shrink-0"/>
                <div>
                    <AlertTitle>Success!</AlertTitle>
                    <AlertDescription>
                        <p>Your credentials has been updated.</p>
                    </AlertDescription>
                </div>
            </Alert>

            <Alert 
                v-if="user.email_verified_at === null" 
                variant="warning" class="mb-5">
                <MailWarning class="shrink-0"/>
                <div>
                    <AlertTitle>Reminder</AlertTitle>
                    <AlertDescription>
                        <p>Your email address is not verified.</p>
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
                    v-model="form.name"
                    :error="form.errors.name"
                />

                <InputField 
                    label="Email" 
                    icon="id-badge" 
                    v-model="form.email"
                    :error="form.errors.email"
                />

                <Button :disabled="form.processing">
                    Save
                </Button>
            </form>
            
        </CardContent>
    </Card>
</template>