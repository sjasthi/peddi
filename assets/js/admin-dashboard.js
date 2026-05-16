$(function () {
    var canvas = document.getElementById('wordCountChart');
    if (!canvas) return;

    var labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
    var values = JSON.parse(canvas.getAttribute('data-values') || '[]');

    var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var gridColor  = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
    var labelColor = isDark ? '#adb5bd' : '#495057';

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Entries',
                data: values,
                backgroundColor: 'rgba(13, 110, 253, 0.75)',
                borderColor:     'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ' ' + ctx.parsed.y.toLocaleString() + ' entries';
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: labelColor },
                    grid:  { color: gridColor }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: labelColor,
                        callback: function (v) { return Number.isInteger(v) ? v : ''; }
                    },
                    grid: { color: gridColor }
                }
            }
        }
    });
});
