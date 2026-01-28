<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import DomainController from '@/actions/App/Http/Controllers/DomainController';
import type { Domain } from '@/types/domain';

const props = defineProps<{
    mode: 'create' | 'edit';
    domain?: Domain | null;
}>();

const emit = defineEmits<{
    cancel: [];
    success: [domainId?: number];
}>();

const form = useForm({
    hostname: '',
    method: 'GET' as 'GET' | 'HEAD',
    interval: 60,
    timeout: 30,
    body: '' as string,
    is_active: true,
});

// Watch for domain changes to populate form
watch(
    () => props.domain,
    (domain) => {
        if (domain && props.mode === 'edit') {
            form.hostname = domain.hostname;
            form.method = domain.method;
            form.interval = domain.interval;
            form.timeout = domain.timeout;
            form.body = domain.body ?? '';
            form.is_active = Boolean(domain.is_active);
        }
    },
    { immediate: true },
);

// Reset form when switching to create mode
watch(
    () => props.mode,
    (mode) => {
        if (mode === 'create') {
            form.reset();
            form.clearErrors();
        }
    },
);

function submitForm() {
    if (props.mode === 'create') {
        form.post(DomainController.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('success');
            },
        });
    } else if (props.mode === 'edit' && props.domain) {
        form.put(DomainController.update(props.domain.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                emit('success', props.domain?.id);
            },
        });
    }
}

const panelTitle = props.mode === 'create' ? 'Add New Domain' : 'Edit Domain';
const panelDescription = props.mode === 'create' ? 'Add a new domain to monitor' : 'Update domain monitoring settings';
</script>

<template>
    <Card class="flex-1 flex flex-col">
        <CardHeader>
            <div class="flex items-center gap-3">
                <Button variant="ghost" size="icon" @click="emit('cancel')">
                    <ArrowLeft class="size-4" />
                </Button>
                <div>
                    <CardTitle>{{ panelTitle }}</CardTitle>
                    <CardDescription>{{ panelDescription }}</CardDescription>
                </div>
            </div>
        </CardHeader>
        <Separator />

        <CardContent class="flex-1 overflow-auto pt-6">
            <form @submit.prevent="submitForm" class="max-w-xl space-y-6">
                <div class="space-y-2">
                    <Label for="hostname">Hostname</Label>
                    <Input
                        id="hostname"
                        v-model="form.hostname"
                        placeholder="example.com"
                        :class="{ 'border-destructive': form.errors.hostname }"
                        required
                    />
                    <p v-if="form.errors.hostname" class="text-xs text-destructive">
                        {{ form.errors.hostname }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">Enter the domain name without protocol (http/https)</p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <Label for="method">Method</Label>
                        <select
                            id="method"
                            v-model="form.method"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="GET">GET</option>
                            <option value="HEAD">HEAD</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="interval">Check Interval</Label>
                        <div class="relative">
                            <Input
                                id="interval"
                                v-model.number="form.interval"
                                type="number"
                                min="10"
                                class="pr-10"
                                :class="{ 'border-destructive': form.errors.interval }"
                                required
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground">sec</span>
                        </div>
                        <p v-if="form.errors.interval" class="text-xs text-destructive">
                            {{ form.errors.interval }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="timeout">Timeout</Label>
                        <div class="relative">
                            <Input
                                id="timeout"
                                v-model.number="form.timeout"
                                type="number"
                                min="1"
                                class="pr-10"
                                :class="{ 'border-destructive': form.errors.timeout }"
                                required
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground">sec</span>
                        </div>
                        <p v-if="form.errors.timeout" class="text-xs text-destructive">
                            {{ form.errors.timeout }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Checkbox id="is_active" v-model="form.is_active" />
                    <div class="space-y-0.5">
                        <Label for="is_active" class="cursor-pointer">Active</Label>
                        <p class="text-xs text-muted-foreground">Enable automatic monitoring for this domain</p>
                    </div>
                </div>

                <Separator />

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : mode === 'create' ? 'Create Domain' : 'Save Changes' }}
                    </Button>
                    <Button type="button" variant="outline" @click="emit('cancel')" :disabled="form.processing">
                        Cancel
                    </Button>
                </div>
            </form>
        </CardContent>
    </Card>
</template>
