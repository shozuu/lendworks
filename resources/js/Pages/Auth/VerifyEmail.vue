<script setup>
    import AuthLayout from '../../Layouts/AuthLayout.vue'
    import { Button } from '@/components/ui/button'
    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
    import { MailCheck } from 'lucide-vue-next'
    import { useForm } from '@inertiajs/vue3'

    defineOptions({ layout: AuthLayout })
    defineProps({ status: String })

    const form = useForm({})
    const submit = () => {
        form.post(route('verification.send'))
    }
</script>

<template>
    <Head title="| Email Verification" />
    <div class="max-w-md m-auto flex flex-col gap-5">
        <Alert v-if="status" variant="success" >
            <MailCheck/>
            <div>
                <AlertTitle>Success!</AlertTitle>
                <AlertDescription>
                    {{ status }}
                </AlertDescription>
            </div>
        </Alert>

        <Card>
            <CardHeader>
                <CardTitle>Please Verify Your Email Address</CardTitle>
                <CardDescription class="mt-2">
                    Thank you for registering! We've sent a verification link to your email. Please check your inbox and click the link to complete your registration and access all features.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit">
                    <Button size="sm" class="w-full" :disabled="form.processing">
                        Resend Verification Email
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>