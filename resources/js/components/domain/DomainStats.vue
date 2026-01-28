<script setup lang="ts">
import { Activity, ArrowUpRight, Calendar, Clock, TrendingUp, Zap } from 'lucide-vue-next';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { Domain } from '@/types/domain';

defineProps<{
    domain: Domain;
}>();
</script>

<template>
    <!-- Primary Stats -->
    <div class="grid grid-cols-3 gap-4">
        <!-- Uptime 24h -->
        <Card>
            <CardContent class="pt-6">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-lg bg-green-500/10 flex items-center justify-center">
                        <TrendingUp class="size-5 text-green-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            {{ domain.uptime_24h !== null ? `${domain.uptime_24h}%` : '—' }}
                        </p>
                        <p class="text-xs text-muted-foreground">Uptime (24h)</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Uptime 30d -->
        <Card>
            <CardContent class="pt-6">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <Calendar class="size-5 text-emerald-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            {{ domain.uptime_30d !== null ? `${domain.uptime_30d}%` : '—' }}
                        </p>
                        <p class="text-xs text-muted-foreground">Uptime (30d)</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Avg Response 24h -->
        <Card>
            <CardContent class="pt-6">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                        <Zap class="size-5 text-blue-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            {{ domain.avg_response_24h ?? '—' }}
                            <span v-if="domain.avg_response_24h" class="text-sm font-normal text-muted-foreground">ms</span>
                        </p>
                        <p class="text-xs text-muted-foreground">Avg Response (24h)</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Secondary Stats -->
    <div class="grid grid-cols-4 gap-4">
        <Card>
            <CardContent class="pt-6">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-lg bg-violet-500/10 flex items-center justify-center">
                        <Activity class="size-5 text-violet-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            {{ domain.latest_check?.response_code ?? '—' }}
                        </p>
                        <p class="text-xs text-muted-foreground">Status Code</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="pt-6">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-lg bg-cyan-500/10 flex items-center justify-center">
                        <ArrowUpRight class="size-5 text-cyan-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            {{ domain.latest_check?.response_time_ms ?? '—' }}
                            <span v-if="domain.latest_check?.response_time_ms" class="text-sm font-normal text-muted-foreground"
                                >ms</span
                            >
                        </p>
                        <p class="text-xs text-muted-foreground">Last Response</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="pt-6">
                <div class="flex items-center gap-3">
                    <div
                        class="size-10 rounded-lg flex items-center justify-center"
                        :class="domain.is_active ? 'bg-green-500/10' : 'bg-muted'"
                    >
                        <Activity class="size-5" :class="domain.is_active ? 'text-green-500' : 'text-muted-foreground'" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            <Badge :variant="domain.is_active ? 'default' : 'secondary'">
                                {{ domain.is_active ? 'Active' : 'Paused' }}
                            </Badge>
                        </p>
                        <p class="text-xs text-muted-foreground">Monitoring</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="pt-6">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-lg bg-orange-500/10 flex items-center justify-center">
                        <Clock class="size-5 text-orange-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ domain.checks_count ?? 0 }}</p>
                        <p class="text-xs text-muted-foreground">Total Checks</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>