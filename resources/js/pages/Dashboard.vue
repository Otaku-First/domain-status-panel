<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useIntervalFn } from '@vueuse/core';
import { computed, ref } from 'vue';

import DomainDetails from '@/components/domain/DomainDetails.vue';
import DomainForm from '@/components/domain/DomainForm.vue';
import DomainList from '@/components/domain/DomainList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import DomainController from '@/actions/App/Http/Controllers/DomainController';
import type { BreadcrumbItem } from '@/types';
import type { Domain } from '@/types/domain';

type PanelMode = 'view' | 'create' | 'edit';

// Props from backend
const props = defineProps<{
    domains: Domain[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// State
const panelMode = ref<PanelMode>('view');
const selectedDomain = ref<Domain | null>(null);
const isRefreshing = ref(false);
const searchQuery = ref('');

// Filtered domains
const filteredDomains = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.domains;
    }
    const query = searchQuery.value.toLowerCase().trim();
    return props.domains.filter((domain) => domain.hostname.toLowerCase().includes(query));
});

// Background refresh
const REFRESH_INTERVAL = 30000; // 30 seconds

async function loadDomainDetails(domainId: number): Promise<Domain | null> {
    try {
        const response = await fetch(DomainController.show(domainId).url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (response.ok) {
            const data = await response.json();
            return data.data;
        }
    } catch (error) {
        console.error('Failed to load domain details:', error);
    }
    return null;
}

async function refreshData() {
    if (isRefreshing.value || panelMode.value !== 'view') return;

    isRefreshing.value = true;

    router.reload({
        only: ['domains'],
        preserveScroll: true,
        onSuccess: async () => {
            // Also refresh selected domain details if one is selected
            if (selectedDomain.value) {
                const updated = await loadDomainDetails(selectedDomain.value.id);
                if (updated) {
                    selectedDomain.value = updated;
                }
            }
        },
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

// Auto-refresh interval
const { pause, resume } = useIntervalFn(refreshData, REFRESH_INTERVAL);

// Methods
function openCreate() {
    pause();
    panelMode.value = 'create';
    selectedDomain.value = null;
}

function openEdit(domain: Domain) {
    pause();
    panelMode.value = 'edit';
    selectedDomain.value = domain;
}

async function selectDomain(domain: Domain) {
    panelMode.value = 'view';
    selectedDomain.value = domain;

    // Load full domain data with checks
    const fullDomain = await loadDomainDetails(domain.id);
    if (fullDomain) {
        selectedDomain.value = fullDomain;
    }
}

function cancelForm() {
    panelMode.value = 'view';
    resume();
}

async function handleFormSuccess(domainId?: number) {
    panelMode.value = 'view';
    resume();

    // Refresh to get updated data
    isRefreshing.value = true;
    router.reload({
        only: ['domains'],
        preserveScroll: true,
        onSuccess: async () => {
            // If we have a domain ID (edit mode), load its full details
            if (domainId) {
                const updated = await loadDomainDetails(domainId);
                if (updated) {
                    selectedDomain.value = updated;
                }
            } else {
                // For create mode, select the newly created domain (last in list by creation)
                selectedDomain.value = null;
            }
        },
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

function handleDeleted() {
    selectedDomain.value = null;
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 gap-6 p-6">
            <!-- Left Panel: Domain List -->
            <DomainList
                :domains="props.domains"
                :filtered-domains="filteredDomains"
                :selected-domain-id="selectedDomain?.id ?? null"
                :is-refreshing="isRefreshing"
                :search-query="searchQuery"
                :is-view-mode="panelMode === 'view'"
                @update:search-query="searchQuery = $event"
                @select="selectDomain"
                @create="openCreate"
                @refresh="refreshData"
            />

            <!-- Right Panel: Form or Details -->
            <DomainForm
                v-if="panelMode === 'create' || panelMode === 'edit'"
                :mode="panelMode"
                :domain="selectedDomain"
                @cancel="cancelForm"
                @success="handleFormSuccess"
            />

            <DomainDetails
                v-else
                :domain="selectedDomain"
                @edit="openEdit"
                @create="openCreate"
                @deleted="handleDeleted"
            />
        </div>
    </AppLayout>
</template>