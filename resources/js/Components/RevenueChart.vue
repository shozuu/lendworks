<script setup>
import { Line } from 'vue-chartjs'
import { computed, ref } from 'vue' // Add this import
import html2canvas from 'html2canvas'  // Add this import
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
  },
  overview: {
    type: String,
    required: true
  },
  totalRevenue: {
    type: Number,
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
          size: 13,
          weight: '600',
          family: 'Inter, system-ui'
        },
        color: '#1e293b', // Even darker for better contrast
        padding: 8,
        maxRotation: 45,
        minRotation: 45
      }
    },
    y: {
      beginAtZero: true,
      border: {
        display: false // Hide y-axis line
      },
      grid: {
        color: '#e2e8f040', // Slightly more visible grid lines
        drawBorder: false,
        lineWidth: 1
      },
      ticks: {
        font: {
          size: 13,
          weight: '600',
          family: 'Inter, system-ui'
        },
        color: '#1e293b',
        padding: 10,
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
      backgroundColor: '#1e293b', // Darker background for tooltip
      titleColor: '#ffffff',
      titleFont: {
        size: 14,
        weight: '600',
        family: 'system-ui'
      },
      bodyColor: '#ffffff',
      bodyFont: {
        size: 13,
        family: 'system-ui'
      },
      padding: 12,
      borderColor: '#475569',
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
    borderWidth: 3, // Thicker line
    pointRadius: 4, // Always show points
    pointBackgroundColor: '#ffffff',
    pointBorderColor: '#10B981',
    pointBorderWidth: 2,
    pointHoverRadius: 8,
    pointHoverBorderWidth: 3,
    pointHoverBackgroundColor: '#ffffff',
    pointHoverBorderColor: '#10B981',
    tension: 0.2, // Less smooth for better data visibility
    fill: true,
    borderColor: '#10B981',
    backgroundColor: (context) => {
      const ctx = context.chart.ctx;
      const gradient = ctx.createLinearGradient(0, 0, 0, 300);
      gradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)'); // More opaque green
      gradient.addColorStop(1, 'rgba(16, 185, 129, 0.05)');
      return gradient;
    }
  }))
}));

// Add chart instance ref
const chartInstance = ref(null);

// Add a new method to get the chart image
const getChartImage = () => {
  if (!chartInstance.value) return null;
  return chartInstance.value.$el.toDataURL('image/png', 1.0);
};

// Update download method
const downloadChart = async (type = 'png') => {
  if (!chartInstance.value) return;
  
  // Get chart image first
  const chartImage = getChartImage();
  if (!chartImage) return;
  
  if (type === 'png') {
    try {
      const wrapper = document.createElement('div');
      wrapper.style.cssText = `
        background-color: white;
        padding: 40px;
        width: 1200px;
        position: fixed;
        left: -9999px;
        font-family: Inter, system-ui;
      `;
      
      wrapper.innerHTML = `
        <div style="margin-bottom: 30px;">
          <h2 style="
            font-size: 28px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
            font-family: Inter, system-ui;
          ">Revenue Overview</h2>
          <p style="
            font-size: 16px;
            color: #475569;
            margin-bottom: 12px;
            font-family: Inter, system-ui;
          ">${props.overview}</p>
          <p style="
            font-size: 20px;
            font-weight: 600;
            color: #0f172a;
            font-family: Inter, system-ui;
          ">Total Revenue: ₱${props.totalRevenue.toLocaleString()}</p>
        </div>
        <div style="
          padding: 20px;
          border-radius: 12px;
          background-color: white;
          box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        ">
          <img src="${chartImage}" style="width: 100%; height: auto;" />
        </div>
      `;
      
      document.body.appendChild(wrapper);
      await new Promise(resolve => setTimeout(resolve, 200));
      
      const finalCanvas = await html2canvas(wrapper, {
        useCORS: true,
        scale: 2,
        logging: false,
        allowTaint: true,
        backgroundColor: 'white'
      });
      
      const link = document.createElement('a');
      link.download = `revenue-chart-${new Date().toISOString().slice(0,10)}.png`;
      link.href = finalCanvas.toDataURL('image/png', 1.0);
      link.click();
      
      document.body.removeChild(wrapper);
    } catch (error) {
      console.error('Error generating image:', error);
    }
  } else if (type === 'print') {
    // Update print template to use chart image
    const printWindow = window.open('');
    printWindow.document.write(`
      <html>
        <head>
          <title>Revenue Chart</title>
          <style>
            @media print {
              body {
                padding: 40px;
                font-family: Inter, system-ui;
              }
              .container {
                max-width: 800px;
                margin: 0 auto;
              }
              .header {
                margin-bottom: 30px;
              }
              .header h1 {
                font-size: 24px;
                margin-bottom: 10px;
                color: #0f172a;
              }
              .header p {
                color: #475569;
                margin-bottom: 8px;
              }
              .total {
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 20px;
                color: #0f172a;
              }
              .chart-container {
                padding: 20px;
                border-radius: 12px;
                background: white;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
              }
              img {
                width: 100%;
                height: auto;
                display: block;
              }
            }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>Revenue Overview</h1>
              <p>${props.overview}</p>
              <div class="total">Total Revenue: ₱${props.totalRevenue.toLocaleString()}</div>
            </div>
            <div class="chart-container">
              <img src="${chartImage}" />
            </div>
          </div>
        </body>
      </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 250);
  }
};

// Expose methods to parent
defineExpose({
  downloadChart
});
</script>

<template>
  <Line 
    ref="chartInstance"
    :data="enhancedData" 
    :options="options" 
    class="h-[300px]" 
  />
</template>

<style>
canvas {
  filter: drop-shadow(0 2px 4px rgb(0 0 0 / 0.1));
}
</style>
