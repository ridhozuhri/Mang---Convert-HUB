(() => {
  const canvas = document.getElementById('rateChart');
  if (!canvas || typeof window.Chart === 'undefined') {
    return;
  }

  const chartUrl = canvas.getAttribute('data-chart-url');
  if (!chartUrl) {
    return;
  }

  fetch(chartUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then((response) => response.json())
    .then((chartData) => {
      const ctx = canvas.getContext('2d');
      if (!ctx) {
        return;
      }

      new window.Chart(ctx, {
        type: 'line',
        data: {
          labels: chartData.labels || [],
          datasets: [
            {
              label: chartData.pair || 'Rate',
              data: chartData.data || [],
              borderColor: '#0a84ff',
              backgroundColor: 'rgba(10,132,255,0.12)',
              tension: 0.3,
              pointRadius: 3,
            },
          ],
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: {
            y: { ticks: { color: '#6b7280' }, grid: { color: '#e6eaf1' } },
            x: { ticks: { color: '#6b7280' }, grid: { color: '#e6eaf1' } },
          },
        },
      });
    })
    .catch(() => {
      // No-op: chart optional
    });
})();
