<script setup lang="ts">
import {
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import type { EloHistoryPoint, Speed } from '@/types';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
);

const props = defineProps<{
    history: EloHistoryPoint[];
}>();

const speedMeta: Record<Speed, { label: string; color: string }> = {
    standard: { label: 'Clásica', color: '#4f46e5' },
    rapid: { label: 'Rápida', color: '#059669' },
    blitz: { label: 'Blitz', color: '#dc2626' },
};

const chartData = computed(() => {
    const dates = [...new Set(props.history.map((point) => point.date))].sort();

    return {
        labels: dates,
        datasets: (['standard', 'rapid', 'blitz'] as Speed[])
            .filter((speed) =>
                props.history.some((point) => point.type === speed),
            )
            .map((speed) => {
                const byDate = new Map(
                    props.history
                        .filter((p) => p.type === speed)
                        .map((p) => [p.date, p.elo]),
                );

                return {
                    label: speedMeta[speed].label,
                    borderColor: speedMeta[speed].color,
                    backgroundColor: speedMeta[speed].color,
                    spanGaps: true,
                    tension: 0.2,
                    data: dates.map((date) => byDate.get(date) ?? null),
                };
            }),
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: { ticks: { precision: 0 } },
    },
};
</script>

<template>
    <div class="h-64">
        <Line
            v-if="props.history.length > 0"
            :data="chartData"
            :options="chartOptions"
        />
        <p
            v-else
            class="flex h-full items-center justify-center text-sm text-muted-foreground"
        >
            Todavía no hay historial suficiente para graficar.
        </p>
    </div>
</template>
