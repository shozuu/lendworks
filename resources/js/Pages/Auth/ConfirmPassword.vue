<script setup>
    import { Button } from '@/components/ui/button'
    import { Checkbox } from '@/components/ui/checkbox'
    import TextLink from '../../Components/TextLink.vue';
    import InputField from '../../Components/InputField.vue';

    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
    import { useForm } from '@inertiajs/vue3';
    import AuthLayout from '../../Layouts/AuthLayout.vue';

    defineOptions({ layout: AuthLayout })

    const form = useForm({
        password: ""
    });

    const submit = () => {
        form.post(route('password.confirm'), {
            onFinish: () => form.reset()
        });
    }
    
</script>

<template>
    <Head title="| Confirm Password"/>

    <div class="max-w-md m-auto">
        <Card>
            <CardHeader class="text-center">
                <CardTitle>Confirm Password</CardTitle>
                <CardDescription class="mt-2 text-center">
                    This is a secure area of the application. Please confirm your password before continuing.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-5">
                    <InputField 
                        label="Password" 
                        type="password"
                        icon="key" 
                        v-model="form.password"
                        placeholder="confirm your password"
                        :error="form.errors.password"
                    />
                    <Button size="sm" class="w-full" :disabled="form.processing">
                        Confirm
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>