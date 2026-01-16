<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">

    <div class="main-content-wrapper">
        <div class="container">
            <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
            <p>This is the admin dashboard. You can manage the pawnshop from here.</p>
        </div>

        <div class="container" style="margin-top: 20px;">
            <div style="background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); padding: 25px; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); border: 1px solid rgba(212, 175, 55, 0.1);">
                <h3 style="margin-top: 0; color: #0a3d0a; margin-bottom: 20px;">Monthly Performance (Last 12 Months)</h3>
                <div style="position: relative; height: 400px; width: 100%;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($_SESSION['loggedin'])) { ?>
    <footer>
        <div class="container">
            <div class="footer-contact">
                <p><i class="fas fa-phone-alt"></i> 0910-809-9699</p>
                <p><a href="https://www.facebook.com/PowerSimPhoneshopOfficial" target="_blank"><i class="fab fa-facebook"></i> Powersim Phoneshop Gadget Trading Inc.</a></p>
            </div>
            <div class="footer-copyright">
                <p>&copy; 2026 Powersim Phoneshop Gadget Trading Inc. Baliuag. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    <?php } ?>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        fetch('api/monthly_performance.php')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('monthlyChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Loan Volume (₱)',
                                data: data.volume,
                                backgroundColor: 'rgba(10, 61, 10, 0.7)',
                                borderColor: 'rgba(10, 61, 10, 1)',
                                borderWidth: 1,
                                borderRadius: 4,
                                barPercentage: 0.6
                            },
                            {
                                label: 'Revenue (₱)',
                                data: data.revenue,
                                backgroundColor: 'rgba(212, 175, 55, 0.7)',
                                borderColor: 'rgba(212, 175, 55, 1)',
                                borderWidth: 1,
                                borderRadius: 4,
                                barPercentage: 0.6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value, index, values) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error loading chart data:', error));
    });
    </script>
    <script>
    // Play staggered entrance animations when redirected after login
    (function(){
        const params = new URLSearchParams(window.location.search);
        if (!params.get('login')) return;
        const containers = document.querySelectorAll('.main-content-wrapper .container');
        let delay = 60;
        containers.forEach((el, idx) => {
            el.style.opacity = 0;
            el.style.transform = 'translateY(18px)';
            el.style.filter = 'blur(6px)';
            el.style.animation = `slideFadeIn 760ms cubic-bezier(0.2,0.8,0.2,1) both`;
            el.style.animationDelay = (delay * (idx+1)) + 'ms';
        });
        // remove the query param so animation doesn't replay
        params.delete('login');
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
    })();
    </script>
</body>
</html>