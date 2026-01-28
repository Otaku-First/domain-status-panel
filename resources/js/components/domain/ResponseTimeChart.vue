<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

interface DomainCheck {
    id: number;
    result: string;
    is_successful: boolean;
    response_time_ms: number | null;
    checked_at: string;
}

const props = defineProps<{
    checks: DomainCheck[];
}>();

const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 200,
        background: 'transparent',
        sparkline: {
            enabled: false,
        },
        toolbar: {
            show: false,
        },
        zoom: {
            enabled: false,
        },
    },
    dataLabels: {
        enabled: false,
    },
    stroke: {
        curve: 'smooth',
        width: 2,
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.1,
            stops: [0, 100],
        },
    },
    xaxis: {
        type: 'datetime',
        labels: {
            datetimeUTC: false,
            format: 'HH:mm',
        },
    },
    yaxis: {
        min: 0,
        labels: {
            formatter: (val: number | null) =>
                val === null ? '' : `${Math.round(val)}ms`,
        },
    },
    tooltip: {
        x: {
            format: 'dd MMM HH:mm',
        },
        y: {
            formatter: (val: number | null) =>
                val === null ? '' : `${Math.round(val)}ms`,
        },
    },
    colors: ['#3b82f6', '#ef4444'],
    grid: {
        borderColor: 'hsl(var(--border))',
        strokeDashArray: 4,
    },
    theme: {
        mode: document.documentElement.classList.contains('dark')
            ? 'dark'
            : 'light',
    },
}));

const series = computed(() => {
    const checks = [...props.checks].sort(
        (a, b) =>
            new Date(a.checked_at).getTime() - new Date(b.checked_at).getTime(),
    );

    let failureActive = false;
    let prevFailureActive = false;
    let lastKnownResponseTime: number | null = null;
    let prevTimestamp: number | null = null;
    let prevResponseValue: number | null = null;

    const successData = [];
    const failureData = [];

    for (const check of checks) {
        if (check.response_time_ms !== null) {
            lastKnownResponseTime = check.response_time_ms;
        }

        if (!check.is_successful) {
            failureActive = true;
        } else {
            failureActive = false;
        }

        const timestamp = new Date(check.checked_at).getTime();
        const responseValue =
            check.response_time_ms !== null
                ? check.response_time_ms
                : lastKnownResponseTime;

        if (
            prevTimestamp !== null &&
            prevResponseValue !== null &&
            prevFailureActive !== failureActive
        ) {
            if (failureActive) {
                // Start failure area from the previous point to avoid gaps.
                failureData.push({
                    x: prevTimestamp,
                    y: prevResponseValue,
                });
            } else if (responseValue !== null) {
                // End failure area at the transition point to avoid gaps.
                failureData.push({
                    x: timestamp,
                    y: responseValue,
                });
            }
        }

        successData.push({
            x: timestamp,
            y: !failureActive ? responseValue : null,
        });

        failureData.push({
            x: timestamp,
            y: failureActive ? responseValue : null,
        });

        prevFailureActive = failureActive;
        prevTimestamp = timestamp;
        prevResponseValue = responseValue;
    }

    return [
        {
            name: 'Response Time',
            data: successData,
        },
        {
            name: 'Failure Window',
            data: failureData,
        },
    ];
});

const hasData = computed(() =>
    series.value.some((seriesItem) =>
        seriesItem.data.some((point) => point.y !== null),
    ),
);

const chartKey = computed(() => {
    const checkIds = props.checks.map((c) => c.id).join('-');
    return `chart-${checkIds}`;
});
</script>

<template>
    <div v-if="hasData" class="h-50">
        <VueApexCharts
            :key="chartKey"
            type="area"
            height="200"
            :options="chartOptions"
            :series="series"
        />
    </div>
    <div
        v-else
        class="flex h-50 items-center justify-center rounded-lg border border-dashed bg-muted/30 text-sm text-muted-foreground"
    >
        No response time data yet
    </div>
</template>
