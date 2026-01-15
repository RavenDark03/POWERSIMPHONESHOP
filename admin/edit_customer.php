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
    <script src="https://cdn.jsdelivr.net/npm/use-postal-ph@1.0.1/dist/index.js"></script>
    <script src="../js/zipcodes.js"></script>
    <style>
        .form-section { margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; }
        /* .form-section h3 removed to use style.css */
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-col { flex: 1; min-width: 0; }
        /* Ensure inputs/selects expand to column width to match Add Customer layout */
        .form-col input, .form-col select, .form-col textarea { width: 100%; box-sizing: border-box; }
        input[type="file"] { padding: 5px; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <a href="index.php" class="logo-container">
                <img src="../images/powersim logo.png" alt="Powersim Phoneshop" class="logo-img">
                <span class="logo-text">Powersim Phoneshop</span>
            </a>
            <nav>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="customers.php">Customers</a></li>
                    <li><a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2>Edit Customer</h2>
        <form action="customer_process.php" method="post" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-row">
                    <div class="form-col">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo $row['first_name']; ?>" required>
                    </div>
                    <div class="form-col">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="<?php echo $row['middle_name']; ?>">
                    </div>
                    <div class="form-col">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?php echo $row['last_name']; ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Contact Number (e.g. +63 9xxxxxxxxx)</label>
                        <input type="text" name="contact_number" value="<?php echo $row['contact_number']; ?>" required id="contact_number" maxlength="14">
                    </div>
                    <div class="form-col">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo isset($row['email']) ? $row['email'] : ''; ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Present Address</h3>
                <div class="form-row">
                    <div class="form-col">
                        <label>Unit/House No.</label>
                        <input type="text" name="present_house_num" value="<?php echo $row['present_house_num']; ?>" required>
                    </div>
                    <div class="form-col">
                        <label>Street</label>
                        <input type="text" name="present_street" value="<?php echo $row['present_street']; ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Subdivision/Village/Purok</label>
                        <input type="text" name="present_subdivision" value="<?php echo $row['present_subdivision']; ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Province</label>
                        <select name="present_province" id="present_province" required onchange="fetchCities('present')">
                            <option value="">Loading...</option>
                        </select>
                        <input type="hidden" name="present_province_text" id="present_province_text" value="<?php echo $row['present_province']; ?>">
                    </div>
                    <div class="form-col">
                        <label>City/Municipality</label>
                        <select name="present_city" id="present_city" required onchange="fetchBarangays('present')">
                            <option value="">Select Province First</option>
                        </select>
                        <input type="hidden" name="present_city_text" id="present_city_text" value="<?php echo $row['present_city']; ?>">
                    </div>
                    <div class="form-col">
                        <label>Barangay</label>
                        <select name="present_barangay" id="present_barangay" required onchange="updateZip('present')">
                             <option value="">Select Barangay</option>
                        </select>
                        <input type="hidden" name="present_barangay_text" id="present_barangay_text" value="<?php echo $row['present_barangay']; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Zip Code</label>
                        <input type="text" name="present_zip" id="present_zip" value="<?php echo $row['present_zip']; ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Permanent Address</h3>
                <div class="form-row">
                    <div class="form-col">
                        <label>Unit/House No.</label>
                        <input type="text" name="permanent_house_num" value="<?php echo $row['permanent_house_num']; ?>" required>
                    </div>
                    <div class="form-col">
                        <label>Street</label>
                        <input type="text" name="permanent_street" value="<?php echo $row['permanent_street']; ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Subdivision/Village/Purok</label>
                        <input type="text" name="permanent_subdivision" value="<?php echo $row['permanent_subdivision']; ?>" required>
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-col">
                        <label>Province</label>
                        <select name="permanent_province" id="permanent_province" required onchange="fetchCities('permanent')">
                            <option value="">Loading...</option>
                        </select>
                        <input type="hidden" name="permanent_province_text" id="permanent_province_text" value="<?php echo $row['permanent_province']; ?>">
                    </div>
                    <div class="form-col">
                        <label>City/Municipality</label>
                        <select name="permanent_city" id="permanent_city" required onchange="fetchBarangays('permanent')">
                             <option value="">Select Province First</option>
                        </select>
                        <input type="hidden" name="permanent_city_text" id="permanent_city_text" value="<?php echo $row['permanent_city']; ?>">
                    </div>
                    <div class="form-col">
                        <label>Barangay</label>
                        <select name="permanent_barangay" id="permanent_barangay" required onchange="updateZip('permanent')">
                             <option value="">Select Barangay</option>
                        </select>
                        <input type="hidden" name="permanent_barangay_text" id="permanent_barangay_text" value="<?php echo $row['permanent_barangay']; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Zip Code</label>
                        <input type="text" name="permanent_zip" id="permanent_zip" value="<?php echo $row['permanent_zip']; ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Identification</h3>
                <div class="form-row">
                    <div class="form-col">
                        <label>ID Type</label>
                        <select name="id_type" id="id_type" required onchange="toggleOtherID()">
                            <option value="">Select ID Type </option>
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
                                    
                                    // Legacy support
                                    if ($row['id_type'] == 'National ID' && $id_name == 'PhilSys ID (National ID)') $selected = 'selected';
                                    if ($row['id_type'] == 'SSS/UMID' && ($id_name == 'SSS ID' || $id_name == 'UMID')) $selected = ''; 
                                    
                                    echo "<option value=\"$id_name\" $selected>$id_name</option>";
                                }
                                echo "</optgroup>";
                            }
                            ?>
                        </select>
                        <input type="text" name="other_id_type" id="other_id_type" value="<?php echo isset($row['other_id_type']) ? $row['other_id_type'] : ''; ?>" placeholder="Please specify" style="display:none; margin-top: 5px;">
                    </div>
                </div>
                <?php
                    // variables already computed above in this file: $front_src, $show_front_preview, $front_placeholder_opacity, $back_src, $show_back_preview, $back_placeholder_opacity
                    // ensure paths are set as expected (they are already set earlier)
                    $front_label = 'Front ID';
                    $back_label = 'Back ID';
                    // do not set required on edit
                    include __DIR__ . '/_id_upload.php';
                ?>
            </div>

            <button type="submit" class="btn btn-primary">Update Customer</button>
        </form>
    </div>
</div>

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

    <script>
        const API_BASE = 'https://psgc.gitlab.io/api';
        
        document.addEventListener('DOMContentLoaded', async () => {
             // Init other ID
            toggleOtherID();

             // Init Text Sync Listeners
            setupSync('present');
            setupSync('permanent');

            // Load and Pre-Select Data
            await loadAndSelect('present');
            await loadAndSelect('permanent');
            
            // Contact Number Protection
            const contactInput = document.getElementById('contact_number');
            
            if(contactInput) {
                // Initial enforcement if empty or partial
                if(!contactInput.value.startsWith('+63 ')) contactInput.value = '+63 ' + contactInput.value.replace(/\D/g, '');

                contactInput.addEventListener('input', function(e) {
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
        });

        // Preview Image Logic
        function previewImage(input, previewId, placeholderId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById(previewId);
                    var placeholder = document.getElementById(placeholderId);
                    
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.opacity = '0'; // Hide placeholder visually
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        // triggerUpload and updateFileName no longer needed

        function toggleOtherID() {
            const select = document.getElementById('id_type');
            const otherInput = document.getElementById('other_id_type');
            if (select && select.value === 'Others') {
                otherInput.style.display = 'block';
                otherInput.required = true;
            } else {
                if(otherInput) {
                    otherInput.style.display = 'none';
                    otherInput.required = false;
                }
            }
        }

        function setupSync(type) {
            document.getElementById(type + '_province').addEventListener('change', function() {
                let text = this.options[this.selectedIndex].text;
                document.getElementById(type + '_province_text').value = text;
            });
            document.getElementById(type + '_city').addEventListener('change', function() {
                let text = this.options[this.selectedIndex].text;
                document.getElementById(type + '_city_text').value = text;
                // Auto-fill Zip using local zipcodes.js first, fallback to remote API
                try {
                    updateZip(type);
                } catch (err) {
                    console.error('updateZip error on change', err);
                }
            });
            document.getElementById(type + '_barangay').addEventListener('change', function() {
                let text = this.options[this.selectedIndex].text;
                document.getElementById(type + '_barangay_text').value = text;
            });
        }

        async function loadAndSelect(type) {
            // 1. Load Provinces
            const savedProv = document.getElementById(type + '_province_text').value;
            const provSelect = document.getElementById(type + '_province');
            
            try {
                const res = await fetch(`${API_BASE}/provinces/`);
                const data = await res.json();
                data.sort((a, b) => a.name.localeCompare(b.name));
                
                let options = '<option value="">Select Province</option>';
                let selectedCode = '';
                
                data.forEach(p => {
                    let isSelected = (p.name.toUpperCase() === savedProv.toUpperCase()) ? 'selected' : '';
                    if(isSelected) selectedCode = p.code;
                    options += `<option value="${p.code}" ${isSelected}>${p.name}</option>`;
                });
                provSelect.innerHTML = options;

                // 2. Load Cities if Province found
                if (selectedCode) {
                    await fetchCities(type, selectedCode, true);
                }

            } catch (e) { console.error(e); }
        }

        async function fetchCities(type, overrideCode = null, isPreload = false) {
            const provinceCode = overrideCode || document.getElementById(type + '_province').value;
            const select = document.getElementById(type + '_city');
            const savedCity = document.getElementById(type + '_city_text').value;

            // keep human-readable province text in sync
            const provSelect = document.getElementById(type + '_province');
            if (provSelect && provSelect.selectedIndex >= 0) {
                document.getElementById(type + '_province_text').value = provSelect.options[provSelect.selectedIndex].text;
            }

            if(!provinceCode) {
                select.innerHTML = '<option value="">Select Province First</option>';
                const brgy = document.getElementById(type + '_barangay');
                if (brgy) brgy.innerHTML = '<option value="">Select City First</option>';
                return;
            }

            select.innerHTML = '<option value="">Loading...</option>';
            
            try {
                const response = await fetch(`${API_BASE}/provinces/${provinceCode}/cities-municipalities/`);
                const data = await response.json();
                data.sort((a, b) => a.name.localeCompare(b.name));
                
                let options = '<option value="">Select City</option>';
                let selectedCode = '';
                
                data.forEach(c => {
                    let isSelected = false;
                    if(isPreload && c.name.toUpperCase() === savedCity.toUpperCase()) {
                        isSelected = true;
                        selectedCode = c.code;
                    }
                    options += `<option value="${c.code}" ${isSelected ? 'selected' : ''}>${c.name}</option>`;
                });
                select.innerHTML = options;

                if(isPreload && selectedCode) {
                    await fetchBarangays(type, selectedCode, true);
                }

            } catch (e) {
                console.error('Error loading cities', e);
                select.innerHTML = '<option value="">Error loading cities</option>';
            }
        }

        async function fetchBarangays(type, overrideCode = null, isPreload = false) {
            const cityCode = overrideCode || document.getElementById(type + '_city').value;
            const select = document.getElementById(type + '_barangay');
            const savedBrgy = document.getElementById(type + '_barangay_text').value;

            // keep human-readable city text in sync
            const citySelect = document.getElementById(type + '_city');
            if (citySelect && citySelect.selectedIndex >= 0) {
                document.getElementById(type + '_city_text').value = citySelect.options[citySelect.selectedIndex].text;
            }

            if(!cityCode) {
                select.innerHTML = '<option value="">Select City First</option>';
                return;
            }

            select.innerHTML = '<option value="">Loading...</option>';

            // add a timeout wrapper for fetch to avoid stuck "Loading..."
            const fetchWithTimeout = (url, options = {}, timeout = 8000) => {
                return Promise.race([
                    fetch(url, options),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), timeout))
                ]);
            };

            try {
                const response = await fetchWithTimeout(`${API_BASE}/cities-municipalities/${encodeURIComponent(cityCode)}/barangays/`);
                if (!response.ok) throw new Error('Network response not ok');
                const data = await response.json();
                if (!Array.isArray(data) || data.length === 0) {
                    select.innerHTML = '<option value="">No barangays found</option>';
                    return;
                }
                data.sort((a, b) => a.name.localeCompare(b.name));

                let options = '<option value="">Select Barangay</option>';
                let foundSelected = false;
                data.forEach(b => {
                    let isSelected = false;
                    if(isPreload && savedBrgy && b.name.toUpperCase() === savedBrgy.toUpperCase()) {
                        isSelected = true;
                        foundSelected = true;
                    }
                    options += `<option value="${b.code}" ${isSelected ? 'selected' : ''}>${b.name}</option>`;
                });
                select.innerHTML = options;

                // If preloading and we found a selected barangay, leave zip update to caller; otherwise update zip now
                if(!isPreload || !foundSelected) updateZip(type);
            } catch (e) {
                console.error('Error loading barangays', e);
                // If we timed out, attempt one retry without the timeout wrapper (longer wait)
                if (e && e.message && e.message.toLowerCase().includes('timeout')) {
                    try {
                        const retryResp = await fetch(`${API_BASE}/cities-municipalities/${encodeURIComponent(cityCode)}/barangays/`);
                        if (retryResp.ok) {
                            const retryData = await retryResp.json();
                            if (Array.isArray(retryData) && retryData.length > 0) {
                                retryData.sort((a, b) => a.name.localeCompare(b.name));
                                let options = '<option value="">Select Barangay</option>';
                                retryData.forEach(b => {
                                    options += `<option value="${b.code}">${b.name}</option>`;
                                });
                                select.innerHTML = options;
                                updateZip(type);
                                return;
                            } else {
                                select.innerHTML = '<option value="">No barangays found</option>';
                                return;
                            }
                        }
                    } catch (retryErr) {
                        console.error('Retry loading barangays failed', retryErr);
                    }
                }
                select.innerHTML = '<option value="">Error loading barangays</option>';
            }
        }

        // Auto-fill zip code using local zipcodes.js first, then usePostalPH as fallback
        async function updateZip(type) {
            try {
                const citySelect = document.getElementById(type + '_city');
                const zipInput = document.getElementById(type + '_zip');
                if (!citySelect || !zipInput) return;

                const cityName = citySelect.options[citySelect.selectedIndex] ? citySelect.options[citySelect.selectedIndex].text : '';
                if (!cityName) { zipInput.value = ''; return; }

                // Prefer local lookup
                if (typeof getZipCode === 'function') {
                    const z = getZipCode(cityName);
                    if (z) { zipInput.value = z; return; }
                }

                // Fallback: use usePostalPH if available
                if (window.usePostalPH && usePostalPH.fetchPostCodes) {
                    try {
                        const { fetchPostCodes } = usePostalPH;
                        const data = await fetchPostCodes({ municipality: cityName });
                        if (data && data.length > 0) {
                            zipInput.value = data[0].post_code || '';
                            return;
                        }
                    } catch (e) {
                        console.error('usePostalPH fetch failed', e);
                    }
                }

                // final fallback: clear
                zipInput.value = '';
            } catch (e) {
                console.error('updateZip error', e);
            }
        }



    </script>
</body>
</html>