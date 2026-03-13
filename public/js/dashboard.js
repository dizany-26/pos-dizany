document.addEventListener('DOMContentLoaded', () => {

    if (typeof Chart === 'undefined') return;

    const canvas = document.getElementById('flujoCajaChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    if (window.flujoChart) {
        window.flujoChart.destroy();
    }

    // 🔥 GRADIENTES
    const gradientGreen = ctx.createLinearGradient(0, 0, 0, 300);
    gradientGreen.addColorStop(0, 'rgba(34,197,94,0.9)');
    gradientGreen.addColorStop(1, 'rgba(34,197,94,0.4)');

    const gradientRed = ctx.createLinearGradient(0, 0, 0, 300);
    gradientRed.addColorStop(0, 'rgba(239,68,68,0.9)');
    gradientRed.addColorStop(1, 'rgba(239,68,68,0.4)');

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const legendColor = isDark ? '#dbe8ff' : '#334155';
    const tickColor = isDark ? '#b8cdef' : '#64748b';
    const gridColor = isDark ? 'rgba(120,151,194,0.20)' : 'rgba(148,163,184,0.15)';
    const tooltipBg = isDark ? '#0b1f3a' : '#0f172a';

    window.flujoChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: window.dashboardLabels,
            datasets: [
                {
                    label: 'Ingresos',
                    data: window.dashboardIngresos,
                    backgroundColor: gradientGreen,
                    borderRadius: 12,
                    borderSkipped: false,
                    barThickness: 26
                },
                {
                    label: 'Egresos',
                    data: window.dashboardEgresos,
                    backgroundColor: gradientRed,
                    borderRadius: 12,
                    borderSkipped: false,
                    barThickness: 26
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 900,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        padding: 20,
                        color: legendColor
                    }
                },
                tooltip: {
                    backgroundColor: tooltipBg,
                    padding: 12,
                    borderWidth: 0,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function (ctx) {
                            return `${ctx.dataset.label}: S/ ${ctx.parsed.y.toFixed(2)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: gridColor
                    },
                    ticks: {
                        callback: value => `S/ ${value}`,
                        color: tickColor
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: tickColor
                    }
                }
            }
        }
    });

});
//animacion
/* ===============================
   ANIMACIÓN CONTADORES KPI
================================ */
document.addEventListener("DOMContentLoaded", function () {

    const counters = document.querySelectorAll('.counter');

    counters.forEach(counter => {

        const target = parseFloat(counter.getAttribute('data-value')) || 0;
        let current = 0;
        const duration = 800; // ms
        const increment = target / (duration / 16);

        function updateCounter() {
            current += increment;

            if (current >= target) {
                current = target;
            }

            counter.innerText = "S/ " + current.toLocaleString('es-PE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            if (current < target) {
                requestAnimationFrame(updateCounter);
            }
        }

        updateCounter();
    });

});