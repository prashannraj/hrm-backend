<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\WfhRequest;
use App\Models\Timesheet;
use App\Models\TravelRequest;
use App\Models\Asset;
use App\Models\Vehicle;
use App\Models\AuditLog;
use App\Models\Policy;
use App\Models\OrganizationSetting;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 1. Seed Users
        User::create([
            'fullName' => 'Prashann Raj',
            'email' => 'admin@appan.com',
            'password' => Hash::make('admin123'),
            'companyName' => 'AppanTech',
            'agreementSigned' => true,
            'signatureDate' => '2026-06-12 10:00:00',
            'signatureName' => 'Prashann Raj',
            'signatureTitle' => 'Managing Director',
            'signatureType' => 'Type',
            'signatureData' => 'Prashann Raj',
        ]);

        // 2. Seed Employees
        Employee::create([
            'id' => 'EMP-001',
            'name' => 'Aarav Sharma',
            'gender' => 'Male',
            'dob' => '1988-04-12',
            'maritalStatus' => 'Married',
            'phone' => '+977-9851012345',
            'email' => 'aarav.sharma@example-ngo.org',
            'address' => 'Kathmandu, Nepal',
            'emergencyContact' => 'Sunita Sharma (+977-9803022110)',
            'citizenshipNo' => '27-01-75-09823',
            'passportNo' => 'N1234567',
            'joinDate' => '2018-01-15',
            'probationMonths' => 6,
            'contractType' => 'Permanent',
            'department' => 'Executive',
            'designation' => 'Executive Director',
            'salaryBasic' => 120000.00,
            'salaryAllowances' => 30000.00,
            'salaryDeductions' => 15000.00,
            'pan' => '302910392',
            'ssf' => 'SSF-89102',
            'cit' => 'CIT-781923',
            'taxInfo' => '15% Bracket',
            'assignedAssets' => ['AST-001', 'AST-004'],
            'education' => 'Ph.D. in Development Studies, Kathmandu University',
            'experience' => '15+ years in international non-governmental project direction.',
            'dependents' => 'Sunita Sharma (Spouse), Aarush Sharma (Son)',
            'lifecycleHistory' => [
                ['id' => 'LC-101', 'date' => '2018-01-15', 'type' => 'Onboard', 'details' => 'Joined Glow Forward Foundation as Executive Director.', 'approvedBy' => 'Board of Directors'],
                ['id' => 'LC-102', 'date' => '2018-07-15', 'type' => 'Confirmation', 'details' => 'Probation successfully completed and confirmed as Permanent staff.', 'approvedBy' => 'Board of Directors'],
                ['id' => 'LC-103', 'date' => '2022-01-15', 'type' => 'Salary Revision', 'details' => 'Annual increments and inflation correction applied.', 'previousValue' => 'NRs. 100,000 Basic', 'newValue' => 'NRs. 120,000 Basic', 'approvedBy' => 'Finance Committee']
            ]
        ]);

        Employee::create([
            'id' => 'EMP-002',
            'name' => 'Priya Patel',
            'gender' => 'Female',
            'dob' => '1992-09-24',
            'maritalStatus' => 'Single',
            'phone' => '+977-9841029384',
            'email' => 'priya.patel@example-ngo.org',
            'address' => 'Lalitpur, Nepal',
            'emergencyContact' => 'Ramesh Patel (+977-9818392019)',
            'citizenshipNo' => '32-02-79-10291',
            'passportNo' => 'N8910239',
            'joinDate' => '2020-03-01',
            'probationMonths' => 3,
            'contractType' => 'Contractual',
            'department' => 'Finance',
            'designation' => 'Finance & Admin Director',
            'salaryBasic' => 95000.00,
            'salaryAllowances' => 20000.00,
            'salaryDeductions' => 12000.00,
            'pan' => '492019203',
            'ssf' => 'SSF-78192',
            'cit' => 'CIT-671829',
            'taxInfo' => '15% Bracket',
            'assignedAssets' => ['AST-002'],
            'education' => 'MBA in Finance, Tribhuvan University',
            'experience' => '8 years corporate & development sector auditing.',
            'dependents' => 'None',
            'lifecycleHistory' => [
                ['id' => 'LC-201', 'date' => '2020-03-01', 'type' => 'Onboard', 'details' => 'Joined Glow Forward Foundation as Finance & Admin Director.', 'approvedBy' => 'Aarav Sharma'],
                ['id' => 'LC-202', 'date' => '2020-06-01', 'type' => 'Confirmation', 'details' => 'Completed 3 months probation period. Confirmed.', 'approvedBy' => 'Aarav Sharma']
            ]
        ]);

        Employee::create([
            'id' => 'EMP-003',
            'name' => 'Kiran Thapa',
            'gender' => 'Male',
            'dob' => '1994-01-10',
            'maritalStatus' => 'Married',
            'phone' => '+977-9851198765',
            'email' => 'kiran.thapa@example-ngo.org',
            'address' => 'Bhaktapur, Nepal',
            'emergencyContact' => 'Meera Thapa (+977-9851101234)',
            'citizenshipNo' => '14-01-71-38291',
            'passportNo' => 'N3019283',
            'joinDate' => '2021-06-15',
            'probationMonths' => 6,
            'contractType' => 'Permanent',
            'department' => 'Programs',
            'designation' => 'Senior Program Coordinator',
            'salaryBasic' => 80000.00,
            'salaryAllowances' => 15000.00,
            'salaryDeductions' => 10000.00,
            'pan' => '501923019',
            'ssf' => 'SSF-38291',
            'cit' => 'CIT-283910',
            'taxInfo' => '15% Bracket',
            'assignedAssets' => ['AST-003'],
            'education' => "M.A. in Social Work, St. Xavier's College",
            'experience' => '5 years working on rural education and healthcare initiatives.',
            'dependents' => 'Meera Thapa (Spouse)',
            'lifecycleHistory' => [
                ['id' => 'LC-301', 'date' => '2021-06-15', 'type' => 'Onboard', 'details' => 'Joined as Senior Program Coordinator.', 'approvedBy' => 'Aarav Sharma'],
                ['id' => 'LC-302', 'date' => '2021-12-15', 'type' => 'Confirmation', 'details' => 'Completed 6-month probation successfully.', 'approvedBy' => 'Srijana Adhikari'],
                ['id' => 'LC-303', 'date' => '2024-04-01', 'type' => 'Promotion', 'details' => 'Promoted from Program Coordinator to Senior Program Coordinator.', 'previousValue' => 'Program Coordinator', 'newValue' => 'Senior Program Coordinator', 'approvedBy' => 'Aarav Sharma']
            ]
        ]);

        Employee::create([
            'id' => 'EMP-004',
            'name' => 'Srijana Adhikari',
            'gender' => 'Female',
            'dob' => '1996-07-18',
            'maritalStatus' => 'Single',
            'phone' => '+977-9801239847',
            'email' => 'srijana.adhikari@example-ngo.org',
            'address' => 'Pokhara, Nepal',
            'emergencyContact' => 'Gopal Adhikari (+977-9801201201)',
            'citizenshipNo' => '41-01-81-00293',
            'passportNo' => 'N4820192',
            'joinDate' => '2022-10-01',
            'probationMonths' => 3,
            'contractType' => 'Contractual',
            'department' => 'Human Resources',
            'designation' => 'HR Officer',
            'salaryBasic' => 60000.00,
            'salaryAllowances' => 10000.00,
            'salaryDeductions' => 8000.00,
            'pan' => '602938192',
            'ssf' => 'SSF-10293',
            'cit' => 'CIT-593819',
            'taxInfo' => '10% Bracket',
            'assignedAssets' => [],
            'education' => 'B.A. in Business Administration, Pokhara University',
            'experience' => '3 years in NGO recruiting and policy implementation.',
            'dependents' => 'Gopal Adhikari (Father)',
            'lifecycleHistory' => [
                ['id' => 'LC-401', 'date' => '2022-10-01', 'type' => 'Onboard', 'details' => 'Joined as HR Officer.', 'approvedBy' => 'Priya Patel'],
                ['id' => 'LC-402', 'date' => '2023-01-01', 'type' => 'Confirmation', 'details' => 'Confirmed in permanent role after 3 months probation.', 'approvedBy' => 'Aarav Sharma']
            ]
        ]);

        Employee::create([
            'id' => 'EMP-005',
            'name' => 'Ram Bahadur',
            'gender' => 'Male',
            'dob' => '1985-11-30',
            'maritalStatus' => 'Married',
            'phone' => '+977-9813928172',
            'email' => 'ram.bahadur@example-ngo.org',
            'address' => 'Lalitpur, Nepal',
            'emergencyContact' => 'Sita Bahadur (+977-9813000111)',
            'citizenshipNo' => '21-03-72-91023',
            'passportNo' => '',
            'joinDate' => '2019-05-01',
            'probationMonths' => 0,
            'contractType' => 'Permanent',
            'department' => 'Operations',
            'designation' => 'Head Driver',
            'salaryBasic' => 40000.00,
            'salaryAllowances' => 12000.00,
            'salaryDeductions' => 5000.00,
            'pan' => '102938129',
            'ssf' => 'SSF-02918',
            'cit' => 'CIT-392812',
            'taxInfo' => '10% Bracket',
            'assignedAssets' => ['VEH-001'],
            'education' => 'Secondary School Completion',
            'experience' => '10+ years light & heavy vehicle driving, off-road experience.',
            'dependents' => 'Sita Bahadur (Spouse), Ramita Bahadur (Daughter)',
            'lifecycleHistory' => [
                ['id' => 'LC-501', 'date' => '2019-05-01', 'type' => 'Onboard', 'details' => 'Joined as Head Driver.', 'approvedBy' => 'Priya Patel']
            ]
        ]);

        // 3. Seed Attendance Logs
        AttendanceLog::create([
            'id' => 'ATT-001',
            'employeeId' => 'EMP-001',
            'employeeName' => 'Aarav Sharma',
            'date' => '2026-07-14',
            'checkIn' => '08:55',
            'checkOut' => '17:05',
            'status' => 'Present',
            'overtimeMinutes' => 5,
            'lateMinutes' => 0,
        ]);

        AttendanceLog::create([
            'id' => 'ATT-002',
            'employeeId' => 'EMP-002',
            'employeeName' => 'Priya Patel',
            'date' => '2026-07-14',
            'checkIn' => '09:15',
            'checkOut' => '17:10',
            'status' => 'Late',
            'overtimeMinutes' => 10,
            'lateMinutes' => 15,
        ]);

        AttendanceLog::create([
            'id' => 'ATT-003',
            'employeeId' => 'EMP-003',
            'employeeName' => 'Kiran Thapa',
            'date' => '2026-07-14',
            'checkIn' => '08:45',
            'checkOut' => '17:00',
            'status' => 'Present',
            'overtimeMinutes' => 0,
            'lateMinutes' => 0,
        ]);

        AttendanceLog::create([
            'id' => 'ATT-004',
            'employeeId' => 'EMP-005',
            'employeeName' => 'Ram Bahadur',
            'date' => '2026-07-14',
            'checkIn' => '08:30',
            'checkOut' => '18:00',
            'status' => 'Present',
            'overtimeMinutes' => 60,
            'lateMinutes' => 0,
        ]);

        // 4. Seed Leave Requests
        LeaveRequest::create([
            'id' => 'LV-101',
            'employeeId' => 'EMP-003',
            'employeeName' => 'Kiran Thapa',
            'leaveType' => 'Sick Leave',
            'startDate' => '2026-07-10',
            'endDate' => '2026-07-11',
            'reason' => 'Severe viral fever',
            'status' => 'Approved',
            'approvedBy' => 'Aarav Sharma',
        ]);

        LeaveRequest::create([
            'id' => 'LV-102',
            'employeeId' => 'EMP-004',
            'employeeName' => 'Srijana Adhikari',
            'leaveType' => 'Casual Leave',
            'startDate' => '2026-07-20',
            'endDate' => '2026-07-22',
            'reason' => "Attending sister's wedding",
            'status' => 'Pending',
        ]);

        // 5. Seed WFH Requests
        WfhRequest::create([
            'id' => 'WFH-001',
            'employeeId' => 'EMP-002',
            'employeeName' => 'Priya Patel',
            'startDate' => '2026-07-15',
            'endDate' => '2026-07-16',
            'reason' => 'Plumbing maintenance at home and tax filing sync',
            'status' => 'Pending',
        ]);

        // 6. Seed Timesheets
        Timesheet::create([
            'id' => 'TS-001',
            'employeeId' => 'EMP-003',
            'employeeName' => 'Kiran Thapa',
            'date' => '2026-07-14',
            'task' => 'Drafting Quarterly Education Progress Report',
            'project' => 'Rural Literacy Initiative',
            'hours' => 8.0,
            'status' => 'Approved',
            'approvedBy' => 'Aarav Sharma',
        ]);

        Timesheet::create([
            'id' => 'TS-002',
            'employeeId' => 'EMP-004',
            'employeeName' => 'Srijana Adhikari',
            'date' => '2026-07-14',
            'task' => 'Sourcing candidate resumes and preliminary interviews',
            'project' => 'Staff Capacity Building',
            'hours' => 7.5,
            'status' => 'Submitted',
        ]);

        // 7. Seed Travel Requests
        TravelRequest::create([
            'id' => 'TRV-001',
            'employeeId' => 'EMP-003',
            'employeeName' => 'Kiran Thapa',
            'destination' => 'Surkhet District, Western Nepal',
            'purpose' => 'Field monitoring of newly constructed literacy hubs',
            'startDate' => '2026-07-25',
            'endDate' => '2026-07-30',
            'estimatedCost' => 35000.00,
            'advanceAmount' => 25000.00,
            'status' => 'Approved',
            'expenses' => [],
            'approvedBy' => 'Priya Patel',
        ]);

        TravelRequest::create([
            'id' => 'TRV-002',
            'employeeId' => 'EMP-001',
            'employeeName' => 'Aarav Sharma',
            'destination' => 'Geneva, Switzerland',
            'purpose' => 'NGO Global Partners Summit and donor networking',
            'startDate' => '2026-08-10',
            'endDate' => '2026-08-16',
            'estimatedCost' => 280000.00,
            'advanceAmount' => 200000.00,
            'status' => 'Pending',
            'expenses' => [],
        ]);

        // 8. Seed Assets
        Asset::create([
            'id' => 'AST-001',
            'code' => 'AST-IT-2023-01',
            'name' => 'MacBook Pro 14"',
            'category' => 'IT',
            'assignedTo' => 'EMP-001',
            'purchaseDate' => '2023-04-12',
            'cost' => 185000.00,
            'status' => 'Active',
            'maintenanceLogs' => [
                ['date' => '2024-11-02', 'cost' => 12000.00, 'description' => 'Battery thermal diagnostic and replacement']
            ],
        ]);

        Asset::create([
            'id' => 'AST-002',
            'code' => 'AST-IT-2023-02',
            'name' => 'Dell Latitude 5430',
            'category' => 'IT',
            'assignedTo' => 'EMP-002',
            'purchaseDate' => '2023-05-20',
            'cost' => 110000.00,
            'status' => 'Active',
            'maintenanceLogs' => [],
        ]);

        Asset::create([
            'id' => 'AST-003',
            'code' => 'AST-IT-2024-05',
            'name' => 'Lenovo ThinkPad L14',
            'category' => 'IT',
            'assignedTo' => 'EMP-003',
            'purchaseDate' => '2024-02-15',
            'cost' => 95000.00,
            'status' => 'Active',
            'maintenanceLogs' => [],
        ]);

        Asset::create([
            'id' => 'AST-004',
            'code' => 'AST-FUR-2021-08',
            'name' => 'Ergonomic Office Mesh Chair',
            'category' => 'Furniture',
            'assignedTo' => 'EMP-001',
            'purchaseDate' => '2021-08-10',
            'cost' => 25000.00,
            'status' => 'Active',
            'maintenanceLogs' => [],
        ]);

        Asset::create([
            'id' => 'AST-005',
            'code' => 'AST-EQ-2022-11',
            'name' => 'Epson Projector EB-E01',
            'category' => 'Equipment',
            'purchaseDate' => '2022-11-30',
            'cost' => 45000.00,
            'status' => 'Maintenance',
            'maintenanceLogs' => [
                ['date' => '2026-07-02', 'cost' => 8000.00, 'description' => 'Projector bulb replacement and filter cleaning']
            ],
        ]);

        // 9. Seed Vehicles
        Vehicle::create([
            'id' => 'VEH-001',
            'plateNumber' => 'Ba 3 Cha 9012',
            'model' => 'Toyota Land Cruiser Prado',
            'driverName' => 'Ram Bahadur',
            'status' => 'Available',
            'fuelLogs' => [
                ['date' => '2026-07-01', 'liters' => 65, 'cost' => 11050, 'mileage' => 124500],
                ['date' => '2026-07-12', 'liters' => 70, 'cost' => 11900, 'mileage' => 125100]
            ],
            'trips' => [
                ['date' => '2026-07-05', 'route' => 'Kathmandu to Sindhupalchok return', 'purpose' => 'Water Project Site Audit', 'miles' => 180],
                ['date' => '2026-07-11', 'route' => 'Kathmandu city transfers', 'purpose' => 'Receiving UN donor delegation', 'miles' => 45]
            ]
        ]);

        Vehicle::create([
            'id' => 'VEH-002',
            'plateNumber' => 'Ba 2 Cha 4810',
            'model' => 'Mahindra Scorpio 4WD',
            'driverName' => 'Hari Prasad',
            'status' => 'Maintenance',
            'fuelLogs' => [
                ['date' => '2026-06-25', 'liters' => 50, 'cost' => 8500, 'mileage' => 98400]
            ],
            'trips' => [
                ['date' => '2026-06-20', 'route' => 'Kathmandu to Trishuli return', 'purpose' => 'Healthcare Hub Setup', 'miles' => 140]
            ]
        ]);

        // 10. Seed Audit Logs
        AuditLog::create([
            'timestamp' => '2026-07-15 08:15:00',
            'user' => 'Srijana Adhikari (HR)',
            'action' => 'Created Employee Record EMP-004',
            'module' => 'HRIS',
        ]);

        AuditLog::create([
            'timestamp' => '2026-07-15 09:10:00',
            'user' => 'Aarav Sharma (Admin)',
            'action' => 'Approved Leave Request LV-101',
            'module' => 'LEAVE',
        ]);

        AuditLog::create([
            'timestamp' => '2026-07-15 10:02:00',
            'user' => 'Priya Patel (Finance)',
            'action' => 'Linked Asset AST-001 to Aarav Sharma',
            'module' => 'ASSETS',
        ]);

        // 11. Seed Policies
        Policy::create([
            'id' => 'POL-001',
            'title' => 'Appan HRM Personnel & Code of Conduct Policy',
            'category' => 'HR',
            'version' => 'v1.4',
            'publishDate' => '2026-06-12',
            'content' => 'This policy establishes the core expectations of professional conduct, operational integrity, and ethical practice. All employees must adhere to high standards of transparency, respect, and compliance with local laws. This includes zero tolerance for discrimination, harassment, or conflicts of interest.',
            'acknowledgedBy' => ['EMP-001', 'EMP-003'],
        ]);

        Policy::create([
            'id' => 'POL-002',
            'title' => 'Travel & Daily Allowance (TADA) Policy',
            'category' => 'Finance',
            'version' => 'v2.1',
            'publishDate' => '2026-07-01',
            'content' => 'Defines reimbursement rates for domestic and international travels. Standard daily allowance for field personnel in Koshi and Madhesh is capped at NRs. 2,500 per day. Official receipt upload is mandatory.',
            'acknowledgedBy' => ['EMP-002'],
        ]);

        Policy::create([
            'id' => 'POL-003',
            'title' => 'Information Security & Asset Protection Guide',
            'category' => 'IT',
            'version' => 'v1.0',
            'publishDate' => '2025-08-15',
            'content' => 'Establishes secure handling parameters for organization-issued hardware (laptops, phones, tablets). Strong password/PIN configs are mandatory.',
            'acknowledgedBy' => ['EMP-001', 'EMP-004'],
        ]);

        // 12. Seed Organization Settings
        OrganizationSetting::create([
            'name' => 'Glow Forward Foundation',
            'acronym' => 'GFF',
            'registeredAddress' => 'Sanepa Heights, Lalitpur, Nepal',
            'email' => 'info@glowforward.org',
            'phone' => '+977-1-5523019',
            'registrationNo' => 'Reg-39201/SWC-9201',
            'fiscalYear' => '2025/2026',
            'departments' => ['Executive', 'Programs', 'Finance', 'Human Resources', 'M&E', 'Operations'],
            'designations' => ['Executive Director', 'Finance & Admin Director', 'Senior Program Coordinator', 'M&E Specialist', 'HR Officer', 'Program Associate', 'Finance Assistant', 'Head Driver', 'Office Assistant'],
            'leavePolicies' => [
                ['type' => 'Casual Leave', 'allocation' => 12, 'cashable' => false],
                ['type' => 'Sick Leave', 'allocation' => 15, 'cashable' => true],
                ['type' => 'Maternity Leave', 'allocation' => 60, 'cashable' => false],
                ['type' => 'Paternity Leave', 'allocation' => 10, 'cashable' => false],
                ['type' => 'Special Leave', 'allocation' => 6, 'cashable' => false]
            ]
        ]);

        $this->call(ErpAccountingCoreSeeder::class);
    }
}
