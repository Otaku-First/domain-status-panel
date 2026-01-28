<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Globe, Pencil, Plus, Trash2 } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import DomainStats from '@/components/domain/DomainStats.vue';
import RecentChecks from '@/components/domain/RecentChecks.vue';
import ResponseTimeChart from '@/components/domain/ResponseTimeChart.vue';
import DomainController from '@/actions/App/Http/Controllers/DomainController';
import type { Domain } from '@/types/domain';

const props = defineProps<{
    domain: Domain | null;
}>();

const emit = defineEmits<{
    edit: [domain: Domain];
    create: [];
    deleted: [];
}>();

function deleteDomain() {
    if (!props.domain) return;

    if (!confirm(`Are you sure you want to delete "${props.domain.hostname}"?`)) {
        return;
    }

    router.delete(DomainController.destroy(props.domain.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            emit('deleted');
        },
    });
}
</script>

<template>
    <Card class="flex-1 flex flex-col">
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle>{{ domain?.hostname ?? 'Domain Details' }}</CardTitle>
                    <CardDescription>
                        {{ domain ? 'Monitoring overview and history' : 'Select a domain to view details' }}
                    </CardDescription>
                </div>
                <div v-if="domain" class="flex gap-2">
                    <Button variant="outline" size="sm" @click="emit('edit', domain)">
                        <Pencil class="size-3.5" />
                        Edit
                    </Button>
                    <Button variant="outline" size="sm" @click="deleteDomain" class="text-destructive hover:text-destructive">
                        <Trash2 class="size-3.5" />
                    </Button>
                </div>
            </div>
        </CardHeader>
        <Separator />

        <CardContent class="flex-1 overflow-auto pt-6">
            <!-- View Details -->
            <div v-if="domain" class="space-y-6">
                <DomainStats :domain="domain" />

                <!-- Response Time Chart -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">Response Time History</CardTitle>
                        <CardDescription>Last 50 checks</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponseTimeChart :checks="domain.checks ?? []" />
                    </CardContent>
                </Card>

                <RecentChecks :checks="domain.checks ?? []" />
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center h-full text-center">
                <div class="size-16 rounded-2xl bg-muted flex items-center justify-center mb-4">
                    <Globe class="size-8 text-muted-foreground" />
                </div>
                <h3 class="font-semibold mb-1">No domain selected</h3>
                <p class="text-sm text-muted-foreground mb-4">Select a domain from the list or add a new one</p>
                <Button variant="outline" @click="emit('create')">
                    <Plus class="size-4" />
                    Add Domain
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
