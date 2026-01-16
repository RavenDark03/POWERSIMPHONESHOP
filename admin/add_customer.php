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
    <title>Add Customer - Powersim Phoneshop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <script src="../js/zipcodes.js"></script>
    <style>
        .form-section { margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-col { flex: 1; }
        .form-col input, .form-col select { width: 100%; box-sizing: border-box; }
        .checkbox-container { margin: 10px 0; }
        .checkbox-label { display:flex; align-items:center; gap:10px; cursor:pointer; user-select:none; }
        .checkbox-label input[type="checkbox"] { position:absolute; opacity:0; width:0; height:0; }
        .checkbox-custom { width:18px; height:18px; border-radius:6px; border:2px solid #e6e6e6; background:#fff; display:inline-block; position:relative; box-sizing:border-box; }
        .checkbox-label input[type="checkbox"]:checked + .checkbox-custom { background:#116530; border-color:#116530; }
        .checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after { content:''; position:absolute; left:5px; top:2px; width:5px; height:10px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg); }
        .checkbox-text { font-weight:700; text-transform:uppercase; color:#333; font-size:0.85rem; }
        input[type="file"] { padding: 5px; }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
    <div class="container">
        <form action="customer_process.php" method="post" enctype="multipart/form-data" class="admin-form">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <h2 style="margin:0;">Add New Customer</h2>
                <a href="customers.php" style="display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #ddd;background:#fff;color:#333;text-decoration:none;font-weight:600;">&larr; Back to Customers</a>
            </div>
            <input type="hidden" name="action" value="add">
            
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-row">
                    <div class="form-col"><label>First Name</label><input type="text" name="first_name" required></div>
                    <div class="form-col"><label>Middle Name</label><input type="text" name="middle_name"></div>
                    <div class="form-col"><label>Last Name</label><input type="text" name="last_name" required></div>
                </div>
                <div class="form-row">
                    <div class="form-col"><label for="contact_number">Contact Number</label><input type="text" name="contact_number" id="contact_number" required placeholder="+63 9xxxxxxxxx" maxlength="14"></div>
                    <div class="form-col"><label for="email">Email Address</label><input type="email" name="email" id="email" required placeholder="customer@example.com"></div>
                    <div class="form-col"><label for="username">Username</label><input type="text" name="username" id="username" placeholder="optional username"></div>
                </div>
            </div>

            <div class="form-section">
                <h3>Present Address</h3>
                <div class="form-row">
                    <div class="form-col"><label>Unit/House No.</label><input type="text" name="present_house_num" id="present_house_num" required></div>
                    <div class="form-col"><label>Street</label><input type="text" name="present_street" id="present_street" required></div>
                </div>
                <div class="form-row">
                    <div class="form-col"><label for="present_subdivision">Subdivision/Village/Purok</label><input type="text" name="present_subdivision" id="present_subdivision" required></div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label for="present_province">Province</label>
                        <select name="present_province" id="present_province" required onchange="fetchCities('present')"><option value="">Select Province</option></select>
                        <input type="hidden" name="present_province_text" id="present_province_text">
                    </div>
                    <div class="form-col">
                        <label for="present_city">City/Municipality</label>
                        <select name="present_city" id="present_city" required onchange="fetchBarangays('present')"><option value="">Select Province First</option></select>
                        <input type="hidden" name="present_city_text" id="present_city_text">
                    </div>
                    <div class="form-col">
                        <label for="present_barangay">Barangay</label>
                        <select name="present_barangay" id="present_barangay" required onchange="updateZip('present')"><option value="">Select City First</option></select>
                        <input type="hidden" name="present_barangay_text" id="present_barangay_text">
                    </div>
                </div>
                 <div class="form-row">
                     <div class="form-col"><label for="present_zip">Zip Code</label><input type="text" name="present_zip" id="present_zip" required></div>
                 </div>
            </div>

            <div class="form-section">
                <h3>Permanent Address</h3>
                <div class="checkbox-container">
                    <label class="checkbox-label">
                        <input type="checkbox" id="same_address" onchange="copyAddress()">
                        <span class="checkbox-custom" aria-hidden="true"></span>
                        <span class="checkbox-text">Same as Present Address</span>
                    </label>
                </div>
                <div class="form-row">
                    <div class="form-col"><label>Unit/House No.</label><input type="text" name="permanent_house_num" id="permanent_house_num" required></div>
                    <div class="form-col"><label>Street</label><input type="text" name="permanent_street" id="permanent_street" required></div>
                </div>
                <div class="form-row">
                    <div class="form-col"><label for="permanent_subdivision">Subdivision/Village/Purok</label><input type="text" name="permanent_subdivision" id="permanent_subdivision" required></div>
                </div>
                 <div class="form-row">
                     <div class="form-col">
                        <label for="permanent_province">Province</label>
                        <select name="permanent_province" id="permanent_province" required onchange="fetchCities('permanent')"><option value="">Select Province</option></select>
                        <input type="hidden" name="permanent_province_text" id="permanent_province_text">
                    </div>
                    <div class="form-col">
                        <label for="permanent_city">City/Municipality</label>
                        <select name="permanent_city" id="permanent_city" required onchange="fetchBarangays('permanent')"><option value="">Select Province First</option></select>
                        <input type="hidden" name="permanent_city_text" id="permanent_city_text">
                    </div>
                    <div class="form-col">
                        <label for="permanent_barangay">Barangay</label>
                        <select name="permanent_barangay" id="permanent_barangay" required onchange="updateZip('permanent')"><option value="">Select City First</option></select>
                        <input type="hidden" name="permanent_barangay_text" id="permanent_barangay_text">
                    </div>
                </div>
                <div class="form-row">
                     <div class="form-col"><label for="permanent_zip">Zip Code</label><input type="text" name="permanent_zip" id="permanent_zip" required></div>
                </div>
            </div>

            <div class="form-section">
                <h3>Identification</h3>
                <div class="form-row">
                    <div class="form-col">
                        <label>ID Type</label>
                        <select name="id_type" id="id_type" required onchange="toggleOtherID()">
                            <option value="">Select ID Type</option>
                            <optgroup label="Primary / Major IDs">
                                <option value="PhilSys ID (National ID)">PhilSys ID (National ID)</option>
                                <option value="Passport">Passport</option>
                                <option value="Driver's License">Driver's License</option>
                                <option value="SSS ID">SSS ID</option>
                                <option value="UMID">UMID (Unified Multi-Purpose ID)</option>
                                <option value="PRC ID">PRC ID</option>
                                <option value="IBP ID">IBP ID</option>
                                <option value="OWWA ID">OWWA ID</option>
                                <option value="OFW ID">OFW ID</option>
                                <option value="Senior Citizen ID">Senior Citizen ID</option>
                                <option value="Voter's ID">Voter's ID</option>
                                <option value="Postal ID">Postal ID</option>
                                <option value="PhilHealth ID">PhilHealth ID</option>
                                <option value="TIN ID">TIN ID</option>
                            </optgroup>
                            <optgroup label="Secondary / Other Accepted IDs">
                                <option value="Government Office ID">Government Office/GOCC ID</option>
                                <option value="DSWD Certification">DSWD Certification</option>
                                <option value="Barangay Certification">Barangay Certification</option>
                                <option value="NBI Clearance">NBI Clearance</option>
                                <option value="Company ID">Company ID</option>
                                <option value="Student ID">Student ID (Current)</option>
                                <option value="Others">Others</option>
                            </optgroup>
                        </select>
                        <input type="text" name="other_id_type" id="other_id_type" placeholder="Please specify" style="display:none; margin-top: 5px;">
                    </div>
                </div>
                <?php
                    // prepare include defaults for Add page (required files)
                    $front_src = '';
                    $show_front_preview = 'display:none;';
                    $front_placeholder_opacity = '';
                    $back_src = '';
                    $show_back_preview = 'display:none;';
                    $back_placeholder_opacity = '';
                    $front_required = true;
                    $back_required = true;
                    include __DIR__ . '/_id_upload.php';
                ?>
            </div>

            <div style="display:flex;gap:10px;align-items:center;">
                <button type="submit" class="btn btn-primary">Add Customer</button>
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

        document.addEventListener('DOMContentLoaded', () => {
            loadProvinces('present');
            loadProvinces('permanent');

            const contactInput = document.getElementById('contact_number');
            if(!contactInput.value.startsWith('+63 ')) contactInput.value = '+63 ';
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
        });

        // Restrict zip inputs to digits only (max 4 chars)
        function restrictZipInput(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0,4);
            });
            el.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) return;
                const allowed = ['Backspace','Tab','ArrowLeft','ArrowRight','Delete','Home','End'];
                if (allowed.includes(e.key)) return;
                if (!/^[0-9]$/.test(e.key)) e.preventDefault();
            });
        }
        restrictZipInput('present_zip');
        restrictZipInput('permanent_zip');

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
            if (select.value === 'Others') {
                otherInput.style.display = 'block';
                otherInput.required = true;
            } else {
                otherInput.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }
        }
        
        async function loadProvinces(type) {
            const select = document.getElementById(type + '_province');
            select.innerHTML = '<option value="">Loading...</option>';
            try {
                const response = await fetch(`${API_BASE}/provinces/`);
                const data = await response.json();
                data.sort((a, b) => a.name.localeCompare(b.name));
                let options = '<option value="">Select Province</option>';
                data.forEach(p => { options += `<option value="${p.code}">${p.name}</option>`; });
                select.innerHTML = options;
            } catch (e) {
                select.innerHTML = '<option value="">Error loading provinces</option>';
            }
        }

        async function fetchCities(type) {
            const provinceCode = document.getElementById(type + '_province').value;
            const citySelect = document.getElementById(type + '_city');
            const brgySelect = document.getElementById(type + '_barangay');
            
            document.getElementById(type + '_province_text').value = document.getElementById(type + '_province').options[document.getElementById(type + '_province').selectedIndex].text;

            if(!provinceCode) {
                 citySelect.innerHTML = '<option value="">Select Province First</option>';
                 brgySelect.innerHTML = '<option value="">Select City First</option>';
                 return;
            }
            
            citySelect.innerHTML = '<option value="">Loading...</option>';
            brgySelect.innerHTML = '<option value="">Select City First</option>';
            
            try {
                const response = await fetch(`${API_BASE}/provinces/${provinceCode}/cities-municipalities/`);
                const data = await response.json();
                data.sort((a, b) => a.name.localeCompare(b.name));
                let options = '<option value="">Select City/Municipality</option>';
                data.forEach(c => { options += `<option value="${c.code}">${c.name}</option>`; });
                citySelect.innerHTML = options;
            } catch (e) {
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            }
        }

        async function fetchBarangays(type) {
            const cityCode = document.getElementById(type + '_city').value;
            const brgySelect = document.getElementById(type + '_barangay');
            
            document.getElementById(type + '_city_text').value = document.getElementById(type + '_city').options[document.getElementById(type + '_city').selectedIndex].text;

            if(!cityCode) {
                 brgySelect.innerHTML = '<option value="">Select City First</option>';
                 return;
            }
            
            brgySelect.innerHTML = '<option value="">Loading...</option>';
            try {
                const response = await fetch(`${API_BASE}/cities-municipalities/${cityCode}/barangays/`);
                const data = await response.json();
                data.sort((a, b) => a.name.localeCompare(b.name));
                let options = '<option value="">Select Barangay</option>';
                data.forEach(b => { options += `<option value="${b.code}">${b.name}</option>`; });
                brgySelect.innerHTML = options;
                updateZip(type);
            } catch (e) {
                brgySelect.innerHTML = '<option value="">Error loading barangays</option>';
            }
        }

        function updateZip(type) {
             const citySelect = document.getElementById(type + '_city');
             const cityName = citySelect.options[citySelect.selectedIndex].text;
             const zipInput = document.getElementById(type + '_zip');
             zipInput.value = getZipCode(cityName);

             const brgySelect = document.getElementById(type + '_barangay');
             if(brgySelect.selectedIndex > 0) {
                document.getElementById(type + '_barangay_text').value = brgySelect.options[brgySelect.selectedIndex].text;
             }
        }

        function copyAddress() {
            if (document.getElementById('same_address').checked) {
                ['house_num', 'street', 'subdivision', 'zip'].forEach(field => {
                    document.getElementById(`permanent_${field}`).value = document.getElementById(`present_${field}`).value;
                });
                
                const presProv = document.getElementById('present_province');
                const perProv = document.getElementById('permanent_province');
                perProv.innerHTML = presProv.innerHTML;
                perProv.value = presProv.value;
                document.getElementById('permanent_province_text').value = document.getElementById('present_province_text').value;

                const presCity = document.getElementById('present_city');
                const perCity = document.getElementById('permanent_city');
                perCity.innerHTML = presCity.innerHTML;
                perCity.value = presCity.value;
                document.getElementById('permanent_city_text').value = document.getElementById('present_city_text').value;
                
                const presBrgy = document.getElementById('present_barangay');
                const perBrgy = document.getElementById('permanent_barangay');
                perBrgy.innerHTML = presBrgy.innerHTML;
                perBrgy.value = presBrgy.value;
                document.getElementById('permanent_barangay_text').value = document.getElementById('present_barangay_text').value;
            } else {
                 document.getElementById('permanent_house_num').value = '';
                 document.getElementById('permanent_street').value = '';
                 document.getElementById('permanent_subdivision').value = '';
                 document.getElementById('permanent_zip').value = '';
                 loadProvinces('permanent');
                 document.getElementById('permanent_city').innerHTML = '<option value="">Select Province First</option>';
                 document.getElementById('permanent_barangay').innerHTML = '<option value="">Select City First</option>';
            }
        }
    </script>
</body>
</html>