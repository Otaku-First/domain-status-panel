<script setup lang="ts">
import { AlertCircle, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { DomainCheck } from '@/types/domain';

const props = defineProps<{
    checks: DomainCheck[];
}>();

const PAGE_SIZE = 10;
const currentPage = ref(1);

const totalPages = computed(() => Math.ceil(props.checks.length / PAGE_SIZE));

const paginatedChecks = computed(() => {
    const start = (currentPage.value - 1) * PAGE_SIZE;
    return props.checks.slice(start, start + PAGE_SIZE);
});

function nextPage() {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
}

function prevPage() {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
}
</script>

<template>
    <Card>
        <CardHeader class="pb-2">
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="text-base">Recent Checks</CardTitle>
                    <CardDescription>Latest monitoring results</CardDescription>
                </div>
                <div v-if="totalPages > 1" class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">
                        {{ currentPage }} / {{ totalPages }}
                    </span>
                    <div class="flex gap-1">
                        <Button variant="outline" size="icon" class="size-8" :disabled="currentPage === 1" @click="prevPage">
                            <ChevronLeft class="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            class="size-8"
                            :disabled="currentPage === totalPages"
                            @click="nextPage"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </CardHeader>
        <CardContent class="p-0">
            <TooltipProvider>
                <div class="divide-y">
                    <template v-if="paginatedChecks.length">
                        <div v-for="check in paginatedChecks" :key="check.id" class="flex items-center gap-4 px-6 py-3">
                            <div class="flex items-center gap-3 min-w-[140px]">
                                <div
                                    class="size-2 rounded-full shrink-0"
                                    :class="check.is_successful ? 'bg-green-500' : 'bg-red-500'"
                                />
                                <Badge :variant="check.is_successful ? 'default' : 'destructive'" class="font-normal">
                                    {{ check.result_label }}
                                </Badge>
                            </div>
                            <div class="text-sm tabular-nums text-muted-foreground w-12 text-center">
                                {{ check.response_code ?? '—' }}
                            </div>
                            <div class="text-sm tabular-nums text-muted-foreground w-20 text-right">
                                {{ check.response_time_ms ? `${check.response_time_ms}ms` : '—' }}
                            </div>
                            <div class="text-sm text-muted-foreground flex-1 text-right">
                                {{ check.checked_at_human }}
                            </div>
                            <div class="w-6 flex justify-center">
                                <Tooltip v-if="check.error_message">
                                    <TooltipTrigger as-child>
                                        <AlertCircle class="size-4 text-destructive cursor-help" />
                                    </TooltipTrigger>
                                    <TooltipContent side="left" class="max-w-xs">
                                        <p class="text-xs font-mono break-all">{{ check.error_message }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </div>
                    </template>
                    <div v-else class="px-6 py-8 text-center text-muted-foreground text-sm">
                        No checks yet. Monitoring will start automatically.
                    </div>
                </div>
            </TooltipProvider>
        </CardContent>
    </Card>
</template>