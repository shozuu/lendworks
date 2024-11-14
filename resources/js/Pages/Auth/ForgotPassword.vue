<script setup>
    import { Button } from '@/components/ui/button'
    import InputField from '../../Components/InputField.vue';
    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
    import { MailCheck } from 'lucide-vue-next'
    import { useForm } from '@inertiajs/vue3';
    import AuthLayout from '../../Layouts/AuthLayout.vue';

    defineOptions({ layout: AuthLayout })
    defineProps({ status: String })

    const form = useForm({
        email: ""
    });

    const submit = () => {
        form.post(route('password.email'));
    }
</script>

<template>
    <Head title="| Forgot Password"/>

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
                <CardTitle>Forgot Password</CardTitle>
                <CardDescription class="mt-2">
                    Let us know your email address and we will email you a password reset link that will allow you to create a new one.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-5">
                    <InputField 
                        label="Email" 
                        icon="envelope" 
                        v-model="form.email"
                        placeholder="email@example.com"
                        :error="form.errors.email"
                    />
                    <Button size="sm" class="w-full" :disabled="form.processing">
                        Resend Verification Email
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>