<script setup>
import { Line } from 'vue-chartjs'
import { computed } from 'vue' // Add this import
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
)

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
});

const options = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  scales: {
    x: {
      grid: {
        display: false, // Hide vertical grid lines
      },
      border: {
        display: false // Hide x-axis line
      },
      ticks: {
        font: {
          size: 11,
          family: 'system-ui'
        },
        color: '#64748b' // Slate-500 color
      }
    },
    y: {
      beginAtZero: true,
      border: {
        display: false // Hide y-axis line
      },
      grid: {
        color: '#e2e8f0' // Slate-200 color
      },
      ticks: {
        font: {
          size: 11,
          family: 'system-ui'
        },
        color: '#64748b', // Slate-500 color
        callback: value => '₱' + value.toLocaleString(),
        maxTicksLimit: 6 // Limit number of ticks for cleaner look
      }
    }
  },
  plugins: {
    legend: {
      display: false // Hide legend since we only have one dataset
    },
    tooltip: {
      backgroundColor: 'white',
      titleColor: '#0f172a', // Slate-900 color
      titleFont: {
        size: 13,
        family: 'system-ui',
        weight: '600'
      },
      bodyColor: '#475569', // Slate-600 color
      bodyFont: {
        size: 12,
        family: 'system-ui'
      },
      padding: 12,
      borderColor: '#e2e8f0', // Slate-200 color
      borderWidth: 1,
      displayColors: false,
      callbacks: {
        title: (items) => {
          return items[0].label;
        },
        label: (item) => {
          return '₱' + item.raw.toLocaleString();
        }
      }
    }
  }
};

// Modify the incoming data to enhance visual style
const enhancedData = computed(() => ({
  ...props.data,
  datasets: props.data.datasets.map(dataset => ({
    ...dataset,
    borderWidth: 2,
    pointRadius: 0,
    pointHoverRadius: 6,
    pointHoverBorderWidth: 2,
    pointHoverBorderColor: '#ffffff',
    pointHoverBackgroundColor: dataset.borderColor,
    tension: 0.3, // Make the line more smooth
    fill: true,
    backgroundColor: (context) => {
      const ctx = context.chart.ctx;
      const gradient = ctx.createLinearGradient(0, 0, 0, 300);
      gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)'); // Light green
      gradient.addColorStop(1, 'rgba(16, 185, 129, 0)'); // Transparent
      return gradient;
    }
  }))
}));
</script>

<template>
  <Line 
    :data="enhancedData" 
    :options="options" 
    class="h-[300px]" 
  />
</template>

<style>
canvas {
  filter: drop-shadow(0 1px 2px rgb(0 0 0 / 0.1));
}
</style>
