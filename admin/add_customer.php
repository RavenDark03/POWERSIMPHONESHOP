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
        /* .form-section h3 removed to use style.css .admin-form h3 */
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-col { flex: 1; }
        /* Input styles removed to use style.css */
        .checkbox-container { display: flex; align-items: center; gap: 5px; margin: 10px 0; }
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
                    <li><a href="customers.php" style="color: #d4af37;">Customers</a></li>
                    <li><a href="pawning.php">Pawning</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Header removed from here to be inside form or styled differently if needed, keeping h2 -->
        <form action="customer_process.php" method="post" enctype="multipart/form-data" class="admin-form">
            <h2>Add New Customer</h2>
            <input type="hidden" name="action" value="add">
            
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-row">
                    <div class="form-col">
                        <label>First Name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-col">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name">
                    </div>
                    <div class="form-col">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" required placeholder="09xxxxxxxxx">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Present Address</h3>
                <div class="form-row">
                    <div class="form-col">
                        <label>Unit/House No.</label>
                        <input type="text" name="present_house_num" id="present_house_num" required>
                    </div>
                    <div class="form-col">
                        <label>Street</label>
                        <input type="text" name="present_street" id="present_street" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="present_subdivision">Subdivision/Village/Purok</label>
                        <input type="text" name="present_subdivision" id="present_subdivision" required>
                    </div>
                </div>
                <!-- Dynamic Address Fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="present_province">Province</label>
                         <select name="present_province" id="present_province" required onchange="fetchCities('present')">
                            <option value="">Select Province</option>
                        </select>
                        <input type="hidden" name="present_province_text" id="present_province_text">
                    </div>
                    <div class="form-group">
                        <label for="present_city">City/Municipality</label>
                        <select name="present_city" id="present_city" required onchange="fetchBarangays('present')">
                            <option value="">Select Province First</option>
                        </select>
                        <input type="hidden" name="present_city_text" id="present_city_text">
                    </div> 
                    <div class="form-group">
                        <label for="present_barangay">Barangay</label>
                        <select name="present_barangay" id="present_barangay" required onchange="updateZip('present')">
                            <option value="">Select Barangay</option>
                        </select>
                        <input type="hidden" name="present_barangay_text" id="present_barangay_text">
                    </div>
                </div>
                <div class="form-row">
                     <div class="form-group">
                        <label for="present_zip">Zip Code</label>
                        <input type="text" name="present_zip" id="present_zip" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Permanent Address</h3>
                <div class="checkbox-container">
                    <input type="checkbox" id="same_address" onchange="copyAddress()">
                    <label for="same_address">Same as Present Address</label>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label>Unit/House No.</label>
                        <input type="text" name="permanent_house_num" id="permanent_house_num" required>
                    </div>
                    <div class="form-col">
                        <label>Street</label>
                        <input type="text" name="permanent_street" id="permanent_street" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="permanent_subdivision">Subdivision/Village/Purok</label>
                        <input type="text" name="permanent_subdivision" id="permanent_subdivision" required>
                    </div>
                </div>
                 <div class="form-row">
                     <div class="form-group">
                        <label for="permanent_province">Province</label>
                        <select name="permanent_province" id="permanent_province" required onchange="fetchCities('permanent')">
                            <option value="">Select Province</option>
                        </select>
                         <input type="hidden" name="permanent_province_text" id="permanent_province_text">
                    </div>
                    <div class="form-group">
                        <label for="permanent_city">City/Municipality</label>
                        <select name="permanent_city" id="permanent_city" required onchange="fetchBarangays('permanent')">
                             <option value="">Select Province First</option>
                        </select>
                         <input type="hidden" name="permanent_city_text" id="permanent_city_text">
                    </div> 
                    <div class="form-group">
                        <label for="permanent_barangay">Barangay</label>
                        <select name="permanent_barangay" id="permanent_barangay" required onchange="updateZip('permanent')">
                            <option value="">Select Barangay</option>
                        </select>
                        <input type="hidden" name="permanent_barangay_text" id="permanent_barangay_text">
                    </div>
                </div>
                <div class="form-row">
                     <div class="form-group">
                        <label for="permanent_zip">Zip Code</label>
                        <input type="text" name="permanent_zip" id="permanent_zip" required>
                    </div>
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
                                <option value="GSIS e-Card">GSIS e-Card</option>
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
                <div class="form-row">
                <div class="form-row">
                    <!-- Professional ID Upload Section -->
                    <div class="id-upload-wrapper" style="width: 100%;">
                        
                        <!-- Front ID -->
                        <div class="id-upload-col">
                            <label class="id-label-header"><i class="fas fa-id-card"></i> Front ID</label>
                            <label class="id-upload-box" for="id_image_front">
                                <div class="upload-placeholder" id="placeholder_front">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to Upload</span>
                                </div>
                                <img id="preview_front" class="id-preview-image">
                                <input type="file" name="id_image_front" id="id_image_front" accept="image/*" onchange="previewImage(this, 'preview_front', 'placeholder_front')" required>
                            </label>
                        </div>

                        <!-- Back ID -->
                        <div class="id-upload-col">
                            <label class="id-label-header"><i class="fas fa-id-card-alt"></i> Back ID</label>
                            <label class="id-upload-box" for="id_image_back">
                                <div class="upload-placeholder" id="placeholder_back">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to Upload</span>
                                </div>
                                <img id="preview_back" class="id-preview-image">
                                <input type="file" name="id_image_back" id="id_image_back" accept="image/*" onchange="previewImage(this, 'preview_back', 'placeholder_back')" required>
                            </label>
                        </div>

                    </div>
                </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Add Customer</button>
        </form>
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

        document.addEventListener('DOMContentLoaded', () => {
            loadProvinces('present');
            loadProvinces('permanent');
            
            setupSync('present');
            setupSync('permanent');

            // Contact Number Protection
            const contactInput = document.getElementById('contact_number');
            
            // Initial enforcement
            if(!contactInput.value.startsWith('+63 ')) contactInput.value = '+63 ';

            contactInput.addEventListener('input', function(e) {
                let value = this.value;
                
                // 1. Ensure prefix exists
                if (!value.startsWith('+63 ')) {
                    value = '+63 ' + value.replace(/\+63\s?|^63/, ''); // Remove attempts to type prefix
                }

                // 2. Keep only valid parts: "+63 " prefix + digits
                let prefix = '+63 ';
                let remainder = value.substring(prefix.length).replace(/\D/g, ''); // Strip non-digits from remainder
                
                this.value = prefix + remainder;
            });

            contactInput.addEventListener('keydown', function(e) {
                // Prevent backspace if cursor is in the prefix (indices 0-3)
                // Prefix is "+63 " (4 chars). Index 3 is space.
                if ((e.key === 'Backspace' || e.key === 'Delete') && this.selectionStart <= 4) {
                    e.preventDefault();
                }
                // Prevent moving cursor into prefix
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
        // triggerUpload and updateFileName no longer needed with label implementation

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
        
        function setupSync(type) {
            document.getElementById(type + '_province').addEventListener('change', function() {
                let text = this.options[this.selectedIndex].text;
                document.getElementById(type + '_province_text').value = text;
            });
            document.getElementById(type + '_city').addEventListener('change', function() {
                let text = this.options[this.selectedIndex].text;
                document.getElementById(type + '_city_text').value = text;
                
                // Auto Auto-fill Zip
                let zip = getZipCode(text);
                document.getElementById(type + '_zip').value = zip;
            });
            document.getElementById(type + '_barangay').addEventListener('change', function() {
                let text = this.options[this.selectedIndex].text;
                document.getElementById(type + '_barangay_text').value = text;
            });
        }

        async function loadProvinces(type) {
            const select = document.getElementById(type + '_province');
            select.innerHTML = '<option value="">Loading...</option>';
            try {
                const response = await fetch(`${API_BASE}/provinces/`);
                const data = await response.json();
                data.sort((a, b) => a.name.localeCompare(b.name));
                
                let options = '<option value="">Select Province</option>';
                data.forEach(p => {
                    options += `<option value="${p.code}">${p.name}</option>`;
                });
                select.innerHTML = options;
            } catch (e) {
                console.error('Error loading provinces', e);
                select.innerHTML = '<option value="">Error loading</option>';
            }
        }

        async function fetchCities(type) {
            const provinceCode = document.getElementById(type + '_province').value;
            const select = document.getElementById(type + '_city');
            const brgySelect = document.getElementById(type + '_barangay');
            
            if(!provinceCode) return;
            
            select.innerHTML = '<option value="">Loading...</option>';
            brgySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            try {
                const response = await fetch(`${API_BASE}/provinces/${provinceCode}/cities-municipalities/`);
                const data = await response.json();
                data.sort((a, b) => a.name.localeCompare(b.name));
                
                let options = '<option value="">Select City</option>';
                data.forEach(c => {
                    options += `<option value="${c.code}">${c.name}</option>`;
                });
                select.innerHTML = options;
            } catch (e) {
                console.error('Error loading cities', e);
            }
        }

        async function fetchBarangays(type) {
            const cityCode = document.getElementById(type + '_city').value;
            const select = document.getElementById(type + '_barangay');
            
            if(!cityCode) return;
            
            select.innerHTML = '<option value="">Loading...</option>';
            try {
                const response = await fetch(`${API_BASE}/cities-municipalities/${cityCode}/barangays/`);
                const data = await response.json();
                data.sort((a, b) => a.name.localeCompare(b.name));
                
                let options = '<option value="">Select Barangay</option>';
                data.forEach(b => {
                    options += `<option value="${b.code}">${b.name}</option>`;
                });
                select.innerHTML = options;
            } catch (e) {
                console.error('Error loading barangays', e);
            }
        }

        function copyAddress() {
            if (document.getElementById('same_address').checked) {
                // Copy Text Fields
                ['house_num', 'street', 'subdivision', 'zip'].forEach(field => {
                    document.getElementById(`permanent_${field}`).value = 
                        document.getElementById(`present_${field}`).value;
                });

                // Copy Selects (Logic needs to be tricky because of Async loading)
                // For now, let's copy the VALUES (codes) and trigger the change events manually or 
                // re-fetch. A simpler way for this proof of concept is manual copy or advanced JS.
                // WE will try to copy the indices/values and trigger updates.
                
                const presProv = document.getElementById('present_province');
                const perProv = document.getElementById('permanent_province');
                
                perProv.innerHTML = presProv.innerHTML;
                perProv.value = presProv.value;
                document.getElementById('permanent_province_text').value = document.getElementById('present_province_text').value;

                // We need to fetch cities for permanent based on copied province
                // But since we just copied HTML, we can copy the City innerHTML too if it's loaded?
                // No, fetching is cleaner.
                
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
                 // ... clear others
            }
        }


    </script>
</body>
</html>