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
          size: 12,
          weight: '500',
          family: 'system-ui'
        },
        color: '#334155', // Darker color for better contrast
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
          size: 12,
          weight: '500',
          family: 'system-ui'
        },
        color: '#334155', // Darker color for better contrast
        padding: 8,
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

// Update download method
const downloadChart = async (type = 'png') => {
  if (!chartInstance.value) return;
  
  // Get chart canvas
  const canvas = chartInstance.value.$el;
  
  if (type === 'png') {
    try {
      // Create wrapper div and add it to the document
      const wrapper = document.createElement('div');
      wrapper.style.cssText = `
        background-color: white;
        padding: 20px;
        width: 1000px;
        position: fixed;
        left: -9999px;
      `;
      
      // Add content to wrapper
      wrapper.innerHTML = `
        <div style="margin-bottom: 20px; font-family: system-ui;">
          <h2 style="font-size: 24px; margin-bottom: 10px;">Revenue Overview</h2>
          <p style="color: #64748b; margin-bottom: 8px;">${props.overview}</p>
          <p style="font-size: 18px; font-weight: 600;">Total Revenue: ₱${props.totalRevenue.toLocaleString()}</p>
        </div>
        <div style="width: 100%;">
          ${canvas.outerHTML}
        </div>
      `;
      
      // Add to document, wait for elements to load
      document.body.appendChild(wrapper);
      await new Promise(resolve => setTimeout(resolve, 100));
      
      // Capture the image
      const finalCanvas = await html2canvas(wrapper, {
        useCORS: true,
        scale: 3, // Higher scale for better quality
        logging: false,
        allowTaint: true,
        backgroundColor: 'white',
        onclone: (document) => {
          const canvas = document.querySelector('canvas');
          if (canvas) {
            canvas.style.filter = 'none'; // Remove shadow for cleaner export
          }
        }
      });
      
      // Create download link
      const link = document.createElement('a');
      link.download = `revenue-chart-${new Date().toISOString().slice(0,10)}.png`;
      link.href = finalCanvas.toDataURL('image/png', 1.0);
      link.click();
      
      // Cleanup
      document.body.removeChild(wrapper);
    } catch (error) {
      console.error('Error generating image:', error);
    }
  } else if (type === 'print') {
    const printWindow = window.open('');
    printWindow.document.write(`
      <html>
        <head>
          <title>Revenue Chart</title>
          <style>
            @media print {
              body {
                padding: 40px;
                font-family: system-ui;
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
              }
              .header p {
                color: #64748b;
                margin-bottom: 8px;
              }
              .total {
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 20px;
              }
              img {
                width: 100%;
                margin-top: 20px;
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
            <img src="${canvas.toDataURL('image/png', 1.0)}" />
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
