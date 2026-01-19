<?php
session_start();
include 'includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: login.php');
    exit();
}

$customer_id = intval($_SESSION['id']);

$stmt = $conn->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$items_stmt = $conn->prepare('SELECT items.*, 
    COALESCE(it.name, items.item_type) AS item_type_display,
    COALESCE(ic.name, items.category) AS category_display,
    COALESCE(cond.name, items.item_condition) AS condition_display
    FROM items
    LEFT JOIN item_types it ON it.name = items.item_type
    LEFT JOIN item_categories ic ON ic.name = items.category
    LEFT JOIN item_conditions cond ON cond.name = items.item_condition
    WHERE items.customer_id = ?
    ORDER BY items.created_at DESC');
$items_stmt->bind_param('i', $customer_id);
$items_stmt->execute();
$items = $items_stmt->get_result();
$items_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Theme variables */
        :root {
            --primary-color: #0a3d0a;
            --secondary-color: #145214;
            --accent-color: #d4af37;
            --accent-hover: #b5952f;
        }
        
        body { background: radial-gradient(circle at 15% 20%, rgba(17,94,89,0.18), transparent 32%), linear-gradient(135deg,#04111b 0%,#062112 100%); color: #0f172a; font-family: 'Outfit', system-ui, -apple-system, sans-serif; }
        .shell { max-width: 1280px; margin: 0 auto; padding: 32px 22px 56px; }
        .nav { display:flex; align-items:center; justify-content:space-between; margin-bottom: 26px; color:#e2e8f0; }
        .nav a { color:#e2e8f0; text-decoration:none; font-weight:600; }
        .nav .brand { display:flex; align-items:center; gap:10px; }
        .nav .brand span { font-size: 1.05rem; letter-spacing:0.4px; }
        .glass { background: rgba(255,255,255,0.96); border:1px solid rgba(10,61,10,0.12); border-radius:18px; box-shadow: 0 18px 50px rgba(0,0,0,0.12); backdrop-filter: blur(10px); }
        .hero { display:grid; grid-template-columns: 1fr; gap:22px; align-items:stretch; }
        .hero-card { padding:32px 30px; min-height:260px; height:100%; background: linear-gradient(145deg, var(--primary-color), var(--secondary-color), #0f5a24); border-radius:18px; color:#f8fafc; box-shadow: 0 16px 48px rgba(0,0,0,0.28); }
        .hero-card h2 { margin:0 0 8px; font-size:1.5rem; }
        .hero-card p { margin:0 0 14px; color:#d9e8d3; }
        .tag { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:999px; background: rgba(255,255,255,0.15); color:#f8fafc; font-size:0.9rem; font-weight:600; }
        .profile-card { padding:22px; height:100%; display:flex; align-items:center; }
        .profile-grid { width:100%; display:grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-top:8px; }
        .pill { background:#f8fafc; color:#0f172a; padding:12px; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 6px 18px rgba(0,0,0,0.05); }
        .pill label { display:block; font-size:0.78rem; color:#475569; letter-spacing:0.04em; text-transform:uppercase; margin-bottom:4px; }
        .pill span { font-weight:700; color:#0f172a; }
        .actions-inline { display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
        .btn-primary { background: var(--accent-color); color:#0b132b; border:1px solid var(--accent-hover); border-radius:10px; padding:10px 14px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 10px 25px rgba(212,175,55,0.25); cursor:pointer; transition: all 0.2s; }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-ghost { background: transparent; color:#0f172a; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; font-weight:600; text-decoration:none; display:inline-flex; gap:8px; align-items:center; cursor:pointer; transition: all 0.2s; }
        .btn-ghost:hover { background:#0f172a; color:#fff; }
        .table-card { margin-top:22px; padding:0; overflow:hidden; }
        .table-head { padding:16px 18px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .table-head h3 { margin:0; font-size:1.1rem; color:#0f172a; }
        .table-head .muted { color:#475569; }
        table { width:100%; border-collapse:collapse; }
        table th, table td { padding:12px 14px; border-bottom:1px solid #eaeaea; text-align:left; }
        table th { background:#f8fafc; color:#475569; font-size:0.9rem; letter-spacing:0.02em; }
        table td { color:#0f172a; font-weight:600; }
        .status { padding:6px 10px; border-radius:10px; font-weight:700; font-size:0.85rem; text-transform:capitalize; }
        .status.pawned { background:#ecfdf3; color:#16a34a; }
        .status.redeemed { background:#e0f2fe; color:#0284c7; }
        .status.sold { background:#fef2f2; color:#dc2626; }
        .status.for_sale { background:#fff7ed; color:#ea580c; }
        .table-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .btn-link { border:1px solid #e2e8f0; background:#fff; padding:8px 10px; border-radius:10px; text-decoration:none; color:#0f172a; font-weight:700; display:inline-flex; gap:6px; align-items:center; cursor:pointer; transition: all 0.2s; }
        .btn-link:hover { background:#0f172a; color:#fff; }
        .muted { color:#475569; font-size:0.9rem; }
        .modal-backdrop { position:fixed; inset:0; background:rgba(9,12,22,0.55); display:none; align-items:center; justify-content:center; padding:20px; z-index: 900; }
        .modal { background:#fff; border-radius:16px; max-width:900px; width:100%; box-shadow:0 28px 80px rgba(0,0,0,0.25); max-height:90vh; overflow:auto; }
        .modal header { display:flex; justify-content:space-between; align-items:center; padding:18px 22px; border-bottom:2px solid var(--primary-color); background: linear-gradient(135deg, rgba(10,61,10,0.05), rgba(212,175,55,0.03)); }
        .modal header h4 { margin:0; font-size:1.05rem; color: var(--primary-color); font-weight:700; }
        .modal .content { padding:20px 22px; display:grid; gap:16px; }
        .modal .field { display:grid; gap:8px; }
        .modal .field.inline { display: grid; grid-template-columns: 1fr 1fr; gap:14px; }
        .modal .field.inline > div { display:grid; gap:8px; }
        .modal .field.inline.three { grid-template-columns: repeat(3, 1fr); }
        .modal label { font-size:0.85rem; color: var(--primary-color); font-weight:700; letter-spacing:0.02em; text-transform:uppercase; }
        .modal input, .modal select, .modal textarea { padding:12px 14px; border:1.5px solid #dbeafe; border-radius:12px; font-size:1rem; width:100%; background:#f8fafc; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        .modal input:focus, .modal select:focus, .modal textarea:focus { outline: none; border-color: var(--accent-color); background:#fafbff; box-shadow: 0 0 0 3px rgba(212,175,55,0.1); }
        .modal textarea { min-height:90px; resize:vertical; }
        .modal .footer { padding:18px 22px; border-top:1px solid #e5e7eb; display:flex; gap:12px; justify-content:flex-end; }
        .badge-label { font-size:0.8rem; color: var(--primary-color); letter-spacing:0.04em; text-transform:uppercase; font-weight:700; }
        .item-summary { background: linear-gradient(135deg, rgba(10,61,10,0.05), rgba(212,175,55,0.03)); border:1.5px solid rgba(10,61,10,0.1); border-radius:12px; padding:12px; }
        .inline { display:flex; gap:8px; flex-wrap:wrap; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="nav">
            <div class="brand">
                <span class="tag"><i class="fas fa-gem"></i> Customer Portal</span>
            </div>
            <div class="actions-inline">
                <a class="btn-ghost" href="dashboard.php">Dashboard</a>
                <a class="btn-primary" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="hero">
            <div class="hero-card glass">
                <h2>Hello, <?php echo htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')); ?></h2>
                <p>Your active pawned items at a glance. Tap Details to review and pay.</p>
                <div class="inline">
                    <span class="tag"><i class="fas fa-id-card"></i> Code: <?php echo htmlspecialchars($customer['customer_code'] ?? ''); ?></span>
                    <span class="tag"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['contact_number'] ?? ''); ?></span>
                </div>
                <div class="actions-inline" style="margin-top:14px;">
                    <button class="btn-primary" type="button" id="editProfileBtn"><i class="fas fa-pen"></i> Edit Profile</button>
                </div>
            </div>
        </div>

        <div class="table-card glass">
            <div class="table-head">
                <div>
                    <h3>Your Pawns</h3>
                    <div class="muted">View details, pay, and download receipts.</div>
                </div>
            </div>
            <?php if ($items && $items->num_rows > 0): ?>
            <table>
                <thead>
                    <tr><th>Item</th><th>Loan</th><th>Status</th><th>Due Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = $items->fetch_assoc()): ?>
                        <?php 
                            $itemType = $row['item_type_display'] ?: $row['item_type'] ?: $row['category_display'];
                        ?>
                        <tr data-item='<?php echo json_encode([
                            "id" => $row['id'],
                            "type" => $itemType,
                            "category" => $row['category_display'],
                            "desc" => $row['item_description'],
                            "loan" => number_format($row['loan_amount'],2),
                            "rate" => $row['interest_rate'],
                            "due" => $row['due_date'],
                            "status" => $row['status'],
                            "serial" => $row['serial_number'],
                            "condition" => $row['condition_display'],
                            "created" => date('M d, Y', strtotime($row['created_at'])),
                        ], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                            <td><?php echo htmlspecialchars($itemType); ?></td>
                            <td>₱<?php echo number_format($row['loan_amount'],2); ?></td>
                            <td><span class="status <?php echo htmlspecialchars($row['status']); ?>"><?php echo htmlspecialchars(str_replace('_',' ',$row['status'])); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                            <td class="table-actions">
                                <button class="btn-link" type="button" onclick="openDetails(this)"><i class="fas fa-eye"></i> Details</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="muted" style="padding:18px;">You have no pawned items yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal-backdrop" id="detailModal">
        <div class="modal">
            <header>
                <h4>Pawn Details</h4>
                <button class="btn-ghost" type="button" onclick="closeModal('detailModal')">Close</button>
            </header>
            <div class="content">
                <div class="item-summary">
                    <div class="inline">
                        <div><span class="badge-label">Item</span><div id="dItem"></div></div>
                        <div><span class="badge-label">Category</span><div id="dCategory"></div></div>
                        <div><span class="badge-label">Condition</span><div id="dCondition"></div></div>
                    </div>
                    <div class="inline" style="margin-top:10px;">
                        <div><span class="badge-label">Loan</span><div id="dLoan" style="font-weight:700;"></div></div>
                        <div><span class="badge-label">Interest</span><div id="dRate"></div></div>
                        <div><span class="badge-label">Due Date</span><div id="dDue"></div></div>
                    </div>
                </div>
                <div class="field">
                    <label>Description</label>
                    <div id="dDesc" class="muted"></div>
                </div>
                <div class="field">
                    <label>Serial</label>
                    <div id="dSerial" class="muted"></div>
                </div>
            </div>
            <div class="footer">
                <a class="btn-link" id="renewLink" href="#"><i class="fas fa-redo"></i> Renew (+30 days)</a>
                <a class="btn-link" id="payLink" href="#"><i class="fas fa-credit-card"></i> Pay / Redeem</a>
                <button class="btn-primary" type="button" onclick="closeModal('detailModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal-backdrop" id="profileModal">
        <form class="modal" method="POST" action="update_profile.php">
            <header>
                <h4>Edit Profile</h4>
                <button class="btn-ghost" type="button" onclick="closeModal('profileModal')">Close</button>
            </header>
            <div class="content">
                <div class="field inline">
                    <div>
                        <label>Username</label>
                        <input name="username" id="username_edit" type="text" value="<?php echo htmlspecialchars($customer['username'] ?? ''); ?>" placeholder="Choose a username (not email)">
                    </div>
                    <div>
                        <label>Email</label>
                        <input name="email" type="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="field inline">
                    <div>
                        <label>Contact Number</label>
                        <input name="contact_number" id="contact_number_edit" type="text" value="<?php echo htmlspecialchars($customer['contact_number'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label>ZIP Code</label>
                        <input name="present_zip" id="present_zip" type="text" placeholder="4-digit ZIP" value="<?php echo htmlspecialchars($customer['present_zip'] ?? ''); ?>" maxlength="4">
                    </div>
                </div>

                <div class="field inline three">
                    <div>
                        <label>Province</label>
                        <select name="present_province" id="present_province" required onchange="fetchCities('present')">
                            <option value="">Select Province</option>
                        </select>
                    </div>
                    <div>
                        <label>City/Municipality</label>
                        <select name="present_city" id="present_city" required onchange="fetchBarangays('present')">
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div>
                        <label>Barangay</label>
                        <select name="present_barangay" id="present_barangay" required onchange="updateZip('present')">
                            <option value="">Select Barangay</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="footer">
                <button class="btn-ghost" type="button" onclick="closeModal('profileModal')">Cancel</button>
                <button class="btn-primary" type="submit">Save Changes</button>
            </div>
        </form>
    </div>

    <script src="js/zipcodes.js"></script>
    <script>
    const API_BASE = 'https://psgc.gitlab.io/api';
    
    // Apply +63 mask similar to admin form
    (function(){
        const contactInput = document.getElementById('contact_number_edit');
        if (contactInput) {
            if (!contactInput.value.startsWith('+63 ')) {
                contactInput.value = '+63 ' + contactInput.value.replace(/^\+?63\s?|^0/, '');
            }
            contactInput.addEventListener('input', function(){
                let v = this.value.replace(/[^0-9+\s]/g,'');
                v = v.replace(/^0/, '');
                if (!v.startsWith('+63 ')) {
                    v = '+63 ' + v.replace(/^\+?63\s?/, '');
                }
                this.value = v.substring(0, 16);
            });
            contactInput.addEventListener('keydown', function(e) {
                if ((e.key === 'Backspace' || e.key === 'Delete') && this.selectionStart <= 4) {
                    e.preventDefault();
                }
                if (e.key === 'ArrowLeft' && this.selectionStart <= 4) {
                    e.preventDefault();
                }
                if (e.key === 'Home') {
                    e.preventDefault();
                    this.setSelectionRange(4, 4);
                }
            });
            contactInput.addEventListener('click', function() {
                if (this.selectionStart < 4) this.setSelectionRange(4, 4);
            });
        }
    })();

    // Restrict zip inputs to digits only
    (function(){
        const zipInput = document.getElementById('present_zip');
        if (zipInput) {
            zipInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 4);
            });
            zipInput.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) return;
                const allowed = ['Backspace','Tab','ArrowLeft','ArrowRight','Delete','Home','End'];
                if (allowed.includes(e.key)) return;
                if (!/^[0-9]$/.test(e.key)) e.preventDefault();
            });
        }
    })();

    // Initialize PH location dropdowns using API and local JSON
    const ZIP_READY = hydrateFromJson('js/ph_locations.json');
    
    document.addEventListener('DOMContentLoaded', async () => {
        await ZIP_READY;
        loadProvinces('present');
        
        // Prefill selections
        const provinceVal = <?php echo json_encode($customer['present_province'] ?? ''); ?>;
        const cityVal = <?php echo json_encode($customer['present_city'] ?? ''); ?>;
        const brgyVal = <?php echo json_encode($customer['present_barangay'] ?? ''); ?>;
        
        if (provinceVal) {
            setTimeout(async () => {
                const provinceSelect = document.getElementById('present_province');
                // Find the option with text matching province value
                for (let opt of provinceSelect.options) {
                    if (opt.text.toLowerCase() === provinceVal.toLowerCase()) {
                        provinceSelect.value = opt.value;
                        break;
                    }
                }
                await fetchCities('present');
                
                if (cityVal) {
                    const citySelect = document.getElementById('present_city');
                    for (let opt of citySelect.options) {
                        if (opt.text.toLowerCase() === cityVal.toLowerCase()) {
                            citySelect.value = opt.value;
                            break;
                        }
                    }
                    await fetchBarangays('present');
                    
                    if (brgyVal) {
                        const brgySelect = document.getElementById('present_barangay');
                        for (let opt of brgySelect.options) {
                            if (opt.text.toLowerCase() === brgyVal.toLowerCase()) {
                                brgySelect.value = opt.value;
                                break;
                            }
                        }
                    }
                }
                updateZip('present');
            }, 200);
        }
    });

    // Username quick guard (no @)
    const profileForm = document.querySelector('#profileModal form');
    if (profileForm) {
        profileForm.addEventListener('submit', (e) => {
            const uname = document.getElementById('username_edit').value || '';
            if (uname.includes('@')) {
                e.preventDefault();
                alert('Username cannot be an email. Please remove the @.');
            }
        });
    }

    // Load provinces into dropdown
    async function loadProvinces(type) {
        const select = document.getElementById(type + '_province');
        select.innerHTML = '<option value="">Loading provinces...</option>';
        try {
            const response = await fetch(`${API_BASE}/provinces/`);
            const data = await response.json();
            data.sort((a, b) => a.name.localeCompare(b.name));
            let options = '<option value="">Select Province</option>';
            data.forEach(p => { 
                options += `<option value="${p.code}" data-name="${p.name}">${p.name}</option>`; 
            });
            select.innerHTML = options;
        } catch (e) {
            console.error('Error loading provinces:', e);
            select.innerHTML = '<option value="">Error loading provinces</option>';
        }
    }

    // Fetch cities/municipalities for selected province
    async function fetchCities(type) {
        const provinceSelect = document.getElementById(type + '_province');
        const provinceCode = provinceSelect.value;
        const citySelect = document.getElementById(type + '_city');
        const brgySelect = document.getElementById(type + '_barangay');

        if(!provinceCode) {
            citySelect.innerHTML = '<option value="">Select Province First</option>';
            brgySelect.innerHTML = '<option value="">Select City First</option>';
            return;
        }
        
        citySelect.innerHTML = '<option value="">Loading cities...</option>';
        brgySelect.innerHTML = '<option value="">Select City First</option>';
        
        try {
            const response = await fetch(`${API_BASE}/provinces/${provinceCode}/cities-municipalities/`);
            const data = await response.json();
            data.sort((a, b) => a.name.localeCompare(b.name));
            let options = '<option value="">Select City/Municipality</option>';
            data.forEach(c => { 
                options += `<option value="${c.code}" data-name="${c.name}">${c.name}</option>`; 
            });
            citySelect.innerHTML = options;
        } catch (e) {
            console.error('Error loading cities:', e);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
        }
    }

    // Fetch barangays for selected city
    async function fetchBarangays(type) {
        const citySelect = document.getElementById(type + '_city');
        const cityCode = citySelect.value;
        const brgySelect = document.getElementById(type + '_barangay');

        if(!cityCode) {
            brgySelect.innerHTML = '<option value="">Select City First</option>';
            return;
        }
        
        brgySelect.innerHTML = '<option value="">Loading barangays...</option>';
        try {
            const response = await fetch(`${API_BASE}/cities-municipalities/${cityCode}/barangays/`);
            const data = await response.json();
            data.sort((a, b) => a.name.localeCompare(b.name));
            let options = '<option value="">Select Barangay</option>';
            data.forEach(b => { 
                options += `<option value="${b.code}" data-name="${b.name}">${b.name}</option>`; 
            });
            brgySelect.innerHTML = options;
            updateZip(type);
        } catch (e) {
            console.error('Error loading barangays:', e);
            brgySelect.innerHTML = '<option value="">Error loading barangays</option>';
        }
    }

    // Update zip code based on selected city
    function updateZip(type) {
        ZIP_READY.finally(() => {
            const citySelect = document.getElementById(type + '_city');
            const cityName = citySelect.options[citySelect.selectedIndex] ? citySelect.options[citySelect.selectedIndex].text : '';
            const zipInput = document.getElementById(type + '_zip');
            const zip = getZipCode(cityName);
            zipInput.value = zip || '';
        });
    }

    function openDetails(btn) {
        const row = btn.closest('tr');
        if (!row) return;
        const data = JSON.parse(row.dataset.item || '{}');
        document.getElementById('dItem').textContent = data.type || '—';
        document.getElementById('dCategory').textContent = data.category || '—';
        document.getElementById('dCondition').textContent = data.condition || '—';
        document.getElementById('dLoan').textContent = '₱' + (data.loan || '0.00');
        document.getElementById('dRate').textContent = (data.rate || 0) + '% / mo';
        document.getElementById('dDue').textContent = data.due || '—';
        document.getElementById('dDesc').textContent = data.desc || '—';
        document.getElementById('dSerial').textContent = data.serial || '—';
        const payLink = document.getElementById('payLink');
        const renewLink = document.getElementById('renewLink');
        payLink.href = 'simulate_payment.php?item_id=' + encodeURIComponent(data.id) + '&type=redeem';
        renewLink.href = 'simulate_payment.php?item_id=' + encodeURIComponent(data.id) + '&type=renew';
        document.getElementById('detailModal').style.display = 'flex';
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }

    document.getElementById('editProfileBtn').addEventListener('click', function() {
        document.getElementById('profileModal').style.display = 'flex';
    });
    </script>
</body>
</html>
