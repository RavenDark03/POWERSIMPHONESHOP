<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$sql = "SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM customers";
$customers = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Pawn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Form normalization for consistent professional appearance */
        .admin-form { background: #fff; padding: 22px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .admin-form h2 { margin-top: 0; margin-bottom: 14px; }
        .form-section { margin-bottom: 22px; padding-bottom: 6px; border-bottom: 1px solid #f0f0f0; }
        .form-section h3 { color: #116530; font-size: 1.05rem; margin-bottom: 12px; }
        .form-row { display:flex; gap:18px; flex-wrap:wrap; align-items:flex-start; }
        .form-col, .form-group { flex:1; min-width:220px; }
        .form-group.full, .form-group[style*="width: 100%"] { flex-basis:100%; width:100%; }
        label { display:block; margin-bottom:8px; font-weight:600; font-size:0.85rem; color:#333; text-transform:uppercase; letter-spacing:0.4px; }
        input[type="text"], input[type="number"], input[type="date"], select, textarea { width:100%; padding:10px 12px; border:1px solid #e6e6e6; border-radius:8px; font-size:1rem; box-sizing:border-box; font-family:inherit; }
        textarea { min-height:72px; }
        .checkbox-container label { font-weight:400; text-transform:none; }
        .form-row .form-col .checkbox-container { margin-top:6px; }
        /* Make loan amount visually prominent but sized consistently */
        #loan_amount { padding:12px; }
        /* Responsive tweaks */
        @media (max-width:700px) {
            .form-row { flex-direction:column; }
            .form-col, .form-group { min-width:100%; }
        }
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
                    <li><a href="pawning.php" style="color: #d4af37;">Pawning</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <div style="max-width: 100%; margin: 0 auto;">
            <!-- Removed separate header div, keeping it inside form for unified look like Add Customer if desired, or keep outside. Add Customer has H2 inside. -->
            
            <form action="pawn_process.php" method="post" id="pawnForm" class="admin-form">
                <h2>New Pawn Transaction</h2>
                <input type="hidden" name="action" value="new_pawn">
                
                <div class="form-section">
                    <h3>Customer Information</h3>
                    <div class="form-row">
                        <div class="form-group" style="width: 100%;">
                            <label for="customer_id">Select Customer</label>
                            <select name="customer_id" id="customer_id" required>
                                <option value="">-- Choose a Customer --</option>
                                <?php
                                if ($customers->num_rows > 0) {
                                    while($row = $customers->fetch_assoc()) {
                                        echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Item Details</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label for="category">Category</label>
                            <select name="category" id="category" required onchange="handleCategoryChange()">
                                <option value="">Select Category</option>
                                <option value="Gadget">Gadget</option>
                                <option value="Silver Jewelry">Silver Jewelry</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label for="item_type">Item Type</label>
                            <select name="item_type" id="item_type" required onchange="handleItemTypeChange()">
                                <option value="">Select Category First</option>
                            </select>
                            <input type="text" name="other_item_type" id="other_item_type" placeholder="Specify Item Type" style="display:none; margin-top: 5px;">
                        </div>
                    </div>

                    <!-- Gadget Specifics -->
                    <div id="gadget-fields" style="display:none; margin-bottom: 20px;">
                        <h4 style="margin: 15px 0 10px; color: #666; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Specific Gadget Details</h4>
                        <div class="form-row">
                            <div class="form-col">
                                <label>Brand</label>
                                <select name="brand" id="brand" onchange="handleBrandChange()">
                                    <option value="">Select Brand</option>
                                </select>
                                <input type="text" name="other_brand" id="other_brand" placeholder="Specify Brand" style="display:none; margin-top: 5px;">
                            </div>
                            <div class="form-col">
                                <label>Model</label>
                                <select name="model" id="model" onchange="checkOtherModel()">
                                    <option value="">Select Brand First</option>
                                </select>
                                <input type="text" name="other_model" id="other_model" placeholder="Specify Model" style="display:none; margin-top: 5px;">
                            </div>
                            <div class="form-col">
                                <label>Serial Number</label>
                                <input type="text" name="serial_number">
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 15px;">
                            <label style="display: block; margin-bottom: 8px;">Accessories</label>
                            <div class="checkbox-container" style="flex-wrap: wrap; gap: 15px;">
                                <label style="text-transform: none; display: inline-flex; align-items: center; gap: 5px;"><input type="checkbox" name="accessories[]" value="Charger"> Charger</label>
                                <label style="text-transform: none; display: inline-flex; align-items: center; gap: 5px;"><input type="checkbox" name="accessories[]" value="Original Box"> Original Box</label>
                                <label style="text-transform: none; display: inline-flex; align-items: center; gap: 5px;"><input type="checkbox" name="accessories[]" value="Earphones"> Earphones</label>
                                <label style="text-transform: none; display: inline-flex; align-items: center; gap: 5px;"><input type="checkbox" name="accessories[]" value="Receipt/Warranty"> Receipt</label>
                                <label style="text-transform: none; display: inline-flex; align-items: center; gap: 5px;"><input type="checkbox" name="accessories[]" value="Case"> Case</label>
                                <label style="text-transform: none; display: inline-flex; align-items: center; gap: 5px;"><input type="checkbox" name="accessories[]" value="Memory Card"> Memory Card</label>
                            </div>
                        </div>
                    </div>

                    <!-- Jewelry Specifics -->
                    <div id="jewelry-fields" style="display:none; margin-bottom: 20px;">
                        <h4 style="margin: 15px 0 10px; color: #666; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Specific Jewelry Details</h4>
                        <div class="form-row">
                            <div class="form-col">
                                <label>Weight (g)</label>
                                <input type="number" name="weight_grams" step="0.01">
                            </div>
                            <div class="form-col">
                                <label>Purity</label>
                                <select name="purity" id="purity" onchange="checkOtherPurity()">
                                    <option value="">Select</option>
                                    <option value="925">925 (Sterling Silver)</option>
                                    <option value="950">950</option>
                                    <option value="999">999 (Fine Silver)</option>
                                    <option value="Others">Others</option>
                                </select>
                                <input type="text" name="other_purity" id="other_purity" placeholder="Specify Purity" style="display:none; margin-top: 5px;">
                        </div>
                    </div>
                </div>

                    <div class="form-row">
                        <div class="form-group" style="width: 100%;">
                            <label>Condition</label>
                            <select name="item_condition" required>
                                <option value="New">New / Sealed</option>
                                <option value="Like New">Like New (Open Box)</option>
                                <option value="Excellent">Excellent (No Scratches)</option>
                                <option value="Good">Good (Minor Wear)</option>
                                <option value="Fair">Fair (Visible Wear)</option>
                                <option value="Damaged">Damaged / Needs Repair</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="width: 100%;">
                            <label for="item_description">Description / Notes</label>
                            <textarea name="item_description" id="item_description" rows="3" placeholder="Optional notes about condition or features..." style="width: 100%; padding: 10px; border: 1px solid #e1e1e1; border-radius: 8px; font-family: inherit; font-size: 1rem;"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Loan Terms</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label for="loan_amount">Loan Amount (₱)</label>
                            <input type="number" name="loan_amount" id="loan_amount" step="0.01" required style="font-weight: bold; color: #2e7d32; font-size: 1.1rem;">
                        </div>
                        <div class="form-col">
                            <label for="interest_rate">Interest Rate (%)</label>
                            <input type="number" name="interest_rate" id="interest_rate" step="0.01" min="5" value="5" required>
                        </div>
                        <div class="form-col">
                            <label for="due_date">Due Date</label>
                            <input type="date" name="due_date" id="due_date" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Create Pawn Transaction</button>
            </form>
        </div>
    </div>

    <script>
        // Data Structure for Cascading Dropdowns
        const categoryData = {
            "Gadget": {
                "Smartphone": {
                    "Apple": ["iPhone 15 Pro Max", "iPhone 15 Pro", "iPhone 15", "iPhone 14 Pro Max", "iPhone 14 Pro", "iPhone 14", "iPhone 13 Pro Max", "iPhone 13", "iPhone 12", "iPhone 11", "Others"],
                    "Samsung": ["Galaxy S24 Ultra", "Galaxy S24+", "Galaxy S24", "Galaxy S23 Ultra", "Galaxy S23", "Galaxy Z Fold5", "Galaxy Z Flip5", "Galaxy A55", "Galaxy A35", "Others"],
                    "Xiaomi": ["14 Ultra", "14", "Redmi Note 13 Pro+", "Redmi Note 13", "Poco X6 Pro", "Others"],
                    "Oppo": ["Find N3", "Find X7 Ultra", "Reno 11 Pro", "Reno 11", "Others"],
                    "Vivo": ["X100 Pro", "X100", "V30 Pro", "V30", "Others"],
                    "Realme": ["12 Pro+", "12 Pro", "11", "C67", "Others"],
                    "Huawei": ["Pura 70 Ultra", "Pura 70", "Mate 60 Pro", "Others"],
                    "Google": ["Pixel 8 Pro", "Pixel 8", "Pixel 7a", "Others"],
                    "Honor": ["Magic6 Pro", "Magic V2", "X9b", "Others"],
                    "Asus": ["ROG Phone 8 Pro", "ROG Phone 8", "Zenfone 11 Ultra", "Others"],
                    "Others": ["Others"]
                },
                "Laptop": {
                    "Apple": ["MacBook Pro 16 M3", "MacBook Pro 14 M3", "MacBook Air 15 M2", "MacBook Air 13 M2", "MacBook Air M1", "Others"],
                    "Asus": ["ROG Zephyrus", "TUF Gaming", "Zenbook", "Vivobook", "Others"],
                    "HP": ["Spectre x360", "Envy", "Pavilion", "Omen", "Victus", "Others"],
                    "Dell": ["XPS 15", "XPS 13", "Inspiron", "G15 Gaming", "Alienware", "Others"],
                    "Lenovo": ["ThinkPad X1", "Legion Pro", "Legion Slim", "IdeaPad", "Yoga", "Others"],
                    "Acer": ["Predator Helios", "Nitro 5", "Swift Go", "Aspire 5", "Others"],
                    "MSI": ["Raider GE", "Stealth", "Katana", "Cyborg", "Modern", "Others"],
                    "Others": ["Others"]
                },
                "Tablet": {
                    "Apple": ["iPad Pro 12.9 M2", "iPad Pro 11 M2", "iPad Air 5", "iPad 10th Gen", "iPad mini 6", "Others"],
                    "Samsung": ["Galaxy Tab S9 Ultra", "Galaxy Tab S9+", "Galaxy Tab S9", "Galaxy Tab S9 FE", "Galaxy Tab A9", "Others"],
                    "Xiaomi": ["Pad 6", "Pad 6 Pro", "Redmi Pad SE", "Others"],
                    "Others": ["Others"]
                },
                "Console": {
                    "Sony": ["PlayStation 5 Slim", "PlayStation 5", "PlayStation 4 Pro", "PlayStation 4 Slim", "Others"],
                    "Nintendo": ["Switch OLED", "Switch V2", "Switch Lite", "Others"],
                    "Microsoft": ["Xbox Series X", "Xbox Series S", "Others"],
                    "Valve": ["Steam Deck OLED", "Steam Deck LCD", "Others"],
                    "Asus": ["ROG Ally", "Others"],
                    "Lenovo": ["Legion Go", "Others"],
                    "Others": ["Others"]
                },
                "Smartwatch": {
                    "Apple": ["Watch Ultra 2", "Watch Series 9", "Watch SE 2", "Others"],
                    "Samsung": ["Galaxy Watch6 Classic", "Galaxy Watch6", "Galaxy Watch5 Pro", "Others"],
                    "Garmin": ["Fenix 7", "Epix Gen 2", "Forerunner 965", "Venu 3", "Others"],
                    "Huawei": ["Watch GT 4", "Watch 4 Pro", "Others"],
                    "Others": ["Others"]
                },
                "Camera": {
                    "Sony": ["A7 IV", "A7R V", "ZV-E10", "A6700", "Others"],
                    "Canon": ["EOS R5", "EOS R6 Mark II", "EOS R50", "Others"],
                    "Fujifilm": ["X-T5", "X100VI", "X-S20", "Others"],
                    "Nikon": ["Z8", "Z6 III", "Zf", "Others"],
                    "GoPro": ["Hero 12 Black", "Hero 11 Black", "Others"],
                    "DJI": ["Osmo Pocket 3", "Osmo Action 4", "Others"],
                    "Others": ["Others"]
                },
                "Others": {
                    "Others": ["Others"]
                }
            },
            "Silver Jewelry": {
                "Ring": null,
                "Necklace": null,
                "Bracelet": null,
                "Earrings": null,
                "Pendant": null,
                "Anklet": null,
                "Set": null,
                "Others": null
            },
            "Others": {
                "Others": null
            }
        };

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('due_date').setAttribute('min', today);

        // Auto-calculate Due Date (3 months from now)
        function setAutoDueDate() {
            let d = new Date();
            d.setMonth(d.getMonth() + 3);
            document.getElementById('due_date').value = d.toISOString().split('T')[0];
        }
        setAutoDueDate(); // Run on load

        function handleCategoryChange() {
            const cat = document.getElementById('category').value;
            const typeSelect = document.getElementById('item_type');
            
            // Toggle Section Visibility
            document.getElementById('gadget-fields').style.display = (cat === 'Gadget') ? 'block' : 'none';
            document.getElementById('jewelry-fields').style.display = (cat === 'Silver Jewelry') ? 'block' : 'none';

            // Populate Item Type Dropdown
            typeSelect.innerHTML = '<option value="">Select Type</option>';
            
            if (categoryData[cat]) {
                Object.keys(categoryData[cat]).forEach(type => {
                    typeSelect.innerHTML += `<option value="${type}">${type}</option>`;
                });
            } else {
                 typeSelect.innerHTML = '<option value="Others">Others</option>';
            }
            
            handleItemTypeChange(); // Reset downstream fields
        }

        function handleItemTypeChange() {
            const cat = document.getElementById('category').value;
            const type = document.getElementById('item_type').value;
            const brandSelect = document.getElementById('brand');
            
            if (cat === 'Gadget') {
                brandSelect.innerHTML = '<option value="">Select Brand</option>';
                if (categoryData[cat] && categoryData[cat][type]) {
                    Object.keys(categoryData[cat][type]).forEach(brand => {
                        brandSelect.innerHTML += `<option value="${brand}">${brand}</option>`;
                    });
                } else {
                     brandSelect.innerHTML = '<option value="Others">Others</option>';
                }
            }
            
            // Toggle Other Item Type Input
            const otherTypeInput = document.getElementById('other_item_type');
            if (type === 'Others') {
                otherTypeInput.style.display = 'block';
                otherTypeInput.required = true;
            } else {
                otherTypeInput.style.display = 'none';
                otherTypeInput.required = false;
            }

            handleBrandChange(); // Reset downstream fields
        }

        function checkOtherPurity() {
            const purity = document.getElementById('purity').value;
            const otherPurityInput = document.getElementById('other_purity');
            
            if (purity === 'Others') {
                otherPurityInput.style.display = 'block';
                otherPurityInput.required = true;
            } else {
                otherPurityInput.style.display = 'none';
                otherPurityInput.required = false;
            }
        }

        function handleBrandChange() {
            const cat = document.getElementById('category').value;
            const type = document.getElementById('item_type').value;
            const brand = document.getElementById('brand').value;
            const modelSelect = document.getElementById('model');
            const otherBrandInput = document.getElementById('other_brand');

            // Show "Other Brand" Input if needed
            if (brand === 'Others') {
                otherBrandInput.style.display = 'block';
                otherBrandInput.required = true;
                modelSelect.innerHTML = '<option value="Others">Others</option>'; // Force model to Others
                checkOtherModel();
                return;
            } else {
                otherBrandInput.style.display = 'none';
                otherBrandInput.required = false;
            }

            modelSelect.innerHTML = '<option value="">Select Model</option>';
            if (cat === 'Gadget' && categoryData[cat][type][brand]) {
                const models = categoryData[cat][type][brand];
                models.forEach(model => {
                    modelSelect.innerHTML += `<option value="${model}">${model}</option>`;
                });
            }
            checkOtherModel();
        }

        function checkOtherModel() {
            const model = document.getElementById('model').value;
            const otherModelInput = document.getElementById('other_model');
            
            if (model === 'Others') {
                otherModelInput.style.display = 'block';
                otherModelInput.required = true;
            } else {
                otherModelInput.style.display = 'none';
                otherModelInput.required = false;
            }
        }
    </script>

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
</body>
</html>