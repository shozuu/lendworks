<script setup>
    import { Info } from 'lucide-vue-next'
    import InputField from '../../Components/InputField.vue';
    import { Button } from '@/components/ui/button'
    import { useForm } from '@inertiajs/vue3';
    import AuthLayout from '../../Layouts/AuthLayout.vue';

    defineOptions({ layout: AuthLayout })
    const props = defineProps({
        token: String,
        email: String
    })

    const form = useForm({
        token: props.token,
        email: props.email,
        password: "",
        password_confirmation: ""
    });

    const submit = () => {
        form.post(route('password.update'), {
            onSuccess: () => form.reset("password", "password_confirmation"),
        })
    }
</script>

<template>
    <Head title="| Reset Password"/>
    <div class="mx-auto max-w-md mt-20">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold mb-2">Enter your new password</h1>
        </div>

        <Alert v-if="status" variant="info" >
            <Info/>
            <div>
                <AlertTitle>Info</AlertTitle>
                <AlertDescription>
                    {{ status }}
                </AlertDescription>
            </div>
        </Alert>

        <form @submit.prevent="submit" class="space-y-6">
            <InputField
                readonly
                label="Email"
                icon="envelope"
                v-model="form.email"
                placeholder="email@example.com"
                :error="form.errors.email"
            />

            <InputField
                label="Password"
                type="password"
                icon="key"
                v-model="form.password"
                placeholder="must be at least 8 characters"
                :error="form.errors.password"
            />

            <InputField
                label="Confirm Password"
                type="password" icon="key"
                v-model="form.password_confirmation"
                placeholder="must be at least 8 characters"
                :error="form.errors.password"
            />

            <Button 
                :disabled="form.processing"
                class="w-full rounded-lg"
                size="lg">
                Reset Password
            </Button>
        </form>
    </div>
</template>
