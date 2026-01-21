<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$id = $_GET['id'];
$sql = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header("Location: customers.php");
    exit();
}

// Prepare existing ID images for preview
$front_src = !empty($row['id_image_front_path']) ? '../' . ltrim($row['id_image_front_path'], '/') : '';
$show_front_preview = $front_src ? 'display:block;' : 'display:none;';
$front_placeholder_opacity = $front_src ? 'opacity:0;' : '';

$back_src = !empty($row['id_image_back_path']) ? '../' . ltrim($row['id_image_back_path'], '/') : '';
$show_back_preview = $back_src ? 'display:block;' : 'display:none;';
$back_placeholder_opacity = $back_src ? 'opacity:0;' : '';
$birthdate_value = !empty($row['birthdate']) ? date('Y-m-d', strtotime($row['birthdate'])) : '';

// Check if same address
$same_address_checked = (
    trim($row['present_house_num'] ?? '') === trim($row['permanent_house_num'] ?? '') &&
    trim($row['present_street'] ?? '') === trim($row['permanent_street'] ?? '') &&
    trim($row['present_subdivision'] ?? '') === trim($row['permanent_subdivision'] ?? '') &&
    trim($row['present_city'] ?? '') === trim($row['permanent_city'] ?? '') &&
    trim($row['present_province'] ?? '') === trim($row['permanent_province'] ?? '') &&
    trim($row['present_zip'] ?? '') === trim($row['permanent_zip'] ?? '')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - Powersim Phoneshop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <script src="../js/zipcodes.js"></script>
    <style>
        .form-section { margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; }
        .section-hidden { display: none; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-col { flex: 1; min-width: 0; }
        .form-col input, .form-col select, .form-col textarea { width: 100%; box-sizing: border-box; }
        input[type="file"] { padding: 5px; }
        .checkbox-container { margin: 10px 0; }
        .checkbox-label { display:flex; align-items:center; gap:10px; cursor:pointer; user-select:none; }
        .checkbox-label input[type="checkbox"] { position:absolute; opacity:0; width:0; height:0; }
        .checkbox-custom { width:18px; height:18px; border-radius:6px; border:2px solid #e6e6e6; background:#fff; display:inline-block; position:relative; box-sizing:border-box; }
        .checkbox-label input[type="checkbox"]:checked + .checkbox-custom { background:#116530; border-color:#116530; }
        .checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after { content:''; position:absolute; left:5px; top:2px; width:5px; height:10px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg); }
        .checkbox-text { font-weight:700; text-transform:uppercase; color:#333; font-size:0.85rem; }
        .section-complete { border-left: 3px solid #116530; padding-left: 12px; }
        #permanent_fields.disabled-look { opacity: 0.5; pointer-events: none; }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
    <div class="container">
        <form action="customer_process.php" method="post" enctype="multipart/form-data" class="admin-form" id="editCustomerForm">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
                <h2 style="margin:0;">Edit Customer</h2>
                <a href="customers.php" style="display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #ddd;background:#fff;color:#333;text-decoration:none;font-weight:600;">&larr; Back to Customers</a>
            </div>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <!-- Section 1: Personal Information -->
            <div class="form-section" id="section-personal">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <div class="form-row">
                    <div class="form-col">
                        <label>First Name</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($row['first_name']); ?>" required>
                    </div>
                    <div class="form-col">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" value="<?php echo htmlspecialchars($row['middle_name'] ?? ''); ?>">
                    </div>
                    <div class="form-col">
                        <label>Last Name</label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($row['last_name']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label for="birthdate">Date of Birth</label>
                        <input type="date" name="birthdate" id="birthdate" value="<?php echo htmlspecialchars($birthdate_value); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($row['contact_number']); ?>" required maxlength="14">
                    </div>
                    <div class="form-col">
                        <label>Email</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-col">
                        <label>Username</label>
                        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($row['username'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Section 2: Present Address -->
            <div class="form-section section-hidden" id="section-present">
                <h3><i class="fas fa-map-marker-alt"></i> Present Address</h3>
                <div class="form-row">
                    <div class="form-col">
                        <label>Unit/House No.</label>
                        <input type="text" name="present_house_num" id="present_house_num" value="<?php echo htmlspecialchars($row['present_house_num'] ?? ''); ?>" required>
                    </div>
                    <div class="form-col">
                        <label>Street</label>
                        <input type="text" name="present_street" id="present_street" value="<?php echo htmlspecialchars($row['present_street'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Subdivision/Village/Purok</label>
                        <input type="text" name="present_subdivision" id="present_subdivision" value="<?php echo htmlspecialchars($row['present_subdivision'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Province</label>
                        <select name="present_province" id="present_province" required onchange="fetchCities('present')">
                            <option value="">Loading provinces...</option>
                        </select>
                        <input type="hidden" name="present_province_text" id="present_province_text" value="<?php echo htmlspecialchars($row['present_province'] ?? ''); ?>">
                    </div>
                    <div class="form-col">
                        <label>City/Municipality</label>
                        <select name="present_city" id="present_city" required onchange="fetchBarangays('present')">
                            <option value="">Select Province First</option>
                        </select>
                        <input type="hidden" name="present_city_text" id="present_city_text" value="<?php echo htmlspecialchars($row['present_city'] ?? ''); ?>">
                    </div>
                    <div class="form-col">
                        <label>Barangay</label>
                        <select name="present_barangay" id="present_barangay" required onchange="updateBarangayText('present')">
                            <option value="">Select City First</option>
                        </select>
                        <input type="hidden" name="present_barangay_text" id="present_barangay_text" value="<?php echo htmlspecialchars($row['present_barangay'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Zip Code</label>
                        <input type="text" name="present_zip" id="present_zip" value="<?php echo htmlspecialchars($row['present_zip'] ?? ''); ?>" required maxlength="4">
                    </div>
                </div>
            </div>

            <!-- Section 3: Permanent Address -->
            <div class="form-section section-hidden" id="section-permanent">
                <h3><i class="fas fa-home"></i> Permanent Address</h3>
                <div class="checkbox-container">
                    <label class="checkbox-label">
                        <input type="checkbox" id="same_address" <?php echo $same_address_checked ? 'checked' : ''; ?> onchange="copyAddress()">
                        <span class="checkbox-custom" aria-hidden="true"></span>
                        <span class="checkbox-text">Same as Present Address</span>
                    </label>
                </div>
                <div id="permanent_fields">
                    <div class="form-row">
                        <div class="form-col">
                            <label>Unit/House No.</label>
                            <input type="text" name="permanent_house_num" id="permanent_house_num" value="<?php echo htmlspecialchars($row['permanent_house_num'] ?? ''); ?>" required>
                        </div>
                        <div class="form-col">
                            <label>Street</label>
                            <input type="text" name="permanent_street" id="permanent_street" value="<?php echo htmlspecialchars($row['permanent_street'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Subdivision/Village/Purok</label>
                            <input type="text" name="permanent_subdivision" id="permanent_subdivision" value="<?php echo htmlspecialchars($row['permanent_subdivision'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Province</label>
                            <select name="permanent_province" id="permanent_province" required onchange="fetchCities('permanent')">
                                <option value="">Loading provinces...</option>
                            </select>
                            <input type="hidden" name="permanent_province_text" id="permanent_province_text" value="<?php echo htmlspecialchars($row['permanent_province'] ?? ''); ?>">
                        </div>
                        <div class="form-col">
                            <label>City/Municipality</label>
                            <select name="permanent_city" id="permanent_city" required onchange="fetchBarangays('permanent')">
                                <option value="">Select Province First</option>
                            </select>
                            <input type="hidden" name="permanent_city_text" id="permanent_city_text" value="<?php echo htmlspecialchars($row['permanent_city'] ?? ''); ?>">
                        </div>
                        <div class="form-col">
                            <label>Barangay</label>
                            <select name="permanent_barangay" id="permanent_barangay" required onchange="updateBarangayText('permanent')">
                                <option value="">Select City First</option>
                            </select>
                            <input type="hidden" name="permanent_barangay_text" id="permanent_barangay_text" value="<?php echo htmlspecialchars($row['permanent_barangay'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Zip Code</label>
                            <input type="text" name="permanent_zip" id="permanent_zip" value="<?php echo htmlspecialchars($row['permanent_zip'] ?? ''); ?>" required maxlength="4">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Identification -->
            <div class="form-section section-hidden" id="section-id">
                <h3><i class="fas fa-id-card"></i> Identification</h3>
                <div class="form-row">
                    <div class="form-col">
                        <label>ID Type</label>
                        <select name="id_type" id="id_type" required onchange="toggleOtherID()">
                            <option value="">Select ID Type</option>
                            <?php
                            $bsp_ids_grouped = [
                                "Primary / Major IDs" => [
                                    "PhilSys ID (National ID)", "Passport", "Driver's License", "SSS ID", "GSIS e-Card", 
                                    "UMID", "PRC ID", "IBP ID", "OWWA ID", "OFW ID", "Senior Citizen ID", 
                                    "Voter's ID", "Postal ID", "PhilHealth ID", "TIN ID"
                                ],
                                "Secondary / Other Accepted IDs" => [
                                    "Government Office ID", "DSWD Certification", "Barangay Certification", 
                                    "NBI Clearance", "Company ID", "Student ID", "Others"
                                ]
                            ];

                            foreach($bsp_ids_grouped as $group_label => $ids) {
                                echo "<optgroup label=\"$group_label\">";
                                foreach($ids as $id_name) {
                                    $selected = ($row['id_type'] == $id_name) ? 'selected' : '';
                                    if ($row['id_type'] == 'National ID' && $id_name == 'PhilSys ID (National ID)') $selected = 'selected';
                                    echo "<option value=\"$id_name\" $selected>$id_name</option>";
                                }
                                echo "</optgroup>";
                            }
                            ?>
                        </select>
                        <input type="text" name="other_id_type" id="other_id_type" value="<?php echo htmlspecialchars($row['other_id_type'] ?? ''); ?>" placeholder="Please specify" style="display:none; margin-top: 5px;">
                    </div>
                </div>
                <?php include __DIR__ . '/_id_upload.php'; ?>
            </div>

            <div style="display:flex;gap:10px;align-items:center;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Customer</button>
            </div>
        </form>
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

    <script>
        const API_BASE = 'https://psgc.gitlab.io/api';
        
        // CRITICAL: Hydrate ZIP code mapping from JSON file
        const ZIP_READY = hydrateFromJson('../js/ph_locations.json');

        // Progressive reveal configuration (same as add_customer.php)
        const sections = {
            personal: {
                required: ['first_name', 'last_name', 'birthdate', 'contact_number', 'email'],
                next: 'section-present'
            },
            present: {
                required: ['present_house_num', 'present_street', 'present_subdivision', 'present_province', 'present_city', 'present_barangay', 'present_zip'],
                next: 'section-permanent'
            },
            permanent: {
                required: ['permanent_house_num', 'permanent_street', 'permanent_subdivision', 'permanent_province', 'permanent_city', 'permanent_barangay', 'permanent_zip'],
                next: 'section-id'
            },
            id: {
                required: ['id_type'],
                next: null
            }
        };

        function isFilled(id) {
            const el = document.getElementById(id);
            if (!el) return false;
            if (el.type === 'checkbox') return el.checked;
            return (el.value || '').trim().length > 0;
        }

        function evaluateSection(key) {
            const cfg = sections[key];
            if (!cfg) return;
            const complete = cfg.required.every(isFilled);
            if (complete && cfg.next) {
                const nextEl = document.getElementById(cfg.next);
                if (nextEl) nextEl.classList.remove('section-hidden');
            }
            // Add visual indicator for completed section
            const sectionEl = document.getElementById('section-' + key);
            if (sectionEl && complete) {
                sectionEl.classList.add('section-complete');
            }
        }

        // Expose for copyAddress
        window.evaluateSection = evaluateSection;

        document.addEventListener('DOMContentLoaded', async () => {
            // Wait for ZIP data to be ready
            await ZIP_READY;
            
            // Initialize Other ID field
            toggleOtherID();
            
            // Set up date restrictions
            const dobInput = document.getElementById('birthdate');
            if (dobInput) {
                const today = new Date();
                today.setFullYear(today.getFullYear() - 18);
                const maxDate = today.toISOString().split('T')[0];
                dobInput.max = maxDate;
                dobInput.min = '1900-01-01';
            }
            
            // Contact number protection
            setupContactNumber();
            
            // Restrict zip inputs to digits only
            restrictZipInput('present_zip');
            restrictZipInput('permanent_zip');
            
            // Load and pre-select addresses
            await loadAndSelectAddress('present');
            await loadAndSelectAddress('permanent');
            
            // Attach listeners to required fields for progressive reveal
            Object.keys(sections).forEach(key => {
                sections[key].required.forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    ['input', 'change'].forEach(evt => el.addEventListener(evt, () => evaluateSection(key)));
                });
            });
            
            // Initial evaluation to show all completed sections (for edit mode)
            ['personal', 'present', 'permanent', 'id'].forEach(evaluateSection);
            
            // If same address was checked, handle it
            const sameCb = document.getElementById('same_address');
            if (sameCb && sameCb.checked) {
                togglePermanentFields(true);
            }
        });

        function setupContactNumber() {
            const contactInput = document.getElementById('contact_number');
            if (!contactInput) return;
            
            if (!contactInput.value.startsWith('+63 ')) {
                contactInput.value = '+63 ' + contactInput.value.replace(/^\+?63\s?|^0/, '');
            }
            
            contactInput.addEventListener('input', function() {
                let value = this.value;
                if (!value.startsWith('+63 ')) {
                    value = '+63 ' + value.replace(/\+63\s?|^63/, '');
                }
                let prefix = '+63 ';
                let remainder = value.substring(prefix.length).replace(/\D/g, '');
                this.value = prefix + remainder;
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

        function restrictZipInput(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 4);
            });
            el.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) return;
                const allowed = ['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Home', 'End'];
                if (allowed.includes(e.key)) return;
                if (!/^[0-9]$/.test(e.key)) e.preventDefault();
            });
        }

        async function loadAndSelectAddress(type) {
            const savedProv = document.getElementById(type + '_province_text').value;
            const savedCity = document.getElementById(type + '_city_text').value;
            const savedBrgy = document.getElementById(type + '_barangay_text').value;
            
            const provSelect = document.getElementById(type + '_province');
            const citySelect = document.getElementById(type + '_city');
            const brgySelect = document.getElementById(type + '_barangay');
            
            // Load provinces
            try {
                const res = await fetch(`${API_BASE}/provinces/`);
                const data = await res.json();
                data.sort((a, b) => a.name.localeCompare(b.name));
                
                let options = '<option value="">Select Province</option>';
                let selectedCode = '';
                
                data.forEach(p => {
                    let isSelected = (savedProv && p.name.toUpperCase() === savedProv.toUpperCase());
                    if (isSelected) selectedCode = p.code;
                    options += `<option value="${p.code}" ${isSelected ? 'selected' : ''}>${p.name}</option>`;
                });
                provSelect.innerHTML = options;
                
                // Load cities if province found
                if (selectedCode) {
                    const cityRes = await fetch(`${API_BASE}/provinces/${selectedCode}/cities-municipalities/`);
                    const cityData = await cityRes.json();
                    cityData.sort((a, b) => a.name.localeCompare(b.name));
                    
                    let cityOptions = '<option value="">Select City/Municipality</option>';
                    let selectedCityCode = '';
                    
                    cityData.forEach(c => {
                        let isSelected = (savedCity && c.name.toUpperCase() === savedCity.toUpperCase());
                        if (isSelected) selectedCityCode = c.code;
                        cityOptions += `<option value="${c.code}" ${isSelected ? 'selected' : ''}>${c.name}</option>`;
                    });
                    citySelect.innerHTML = cityOptions;
                    
                    // Load barangays if city found
                    if (selectedCityCode) {
                        const brgyRes = await fetch(`${API_BASE}/cities-municipalities/${selectedCityCode}/barangays/`);
                        const brgyData = await brgyRes.json();
                        brgyData.sort((a, b) => a.name.localeCompare(b.name));
                        
                        let brgyOptions = '<option value="">Select Barangay</option>';
                        
                        brgyData.forEach(b => {
                            let isSelected = (savedBrgy && b.name.toUpperCase() === savedBrgy.toUpperCase());
                            brgyOptions += `<option value="${b.code}" ${isSelected ? 'selected' : ''}>${b.name}</option>`;
                        });
                        brgySelect.innerHTML = brgyOptions;
                    }
                }
            } catch (e) {
                console.error('Error loading address for ' + type, e);
                provSelect.innerHTML = '<option value="">Error loading provinces</option>';
            }
        }

        async function fetchCities(type) {
            const provinceSelect = document.getElementById(type + '_province');
            const provinceCode = provinceSelect.value;
            const citySelect = document.getElementById(type + '_city');
            const brgySelect = document.getElementById(type + '_barangay');
            const zipInput = document.getElementById(type + '_zip');
            
            // Update hidden text field
            if (provinceSelect.selectedIndex > 0) {
                document.getElementById(type + '_province_text').value = provinceSelect.options[provinceSelect.selectedIndex].text;
            }
            
            if (!provinceCode) {
                citySelect.innerHTML = '<option value="">Select Province First</option>';
                brgySelect.innerHTML = '<option value="">Select City First</option>';
                zipInput.value = '';
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
                    options += `<option value="${c.code}">${c.name}</option>`;
                });
                citySelect.innerHTML = options;
            } catch (e) {
                console.error('Error loading cities:', e);
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            }
            
            evaluateSection(type);
        }

        async function fetchBarangays(type) {
            const citySelect = document.getElementById(type + '_city');
            const cityCode = citySelect.value;
            const brgySelect = document.getElementById(type + '_barangay');
            const zipInput = document.getElementById(type + '_zip');
            
            // Update hidden text field
            if (citySelect.selectedIndex > 0) {
                document.getElementById(type + '_city_text').value = citySelect.options[citySelect.selectedIndex].text;
            }
            
            if (!cityCode) {
                brgySelect.innerHTML = '<option value="">Select City First</option>';
                zipInput.value = '';
                return;
            }
            
            brgySelect.innerHTML = '<option value="">Loading barangays...</option>';
            
            try {
                const response = await fetch(`${API_BASE}/cities-municipalities/${cityCode}/barangays/`);
                const data = await response.json();
                data.sort((a, b) => a.name.localeCompare(b.name));
                
                let options = '<option value="">Select Barangay</option>';
                data.forEach(b => {
                    options += `<option value="${b.code}">${b.name}</option>`;
                });
                brgySelect.innerHTML = options;
                
                // Auto-fill ZIP code
                updateZip(type);
            } catch (e) {
                console.error('Error loading barangays:', e);
                brgySelect.innerHTML = '<option value="">Error loading barangays</option>';
            }
            
            evaluateSection(type);
        }

        function updateBarangayText(type) {
            const brgySelect = document.getElementById(type + '_barangay');
            if (brgySelect.selectedIndex > 0) {
                document.getElementById(type + '_barangay_text').value = brgySelect.options[brgySelect.selectedIndex].text;
            }
            evaluateSection(type);
        }

        function updateZip(type) {
            const citySelect = document.getElementById(type + '_city');
            const zipInput = document.getElementById(type + '_zip');
            
            if (!citySelect || citySelect.selectedIndex <= 0) {
                return;
            }
            
            const cityName = citySelect.options[citySelect.selectedIndex].text;
            
            // Use the getZipCode function from zipcodes.js
            if (typeof getZipCode === 'function') {
                const zip = getZipCode(cityName);
                if (zip) {
                    zipInput.value = zip;
                }
            }
        }

        function togglePermanentFields(hide) {
            const fieldsDiv = document.getElementById('permanent_fields');
            if (fieldsDiv) {
                if (hide) {
                    fieldsDiv.classList.add('disabled-look');
                } else {
                    fieldsDiv.classList.remove('disabled-look');
                }
            }
        }

        async function copyAddress() {
            const same = document.getElementById('same_address');
            if (!same) return;
            
            if (same.checked) {
                // Copy all text fields
                const textFields = ['house_num', 'street', 'subdivision', 'zip'];
                textFields.forEach(field => {
                    const src = document.getElementById('present_' + field);
                    const dst = document.getElementById('permanent_' + field);
                    if (src && dst) dst.value = src.value;
                });
                
                // Copy province dropdown
                const presProv = document.getElementById('present_province');
                const perProv = document.getElementById('permanent_province');
                perProv.innerHTML = presProv.innerHTML;
                perProv.value = presProv.value;
                document.getElementById('permanent_province_text').value = document.getElementById('present_province_text').value;
                
                // Copy city dropdown
                const presCity = document.getElementById('present_city');
                const perCity = document.getElementById('permanent_city');
                perCity.innerHTML = presCity.innerHTML;
                perCity.value = presCity.value;
                document.getElementById('permanent_city_text').value = document.getElementById('present_city_text').value;
                
                // Copy barangay dropdown
                const presBrgy = document.getElementById('present_barangay');
                const perBrgy = document.getElementById('permanent_barangay');
                perBrgy.innerHTML = presBrgy.innerHTML;
                perBrgy.value = presBrgy.value;
                document.getElementById('permanent_barangay_text').value = document.getElementById('present_barangay_text').value;
                
                togglePermanentFields(true);
            } else {
                // Clear and reset permanent address fields
                document.getElementById('permanent_house_num').value = '';
                document.getElementById('permanent_street').value = '';
                document.getElementById('permanent_subdivision').value = '';
                document.getElementById('permanent_zip').value = '';
                document.getElementById('permanent_province_text').value = '';
                document.getElementById('permanent_city_text').value = '';
                document.getElementById('permanent_barangay_text').value = '';
                
                // Reload provinces for permanent address
                const provSelect = document.getElementById('permanent_province');
                try {
                    const res = await fetch(`${API_BASE}/provinces/`);
                    const data = await res.json();
                    data.sort((a, b) => a.name.localeCompare(b.name));
                    let options = '<option value="">Select Province</option>';
                    data.forEach(p => {
                        options += `<option value="${p.code}">${p.name}</option>`;
                    });
                    provSelect.innerHTML = options;
                } catch (e) {
                    provSelect.innerHTML = '<option value="">Error loading provinces</option>';
                }
                
                document.getElementById('permanent_city').innerHTML = '<option value="">Select Province First</option>';
                document.getElementById('permanent_barangay').innerHTML = '<option value="">Select City First</option>';
                
                togglePermanentFields(false);
            }
            
            evaluateSection('permanent');
        }

        function previewImage(input, previewId, placeholderId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById(previewId);
                    var placeholder = document.getElementById(placeholderId);
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.opacity = '0';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function toggleOtherID() {
            const select = document.getElementById('id_type');
            const otherInput = document.getElementById('other_id_type');
            if (select && select.value === 'Others') {
                otherInput.style.display = 'block';
                otherInput.required = true;
            } else if (otherInput) {
                otherInput.style.display = 'none';
                otherInput.required = false;
            }
            evaluateSection('id');
        }
    </script>
</body>
</html>
