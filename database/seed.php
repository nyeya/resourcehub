<?php
/**
 * ResourceHub Database Seeder
 * Populates sample data required for demonstration and acceptance tests.
 * All dates are dynamically relative to runtime so sample data never expires.
 */

require_once __DIR__ . '/../includes/init.php';

try {
    $pdo = Database::getConnection();

    echo "Seeding database..." . PHP_EOL;

    // Start transaction
    $pdo->beginTransaction();

    // 1. Clear existing records in proper order
    $pdo->exec("TRUNCATE maintenance, loan_history, loans, equipment, locations, categories, users RESTART IDENTITY CASCADE");

    // 2. Seed Users (Password: Password123!)
    $defaultHash = password_hash('Password123!', PASSWORD_BCRYPT);

    $users = [
        ['name' => 'Alice Admin',        'email' => 'admin@resourcehub.local',       'role' => 'administrator', 'is_active' => true],
        ['name' => 'Charlie Coordinator','email' => 'coordinator@resourcehub.local', 'role' => 'coordinator',   'is_active' => true],
        ['name' => 'Sarah Staff',        'email' => 'staff1@resourcehub.local',      'role' => 'staff',         'is_active' => true],
        ['name' => 'Sam Fieldwork',      'email' => 'staff2@resourcehub.local',      'role' => 'staff',         'is_active' => true],
        ['name' => 'Sophia Research',    'email' => 'staff3@resourcehub.local',      'role' => 'staff',         'is_active' => true],
        ['name' => 'Ian Inactive',       'email' => 'inactive@resourcehub.local',    'role' => 'staff',         'is_active' => false],
    ];

    $userStmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, role, is_active)
        VALUES (:name, :email, :password_hash, :role, :is_active)
    ");

    foreach ($users as $u) {
        $userStmt->execute([
            ':name' => $u['name'],
            ':email' => $u['email'],
            ':password_hash' => $defaultHash,
            ':role' => $u['role'],
            ':is_active' => $u['is_active'] ? 'true' : 'false',
        ]);
    }
    echo " -> 6 users seeded (password for all: Password123!)" . PHP_EOL;

    // 3. Seed Categories (4 categories)
    $categories = [
        ['name' => 'Laptops & Computers',   'description' => 'Laptops, mobile workstations and tablets for portable computing'],
        ['name' => 'Audio/Visual & Media',   'description' => 'Cameras, audio recorders, microphones, tripods and projection equipment'],
        ['name' => 'Laboratory Instruments', 'description' => 'Specialised meters, sensors, scales, oscilloscopes and testing tools'],
        ['name' => 'Fieldwork & Surveying',  'description' => 'GPS units, outdoor data loggers, drills and environmental survey gear'],
    ];

    $catStmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
    foreach ($categories as $c) {
        $catStmt->execute($c);
    }
    echo " -> 4 categories seeded" . PHP_EOL;

    // 4. Seed Locations (3 locations)
    $locations = [
        ['name' => 'Central Equipment Store', 'building' => 'Admin Block A',      'room' => 'Room 102'],
        ['name' => 'Science & Tech Lab Hub',   'building' => 'Engineering Wing C', 'room' => 'Lab 205'],
        ['name' => 'Field Station Depot',     'building' => 'South Station',      'room' => 'Depot Bay 4'],
    ];

    $locStmt = $pdo->prepare("INSERT INTO locations (name, building, room) VALUES (:name, :building, :room)");
    foreach ($locations as $l) {
        $locStmt->execute($l);
    }
    echo " -> 3 locations seeded" . PHP_EOL;

    // 5. Seed Equipment (20 assets)
    // Note: Asset 4 (EQ-1004) is damaged with open maintenance
    $equipment = [
        // Laptops (Cat 1)
        ['asset_tag' => 'EQ-1001', 'name' => 'Dell Latitude 5420 Laptop', 'cat' => 1, 'loc' => 1, 'model' => 'Latitude 5420', 'sn' => 'DL5420-9841', 'cond' => 'good', 'stat' => 'available', 'notes' => '16GB RAM, 512GB SSD, Windows 11 Pro'],
        ['asset_tag' => 'EQ-1002', 'name' => 'Lenovo ThinkPad T14 Gen 3', 'cat' => 1, 'loc' => 1, 'model' => 'ThinkPad T14',  'sn' => 'TP14-3329', 'cond' => 'new',  'stat' => 'available', 'notes' => 'AMD Ryzen 7, 32GB RAM'],
        ['asset_tag' => 'EQ-1003', 'name' => 'Apple MacBook Pro 14" M2',  'cat' => 1, 'loc' => 2, 'model' => 'MacBook Pro 14', 'sn' => 'MBP14-8842', 'cond' => 'good', 'stat' => 'available', 'notes' => 'Includes 67W USB-C power adapter'],
        
        // AV & Media (Cat 2)
        ['asset_tag' => 'EQ-1004', 'name' => 'Sony Alpha a7 IV Mirrorless Camera', 'cat' => 2, 'loc' => 1, 'model' => 'ILCE-7M4', 'sn' => 'SNY7-4401', 'cond' => 'damaged', 'stat' => 'maintenance', 'notes' => 'Front lens mount damaged during fieldwork return; currently in repair'],
        ['asset_tag' => 'EQ-1005', 'name' => 'Canon XA40 Professional Camcorder', 'cat' => 2, 'loc' => 1, 'model' => 'XA40 4K',   'sn' => 'CNXA-5521', 'cond' => 'good', 'stat' => 'available', 'notes' => 'Includes XLR audio handle and battery charger'],
        ['asset_tag' => 'EQ-1006', 'name' => 'Panasonic Lumix GH5 Camera Kit',     'cat' => 2, 'loc' => 1, 'model' => 'DC-GH5',    'sn' => 'PANG-1092', 'cond' => 'fair', 'stat' => 'available', 'notes' => 'Includes 12-60mm lens and 2 batteries'],
        ['asset_tag' => 'EQ-1007', 'name' => 'DJI Mic 2 Dual Wireless Audio Kit',  'cat' => 2, 'loc' => 2, 'model' => 'DJI-MIC2',  'sn' => 'DJMC-9081', 'cond' => 'good', 'stat' => 'available', 'notes' => 'Charging case, 2 transmitters, 1 receiver'],
        ['asset_tag' => 'EQ-1008', 'name' => 'Epson EB-2250U High-Lumen Projector', 'cat' => 2, 'loc' => 1, 'model' => 'EB-2250U', 'sn' => 'EPSN-7734', 'cond' => 'good', 'stat' => 'available', 'notes' => '5,000 lumens, HDMI and VGA inputs'],
        ['asset_tag' => 'EQ-1009', 'name' => 'Rode VideoMic Pro+ Shotgun Mic',     'cat' => 2, 'loc' => 2, 'model' => 'VMP+',      'sn' => 'RODE-6211', 'cond' => 'good', 'stat' => 'available', 'notes' => 'Camera shoe-mount condenser microphone'],
        
        // Laboratory Instruments (Cat 3)
        ['asset_tag' => 'EQ-1010', 'name' => 'Fluke 87V Industrial Multimeter',    'cat' => 3, 'loc' => 2, 'model' => '87V Max',   'sn' => 'FLK8-1123', 'cond' => 'good', 'stat' => 'available', 'notes' => 'True-RMS industrial multimeter with probe leads'],
        ['asset_tag' => 'EQ-1011', 'name' => 'Rigol DS1054Z Digital Oscilloscope',  'cat' => 3, 'loc' => 2, 'model' => 'DS1054Z',   'sn' => 'RIGL-4902', 'cond' => 'good', 'stat' => 'available', 'notes' => '50 MHz 4-channel digital oscilloscope with probes'],
        ['asset_tag' => 'EQ-1012', 'name' => 'Ohaus Pioneer Precision Balance Scale','cat' => 3, 'loc' => 2, 'model' => 'PX224',    'sn' => 'OHAU-3004', 'cond' => 'fair', 'stat' => 'available', 'notes' => 'Calibrated to 0.1 mg precision; handle with care'],
        ['asset_tag' => 'EQ-1013', 'name' => 'Hach HQ40D Multi-Parameter Water Meter','cat' => 3, 'loc' => 2, 'model' => 'HQ40D',   'sn' => 'HACH-8192', 'cond' => 'good', 'stat' => 'available', 'notes' => 'pH, conductivity and dissolved oxygen probes'],
        ['asset_tag' => 'EQ-1014', 'name' => 'FLIR E6-XT Infrared Thermal Camera',  'cat' => 3, 'loc' => 2, 'model' => 'E6-XT',     'sn' => 'FLIR-9912', 'cond' => 'new',  'stat' => 'available', 'notes' => '240x180 resolution thermal imaging camera'],
        
        // Fieldwork & Surveying (Cat 4)
        ['asset_tag' => 'EQ-1015', 'name' => 'Garmin GPSMAP 66sr Handheld GPS',    'cat' => 4, 'loc' => 3, 'model' => 'GPSMAP 66sr','sn' => 'GRMN-5012', 'cond' => 'good', 'stat' => 'available', 'notes' => 'Multi-band satellite frequency support with TOPO maps'],
        ['asset_tag' => 'EQ-1016', 'name' => 'Trimble Catalyst DA2 GNSS Receiver', 'cat' => 4, 'loc' => 3, 'model' => 'DA2',       'sn' => 'TRMB-2219', 'cond' => 'good', 'stat' => 'available', 'notes' => 'Centimeter-accuracy mobile GNSS antenna and pole'],
        ['asset_tag' => 'EQ-1017', 'name' => 'Decagon 5TM Soil Moisture Sensor Kit','cat' => 4, 'loc' => 3, 'model' => '5TM-Pro',   'sn' => 'DCGN-7120', 'cond' => 'good', 'stat' => 'available', 'notes' => 'Includes 5 probes and handheld data logger'],
        ['asset_tag' => 'EQ-1018', 'name' => 'Kestrel 5500 Weather Meter',          'cat' => 4, 'loc' => 3, 'model' => '5500 Link', 'sn' => 'KEST-3301', 'cond' => 'good', 'stat' => 'available', 'notes' => 'Wind speed, temperature, humidity and barometric tracking'],
        ['asset_tag' => 'EQ-1019', 'name' => 'DJI Mavic 3 Enterprise Survey Drone', 'cat' => 4, 'loc' => 3, 'model' => 'M3E',       'sn' => 'DJME-0029', 'cond' => 'good', 'stat' => 'available', 'notes' => 'Includes RTK module, 3 batteries and hard case'],
        ['asset_tag' => 'EQ-1020', 'name' => 'Bosch GRL 900-26 Rotary Laser Level', 'cat' => 4, 'loc' => 3, 'model' => 'GRL 900',  'sn' => 'BSCH-6610', 'cond' => 'fair', 'stat' => 'retired',   'notes' => 'Retired from active loaning due to out-of-spec leveling calibration'],
    ];

    $eqStmt = $pdo->prepare("
        INSERT INTO equipment (asset_tag, name, category_id, location_id, model, serial_number, condition, status, notes)
        VALUES (:asset_tag, :name, :category_id, :location_id, :model, :serial_number, :condition, :status, :notes)
    ");

    foreach ($equipment as $e) {
        $eqStmt->execute([
            ':asset_tag' => $e['asset_tag'],
            ':name' => $e['name'],
            ':category_id' => $e['cat'],
            ':location_id' => $e['loc'],
            ':model' => $e['model'],
            ':serial_number' => $e['sn'],
            ':condition' => $e['cond'],
            ':status' => $e['stat'],
            ':notes' => $e['notes'],
        ]);
    }
    echo " -> 20 equipment assets seeded" . PHP_EOL;

    // 6. Seed Open Maintenance Record for Damaged Asset EQ-1004 (ID 4)
    $maintStmt = $pdo->prepare("
        INSERT INTO maintenance (asset_id, opened_by, opened_at, issue_description, status)
        VALUES (:asset_id, :opened_by, :opened_at, :issue_description, 'open')
    ");
    $maintStmt->execute([
        ':asset_id' => 4, // EQ-1004
        ':opened_by' => 2, // Coordinator Charlie
        ':opened_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        ':issue_description' => 'Camera lens mount flange bent upon return. Awaiting replacement parts and alignment test.',
    ]);
    echo " -> 1 open maintenance record seeded" . PHP_EOL;

    // 7. Seed 12 Loans (using dynamic dates relative to today)
    // Today's date reference
    $today = new DateTime('now', new DateTimeZone('UTC'));
    $fmt = function($offsetDays) use ($today) {
        $d = clone $today;
        if ($offsetDays >= 0) {
            $d->modify("+{$offsetDays} days");
        } else {
            $d->modify("{$offsetDays} days");
        }
        return $d->format('Y-m-d');
    };
    $fmtDt = function($offsetDays, $time = '09:00:00') use ($today) {
        $d = clone $today;
        if ($offsetDays >= 0) {
            $d->modify("+{$offsetDays} days");
        } else {
            $d->modify("{$offsetDays} days");
        }
        return $d->format('Y-m-d') . ' ' . $time;
    };

    $loans = [
        // 1. Overdue checkout (start -7 days, end -2 days) - EQ-1001 (Dell Latitude) to Sarah Staff
        [
            'asset_id' => 1, 'user_id' => 3, 'start' => $fmt(-7), 'end' => $fmt(-2),
            'status' => 'checked_out', 'purpose' => 'Field survey data capture in North District',
            'app_by' => 2, 'app_at' => $fmtDt(-8), 'chk_by' => 2, 'chk_at' => $fmtDt(-7),
            'ret_by' => null, 'ret_at' => null, 'ret_cond' => null, 'ret_notes' => null,
            'rej_reason' => null
        ],
        // 2. Active normal checkout (start -1 days, end +3 days) - EQ-1002 (Lenovo ThinkPad) to Sam Fieldwork
        [
            'asset_id' => 2, 'user_id' => 4, 'start' => $fmt(-1), 'end' => $fmt(3),
            'status' => 'checked_out', 'purpose' => 'Onsite environmental data compilation',
            'app_by' => 2, 'app_at' => $fmtDt(-2), 'chk_by' => 2, 'chk_at' => $fmtDt(-1),
            'ret_by' => null, 'ret_at' => null, 'ret_cond' => null, 'ret_notes' => null,
            'rej_reason' => null
        ],
        // 3. Approved future loan (start +2 days, end +5 days) - EQ-1003 (MacBook Pro) to Sophia Research
        [
            'asset_id' => 3, 'user_id' => 5, 'start' => $fmt(2), 'end' => $fmt(5),
            'status' => 'approved', 'purpose' => 'Machine learning workshop presentation',
            'app_by' => 2, 'app_at' => $fmtDt(-1), 'chk_by' => null, 'chk_at' => null,
            'ret_by' => null, 'ret_at' => null, 'ret_cond' => null, 'ret_notes' => null,
            'rej_reason' => null
        ],
        // 4. Conflicting Pending Request A on EQ-1007 (start +4 days, end +7 days) by Sarah Staff
        [
            'asset_id' => 7, 'user_id' => 3, 'start' => $fmt(4), 'end' => $fmt(7),
            'status' => 'pending', 'purpose' => 'Interview recording for departmental documentary',
            'app_by' => null, 'app_at' => null, 'chk_by' => null, 'chk_at' => null,
            'ret_by' => null, 'ret_at' => null, 'ret_cond' => null, 'ret_notes' => null,
            'rej_reason' => null
        ],
        // 5. Conflicting Pending Request B on EQ-1007 (start +5 days, end +8 days) by Sam Fieldwork
        [
            'asset_id' => 7, 'user_id' => 4, 'start' => $fmt(5), 'end' => $fmt(8),
            'status' => 'pending', 'purpose' => 'Audio recording for conference proceedings',
            'app_by' => null, 'app_at' => null, 'chk_by' => null, 'chk_at' => null,
            'ret_by' => null, 'ret_at' => null, 'ret_cond' => null, 'ret_notes' => null,
            'rej_reason' => null
        ],
        // 6. Normal Pending Request - EQ-1010 (Fluke Multimeter) by Sophia Research
        [
            'asset_id' => 10, 'user_id' => 5, 'start' => $fmt(1), 'end' => $fmt(4),
            'status' => 'pending', 'purpose' => 'Electronics testing for prototype testing board',
            'app_by' => null, 'app_at' => null, 'chk_by' => null, 'chk_at' => null,
            'ret_by' => null, 'ret_at' => null, 'ret_cond' => null, 'ret_notes' => null,
            'rej_reason' => null
        ],
        // 7. Normal Pending Request - EQ-1015 (Garmin GPSMAP) by Sarah Staff
        [
            'asset_id' => 15, 'user_id' => 3, 'start' => $fmt(6), 'end' => $fmt(9),
            'status' => 'pending', 'purpose' => 'Forestry plot boundary mapping project',
            'app_by' => null, 'app_at' => null, 'chk_by' => null, 'chk_at' => null,
            'ret_by' => null, 'ret_at' => null, 'ret_cond' => null, 'ret_notes' => null,
            'rej_reason' => null
        ],
        // 8. Returned Loan 1 (Good Condition) - EQ-1008 (Projector) by Sam Fieldwork
        [
            'asset_id' => 8, 'user_id' => 4, 'start' => $fmt(-14), 'end' => $fmt(-12),
            'status' => 'returned', 'purpose' => 'Guest lecture projection in Auditorium',
            'app_by' => 2, 'app_at' => $fmtDt(-15), 'chk_by' => 2, 'chk_at' => $fmtDt(-14),
            'ret_by' => 2, 'ret_at' => $fmtDt(-12), 'ret_cond' => 'good', 'ret_notes' => 'Returned on time in full working order with power cable and remote.',
            'rej_reason' => null
        ],
        // 9. Returned Loan 2 (Damaged Return which triggered maintenance) - EQ-1004 (Sony Camera) by Sophia Research
        [
            'asset_id' => 4, 'user_id' => 5, 'start' => $fmt(-6), 'end' => $fmt(-3),
            'status' => 'returned', 'purpose' => 'Field photo documentation',
            'app_by' => 2, 'app_at' => $fmtDt(-7), 'chk_by' => 2, 'chk_at' => $fmtDt(-6),
            'ret_by' => 2, 'ret_at' => $fmtDt(-3), 'ret_cond' => 'damaged', 'ret_notes' => 'Camera returned with loose lens bayonet. Transferred to maintenance.',
            'rej_reason' => null
        ],
        // 10. Rejected Loan Request - EQ-1019 (Drone) by Sarah Staff
        [
            'asset_id' => 19, 'user_id' => 3, 'start' => $fmt(-4), 'end' => $fmt(-2),
            'status' => 'rejected', 'purpose' => 'Aerial aerial mapping without certified pilot license',
            'app_by' => 2, 'app_at' => $fmtDt(-5), 'chk_by' => null, 'chk_at' => null,
            'ret_by' => null, 'ret_at' => null, 'ret_cond' => null, 'ret_notes' => null,
            'rej_reason' => 'Pilot certification not submitted with application.'
        ],
        // 11. Cancelled Loan Request - EQ-1011 (Oscilloscope) by Sam Fieldwork
        [
            'asset_id' => 11, 'user_id' => 4, 'start' => $fmt(3), 'end' => $fmt(6),
            'status' => 'cancelled', 'purpose' => 'Laboratory session rescheduled by department',
            'app_by' => null, 'app_at' => null, 'chk_by' => null, 'chk_at' => null,
            'ret_by' => null, 'ret_at' => null, 'ret_cond' => null, 'ret_notes' => null,
            'rej_reason' => null
        ],
        // 12. Additional Approved Loan - EQ-1014 (FLIR Thermal Camera) by Sophia Research
        [
            'asset_id' => 14, 'user_id' => 5, 'start' => $fmt(3), 'end' => $fmt(7),
            'status' => 'approved', 'purpose' => 'Thermal insulation evaluation of faculty laboratories',
            'app_by' => 2, 'app_at' => $fmtDt(0), 'chk_by' => null, 'chk_at' => null,
            'ret_by' => null, 'ret_at' => null, 'ret_cond' => null, 'ret_notes' => null,
            'rej_reason' => null
        ]
    ];

    $loanStmt = $pdo->prepare("
        INSERT INTO loans (
            asset_id, user_id, start_date, end_date, status, purpose,
            rejection_reason, approved_by, approved_at, checked_out_by, checked_out_at,
            returned_by, returned_at, return_condition, return_notes
        ) VALUES (
            :asset_id, :user_id, :start_date, :end_date, :status, :purpose,
            :rejection_reason, :approved_by, :approved_at, :checked_out_by, :checked_out_at,
            :returned_by, :returned_at, :return_condition, :return_notes
        ) RETURNING id
    ");

    $histStmt = $pdo->prepare("
        INSERT INTO loan_history (loan_id, action, performed_by, notes, created_at)
        VALUES (:loan_id, :action, :performed_by, :notes, :created_at)
    ");

    foreach ($loans as $idx => $l) {
        $loanStmt->execute([
            ':asset_id' => $l['asset_id'],
            ':user_id' => $l['user_id'],
            ':start_date' => $l['start'],
            ':end_date' => $l['end'],
            ':status' => $l['status'],
            ':purpose' => $l['purpose'],
            ':rejection_reason' => $l['rej_reason'],
            ':approved_by' => $l['app_by'],
            ':approved_at' => $l['app_at'],
            ':checked_out_by' => $l['chk_by'],
            ':checked_out_at' => $l['chk_at'],
            ':returned_by' => $l['ret_by'],
            ':returned_at' => $l['ret_at'],
            ':return_condition' => $l['ret_cond'],
            ':return_notes' => $l['ret_notes'],
        ]);
        $loanId = $loanStmt->fetchColumn();

        // Add history events according to status
        $histStmt->execute([
            ':loan_id' => $loanId,
            ':action' => 'requested',
            ':performed_by' => $l['user_id'],
            ':notes' => 'Loan request submitted.',
            ':created_at' => $l['app_at'] ?: date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);

        if (in_array($l['status'], ['approved', 'checked_out', 'returned'])) {
            $histStmt->execute([
                ':loan_id' => $loanId,
                ':action' => 'approved',
                ':performed_by' => $l['app_by'],
                ':notes' => 'Loan request approved by coordinator.',
                ':created_at' => $l['app_at'],
            ]);
        }

        if (in_array($l['status'], ['checked_out', 'returned'])) {
            $histStmt->execute([
                ':loan_id' => $loanId,
                ':action' => 'checked_out',
                ':performed_by' => $l['chk_by'],
                ':notes' => 'Equipment handed over and checked out to borrower.',
                ':created_at' => $l['chk_at'],
            ]);
        }

        if ($l['status'] === 'returned') {
            $histStmt->execute([
                ':loan_id' => $loanId,
                ':action' => 'returned',
                ':performed_by' => $l['ret_by'],
                ':notes' => 'Equipment physically returned: condition recorded as ' . $l['ret_cond'] . '.',
                ':created_at' => $l['ret_at'],
            ]);
        }

        if ($l['status'] === 'rejected') {
            $histStmt->execute([
                ':loan_id' => $loanId,
                ':action' => 'rejected',
                ':performed_by' => $l['app_by'],
                ':notes' => 'Loan request rejected: ' . $l['rej_reason'],
                ':created_at' => $l['app_at'],
            ]);
        }

        if ($l['status'] === 'cancelled') {
            $histStmt->execute([
                ':loan_id' => $loanId,
                ':action' => 'cancelled',
                ':performed_by' => $l['user_id'],
                ':notes' => 'Loan request cancelled by requester.',
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
    echo " -> 12 loans with full audit history seeded" . PHP_EOL;

    $pdo->commit();
    echo "Seeding completed successfully!" . PHP_EOL;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Seeding error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

