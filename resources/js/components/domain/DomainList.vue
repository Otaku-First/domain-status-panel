<script setup lang="ts">
import { AlertCircle, Globe, Plus, RefreshCw, Search } from 'lucide-vue-next';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import type { Domain, DomainCheck } from '@/types/domain';

const props = defineProps<{
    domains: Domain[];
    filteredDomains: Domain[];
    selectedDomainId: number | null;
    isRefreshing: boolean;
    searchQuery: string;
    isViewMode: boolean;
}>();

const emit = defineEmits<{
    'update:searchQuery': [value: string];
    select: [domain: Domain];
    create: [];
    refresh: [];
}>();

function getStatusVariant(result?: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (!result) return 'secondary';
    return result === 'SUCCESS' ? 'default' : 'destructive';
}

function getStatusLabel(check?: DomainCheck): string {
    if (!check) return 'Pending';
    return check.result_label;
}
</script>

<template>
    <div class="w-[420px] flex flex-col gap-4 shrink-0">
        <Button @click="emit('create')" class="w-full" size="lg">
            <Plus class="size-4" />
            Add Domain
        </Button>

        <Card class="flex-1 flex flex-col">
            <CardHeader class="pb-3">
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle class="text-base">Monitored Domains</CardTitle>
                        <CardDescription>{{ filteredDomains.length }} of {{ domains.length }} domains</CardDescription>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        @click="emit('refresh')"
                        :disabled="isRefreshing"
                        class="size-8"
                    >
                        <RefreshCw class="size-4" :class="{ 'animate-spin': isRefreshing }" />
                    </Button>
                </div>
                <div class="relative mt-2">
                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                    <Input
                        :model-value="searchQuery"
                        @update:model-value="emit('update:searchQuery', $event)"
                        placeholder="Search domains..."
                        class="pl-8 h-9"
                    />
                </div>
            </CardHeader>
            <Separator />
            <CardContent class="flex-1 overflow-auto p-0">
                <div class="divide-y">
                    <button
                        v-for="domain in filteredDomains"
                        :key="domain.id"
                        @click="emit('select', domain)"
                        class="w-full p-4 text-left hover:bg-accent/50 transition-colors"
                        :class="{
                            'bg-accent': selectedDomainId === domain.id && isViewMode,
                            'border-l-2 border-l-destructive': domain.is_down,
                        }"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="size-9 rounded-lg flex items-center justify-center shrink-0"
                                    :class="domain.is_down ? 'bg-destructive/10' : 'bg-primary/10'"
                                >
                                    <Globe class="size-4" :class="domain.is_down ? 'text-destructive' : 'text-primary'" />
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium truncate flex items-center gap-2">
                                        {{ domain.hostname }}
                                        <AlertCircle v-if="domain.is_down" class="size-3.5 text-destructive" />
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-0.5">
                                        {{ domain.method }} &middot; every {{ domain.interval }}s
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                <Badge :variant="getStatusVariant(domain.latest_check?.result)">
                                    {{ getStatusLabel(domain.latest_check) }}
                                </Badge>
                                <span
                                    v-if="domain.uptime_24h !== null"
                                    class="text-xs tabular-nums"
                                    :class="
                                        domain.uptime_24h >= 99
                                            ? 'text-green-600'
                                            : domain.uptime_24h >= 95
                                              ? 'text-yellow-600'
                                              : 'text-destructive'
                                    "
                                >
                                    {{ domain.uptime_24h }}% uptime
                                </span>
                            </div>
                        </div>
                    </button>

                    <div v-if="!filteredDomains.length" class="p-8 text-center text-muted-foreground">
                        <Globe class="size-10 mx-auto mb-3 opacity-50" />
                        <template v-if="searchQuery && domains.length">
                            <p class="text-sm">No domains found</p>
                            <p class="text-xs mt-1">Try a different search term</p>
                        </template>
                        <template v-else>
                            <p class="text-sm">No domains yet</p>
                            <p class="text-xs mt-1">Click "Add Domain" to get started</p>
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>