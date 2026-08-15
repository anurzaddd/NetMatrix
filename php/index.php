<?php
// NetMatrix - Dashboard Utama
// Author: Amir Hossein Nourzadeh

session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ambil data dari database
$conn = getDBConnection();
$device_count = $conn->query("SELECT COUNT(*) as count FROM devices")->fetch_assoc()['count'];
$alert_count = $conn->query("SELECT COUNT(*) as count FROM alerts WHERE status='active'")->fetch_assoc()['count'];
$user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Ambil data real-time dari Java
$java_data = file_get_contents('http://java:8080/api/status') ?: '{"status":"online"}';
$java_status = json_decode($java_data, true);

// Ambil data dari MATLAB
$matlab_data = file_get_contents('http://matlab:8888/api/analysis') ?: '{"status":"online"}';
$matlab_status = json_decode($matlab_data, true);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetMatrix - داشبورد مدیریت شبکه</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'templates/header.html'; ?>

    <div class="container-fluid px-4 py-3">
        <!-- Dashboard Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card dashboard-card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-server me-2"></i> دستگاه‌ها</h5>
                        <h2 class="mb-0"><?= $device_count ?></h2>
                        <small>دستگاه متصل</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card dashboard-card bg-danger text-white shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-exclamation-triangle me-2"></i> هشدارها</h5>
                        <h2 class="mb-0"><?= $alert_count ?></h2>
                        <small>هشدار فعال</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card dashboard-card bg-success text-white shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users me-2"></i> کاربران</h5>
                        <h2 class="mb-0"><?= $user_count ?></h2>
                        <small>کاربر فعال</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card dashboard-card bg-info text-white shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-chart-line me-2"></i> وضعیت</h5>
                        <h2 class="mb-0">
                            <span class="badge bg-<?= $java_status['status'] == 'online' ? 'success' : 'danger' ?>">
                                <?= $java_status['status'] == 'online' ? 'فعال' : 'غیرفعال' ?>
                            </span>
                        </h2>
                        <small>سرویس Java</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <i class="fas fa-chart-area me-2"></i> تحلیل ترافیک شبکه
                    </div>
                    <div class="card-body">
                        <canvas id="trafficChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <i class="fas fa-pie-chart me-2"></i> توزیع دستگاه‌ها
                    </div>
                    <div class="card-body">
                        <canvas id="deviceChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Device Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list me-2"></i> لیست دستگاه‌ها</span>
                        <button class="btn btn-sm btn-primary" onclick="scanNetwork()">
                            <i class="fas fa-sync me-1"></i> اسکن شبکه
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0" id="deviceTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>نام دستگاه</th>
                                        <th>آی‌پی</th>
                                        <th>مک‌آدرس</th>
                                        <th>فروشنده</th>
                                        <th>وضعیت</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody id="deviceTableBody">
                                    <!-- توسط JavaScript پر می‌شود -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MATLAB Analysis -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <i class="fas fa-brain me-2"></i> تحلیل MATLAB - پیش‌بینی ترافیک
                    </div>
                    <div class="card-body">
                        <div id="matlabAnalysis">
                            <div class="text-center text-muted">
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                در حال دریافت تحلیل از MATLAB...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.html'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script>
        // داده‌های نمودار
        const ctx1 = document.getElementById('trafficChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'],
                datasets: [{
                    label: 'ورودی (Mbps)',
                    data: [12, 19, 25, 30, 28, 35, 22, 15],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52,152,219,0.1)',
                    fill: true
                }, {
                    label: 'خروجی (Mbps)',
                    data: [8, 15, 20, 25, 22, 28, 18, 10],
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46,204,113,0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });

        // نمودار دایره‌ای
        const ctx2 = document.getElementById('deviceChart').getContext('2d');
        new Chart(ctx2, {
            type: 'pie',
            data: {
                labels: ['سوئیچ', 'سرور', 'کلاینت', 'پرینتر', 'دوربین'],
                datasets: [{
                    data: [5, 3, 12, 4, 2],
                    backgroundColor: ['#3498db', '#2ecc71', '#f1c40f', '#e74c3c', '#9b59b6']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // بارگذاری لیست دستگاه‌ها
        loadDevices();

        function loadDevices() {
            fetch('api.php?action=get_devices')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('deviceTableBody');
                    tbody.innerHTML = '';
                    data.forEach((device, index) => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${device.name || 'Unknown'}</td>
                                <td>${device.ip}</td>
                                <td>${device.mac || 'N/A'}</td>
                                <td>${device.vendor || 'Unknown'}</td>
                                <td><span class="badge bg-${device.status === 'active' ? 'success' : 'danger'}">${device.status === 'active' ? 'فعال' : 'غیرفعال'}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewDevice('${device.id}')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                });
        }

        function scanNetwork() {
            // درخواست به Java برای اسکن شبکه
            fetch('http://localhost:8080/api/scan')
                .then(response => response.json())
                .then(data => {
                    alert('اسکن شبکه کامل شد! ' + data.devices_found + ' دستگاه پیدا شد.');
                    loadDevices();
                })
                .catch(() => {
                    alert('خطا در اسکن شبکه!');
                });
        }

        function viewDevice(id) {
            // نمایش اطلاعات دستگاه
            fetch('api.php?action=get_device&id=' + id)
                .then(response => response.json())
                .then(data => {
                    alert(`
                        نام: ${data.name || 'Unknown'}
                        آی‌پی: ${data.ip}
                        مک: ${data.mac || 'N/A'}
                        فروشنده: ${data.vendor || 'Unknown'}
                        وضعیت: ${data.status || 'Unknown'}
                    `);
                });
        }

        // دریافت تحلیل از MATLAB
        fetch('http://localhost:8888/api/analysis')
            .then(response => response.json())
            .then(data => {
                document.getElementById('matlabAnalysis').innerHTML = `
                    <div class="row">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h6>پیش‌بینی ترافیک</h6>
                                <h4>${data.traffic_prediction || 'N/A'} Mbps</h4>
                                <small class="text-muted">طی ۲۴ ساعت آینده</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h6>سطح خطر</h6>
                                <h4 class="text-${data.risk_level === 'high' ? 'danger' : data.risk_level === 'medium' ? 'warning' : 'success'}">
                                    ${data.risk_level || 'N/A'}
                                </h4>
                                <small class="text-muted">${data.risk_description || ''}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h6>ناهنجاری‌ها</h6>
                                <h4>${data.anomaly_count || 0}</h4>
                                <small class="text-muted">${data.anomaly_count > 0 ? 'نیاز به بررسی' : 'همه‌چیز عادی'}</small>
                            </div>
                        </div>
                    </div>
                `;
            })
            .catch(() => {
                document.getElementById('matlabAnalysis').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        سرویس MATLAB در دسترس نیست
                    </div>
                `;
            });
    </script>
</body>
</html>
